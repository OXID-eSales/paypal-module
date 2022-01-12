[{if $listitem->blacklist == 1}]
    [{assign var="listclass" value=listitem3}]
[{else}]
    [{assign var="listclass" value=listitem$blWhite}]
[{/if}]
[{if $listitem->oxarticles__oxid->value == $oxid}]
    [{assign var="listclass" value=listitem4}]
[{/if}]
<td valign="top" class="[{$listclass}][{if $listitem->showActiveCheckInAdminPanel}] active[{/if}]" height="15">
    <div class="listitemfloating">&nbsp;</div>
</td>
<td valign="top" class="[{$listclass}][{if $oView->isSubscriptionProduct($listitem->oxarticles__oxid->value)}] active[{/if}]" height="15">
    <div class="listitemfloating">&nbsp;</div>
</td>
<td valign="top" class="[{$listclass}]">
    <div class="listitemfloating">
        <a href="Javascript:top.oxid.admin.editThis('[{$listitem->oxarticles__oxid->value}]');" class="[{$listclass}]">
            [{$listitem->oxarticles__oxartnum->value}]
        </a>
    </div>
</td>
<td valign="top" class="[{$listclass}]" height="15">
    <div class="listitemfloating">
        &nbsp;
        <a href="Javascript:top.oxid.admin.editThis('[{$listitem->oxarticles__oxid->value}]');" class="[{$listclass}]">
            [{$listitem->pwrsearchval|oxtruncate:200:"..":false}]
        </a>
    </div>
</td>
<td valign="top" class="[{$listclass}]">
    <div class="listitemfloating">
        <a href="Javascript:top.oxid.admin.editThis('[{$listitem->oxarticles__oxid->value}]');" class="[{$listclass}]">
            [{$listitem->oxarticles__oxshortdesc->value|strip_tags|oxtruncate:45:"..":true}]
        </a>
    </div>
</td>
<td class="[{$listclass}]">
    [{include file="include/article_list_actions.tpl"}]
</td>