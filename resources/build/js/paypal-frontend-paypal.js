window.OxidPayPal = {
    sdkLoaded: false,
    onSDKLoaded: function () {
        const PayPalSDKLoadedEvent = new CustomEvent('PayPalSDKLoadedEvent', {
            bubbles: false,
            cancelable: false
        });

        window.dispatchEvent(PayPalSDKLoadedEvent);
        this.sdkLoaded = true;
    },
    isSDKLoaded: function () {
        return this.sdkLoaded;
    }
};
