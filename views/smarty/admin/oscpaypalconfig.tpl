[{include file="headitem.tpl" title="paypal" box="boxpaypal"}]
[{assign var="isSandBox" value=$config->isSandbox()}]
[{assign var="sSelfLink" value=$oViewConf->getSslSelfLink()|replace:"&amp;":"&"}]

[{capture assign="sPayPalJS"}]
    [{strip}]
    window.isSandBox = '[{$isSandBox}]';
    window.selfLink = '[{$sSelfLink}]';
    window.selfControl = window.selfLink + 'cl=oscpaypalconfig';
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
    <form id="configForm" name="configForm" action="[{$oViewConf->getSslSelfLink()}]" method="post" autocomplete="off">
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
                            <div>
                                [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_PUI" suffix="COLON"}] [{if $config->isLivePuiEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                <br>
                                [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC" suffix="COLON"}] [{if $config->isLiveAcdcEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}] [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC_FALLBACK"}][{/if}]
                                <br>
                                [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_VAULTING" suffix="COLON"}] [{if $config->isLiveVaultingEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                            </div>
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
                            <div>
                                [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_PUI" suffix="COLON"}] [{if $config->isSandboxPuiEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
                                <br>
                                [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC" suffix="COLON"}] [{if $config->isSandboxAcdcEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}] [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_ACDC_FALLBACK"}][{/if}]
                                <br>
                                [{oxmultilang ident="OSC_PAYPAL_SPECIAL_PAYMENTS_VAULTING" suffix="COLON"}] [{if $config->isSandboxVaultingEligibility()}][{oxmultilang ident="GENERAL_YES"}][{else}][{oxmultilang ident="GENERAL_NO"}][{/if}]
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
                        [{oxmultilang ident="OSC_PAYPAL_STANDARD_CAPTURE_TIME"}]
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
                            <a class="btn btn-primary bottom-space" href="[{$sSelfLink|cat:"cl=oscpaypalconfig&fnc=transferBannerSettings"}]">[{oxmultilang ident='OSC_PAYPAL_BANNER_TRANSFERLEGACYSETTINGS'}]</a>
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
                                        <input type="checkbox" name="conf[oscPayPalSetVaulting]" [{if $config->getIsVaultingActive()}]checked[{/if}] value="1">
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
        <button type="submit" class="btn btn-primary bottom-space">[{oxmultilang ident="GENERAL_SAVE"}]</button>
    </form>
</div>
[{include file="bottomitem.tpl"}]

<script id="paypal-js" src="https://www.paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js"></script>
