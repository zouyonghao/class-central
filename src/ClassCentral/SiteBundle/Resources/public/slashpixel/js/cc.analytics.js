const Analytics = (function(){

    var keenAdTrackingClient;

    function init() {
    }

    function initKeenAdTrackingWriteClient(projectId, writeKey) {
        keenAdTrackingClient = new Keen({
            projectId: projectId, // String (required always)
            writeKey: writeKey,   // String (required for sending data)
            protocol: "https",         // String (optional: https | http | auto)
        });
    }

    function logAds(provider,adUnit,title) {
        var adClick = {
            provider: provider,
            adUnit: adUnit,
            title: title
        }

        ga("send","event","Ad Clicks - By Provider", provider,title.concat(' | ', adUnit));
        ga("send","event","Ad Clicks - By Ad Unit", adUnit,title);

        if(keenAdTrackingClient) {
            keenAdTrackingClient.addEvent("Ad Clicks",adClick,function(err,res){
                if (err) {
                    // there was an error!

                }
                else {

                }
            });
        }

    }

    return {
        'init' : init,
        'initKeenAdTrackingWriteClient' : initKeenAdTrackingWriteClient,
        'logAds': logAds
    };
})();

export default Analytics;
