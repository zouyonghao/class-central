var CC = CC || {
        Class : {}
}

CC.Class['Analytics'] = (function(){

    var keenAdTrackingClient;
    var utilities = CC.Class['Utilities'];


    function init() {
    }

    function initKeenAdTrackingWriteClient(projectId, writeKey) {
        keenAdTrackingClient = new Keen({
            projectId: projectId, // String (required always)
            writeKey: writeKey,   // String (required for sending data)
            protocol: "https",         // String (optional: https | http | auto)
        });
    }

    function logCourseAd(src, providerName, courseName) {
        var courseAd = {
            src : src,
            provider : providerName,
            course : courseName
        }

        //

        if(keenAdTrackingClient) {
            keenAdTrackingClient.addEvent("courseAds", courseAd, function(err, res){
                if (err) {
                    // there was an error!

                }
                else {

                }
            });
        }
    }

    function logTextAd(src,adTitle) {
        var textAd = {
            src: src,
            adTitle: adTitle
        }
        if(keenAdTrackingClient) {
            keenAdTrackingClient.addEvent("textAds",textAd,function(err,res){
                if (err) {
                    // there was an error!

                }
                else {

                }
            });
        }
    }

    function logBannerAd(provider,location,desc) {
        var bannerAd = {
            provider: provider,
            location: location,
            desc: desc
        }
        if(keenAdTrackingClient) {
            keenAdTrackingClient.addEvent("bannerAds",bannerAd,function(err,res){
                if (err) {
                    // there was an error!

                }
                else {

                }
            });
        }
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
        'logCourseAd': logCourseAd,
        'logTextAd' : logTextAd,
        'logBannerAd' : logBannerAd,
        'logAds': logAds
    };
})();
