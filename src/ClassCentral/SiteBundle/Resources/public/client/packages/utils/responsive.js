const mediaSizes = {
  "xsmallOnly": (screenSize) => (screenSize <= 480),
  "smallUp": (screenSize) => (screenSize >= 481),
  "smallOnly": (screenSize) => (screenSize > 480 && screenSize <= 640),
  "mediumUp": (screenSize) => (screenSize > 640),
  "mediumOnly": (screenSize) => (screenSize > 640 && screenSize <= 768),
  "largeUp": (screenSize) => (screenSize > 768),
  "largeOnly": (screenSize) => (screenSize > 768 && screenSize <= 1024),
  "xlargeUp": (screenSize) => (screenSize > 1024),
  "xlargeOnly": (screenSize) => (screenSize > 1024 && screenSize <= 1200),
  "xxlargeUp": (screenSize) => (screenSize > 1200),
  "xxlargeOnly": (screenSize) => (screenSize > 1200),
};

class Responsive {
  constructor() {
    document.addEventListener("DOMContentLoaded", () => {
      this.watchResize();
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
