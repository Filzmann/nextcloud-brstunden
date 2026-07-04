(function() {
    const client = new window.LocalBase.api.ApiClient({ appId: 'brstunden' });

    window.BRStunden = window.BRStunden || {};
    window.BRStunden.api = {
        request: client.request.bind(client)
    };
})();
