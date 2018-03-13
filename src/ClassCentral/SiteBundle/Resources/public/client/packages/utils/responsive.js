import breakpoints from "../settings/breakpoints"

const mediaSizes = {
  "xsmallOnly": (screenSize) => (screenSize <= breakpoints.small),
  "smallUp": (screenSize) => (screenSize >= (breakpoints.small + 1)),
  "smallOnly": (screenSize) => (screenSize > breakpoints.small && screenSize <= breakpoints.medium),
  "mediumUp": (screenSize) => (screenSize > breakpoints.medium),
  "mediumOnly": (screenSize) => (screenSize > breakpoints.medium && screenSize <= breakpoints.large),
  "largeUp": (screenSize) => (screenSize > breakpoints.large),
  "largeOnly": (screenSize) => (screenSize > breakpoints.large && screenSize <= breakpoints.xlarge),
  "xlargeUp": (screenSize) => (screenSize > breakpoints.xlarge),
  "xlargeOnly": (screenSize) => (screenSize > breakpoints.xlarge && screenSize <= breakpoints.xxlarge),
  "xxlargeUp": (screenSize) => (screenSize > breakpoints.xxlarge),
  "xxlargeOnly": (screenSize) => (screenSize > breakpoints.xxlarge),
};

class Responsive {
  constructor() {
    document.addEventListener("DOMContentLoaded", () => {
      this.watchResize();
      // trigger the initial resize
      $(window).trigger("resize");
    });
  }

  watchResize() {
    let lastMediaSizes = { matching: [], current: null };
    $(window).on("resize", () => {
      try {
        const mediaSizes = this.getMediaSize();
        if (mediaSizes.current !== lastMediaSizes.current) {
          $(document).trigger("media:change", [mediaSizes.current]);
          $("[data-responsive]").each(function() {
            const classes = $(this).data("responsive");
            let replaceClasses = "";
            let addClasses = "";
            
            lastMediaSizes.matching.forEach((size) => {
              replaceClasses += ` ${classes[size] || ""}`;
            })
            mediaSizes.matching.forEach((size) => {
              addClasses += ` ${classes[size] || ""}`;
            })

            $(this).removeClass(replaceClasses).addClass(addClasses);
          });
        }
        lastMediaSizes = mediaSizes;
      } catch (e) {}
    });
  }

  getMediaSize() {
    if (typeof window !== "undefined") {
      let matchingSizes = []
      Object.keys(mediaSizes).forEach((key) => {
        if (mediaSizes[key](window.innerWidth)) {
          matchingSizes.push(key);
        }
      });
      return {
        matching: matchingSizes,
        current: matchingSizes.filter((size) => (size.match(/Only/)))[0].replace("Only", ""),
      };
    }
    return false;
  };
}

export default new Responsive();
