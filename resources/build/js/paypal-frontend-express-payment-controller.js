(function(){
    window.addEventListener('popstate', async function (e) {
        if (true === window.PayPalExpressSession.started) {
            window.PayPalExpressSession.started = false;
            await window.PayPalExpressSession.cancelPayPalExpressSession();
            window.history.back(); //real back 1 step
        }
    });
})();

