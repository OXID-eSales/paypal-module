[{include file="headitem.tpl" title="paypal" box="boxpaypal"}]
[{assign var="isSandBox" value=$config->isSandbox()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]
[{capture assign="sPayPalJS"}]
    [{strip}]
    window.isSandBox = '[{$isSandBox}]';
    window.selfLink = '[{$sSelfLink}]';
    [{/strip}]
    [{/capture}]

[{oxscript add=$sPayPalJS}]

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oModule->getInfo('id')}]">
    <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
    <input type="hidden" name="fnc" value="">
    <input type="hidden" name="actshop" value="[{$oViewConf->getActiveShopId()}]">
    <input type="hidden" name="updatenav" value="">
    <input type="hidden" name="editlanguage" value="[{$editlanguage}]">
</form>

<div id="content" class="paypal-config">
    <div class="alert alert-[{if $Errors.paypal_error}]danger[{else}]success[{/if}]" role="alert">
        [{if $Errors.paypal_error}]
        [{oxmultilang ident="OSC_PAYPAL_ERR_CONF_INVALID"}]
        [{else}]
        [{oxmultilang ident="OSC_PAYPAL_CONF_VALID"}]
        [{/if}]
    </div>
    <div id="paypal-overlay"><div class="loader"></div></div>
    <form id="configForm" name="configForm" action="[{$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]" method="post" autocomplete="off">
        [{$oViewConf->getHiddenSid()}]
        <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
        <input type="hidden" name="fnc" value="save">
        <input type="hidden" name="oxid" value="[{$oModule->getInfo('id')}]">
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
                            <select name="conf[oscPayPalSandboxMode]" id="opmode" class="form-control">
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

                        <p class="help-block text-danger">[{oxmultilang ident="HELP_OSC_PAYPAL_CREDENTIALS_PART1"}]</p>
                        <p class="help-block text-danger">[{oxmultilang ident="HELP_OSC_PAYPAL_CREDENTIALS_PART2"}]</p>

                        <p class="live"><a target="_blank"
                                           class="popuplink2"
                                           href="[{$oView->getLiveSignUpMerchantIntegrationLink()}]"
                                           id="paypalonboardinglive"
                                           data-paypal-onboard-complete="onboardedCallbackLive"
                                           data-paypal-button="PPLtBlue">
                                [{oxmultilang ident="OSC_PAYPAL_LIVE_BUTTON_CREDENTIALS"}]
                            </a>
                        </p>

                        <h3 class="live">[{oxmultilang ident="OSC_PAYPAL_LIVE_CREDENTIALS"}]</h3>

                        <div class="form-group live">
                            <label for="client-id">[{oxmultilang ident="OSC_PAYPAL_CLIENT_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="client-id" name="conf[oscPayPalClientId]" value="[{$config->getLiveClientId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_CLIENT_ID"}]</span>
                            </div>
                        </div>

                        <div class="form-group live">
                            <label for="client-secret">[{oxmultilang ident="OSC_PAYPAL_CLIENT_SECRET"}]</label>
                            <div class="controls">
                                <input class="password_input form-control" type="password" name="conf[oscPayPalClientSecret]" data-empty="[{if $config->getLiveClientSecret()}]false[{else}]true[{/if}]" data-errorMessage="[{oxmultilang ident="MODULE_PASSWORDS_DO_NOT_MATCH"}]" [{$readonly}] title="[{oxmultilang ident="MODULE_REPEAT_PASSWORD"}]" />
                                <span id="client-secret" class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_CLIENT_SECRET"}]</span>
                            </div>
                        </div>

                        <div class="form-group live">
                            <label for="merchant-id">[{oxmultilang ident="OSC_PAYPAL_MERCHANT_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="merchant-id" name="conf[oscPayPalClientMerchantId]" value="[{$config->getLiveMerchantId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_MERCHANT_ID"}]</span>
                            </div>
                        </div>

                        <div class="form-group live">
                            <label for="webhook-id">[{oxmultilang ident="OSC_PAYPAL_WEBHOOK_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="webhook-id" name="conf[oscPayPalWebhookId]" value="[{$config->getLiveWebhookId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_WEBHOOK_ID"}]</span>
                            </div>
                        </div>

                        <div class="form-group live">
                            <label for="special-payments">[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS" suffix="COLON"}]</label>
                            <ul>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_PUI" suffix="COLON"}]</b>
                                    [{if $config->isLivePuiEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC" suffix="COLON"}]</b>
                                    [{if $config->isLiveAcdcEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}] [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC_FALLBACK"}][{/if}]</li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_VAULTING" suffix="COLON"}]</b>
                                    [{if $config->isLiveVaultingEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_GOOGLEPAY" suffix="COLON"}]</b>
                                    [{if $config->isLiveGooglePayEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_APPLEPAY" suffix="COLON"}]</b>
                                    [{if $config->isLiveApplePayEligibility()}][{oxmultilang ident="GENERAL_YES"}] [{oxmultilang ident="OSC_PAYPAL_INSTALL_NOTE_APPLEPAY"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_EPS" suffix="COLON"}]</b>
                                    [{if $config->isLiveEpsEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_PRZELEWY24" suffix="COLON"}]</b>
                                    [{if $config->isLivePrzelewy24Eligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_BLIK" suffix="COLON"}]</b>
                                    [{if $config->isLiveBlikEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_BANCONTACT" suffix="COLON"}]</b>
                                    [{if $config->isLiveBanContactEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_IDEAL" suffix="COLON"}]</b>
                                    [{if $config->isLiveIDealEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                [{* SEPA unbranded comming soon
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_SEPA" suffix="COLON"}]</b>
                                    [{if $config->isLiveSepaEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                *}]
                            </ul>
                        </div>

                        <p class="sandbox"><a target="_blank"
                                              class="popuplink"
                                              href="[{$oView->getSandboxSignUpMerchantIntegrationLink()}]"
                                              id="paypalonboardingsandbox"
                                              data-paypal-onboard-complete="onboardedCallbackSandbox"
                                              data-paypal-button="PPLtBlue">
                                [{oxmultilang ident="OSC_PAYPAL_SANDBOX_BUTTON_CREDENTIALS"}]
                            </a>
                        </p>

                        <h3 class="sandbox">[{oxmultilang ident="OSC_PAYPAL_SANDBOX_CREDENTIALS"}]</h3>

                        <div class="form-group sandbox">
                            <label for="client-sandbox-id">[{oxmultilang ident="OSC_PAYPAL_CLIENT_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="client-sandbox-id" name="conf[oscPayPalSandboxClientId]" value="[{$config->getSandboxClientId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_SANDBOX_CLIENT_ID"}]</span>
                            </div>
                        </div>

                        <div class="form-group sandbox">
                            <label for="client-sandbox-secret">[{oxmultilang ident="OSC_PAYPAL_CLIENT_SECRET"}]</label>
                            <div class="controls">
                                <input class="password_input form-control" type="password" name="conf[oscPayPalSandboxClientSecret]" data-empty="[{if $config->getSandboxClientSecret()}]false[{else}]true[{/if}]" data-errorMessage="[{oxmultilang ident="MODULE_PASSWORDS_DO_NOT_MATCH"}]" [{$readonly}] title="[{oxmultilang ident="MODULE_REPEAT_PASSWORD"}]" />
                                <span id="client-sandbox-secret" class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_SANDBOX_CLIENT_SECRET"}]</span>
                            </div>
                        </div>

                        <div class="form-group sandbox">
                            <label for="merchant-sandbox-id">[{oxmultilang ident="OSC_PAYPAL_MERCHANT_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="merchant-sandbox-id" name="conf[oscPayPalSandboxClientMerchantId]" value="[{$config->getSandboxMerchantId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_SANDBOX_MERCHANT_ID"}]</span>
                            </div>
                        </div>

                        <div class="form-group sandbox">
                            <label for="webhook-sandbox-id">[{oxmultilang ident="OSC_PAYPAL_WEBHOOK_ID"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="webhook-sandbox-id" name="conf[oscPayPalSandboxWebhookId]" value="[{$config->getSandboxWebhookId()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_SANDBOX_WEBHOOK_ID"}]</span>
                            </div>
                        </div>

                        <div class="form-group sandbox">
                            <label for="special-payments-sandbox">[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS" suffix="COLON"}]</label>
                            <ul>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_PUI" suffix="COLON"}]</b>
                                    [{if $config->isSandboxPuiEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC" suffix="COLON"}]</b>
                                    [{if $config->isSandboxAcdcEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}] [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC_FALLBACK"}][{/if}]</li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_VAULTING" suffix="COLON"}]</b>
                                    [{if $config->isSandboxVaultingEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_GOOGLEPAY" suffix="COLON"}]</b>
                                    [{if $config->isSandboxGooglePayEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_APPLEPAY" suffix="COLON"}]</b>
                                    [{if $config->isSandboxApplePayEligibility()}][{oxmultilang ident="GENERAL_YES"}] [{oxmultilang ident="OSC_PAYPAL_INSTALL_NOTE_APPLEPAY"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_EPS" suffix="COLON"}]</b>
                                    [{if $config->isSandboxEpsEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_PRZELEWY24" suffix="COLON"}]</b>
                                    [{if $config->isSandboxPrzelewy24Eligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_BLIK" suffix="COLON"}]</b>
                                    [{if $config->isSandboxBlikEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_BANCONTACT" suffix="COLON"}]</b>
                                    [{if $config->isSandboxBanContactEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_IDEAL" suffix="COLON"}]</b>
                                    [{if $config->isSandboxIDealEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                [{* SEPA unbranded comming soon
                                <li>
                                    <b>[{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_SEPA" suffix="COLON"}]</b>
                                    [{if $config->isSandboxSepaEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                </li>
                                *}]
                            </ul>
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
                                <p><span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BUTTON_PLACEMEMT"}]</span></p>
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[oscPayPalShowProductDetailsButton]" [{if $config->showPayPalProductDetailsButton()}]checked[{/if}] value="1">
                                            [{oxmultilang ident="OSC_PAYPAL_PRODUCT_DETAILS_BUTTON_PLACEMENT"}]
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[oscPayPalShowBasketButton]" [{if $config->showPayPalBasketButton()}]checked[{/if}] value="1">
                                            [{oxmultilang ident="OSC_PAYPAL_BASKET_BUTTON_PLACEMENT"}]
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[oscPayPalShowMiniBasketButton]" [{if $config->showPayPalMiniBasketButton()}]checked[{/if}] value="1">
                                            [{oxmultilang ident="OSC_PAYPAL_MINIBASKET_BUTTON_PLACEMENT"}]
                                        </label>
                                    </div>
                                </div>
                                <p><span class="help-block">[{oxmultilang ident="HELP_OSC_SHOW_PAYPAL_PAYLATER_BUTTON"}]</span></p>
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[oscPayPalShowPayLaterButton]" [{if $config->showPayPalPayLaterButton()}]checked[{/if}] value="1">
                                            [{oxmultilang ident="OSC_SHOW_PAYPAL_PAYLATER_BUTTON"}]
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading10">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse_button_style" aria-expanded="false" aria-controls="collapse_button_style">
                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_TITLE"}]
                    </h4>
                </div>

                <div id="collapse_button_style" class="collapse" aria-labelledby="heading10" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="style-button-layout">[{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LAYOUT"}]</label>
                            <div class="controls">
                                <select name="conf[oscPayPalButtonStyleLayout]" id="style-button-layout" class="form-control">
                                    <option value="horizontal" [{if $config->getPayPalButtonStyleLayout() == 'horizontal'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LAYOUT_HORIZONTAL"}]
                                    </option>
                                    <option value="vertical" [{if $config->getPayPalButtonStyleLayout() == 'vertical'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LAYOUT_VERTICAL"}]
                                    </option>
                                </select>
                            </div>
                            <span class="help-block">[{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LAYOUT_HELP"}]</span>
                        </div>
                        <div class="form-group">
                            <label for="style-button-color">[{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_COLOR"}]</label>
                            <div class="controls">
                                <select name="conf[oscPayPalButtonStyleColor]" id="style-button-color" class="form-control">
                                    <option value="gold" [{if $config->getPayPalButtonStyleColor() == 'gold'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_COLOR_GOLD"}]
                                    </option>
                                    <option value="blue" [{if $config->getPayPalButtonStyleColor() == 'blue'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_COLOR_BLUE"}]
                                    </option>
                                    <option value="silver" [{if $config->getPayPalButtonStyleColor() == 'silver'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_COLOR_SILVER"}]
                                    </option>
                                    <option value="white" [{if $config->getPayPalButtonStyleColor() == 'white'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_COLOR_WHITE"}]
                                    </option>
                                    <option value="black" [{if $config->getPayPalButtonStyleColor() == 'black'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_COLOR_BLACK"}]
                                    </option>
                                </select>
                            </div>
                            <span class="help-block">[{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_COLOR_HELP"}]</span>
                        </div>
                        <div class="form-group">
                            <label for="style-button-shape">[{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_SHAPE"}]</label>
                            <div class="controls">
                                <select name="conf[oscPayPalButtonStyleShape]" id="style-button-shape" class="form-control">
                                    <option value="rect" [{if $config->getPayPalButtonStyleShape() == 'rect'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_SHAPE_RECT"}]
                                    </option>
                                    <option value="sharp" [{if $config->getPayPalButtonStyleShape() == 'sharp'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_SHAPE_SHARP"}]
                                    </option>
                                    <option value="pill" [{if $config->getPayPalButtonStyleShape() == 'pill'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_SHAPE_PILL"}]
                                    </option>
                                </select>
                            </div>
                            <span class="help-block">[{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_SHAPE_HELP"}]</span>
                        </div>
                        <div class="form-group">
                            <label for="style-button-label">[{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LABEL"}]</label>
                            <div class="controls">
                                <select name="conf[oscPayPalButtonStyleLabel]" id="style-button-label" class="form-control">
                                    <option value="paypal" [{if $config->getPayPalButtonStyleLabel() == 'paypal'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LABEL_PAYPAL"}]
                                    </option>
                                    <option value="checkout" [{if $config->getPayPalButtonStyleLabel() == 'checkout'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LABEL_CHECKOUT"}]
                                    </option>
                                    <option value="buynow" [{if $config->getPayPalButtonStyleLabel() == 'buynow'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LABEL_BUYNOW"}]
                                    </option>
                                    <option value="pay" [{if $config->getPayPalButtonStyleLabel() == 'pay'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LABEL_PAY"}]
                                    </option>
                                    [{*
                                    <option value="installment" [{if $config->getPayPalButtonStyleLabel() == 'installment'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LABEL_INSTALLMENT"}]
                                    </option>
                                    *}]
                                </select>

                            </div>
                            <span class="help-block">[{oxmultilang ident="OSC_PAYPAL_STYLE_BUTTON_LABEL_HELP"}]</span>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card">
                <div class="card-header" id="heading3">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                        [{oxmultilang ident="OSC_PAYPAL_EXPRESS_LOGIN_TITLE"}]
                    </h4>
                </div>

                <div id="collapse3" class="collapse" aria-labelledby="heading3" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="controls">
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[oscPayPalLoginWithPayPalEMail]" [{if $config->loginWithPayPalEMail()}]checked[{/if}] value="1">
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
                        [{oxmultilang ident="OSC_PAYPAL_CAPTURE_STRATEGY_TITLE"}]
                    </h4>
                </div>

                <div id="collapse4" class="collapse" aria-labelledby="heading4" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="capture-time">[{oxmultilang ident="OSC_PAYPAL_STANDARD_CAPTURE_TIME_LABEL"}]</label>
                            <div class="controls">
                                <select name="conf[oscPayPalStandardCaptureStrategy]" id="capture-time" class="form-control">
                                    <option value="directly" [{if $config->getPayPalStandardCaptureStrategy() == 'directly'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STANDARD_CAPTURE_TIME_DIRECTLY"}]
                                    </option>
                                    <option value="delivery" [{if $config->getPayPalStandardCaptureStrategy() == 'delivery'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STANDARD_CAPTURE_TIME_DELIVERY"}]
                                    </option>
                                    <option value="manually" [{if $config->getPayPalStandardCaptureStrategy() == 'manually'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_STANDARD_CAPTURE_TIME_MANUALLY"}]
                                    </option>
                                </select>
                            </div>
                            <span class="help-block">[{oxmultilang ident="OSC_PAYPAL_STANDARD_CAPTURE_TIME_HELP"}]</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading5">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                        [{oxmultilang ident="OSC_PAYPAL_SCA_CONTINGENCY"}]
                    </h4>
                </div>

                <div id="collapse5" class="collapse" aria-labelledby="heading5" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="sca-config">[{oxmultilang ident="OSC_PAYPAL_SCA_CONTINGENCY_LABEL"}]</label>
                            <div class="controls">
                                <select name="conf[oscPayPalSCAContingency]" id="sca-config" class="form-control">
                                    <option value="SCA_ALWAYS" [{if $config->getPayPalSCAContingency() == 'SCA_ALWAYS'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_SCA_ALWAYS"}]
                                    </option>
                                    <option value="SCA_WHEN_REQUIRED" [{if $config->getPayPalSCAContingency() == 'SCA_WHEN_REQUIRED'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_SCA_WHEN_REQUIRED"}]
                                    </option>
                                    <option value="SCA_DISABLED" [{if $config->alwaysIgnoreSCAResult()}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_SCA_DISABLED"}]
                                    </option>
                                </select>
                            </div>
                            <span class="help-block">[{oxmultilang ident="OSC_PAYPAL_SCA_CONTINGENCY_HELP"}]</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading6">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                        [{oxmultilang ident="OSC_PAYPAL_HANDLING_NOT_FINISHED_ORDERS_TITLE"}]
                    </h4>
                </div>

                <div id="collapse6" class="collapse" aria-labelledby="heading6" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="controls">
                                <div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[oscPayPalCleanUpNotFinishedOrdersAutomaticlly]" [{if $config->cleanUpNotFinishedOrdersAutomaticlly()}]checked[{/if}] value="1">
                                            [{oxmultilang ident="OSC_PAYPAL_HANDLING_NOT_FINISHED_ORDERS"}]
                                        </label>
                                    </div>
                                </div>
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_HANDLING_NOT_FINISHED_ORDERS"}]</span>
                            </div>
                            <label for="starttime-cleanup">[{oxmultilang ident="OSC_PAYPAL_STARTTIME_CLEANUP_ORDERS"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="starttime-cleanup" name="conf[oscPayPalStartTimeCleanUpOrders]" value="[{$config->getStartTimeCleanUpOrders()}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_STARTTIME_CLEANUP_ORDERS"}]</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading7">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse7" aria-expanded="false" aria-controls="collapse7">
                        [{oxmultilang ident="OSC_PAYPAL_BANNER_CREDENTIALS"}]
                    </h4>
                </div>
                <div id="collapse7" class="collapse" aria-labelledby="heading7" data-parent="#accordion">
                    <div class="card-body">
                        [{if $oView->showTransferLegacySettingsButton()}]
                        <a class="btn btn-primary bottom-space" href="[{$sSelfLink|cat:"cl="|cat:$oViewConf->getActiveClassName()|cat:"&fnc=transferBannerSettings"}]">[{oxmultilang ident='OSC_PAYPAL_BANNER_TRANSFERLEGACYSETTINGS'}]</a>
                        [{/if}]
                        <p>[{oxmultilang ident="OSC_PAYPAL_BANNER_INFOTEXT"}]</p>
                        <div class="form-group">
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oscPayPalBannersShowAll]" [{if $config->showAllPayPalBanners()}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_SHOW_ALL"}]
                                    </label>
                                </div>
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_SHOP_MODULE_SHOW_ALL"}]</span>
                            </div>


                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oscPayPalBannersStartPage]" [{if $config->showBannersOnStartPage()}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_STARTPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-startpage">[{oxmultilang ident="OSC_PAYPAL_BANNER_STARTPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-startpage" name="conf[oscPayPalBannersStartPageSelector]" value="[{$config->getStartPageBannerSelector()}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_STARTPAGESELECTOR"}]</span>

                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oscPayPalBannersCategoryPage]" [{if $config->showBannersOnCategoryPage()}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_CATEGORYPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-categorypage">[{oxmultilang ident="OSC_PAYPAL_BANNER_CATEGORYPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-categorypage" name="conf[oscPayPalBannersCategoryPageSelector]" value="[{$config->getCategoryPageBannerSelector()}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_CATEGORYPAGESELECTOR"}]</span>

                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oscPayPalBannersSearchResultsPage]" [{if $config->showBannersOnSearchPage()}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_SEARCHRESULTSPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-searchpage">[{oxmultilang ident="OSC_PAYPAL_BANNER_SEARCHRESULTSPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-searchpage" name="conf[oscPayPalBannersSearchResultsPageSelector]" value="[{$config->getSearchPageBannerSelector()}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_SEARCHRESULTSPAGESELECTOR"}]</span>

                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oscPayPalBannersProductDetailsPage]" [{if $config->showBannersOnProductDetailsPage()}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_DETAILSPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-detailspage">[{oxmultilang ident="OSC_PAYPAL_BANNER_DETAILSPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-detailspage" name="conf[oscPayPalBannersProductDetailsPageSelector]" value="[{$config->getProductDetailsPageBannerSelector()}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_DETAILSPAGESELECTOR"}]</span>

                            <hr>
                            <div class="controls">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="conf[oscPayPalBannersCheckoutPage]" [{if $config->showBannersOnCheckoutPage()}]checked[{/if}] value="1">
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_CHECKOUTPAGE"}]
                                    </label>
                                </div>
                            </div>
                            <label for="banner-cart">[{oxmultilang ident="OSC_PAYPAL_BANNER_CARTPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-cart" name="conf[oscPayPalBannersCartPageSelector]" value="[{$config->getPayPalCheckoutBannerCartPageSelector()}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_CARTPAGESELECTOR"}]</span>
                            <label for="banner-paymentpage">[{oxmultilang ident="OSC_PAYPAL_BANNER_PAYMENTPAGESELECTOR"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="banner-paymentpage" name="conf[oscPayPalBannersPaymentPageSelector]" value="[{$config->getPayPalCheckoutBannerPaymentPageSelector()}]">
                            </div>
                            <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_BANNER_PAYMENTPAGESELECTOR"}]</span>

                            <hr>
                            <label for="color-schema">[{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEME"}]</label>
                            <div class="controls">
                                <select name="conf[oscPayPalBannersColorScheme]" id="color-schema" class="form-control">
                                    <option value="blue" [{if $config->getPayPalCheckoutBannerColorScheme() == 'blue'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEBLUE"}]
                                    </option>
                                    <option value="black" [{if $config->getPayPalCheckoutBannerColorScheme() == 'black'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEBLACK"}]
                                    </option>
                                    <option value="white" [{if $config->getPayPalCheckoutBannerColorScheme() == 'white'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEWHITE"}]
                                    </option>
                                    <option value="gray" [{if $config->getPayPalCheckoutBannerColorScheme() == 'gray'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEGRAY"}]
                                    </option>
                                    <option value="monochrome" [{if $config->getPayPalCheckoutBannerColorScheme() == 'monochrome'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEMONOCHROME"}]
                                    </option>
                                    <option value="grayscale" [{if $config->getPayPalCheckoutBannerColorScheme() == 'grayscale'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_BANNER_COLORSCHEMEGRAYSCALE"}]
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="heading8">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse8" aria-expanded="false" aria-controls="collapse8">
                        [{oxmultilang ident="OSC_PAYPAL_LOCALISATIONS"}]
                    </h4>
                </div>

                <div id="collapse8" class="collapse" aria-labelledby="heading8" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="locales">[{oxmultilang ident="OSC_PAYPAL_LOCALES"}]</label>
                            <div class="controls">
                                <input type="text" class="form-control" id="locales" name="conf[oscPayPalLocales]" value="[{$config->getSupportedLocalesCommaSeparated()}]" />
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_LOCALES"}]</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="heading9">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse9" aria-expanded="false" aria-controls="collapse9">
                        [{oxmultilang ident="OSC_PAYPAL_VAULTING_TITLE"}]
                    </h4>
                </div>

                <div id="collapse9" class="collapse" aria-labelledby="heading9" data-parent="#accordion">
                    <div class="card-body">
                            <div class="form-group">
                                <div class="controls">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[oscPayPalSetVaulting]" value="1" [{if $config->getIsVaultingActive()}] checked[{/if}][{if !$config->isVaultingEligibility()}] disabled[{/if}]>
                                            [{oxmultilang ident="OSC_PAYPAL_VAULTING_ACTIVATE_VAULTING"}]
                                        </label>
                                    </div>
                                    <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_VAULTING_ACTIVATE_VAULTING"}]</span>
                                </div>
                            </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading10">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse10" aria-expanded="false" aria-controls="collapse10">
                        [{oxmultilang ident="OSC_PAYPAL_EXPRESS_SHIPPING_TITLE"}]
                    </h4>
                </div>
                <div id="collapse10" class="collapse" aria-labelledby="heading10" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="controls">
                                <div class="form-group">
                                    <label for="shippingExpress">[{oxmultilang ident="OSC_PAYPAL_EXPRESS_SHIPPING_TITLE"}]</label>
                                    <div class="controls">
                                        <input type="text" id="shippingExpress" class="form-control" name="conf[oscPayPalDefaultShippingPriceExpress]" value="[{$config->getDefaultShippingPriceForExpress()|string_format:"%.2f"}]" />
                                        <span class="help-block">[{oxmultilang ident="OSC_PAYPAL_EXPRESS_SHIPPING_DESC"}]</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header" id="heading11">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse11" aria-expanded="false" aria-controls="collapse11">
                        [{oxmultilang ident="OSC_PAYPAL_CUSTOM_ID_CONTENTS_TITLE"}]
                    </h4>
                </div>
                <div id="collapse11" class="collapse" aria-labelledby="heading11" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="controls">
                                <div class="form-group">
                                    <div class="controls">
                                        <input type="checkbox" name="conf[oscPayPalUseStructuralCustomIdSchema]" value="1" [{if $config->isCustomIdSchemaStructural()}] checked[{/if}]>
                                        <span class="help-block">[{oxmultilang ident="OSC_PAYPAL_CUSTOM_ID_CONTENTS_DESC"}]</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header" id="heading12">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse12" aria-expanded="false" aria-controls="collapse12">
                        [{oxmultilang ident="OSC_PAYPAL_DEBUG_LEVEL_OVERRIDE_TITLE"}]
                    </h4>
                </div>
                <div id="collapse12" class="collapse" aria-labelledby="heading12" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="debug-level">[{oxmultilang ident="OSC_PAYPAL_DEBUG_LEVEL"}]</label>
                            <div class="controls">
                                <select name="conf[oscPayPalDebugLevel]" id="debug-level" class="form-control">
                                    <option value="off" [{if $config->getPayPalDebugLevel() == 'off'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_DEBUG_LEVEL_OFF"}]
                                    </option>
                                    <option value="debug" [{if $config->getPayPalDebugLevel() == 'debug'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_DEBUG_LEVEL_DEBUG"}]
                                    </option>
                                    <option value="error" [{if $config->getPayPalDebugLevel() == 'error'}]selected[{/if}]>
                                        [{oxmultilang ident="OSC_PAYPAL_DEBUG_LEVEL_ERROR"}]
                                    </option>
                                </select>
                                <span class="help-block">[{oxmultilang ident="HELP_OSC_PAYPAL_DEBUG_LEVEL"}]</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            [{*
            <div class="card">
                <div class="card-header" id="heading9">
                    <h4 class="collapsed" data-toggle="collapse" data-target="#collapse9" aria-expanded="false" aria-controls="collapse9">
                        [{oxmultilang ident="OSC_PAYPAL_GOOGLEPAY_TITLE"}]
                    </h4>
                </div>
            <div id="collapse9" class="collapse" aria-labelledby="heading9" data-parent="#accordion">
                <div class="card-body">
                    <div class="form-group">
                        <div class="controls">
                            <div class="form-group">
                                <div class="controls">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="conf[oscPayPalUseGooglePayAddress]" [{if $config->getIsGooglePayDeliveryAdressActive()}]checked[{/if}] value="1" >
                                            [{oxmultilang ident="OSC_PAYPAL_GOOGLEPAY_ADDRESS_ACTIVATE"}]
                                        </label>
                                    </div>
                                    <span class="help-block">[{oxmultilang ident="HELP_OSC_OSC_PAYPAL_GOOGLEPAY_ADRESS_ACTIVATE"}]</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> *}]
        </div>
        <button type="submit" class="btn btn-primary bottom-space">[{oxmultilang ident="GENERAL_SAVE"}]</button>
    </form>
</div>
[{oxscript include="js/libs/jquery.min.js"}]
[{oxscript include="js/libs/jquery-ui.min.js"}]
[{oxscript include="js/widgets/oxmoduleconfiguration.js"}]
[{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','src/js/bootstrap.min.js')|filemtime}]
[{oxscript include=$oViewConf->getModuleUrl('osc_paypal','src/js/bootstrap.min.js')|cat:"?"|cat:$sFileMTime priority=6}]
[{assign var="sFileMTime" value=$oViewConf->getModulePath('osc_paypal','src/js/paypal-admin.min.js')|filemtime}]
[{oxscript include=$oViewConf->getModuleUrl('osc_paypal','src/js/paypal-admin.min.js')|cat:"?"|cat:$sFileMTime priority=8}]
[{oxscript add="$('#configForm').oxModuleConfiguration();" priority=10}]
<script id="paypal-js" src="https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>

[{include file="bottomitem.tpl"}]