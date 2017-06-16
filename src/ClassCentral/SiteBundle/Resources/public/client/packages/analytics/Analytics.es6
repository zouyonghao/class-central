import _ from "lodash";
import { Client, addOns } from "packages/analytics/Keen";
import globalProps from "packages/analytics/properties/globalProps";

class Analytics {

  constructor(config) {
    this.config = config;
    this.Client = new Client(config);
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
    const methodName = _.camelCase(eventName);

    if (this[methodName]) {
      this[methodName](trackingProps);
    }

    if (this.Client) {
      this.Client.addEvent(eventName, trackingProps);
    }
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
