import { camelCase, throttle } from "lodash";
import { Client, addOns } from "packages/analytics/Keen";
import globalProps from "packages/analytics/properties/globalProps";
import { isInView } from "packages/utils/index";

class Analytics {

  constructor(config) {
    this.config = config;
    this.Client = new Client(config);
    this.ads = [];

    if (config.trackAdImpressions) {
      this.trackAdImpressions();
    }
  }

  getTrackingProps(eventTrackingProps) {
    return {
      ...globalProps(),
      ...this.config.globalProps || null,
      ...eventTrackingProps || null,
      keen: { addons: addOns() },
    };
  }

  track(eventName, eventTrackingProps) {
    const trackingProps = this.getTrackingProps(eventTrackingProps);
    const methodName = camelCase(eventName);

    if (this[methodName]) {
      this[methodName](trackingProps);
    }

    if (this.Client) {
      this.Client.addEvent(eventName, trackingProps);
    }
  }

  trackAdImpressions() {
    document.addEventListener("DOMContentLoaded", () => {
      this.getPageAds();
    });

    const fireImpression = throttle(() => {
      this.ads.forEach((ad, index) => {
        if (isInView(ad) && this.ads[index].hasAttribute("data-track")) {
          try {
            this.track(ad.dataset.track, JSON.parse(ad.dataset.trackProps));
          } catch (e) {
            this.track("AD_PROP_ERROR", ad.dataset.track);
          }
          this.ads[index].removeAttribute("data-track");
        }
      });
    }, 200);

    window.addEventListener("scroll", fireImpression);
    window.addEventListener("resize", fireImpression);
  }

  getPageAds() {
    const nodes = document.querySelectorAll("[data-track]");
    this.ads = [].slice.call(nodes, 0);
    window.dispatchEvent(new Event("scroll"));

    return this.ads;
  }

  adClick(trackingProps) {
    window.ga("send", "event",
      "Ad Clicks - By Provider",
      trackingProps.ad.provider,
      trackingProps.ad.title.concat(" | ", trackingProps.ad.unit),
    );
    window.ga("send", "event",
      "Ad Clicks - By Ad Unit",
      trackingProps.ad.unit,
      trackingProps.ad.title,
    );
  }
}

export default Analytics;
