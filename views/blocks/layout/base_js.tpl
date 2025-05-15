[{$smarty.block.parent}]
[{assign var="oConfig" value=$oViewConf->getConfig()}]
[{assign var="PayPalSDKJS" value=$oConfig->getGlobalParameter("PayPalSDKJS")}]
[{$PayPalSDKJS}]
