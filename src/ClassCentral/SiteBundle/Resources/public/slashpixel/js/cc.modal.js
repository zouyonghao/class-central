class Modal {
  constructor() {
    document.addEventListener("DOMContentLoaded", () => {
      this.el = $("[data-modal]");
      this.body = $("body");
      this.elContent = this.el.find("[data-content]");
      this.bindEvents();
      $(document).trigger("modal:ready");
    });
  }

  bindEvents() {
    this.el.on("click", (event) => {
      if (
        !this.el.hasClass("modal-close-disabled") &&
        typeof($(event.target).data("modal-blanket")) !== "undefined" ||
        typeof($(event.target).data("modal")) !== "undefined" ||
        typeof($(event.target).data("modal-close")) !== "undefined"
      ) {
        this.close();
      }
    });
  }

  disableClose() {
    this.el.addClass("modal-close-disabled");
  }

  open() {
    this.elContent.html('<p class="loading-pulse absolute cc-search-loading-pulse" style="top: 40%; left: 50%;"></p>');
    this.body.addClass("modal-active");
    this.el.addClass("active");
    if ($("#news-banner").length) {
      $("#news-banner").find(".news-banner-container").removeClass("z-top");
    }
  }

  isOpen() {
    return this.el.hasClass("active");
  }

  loading() {
    this.elContent.html('<div style="<p class="loading-pulse absolute cc-search-loading-pulse" style="top: 40%; left: 50%;"></p>');
  }

  content(content, callback) {
    this.elContent.html(content);
    if (callback) {
      setTimeout(() => {
        callback();
      }, 0);
    }
  }

  close() {
    this.body.removeClass("modal-active");
    this.el.removeClass("active");
    this.elContent.html("");
    if ($("#news-banner").length) {
      $("#news-banner").find(".news-banner-container").addClass("z-top");
    }
  }
}

export default new Modal();
