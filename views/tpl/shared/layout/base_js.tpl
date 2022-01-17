[{if $oViewConf->isPayPalActive()}]
    [{if $submitCart}]
    <script>
        document.getElementById('orderConfirmAgbBottom').submit();
    </script>
    [{/if}]
    [{if $oViewConf->getTopActiveClassName()|lower == 'account_order'}]
        [{capture assign="stornoButtons"}]
            [{assign var="sSelfLink" value=$oViewConf->getSelfLink()|replace:"&amp;":"&"}]
             [{literal}]
            document.addEventListener('click', function (event) {
                if (!event.target.matches('.subscriptionCancelButton')) return;
                event.preventDefault();
                let el = event.target,
                params = 'cl=oscpaypalproxy&fnc=sendCancelRequest&orderId=' + el.getAttribute("data-orderid");
                params += '&stoken=[{/literal}][{$oViewConf->getSessionChallengeToken()}][{literal}]';
                fetch('[{/literal}][{$sSelfLink}][{literal}]' + params, {
                    method: 'post',
                    headers: {
                        'content-type': 'application/json'
                    }
                }).then(function(data) {
                    el.innerText = el.getAttribute("data-successtext");
                    el.disabled = true;
                });
            }, false);
            [{/literal}]
        [{/capture}]
        [{oxscript add=$stornoButtons}]
    [{/if}]
[{/if}]
