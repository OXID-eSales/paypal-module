[{include file="headitem.tpl" title="paypal"}]
[{assign var="isSandBox" value=$config->isSandbox()}]
[{capture assign="sPayPalJS"}]
    [{strip}]
        window.isSandBox = '[{$isSandBox}]';
        window.selfLink = '[{$oViewConf->getSelfLink()|replace:"&amp;":"&"}]';
    [{/strip}]
[{/capture}]

[{oxscript add=$sPayPalJS}]

<div id="content" class="paypal-config">
    <h1>[{oxmultilang ident="paypal"}] [{oxmultilang ident="OSC_PAYPAL_CONFIG"}]</h1>
    <div class="alert alert-[{if $Errors.paypal_error}]danger[{else}]success[{/if}]" role="alert">
        [{if $Errors.paypal_error}]
            [{oxmultilang ident="OSC_PAYPAL_ERR_CONF_INVALID"}]
        [{else}]
            [{oxmultilang ident="OSC_PAYPAL_CONF_VALID"}]
        [{/if}]
    </div>
    <div id="overlay"><div class="loader"></div></div>
    <form id="configForm" name="configForm" action="[{$oViewConf->getSelfLink()}]" method="post" autocomplete="off">
        [{$oViewConf->getHiddenSid()}]
        <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
        <input type="hidden" name="fnc" value="save">

        <div id="accordion">
            <div class="card">
                <div class="card-header" id="heading1">
                    <h4 class="" data-toggle="collapse" data-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                        [{oxmultilang ident="OSC_PAYPAL_CREDENTIALS"}]
                    </h4>
                </div>

                <div id="collapse1" class="collapse show" aria-labelledby="heading1" data-parent="#accordion">
                    <div class="card-body">
                        <label for="opmode">[{oxmultilang ident="OSC_PAYPAL_OPMODE"}]</label>
                        <div class="controls">
                            <select name="conf[blPayPalSandboxMode]" id="opmode" class="form-control">
                                <option value="sandbox" [{if $config->isSandbox()}]selected[{/if}]>
                                    [{oxmultilang ident="OSC_PAYPAL_OPMODE_SANDBOX"}]
                                </option>
                                <option value="live" [{if !$config->isSandbox()}]selected[{/if}]>
                                    [{oxmultilang ident="OSC_PAYPAL_OPMODE_LIVE"}]
                                </option>
                            </select>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_OPMODE"}]</span>
                        </div>

                        <label>[{oxmultilang ident="OSC_PAYPAL_CREDENTIALS"}]</label>

                        <p class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_CREDENTIALS"}]</p>

                        [{if !$config->getLiveClientId() && !$config->getLiveClientSecret() && !$config->getLiveWebhookId()}]
                            [{assign var='liveMerchantSignUpLink' value=$oView->getLiveSignUpMerchantIntegrationLink()}]
                            <p class="live"><a target="_blank"
                                  class="boardinglink"
                                  href="[{$liveMerchantSignUpLink}]"
                                  data-paypal-button="PPLtBlue">
                                    [{oxmultilang ident="OSC_PAYPAL_LIVE_BUTTON_CREDENTIALS"}]
                               </a>
                            </p>
                        [{/if}]

                        <h3 class="live">[{oxmultilang ident="OSC_PAYPAL_LIVE_CREDENTIALS"}]</h3>

                        <div class="form-group live">
                            <label for="client-id">[{oxmultilang ident="OSC_PAYPAL_CLIENT_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="client-id" name="conf[sPayPalClientId]" value="[{$config->getLiveClientId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_CLIENT_ID"}]</span>
                            </div>
                        </div>

                        <div class="form-group live">
                            <label for="client-secret">[{oxmultilang ident="OSC_PAYPAL_CLIENT_SECRET"}]</label>
                            <div class="controls">
                                <input class="password_input form-control" type="password" name="conf[sPayPalClientSecret]" data-empty="[{if $config->getLiveClientSecret()}]false[{else}]true[{/if}]" data-errorMessage="[{oxmultilang ident="MODULE_PASSWORDS_DO_NOT_MATCH"}]" [{$readonly}] title="[{oxmultilang ident="MODULE_REPEAT_PASSWORD"}]" />
                                <span id="client-secret" class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_CLIENT_SECRET"}]</span>
                            </div>
                        </div>

                        <div class="form-group live">
                            <label for="webhook-id">[{oxmultilang ident="OSC_PAYPAL_WEBHOOK_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="webhook-id" name="conf[sPayPalWebhookId]" value="[{$config->getLiveWebhookId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_WEBHOOK_ID"}]</span>
                            </div>
                        </div>

                        [{if !$config->getSandboxClientId() && !$config->getSandboxClientSecret() && !$config->getSandboxWebhookId()}]
                            [{assign var='sandboxMerchantSignUpLink' value=$oView->getSandboxSignUpMerchantIntegrationLink()}]
                            <p class="sandbox"><a target="_blank"
                                  class="boardinglink"
                                  href="[{$sandboxMerchantSignUpLink}]"
                                  data-paypal-onboard-complete="onboardedCallbackSandbox"
                                  data-paypal-button="PPLtBlue">
                                    [{oxmultilang ident="OSC_PAYPAL_SANDBOX_BUTTON_CREDENTIALS"}]
                                </a>
                            </p>
                        [{/if}]

                        <h3 class="sandbox">[{oxmultilang ident="OSC_PAYPAL_SANDBOX_CREDENTIALS"}]</h3>

                        <div class="form-group sandbox">
                            <label for="client-sandbox-id">[{oxmultilang ident="OSC_PAYPAL_CLIENT_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="client-sandbox-id" name="conf[sPayPalSandboxClientId]" value="[{$config->getSandboxClientId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_SANDBOX_CLIENT_ID"}]</span>
                            </div>
                        </div>

                        <div class="form-group sandbox">
                            <label for="client-sandbox-secret">[{oxmultilang ident="OSC_PAYPAL_CLIENT_SECRET"}]</label>
                            <div class="controls">
                                <input class="password_input form-control" type="password" name="conf[sPayPalSandboxClientSecret]" data-empty="[{if $config->getSandboxClientSecret()}]false[{else}]true[{/if}]" data-errorMessage="[{oxmultilang ident="MODULE_PASSWORDS_DO_NOT_MATCH"}]" [{$readonly}] title="[{oxmultilang ident="MODULE_REPEAT_PASSWORD"}]" />
                                <span id="client-sandbox-secret" class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_SANDBOX_CLIENT_SECRET"}]</span>
                            </div>
                        </div>

                        <div class="form-group sandbox">
                            <label for="webhook-sandbox-id">[{oxmultilang ident="OSC_PAYPAL_WEBHOOK_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="webhook-sandbox-id" name="conf[sPayPalSandboxWebhookId]" value="[{$config->getSandboxWebhookId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_SANDBOX_WEBHOOK_ID"}]</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading2">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                        [{oxmultilang ident="OSC_PAYPAL_BUTTON_PLACEMEMT_TITLE"}]
                    </h4>
                </div>

                <div id="collapse2" class="collapse" aria-labelledby="heading2" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="controls">
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[blPayPalShowProductDetailsButton]" [{if $config->showPayPalProductDetailsButton()}]checked[{/if}] value="1">
                                            [{oxmultilang ident="OSC_PAYPAL_PRODUCT_DETAILS_BUTTON_PLACEMENT"}]
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[blPayPalShowBasketButton]" [{if $config->showPayPalBasketButton()}]checked[{/if}] value="1">
                                            [{oxmultilang ident="OSC_PAYPAL_BASKET_BUTTON_PLACEMENT"}]
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[blPayPalShowCheckoutButton]" [{if $config->showPayPalCheckoutButton()}]checked[{/if}] value="1">
                                            [{oxmultilang ident="OSC_PAYPAL_CHECKOUT_PLACEMENT"}]
                                        </label>
                                    </div>
                                </div>
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BUTTON_PLACEMEMT"}]</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading2">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                        [{oxmultilang ident="OSC_PAYPAL_EXPRESS_LOGIN_TITLE"}]
                    </h4>
                </div>

                <div id="collapse2" class="collapse" aria-labelledby="heading2" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="controls">
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[blPayPalLoginWithPayPalEMail]" [{if $config->loginWithPayPalEMail()}]checked[{/if}] value="1">
                                            [{oxmultilang ident="OSC_PAYPAL_LOGIN_WITH_PAYPAL_EMAIL"}]
                                        </label>
                                    </div>
                                </div>
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_EXPRESS_LOGIN"}]</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading4">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                        [{oxmultilang ident="OSC_PAYPAL_SUBSCRIBE_OPTIONS"}]
                    </h4>
                </div>
                <div id="collapse4" class="collapse" aria-labelledby="heading4" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="autobilloutstanding">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_AUTOMATICALLY_BILL"}]</label>
                            <div class="controls">
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input id="autobilloutstanding" type="checkbox" name="conf[blPayPalAutoBillOutstanding]" [{if $config->getAutoBillOutstanding()}]checked[{/if}] value="1">
                                        </label>
                                        <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BILLING_PLAN_AUTOMATICALLY_BILL"}]</span>
                                    </div>
                                </div>
                            </div>

                            <label for="setupfeefailureaction">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_FAILURE_ACTION"}]</label>
                            <div class="controls">
                                <select name="conf[sPayPalSetupFeeFailureAction]" id="setupfeefailureaction" class="form-control">
                                    <option value="CONTINUE" [{if $config->getSetupFeeFailureAction() == 'CONTINUE'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_ACTION_CONTINUE"}]
                                    </option>
                                    <option value="CANCEL" [{if $config->getSetupFeeFailureAction() == 'CANCEL'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_ACTION_CANCEL"}]
                                    </option>
                                </select>
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BILLING_PLAN_FAILURE_ACTION"}]</span>
                            </div>

                            <label for="paymentfailurethreshold">[{oxmultilang ident="OSC_PAYPAL_BILLING_PLAN_FAILURE_THRESHOLD"}]</label>
                            <div class="controls">
                                <select name="conf[sPayPalPaymentFailureThreshold]" id="paymentfailurethreshold" class="form-control">
                                    [{foreach from=$oView->getTotalCycleDefaults() item=value key=name}]
                                        <option value="[{$value}]" [{if $config->getPaymentFailureThreshold() == $value}]selected[{/if}]>[{$value}]</option>
                                    [{/foreach}]
                                </select>
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BILLING_PLAN_FAILURE_THRESHOLD"}]</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading4">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                        [{oxmultilang ident="OSC_PAYPAL_BANNER_CREDENTIALS"}]
                    </h4>
                </div>
                <div id="collapse5" class="collapse" aria-labelledby="heading4" data-parent="#accordion">
                    <div class="card-body">
                        <p>[{oxmultilang ident="OSC_PAYPAL_BANNER_INFOTEXT"}]</p>
                        <div class="form-group">
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oePayPalBannersShowAll]" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersShowAll')}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_SHOW_ALL"}]
                                    </label>
                                </div>
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_SHOP_MODULE_SHOW_ALL"}]</span>
                            </div>


                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oePayPalBannersStartPage]" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersStartPage')}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_STARTPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-startpage">[{oxmultilang ident="OSC_PAYPAL_BANNER_STARTPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-startpage" name="conf[oePayPalBannersStartPageSelector]" value="[{$config->getPayPalModuleConfigurationValue('oePayPalBannersStartPageSelector')}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_STARTPAGESELECTOR"}]</span>

                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oePayPalBannersCategoryPage]" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersCategoryPage')}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_CATEGORYPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-categorypage">[{oxmultilang ident="OSC_PAYPAL_BANNER_CATEGORYPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-categorypage" name="conf[oePayPalBannersCategoryPageSelector]" value="[{$config->getPayPalModuleConfigurationValue('oePayPalBannersCategoryPageSelector')}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_CATEGORYPAGESELECTOR"}]</span>

                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oePayPalBannersSearchResultsPage]" custom-data="[{$config->getPayPalModuleConfigurationValue('oePayPalBannersSearchResultsPage')}]" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersSearchResultsPage')}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_SEARCHRESULTSPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-searchpage">[{oxmultilang ident="OSC_PAYPAL_BANNER_SEARCHRESULTSPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-searchpage" name="conf[oePayPalBannersSearchResultsPageSelector]" value="[{$config->getPayPalModuleConfigurationValue('oePayPalBannersSearchResultsPageSelector')}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_SEARCHRESULTSPAGESELECTOR"}]</span>

                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oePayPalBannersProductDetailsPage]" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersProductDetailsPage')}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_DETAILSPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-detailspage">[{oxmultilang ident="OSC_PAYPAL_BANNER_DETAILSPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-detailspage" name="conf[oePayPalBannersProductDetailsPageSelector]" value="[{$config->getPayPalModuleConfigurationValue('oePayPalBannersProductDetailsPageSelector')}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_DETAILSPAGESELECTOR"}]</span>

                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oePayPalBannersCheckoutPage]" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersCheckoutPage')}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_CHECKOUTPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-cart">[{oxmultilang ident="OSC_PAYPAL_BANNER_CARTPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-cart" name="conf[oePayPalBannersCartPageSelector]" value="[{$config->getPayPalModuleConfigurationValue('oePayPalBannersCartPageSelector')}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_CARTPAGESELECTOR"}]</span>
                            <label for="banner-paymentpage">[{oxmultilang ident="OSC_PAYPAL_BANNER_PAYMENTPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-paymentpage" name="conf[oePayPalBannersPaymentPageSelector]" value="[{$config->getPayPalModuleConfigurationValue('oePayPalBannersPaymentPageSelector')}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_PAYMENTPAGESELECTOR"}]</span>

                            <hr>
                            <label for="color-schema">[{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEME"}]</label>
                            <div class="controls">
                                <select name="conf[oePayPalBannersColorScheme]" id="color-schema" class="form-control">
                                    <option value="blue" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersColorScheme') == 'blue'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEBLUE"}]
                                    </option>
                                    <option value="black" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersColorScheme') == 'black'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEBLACK"}]
                                    </option>
                                    <option value="white" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersColorScheme') == 'white'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEWHITE"}]
                                    </option>
                                    <option value="white-no-border" [{if $config->getPayPalModuleConfigurationValue('oePayPalBannersColorScheme') == 'white-no-border'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEWHITENOBORDER"}]
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary bottom-space">[{oxmultilang ident="GENERAL_SAVE"}]</button>
    </form>
</div>
[{include file="bottomitem.tpl"}]

<script id="paypal-js" src="https://www.sandbox.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>
