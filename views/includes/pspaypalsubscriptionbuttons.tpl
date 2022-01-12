[{oxscript include=$oViewConf->getPayPalJsSdkUrl(true)}]
[{assign var="sSelfLink" value=$oViewConf->getSelfLink()|replace:"&amp;":"&"}]
[{assign var="sCleanLink" value=$selfLink|replace:"?":""}]
[{if !$aid}]
    [{assign var="aid" value=""}]
[{/if}]
<h4 class="paypal-subscription-headline">[{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION"}]</h4>

[{foreach from=$subscriptionPlans item=subscriptionPlan}]
    <h5 class="paypal-subscription-name">[{$subscriptionPlan->name}]</h5>
    <p class="paypal-subscription-desc">[{$subscriptionPlan->description}]</p>
    [{if $subscriptionPlan->payment_preferences->setup_fee->value != 0.0}]
        <p class="paypal-subscription-setupfee">
            [{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_SETUP_FEE" suffix="COLON"}]
            [{$subscriptionPlan->payment_preferences->setup_fee->value|number_format:2}]
            [{$subscriptionPlan->payment_preferences->setup_fee->currency_code}]
        </p>
    [{/if}]
    <ul class="list-unstyled">
        [{foreach from=$subscriptionPlan->billing_cycles item=billing_cycle key=name}]
            <li>
                [{$billing_cycle->pricing_scheme->fixed_price->value|number_format:2}] [{$billing_cycle->pricing_scheme->fixed_price->currency_code}] /
                [{oxmultilang ident="OSC_PAYPAL_FREQUENCY_"|cat:$billing_cycle->frequency->interval_unit}]
                [{oxmultilang ident="OSC_PAYPAL_FOR"}]
                [{$billing_cycle->frequency->interval_count}]
                [{oxmultilang ident="OSC_PAYPAL_FREQUENCY_"|cat:$billing_cycle->frequency->interval_unit}]
            </li>
        [{/foreach}]
    </ul>
    [{if $oxcmp_user && $oxcmp_user->hasSubscribed($subscriptionPlan->id)}]
        [{oxmultilang ident="OSC_PAYPAL_SUBSCRIPTION_NOTE"}]
    [{/if}]

    <div id="button[{$subscriptionPlan->id}]" class="paypal-button-container [{$buttonClass}]"></div>
    [{capture assign="paypal_init"}]
        [{literal}]
        paypal.Buttons({
            style: {
                color: 'gold',
                shape: 'rect',
                label: 'subscribe',
                height: 55
            },
            onInit: function() {
                console.log("PayPal JS SDK was initialized. No action required.");
            },
            createSubscription: function(data, actions) {
                [{/literal}]
                    [{if $isLoggedIn}]
                        [{literal}]
                            return fetch('[{/literal}][{$sSelfLink|cat:"cl=PayPalProxyController&fnc=createSubscriptionOrder&aid="|cat:$aid|cat:"&subscriptionPlanId="|cat:$subscriptionPlan->id|cat:"&stoken="|cat:$oViewConf->getSessionChallengeToken()}][{literal}]', {
                                method: 'post',
                                headers: {
                                    'content-type': 'application/json'
                                }
                                }).then(function(data) {
                                    return actions.subscription.create({"plan_id":"[{/literal}][{$subscriptionPlan->id}][{literal}]"});
                                })
                        [{/literal}]
                    [{else}]
                        [{literal}]return window.location.href="[{/literal}][{$sSelfLink|cat:"cl=account&return="|cat:$currentUrl}][{literal}]"[{/literal}]
                    [{/if}]
                [{literal}]
            },
            onApprove: function(data, actions) {
                document.getElementById("overlay").style.display = "block";
                let params = 'cl=PayPalProxyController&fnc=saveSubscriptionOrder&billingAgreementId=' + data.subscriptionID;
                params += '&subscriptionPlanId=[{/literal}][{$subscriptionPlan->id}][{literal}]';
                params += '&aid=[{/literal}][{$aid}][{literal}]';
                params += '&showOverlay=1';
                params += '&stoken=[{/literal}][{$oViewConf->getSessionChallengeToken()}][{literal}]';
                fetch('[{/literal}][{$sSelfLink}][{literal}]' + params, {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json'
                    }
                }).then(function(data) {
                    document.getElementById("overlay").style.display = "block";
                    window.location.href="[{/literal}][{$sSelfLink|cat:"cl=order&func=doOrder&subscribe=1&showOverlay=1"|cat:"&stoken="|cat:$oViewConf->getSessionChallengeToken()}][{literal}]"
                })
            },
            onCancel: function(data, actions) {
                window.location.href="[{/literal}][{$sSelfLink}][{literal}]"
                console.log('Consumer cancelled the PayPal Subscription Flow. No action required.');
            }
        }).render('#[{/literal}]button[{$subscriptionPlan->id}][{literal}]');
        [{/literal}]
    [{/capture}]
    [{oxscript add=$paypal_init}]
[{/foreach}]