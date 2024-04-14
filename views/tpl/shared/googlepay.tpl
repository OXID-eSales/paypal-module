[{assign var="sToken" value=$oViewConf->getSessionChallengeToken()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
[{assign var="config" value=$oViewConf->getPayPalCheckoutConfig()}]

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
      merchantInfo = null,
      paySumInfo = null;
      
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
     paymentDataRequest.shippingAddressRequired = true;
     paymentDataRequest.shippingAddressParameters = { 'phoneNumberRequired': true };

     return paymentDataRequest;
  }
  
  function onPaymentAuthorized(paymentData) {
     return new Promise(function (resolve, reject) {
         processPayment(paymentData)
        .then(function (data) {
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
     paymentsClient.loadPaymentData(paymentDataRequest)
     .then(function() {
         //location.replace("[{$sSelfLink|cat:"cl=order"}]");
     })
     .catch(err => {
        if( err.statusCode != "CANCELED")
           console.log(err)
     }); 
  }
   
  async function processPayment(paymentData) {
     try {        
         console.log(paymentData);       

         /*** Create oxid Order ***/  
         const createOrderUrl = '[{$sSelfLink|cat:"cl=oscpaypalproxy&fnc=createGooglepayOrder&paymentid=oscpaypal_googlepay&context=continue&aid="|cat:$aid|cat:"&stoken="|cat:$sToken}]';
 
         const { id } = await fetch(createOrderUrl, {
                    method: "POST",
                    headers: {
                      "Content-Type": "application/json",
                    },
                    body: JSON.stringify(paymentData),
         }).then((res) => res.json());
         
         console.log(id);  
                  
         const confirmOrderResponse = await paypal.Googlepay().confirmOrder({
            orderId: id,
            paymentMethodData: paymentData.paymentMethodData,
         });
         
         console.log(confirmOrderResponse);
         
         const confirmOrderPromise = Promise.resolve(confirmOrderResponse);
         console.log(confirmOrderPromise);
         
         console.log(status);  
                  

                  
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
[{oxscript add=$smarty.capture.detailsGooglePayScript}]
<script async="async" src="https://pay.google.com/gp/p/js/pay.js" onload="onGooglePayLoaded()"></script>
