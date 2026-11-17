(function() {
    const client = new window.OCA.BrStunden.Shared.api.ApiClient({ appId: 'brstunden' });

    window.BRStunden = window.BRStunden || {};
    window.BRStunden.api = {
        request: client.request.bind(client)
    };
})();
