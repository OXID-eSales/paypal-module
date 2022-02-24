[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign box="list"}]
[{assign var="sSelfLink" value=$oViewConf->getSelfLink()|replace:"&amp;":"&"}]

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="cl" value="[{$oViewConf->getTopActiveClassName()}]">
</form>

<div class="container-fluid">
    <p>
        <br>[{oxmultilang ident="OSC_PAYPAL_SUBSCRITION_PART_NOTE" suffix="COLON"}]
        <a class="jumplink" href="[{$oViewConf->getSelfLink()}]cl=admin_order&oxid=[{$payPalParentSubscriptionOrder->getId()}]" target="basefrm">
            [{$payPalParentSubscriptionOrder->oxorder__oxordernr->value}]
        </a>
    </p>
</div>
[{include file="bottomitem.tpl"}]

