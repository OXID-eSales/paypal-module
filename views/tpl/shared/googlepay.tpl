[{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]
[{assign var="oConfig" value=$oViewConf->getConfig()}]
[{assign var="bGooglePayDelivery" value=$oConfig->getConfigParam('oscPayPalUseGooglePayAddress')}]
<style>
    #oscpaypal_googlepay {
        float: right;
    }
</style>
<script>
[{capture name="detailsGooglePayScript"}]
  document.addEventListener("DOMContentLoaded", (event) => {
      if (google && paypal.Googlepay) {
        onGooglePayLoaded().catch(console.log);
     }
  });

  const baseRequest = {
     apiVersion: 2,
     apiVersionMinor: 0,
  };

  let paymentsClient = null,
      allowedPaymentMethods = null,
      merchantInfo = null;

  /* Configure your site's support for payment methods supported by the Google Pay */
  function getGoogleIsReadyToPayRequest(allowedPaymentMethods) {
     return Object.assign({}, baseRequest, {
        allowedPaymentMethods: allowedPaymentMethods,
        billingAddressRequired: true,
        assuranceDetailsRequired: true,
        billingAddressParameters: { format: 'FULL' },
     });
  }

  /* Fetch Default Config from PayPal via PayPal SDK */
  async function getGooglePayConfig() {
     if (allowedPaymentMethods == null || merchantInfo == null) {
        const googlePayConfig = await paypal.Googlepay().config();

        allowedPaymentMethods = googlePayConfig.allowedPaymentMethods;
        merchantInfo = googlePayConfig.merchantInfo;
        merchantInfo.merchantName = [{$oxcmp_shop->oxshops__oxname->value|json_encode}];
     }
     return {
        allowedPaymentMethods,
        merchantInfo,
     };
  }

  /* Configure support for the Google Pay API */
  async function getGooglePaymentDataRequest() {
     const paymentDataRequest = Object.assign({}, baseRequest);
     const { allowedPaymentMethods, merchantInfo } = await getGooglePayConfig();

     paymentDataRequest.allowedPaymentMethods = allowedPaymentMethods;
     paymentDataRequest.merchantInfo = merchantInfo;

     paymentDataRequest.callbackIntents = ["PAYMENT_AUTHORIZATION"];
     paymentDataRequest.emailRequired = true;
     paymentDataRequest.shippingAddressRequired = [{if $bGooglePayDelivery == true}]true[{else}]false[{/if}];
     paymentDataRequest.shippingAddressParameters = { 'phoneNumberRequired': true };

     return paymentDataRequest;
  }

  function onPaymentAuthorized(paymentData) {
     return new Promise(function (resolve, reject) {
         processPayment(paymentData)
        .then(function (data) {
            console.log('onPaymentAuthorized')
            console.log(data)
            console.log('onPaymentAuthorized End')

            resolve({ transactionState: "SUCCESS" });
     })
     .catch(function (errDetails) {
           resolve({ transactionState: "ERROR" });
        });
     });
  }

  function getGooglePaymentsClient() {
     if (paymentsClient === null) {
        paymentsClient = new google.payments.api.PaymentsClient({
           environment: [{ if $config->isSandbox() }]"TEST"[{else}]"PRODUCTION"[{/if}],
           paymentDataCallbacks: {
              onPaymentAuthorized: onPaymentAuthorized,
           },
        });
     }
     return paymentsClient;
  }

  async function onGooglePayLoaded() {

      const paymentsClient = getGooglePaymentsClient();
     const { allowedPaymentMethods } = await getGooglePayConfig();
             paymentsClient
            .isReadyToPay(getGoogleIsReadyToPayRequest(allowedPaymentMethods))
            .then(function (response) {
                [{if $config->isSandbox() }]
                console.log('getGoogleIsReadyToPayResponse debug')
                console.log(response)
                console.log('getGoogleIsReadyToPayResponse debugend')
                [{/if}]
               if (response.result) {
                  addGooglePayButton();
               }
            })
            .catch(function (err) {
               console.error(err);
            });
  }

  function addGooglePayButton() {
     const paymentsClient = getGooglePaymentsClient();
     const button = paymentsClient.createButton({
        buttonType: 'buy',
        buttonLocale: '[{$oView->getActiveLangAbbr()|oxlower}]',
        onClick: onGooglePaymentButtonClicked,
     });
     document.getElementById("[{$buttonId}]").appendChild(button);
  }

  async function onGooglePaymentButtonClicked() {
     const paymentDataRequest = await getGooglePaymentDataRequest();

     try {
        const getBasketUrl = "[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=getGooglepayBasket&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]";
        const basketDetails = await fetch(getBasketUrl);
        const json = await basketDetails.json();

        paymentDataRequest.transactionInfo = {
           displayItems: json.displayItems,
           countryCode: json.countryCode,
           currencyCode: json.currencyCode,
           totalPriceStatus: json.totalPriceStatus,
           totalPrice: json.totalPrice,
           totalPriceLabel: json.totalPriceLabel,
        };

     } catch (error) {
        console.error(error);
     }

     const paymentsClient = getGooglePaymentsClient();
      [{if $config->isSandbox() }]
      console.log('paymentDataRequest debug')
      console.log(paymentDataRequest)
      console.log('paymentDataRequest debugend')
      [{/if}]
     paymentsClient.loadPaymentData(paymentDataRequest)
     .then(function() {
      //   location.replace("[{$sSelfLink|cat:"cl=order&fnc=execute"}]");
     })
     .catch(err => {
        if( err.statusCode != "CANCELED")
           console.log(err)
     });
  }

  async function processPayment(paymentData) {
     try {
         /*** Create oxid Order ***/
         const createOrderUrl = '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createGooglepayOrder&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]';
         const { id } = await fetch(createOrderUrl, {
               method: "POST",
               headers: { "Content-Type": "application/json" },
               body: JSON.stringify(paymentData),
         }).then((res) => res.json());

         const confirmOrderResponse = await paypal.Googlepay().confirmOrder({
            orderId: id,
            paymentMethodData: paymentData.paymentMethodData,
         });
         [{if $config->isSandbox() }]
         console.log('confirmOrderResponse debug')
         console.log(confirmOrderResponse)
         console.log('confirmOrderResponse debugend')
         [{/if}]
         if (confirmOrderResponse.status === "APPROVED") {
             [{if $config->isSandbox() }]
             console.log('approved paymentData debug')
             console.log(paymentData)
             console.log('approved paymentData debugend')
             [{/if}]
             function onApprove(confirmOrderResponse, actions) {
                 // Create a new form dynamically
                 var form = document.createElement("form");
                 form.setAttribute("method", "post");
                 form.setAttribute("action", `[{$sSelfLink|cat:"cl=order&fnc=createGooglePayOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken|cat:"&sDeliveryAddressMD5="|cat:$oView->getDeliveryAddressMD5()}]`);

                 // Create hidden form elements and append them to the form
                 var orderIDField = document.createElement("input");
                 orderIDField.setAttribute("type", "hidden");
                 orderIDField.setAttribute("name", "orderID");
                 orderIDField.setAttribute("value", confirmOrderResponse.id);
                 form.appendChild(orderIDField);

                 // Optional: Add more fields if needed

                 // Append the form to the body
                 document.body.appendChild(form);

                 // Submit the form
                 form.submit();

                 // Handle the response (Optional, as form submission will cause page reload)
                 form.onsubmit = function(event) {
                     event.preventDefault(); // Prevent the form from submitting normally
                     fetch(form.action, {
                         method: 'post',
                         body: new FormData(form)
                     }).then(function(res) {
                         return res.json();
                     }).then(function(data) {
                         console.log(data);
                         if (data.status === "ERROR") {
                             location.reload();
                         } else if (data.id && data.status === "APPROVED") {
                             // Redirect to a new URL if needed
                             // location.replace(`${sSelfLink}cl=order&fnc=execute`);
                         }
                     });
                 };
             }
             function onCreated(confirmOrderResponse, actions) {
                 captureData = new FormData();
                 captureData.append('orderID', confirmOrderResponse.id);
                 return fetch('[{$sSelfLink|cat:"cl=order&fnc=captureGooglePayOrder&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken|cat:"&sDeliveryAddressMD5="|cat:$oView->getDeliveryAddressMD5()}]', {
                     method: 'post',
                     body: captureData }).then(function(res) {
                     return res.json();
                 }).then(function(data) {
                     [{if $config->isSandbox() }]
                     console.log('onCreated captureData debug')
                     console.log(data)
                     console.log('onCreated captureData debugend')
                     [{/if}]
                     var errorDetail = Array.isArray(data.details) && data.details[0];
                     var goNext = Array.isArray(data.location) && data.location[0];

                     window.location.href = '[{$sSelfLink}]' + goNext;
                     console.log(data)
                     if (data.status === "ERROR") {
                         location.reload();
                     } else if (data.id && data.status === "APPROVED") {
                        // location.replace(`${sSelfLink}cl=order&fnc=execute`);
                     }
                 });
             }
             await onApprove(confirmOrderResponse, null);  // Assuming 'actions' is not needed or can be null
             await onCreated(confirmOrderResponse, null);  // Assuming 'actions' is not needed or can be null

             /***  Capture the Order ****/
            //const captureResponse = await fetch(`/orders/${id}/capture`, {
            //          method: "POST",
            //}).then((res) => res.json());

            return { transactionState: "SUCCESS" };

         } else {
            return { transactionState: "ERROR" };
         }

      } catch (err) {
         return {
            transactionState: "ERROR",
            error: {
               message: err.message,
            },
         };
      }
  }
[{/capture}]
</script>
[{oxscript include="https://pay.google.com/gp/p/js/pay.js" }]
[{oxscript add=$smarty.capture.detailsGooglePayScript}]
