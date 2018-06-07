import { formatNumber } from "../../client/packages/utils/format";

class Ui {
  constructor() {
    this.removeParentOnClick();
    this.showTargetOnClick();
    document.addEventListener("DOMContentLoaded", () => {
      this.formatCounts();
      this.slideShow();
      this.select();
      this.radio();
      this.rating();
      this.formErrors();
      this.tabs();
      this.dropdowns();
      this.mediaImages();
      this.checkboxToggle();
      this.pulseTooltips();
      this.slideTo();
      this.expand();

      if ($(".sidebar-prmo").length) {
        this.stickyAds();
      }

      $(".bfh-number-btn.inc").addClass('btn-small btn-white icon-chevron-up icon--center');
      $(".bfh-number-btn.dec").addClass('btn-small btn-white icon-chevron-down icon--center');

      $("#news-banner a").click(function() {
        Cookies.set("news_banner", NEWS_BANNER || 1, { expires: 365 });
      });

      if (window.location.search.match(/news_banner=1/)) {
        Cookies.set("news_banner", NEWS_BANNER || 1, { expires: 365 });
      }
    });
  }

  pulseTooltips() {
    setTimeout(() => {
      $("[data-pulse-trigger]").addClass("icon--pulse-stop");
    }, 12500);

    const showTooltip = (el) => {
      const target = $(el).data("pulse-trigger");
      $(el).addClass("icon--pulse-stop active");
      $(`[data-pulse=${target}]`).removeClass("hidden");
    }
    const hideTooltip = (el) => {
      const target = $(el).data("pulse-trigger");
      $(el).removeClass("active");
      $(`[data-pulse=${target}]`).addClass("hidden");
    }

    $(document).on("click", "[data-pulse-trigger]", function(event) {
      if ($(window).width() < 1024) {
        event.preventDefault();
        if ($(this).hasClass("active")) {
          hideTooltip(this);
        } else {
          showTooltip(this);
        }
      }
    });
    $(document).on("click", "[data-pulse-target]", function(event) {
      if ($(window).width() < 1024) {
        event.preventDefault();
        hideTooltip(this);
      }
    });
    $(document).on("mouseenter", "[data-pulse-trigger]", function(event) {
      if ($(window).width() >= 1024) {
        event.preventDefault();
        showTooltip(this);
      }
    });
    $(document).on("mouseleave", "[data-pulse-trigger]", function(event) {
      if ($(window).width() >= 1024) {
        event.preventDefault();
        hideTooltip(this);
      }
    });
  }

  removeParentOnClick() {
    document.addEventListener("click", function handler(event) {
      for (let target = event.target; target && target !== this; target = target.parentNode) {
        if (target.matches("[data-remove-parent]")) {
          const el = target.parentElement;
          $(el).hide();
          if ($(target).data('cookie')) {
            Cookies.set($(target).data('cookie'), $(target).data('cookie-value') || 1);
          }
        }
      }
    }, false);
  }

  formErrors() {
    $('.form .error').on("change", function() {
      $(this).removeClass('error');
      $('.input-error').remove();
    });
  }

  expand() {
    $('[data-expand]').on("click", function(event) {
      event.preventDefault();
      const anchor = $(this);
      const target = anchor.data("expand");
      const item = $(`[data-expand-target=${target}]`);

      if (anchor.hasClass("active")) {
        anchor.removeClass("active");
        item.hide();
        anchor.find(".icon--right").removeClass("icon-chevron-down-gray").addClass("icon-chevron-right-gray");
      } else {
        anchor.addClass("active");
        item.removeClass("hidden").show();
        anchor.find(".icon--right").removeClass("icon-chevron-right-gray").addClass("icon-chevron-down-gray");
      }
    });
  }

  stickyAds() {

    const sidebarAds = $(".sidebar-prmo");
    const offset = sidebarAds.data("sticky-offset") ? parseInt(sidebarAds.data("sticky-offset"), 10) : 0;
    const sidebarAdsClone = sidebarAds.clone();
    sidebarAds.after(sidebarAdsClone.addClass("sidebar-prmo-clone fixed top hidden").css("paddingTop", `${offset}px`).removeClass("sidebar-prmo"));

    $(window).on("scroll", () => {
      this.checkAdsPosition(sidebarAds, sidebarAdsClone, offset);
    });
    $(window).on("resize", () => {
      this.checkAdsPosition(sidebarAds, sidebarAdsClone, offset);
    });

    $(window).trigger("scroll");
  }

  checkAdsPosition(sidebarAds, sidebarAdsClone, offset) {
    const topPos = ($(".tables-wrap").offset().top + $(".tables-wrap").height() - $('.sidebar-prmo').height() - 50);
    const pastBottom = $(window).scrollTop() >= topPos;
    const width = sidebarAds.width();
    const mediaLargeUp = window.innerWidth > 768;

    if (pastBottom && mediaLargeUp) {
      sidebarAdsClone
        .removeClass("fixed top")
        .addClass("absolute")
        .css("top", `${topPos}px`)
        .width(width);
    }
    else if ($(window).scrollTop() >= $(".sidebar-prmo").position().top - offset && mediaLargeUp) {
      sidebarAds.addClass("invisible");
      sidebarAdsClone
        .removeClass("hidden absolute")
        .addClass("fixed top")
        .css("top", "")
        .width(width);
    } else {
      sidebarAds.removeClass("invisible");
      sidebarAdsClone.addClass("hidden");
    }
  }

  mediaImages() {
    const switchEls = $('[data-media-switch]');
    if (switchEls.length > 0) {
      $(window).on("resize", () => {
        $('[data-media-switch]').each(function(el, index) {
          const breakpoint = parseInt($(this).data('media-switch'), 10);
          if (window.innerWidth > breakpoint) {
            $(this).attr('src', $(this).data("large"));
          } else {
            $(this).attr('src', $(this).data("small"));
          }
        });
      });
      $(window).trigger("resize");
    }
  }

  showTargetOnClick() {
    document.addEventListener("click", function handler(event) {
      for (let target = event.target; target && target !== this; target = target.parentNode) {
        if (target.matches("[data-show]")) {
          event.preventDefault();
          Array.from(document.querySelectorAll("[data-show-target]")).forEach((item) => {
            item.classList.add('hidden');
          });
          Array.from(document.querySelectorAll(`[data-show-target="${target.dataset.show}"]`)).forEach((item) => {
            item.classList.remove('hidden');
          });
          Array.from(document.querySelectorAll("[data-show]")).forEach((item) => {
            item.dataset.inactiveState.split(" ").forEach((cls) => {
              item.classList.add(cls);
            });
            item.dataset.activeState.split(" ").forEach((cls) => {
              item.classList.remove(cls);
            });
          });
          Array.from(document.querySelectorAll(`[data-show="${target.dataset.show}"]`)).forEach((item) => {
            item.dataset.inactiveState.split(" ").forEach((cls) => {
              item.classList.remove(cls);
            });
            item.dataset.activeState.split(" ").forEach((cls) => {
              item.classList.add(cls);
            });
          });
        }
      }
    }, false);
  }

  formatCounts() {
    Array.from(document.querySelectorAll("[data-format-number]")).forEach((item) => {
      item.innerHTML = formatNumber(parseInt(item.innerHTML, 10)).shortHand;
      item.classList.remove("text--white");
    });
  }

  select() {
    $('[data-select]').each(function() {
      $(this).on("change", function() {
        const val = $(this).val();
        $(`[data-select-label]`).text(val);
        $(`[data-show="${val}"]`).get(0).click();
      })
    })
  }

  radio() {
    $("[data-radio]").each(function() {
      $(this).on("change", function() {
        $(`[name=${$(this).attr('name')}]`).next('label').removeClass('selected');
        $(this).next('label').addClass('selected');
      })
    })
  }

  dropdowns() {
    $(document).on("click", "[data-dropdown-close]", function(event) {
      event.preventDefault();
      $(this).closest("[data-dropdown-menu]").hide();
    });
    $(document).on("mouseenter", "[data-dropdown]", function(event) {
      $("[data-dropdown-menu]").hide();
      $(this).addClass("active")
      $(this).find("[data-dropdown-menu]").show();
    });
    $(document).on("mouseleave", "[data-dropdown]", function(event) {
      $(this).removeClass("active");
      $(this).find("[data-dropdown-menu]").hide();
    });
  }

  tabs() {
    $("[data-tab]").on("click", function(event) {
      const tab = $(this).data("tab");

      if ($(this).data("tab-mobile-clickthrough") && window.innerWidth <= 768) {
        return;
      }

      event.preventDefault();
      const activeState = $(this).data("tab-active-state");
      const inactiveState = $(this).data("tab-inactive-state");
      const family = $(this).data("tab-family");
      const triggerChildren = family ? $("[data-tab]").filter(`[data-tab-family=${family}]`) : $("[data-tab]");
      const targetChildren = family ? $("[data-tab-target]").filter(`[data-tab-family=${family}]`).addClass("hidden") : $("[data-tab-target]");

      triggerChildren
        .data("active", false)
        .removeClass(activeState ? activeState : "bg-white")
        .addClass(inactiveState ? inactiveState : "bg-gray");
      targetChildren.addClass("hidden");

      $(this).data("active", true)
        .addClass(activeState ? activeState : "bg-white")
        .removeClass(inactiveState ? inactiveState : "bg-gray");
      $(`[data-tab-target=${tab}]`).removeClass('hidden');
    });
  }

  rating() {
    const showRating = (el) => {
      const container = el.closest('[data-rating]');
      const star = el.find('i');
      const rating = parseInt(el.data('rating-value'), 10);

      $("[data-rating-value]").find('i').removeClass("icon-star").addClass("icon-star-outline");

      for (let i = 1; i <= rating; i++) {
        $(`[data-rating-value="${i}"]`).find('i').removeClass("icon-star-outline").addClass("icon-star");
      }
      return container;
    }

    $("[data-rating]").on("mouseenter", "[data-rating-value]", function() {
      showRating($(this));
    });
    $("[data-rating]").on("mouseleave", function() {
      const container = $(this).closest("[data-rating]");
      const index = parseInt(container.find("input").val(), 10) || -1;

      if (index > 0) {
        const el = container.find("[data-rating-value]").eq(index-1);
        showRating(el);
      } else {
        container.find(".icon-star").removeClass('icon-star').addClass('icon-star-outline');
      }
    });

    $("[data-rating]").on("click", "[data-rating-value]", function(event) {
      event.preventDefault();
      $(this).addClass("selected");
      setTimeout(() => {
        $(this).removeClass("selected");
      }, 300);
      const container = $(this).closest("[data-rating]");
      container.find('input').val(container.find('.icon-star').length);
    })
  }

  checkboxToggle() {
    $(document).on("click", "[data-checkbox=toggle]", function(event) {
      const target = $(event.currentTarget);
      const checkbox = $(event.currentTarget).find("input[type=checkbox]");

      target.find(".checkbox--label").html(checkbox.is(":checked") ? "On" : "Off");
      target.find("[data-checkbox-label]").html(checkbox.is(":checked") ? "My Class Central profile is public" : "My Class Central profile is private");
    });
  }

  slideShow() {
    const slideshowIntervals = [];

    const startInterval = (item) => {
      return setInterval(() => {
        const $slideshow = $(item);
        const $next = $slideshow.find(".next");
        const $active = $slideshow.find(".active");
        const index = $next.data("slideshow-item").index;
        setActiveSlide($slideshow, index);
      }, 7500);
    }

    const setActiveSlide = ($slideshow, index) => {
      const $active = $slideshow.find(".active");
      const $items = $slideshow.find("[data-slideshow-item]");
      const $navItems = $slideshow.find("nav button");
      const $next = $items.eq(index);
      const bgData = $next.data("slideshow-item").bg;

      let $upcoming;

      $slideshow.removeClass("bg-charcoal-dark bg-white cc-gradient-2017 cc-gradient-gold cc-gradient-purple-blue cc-gradient-orange-green")
      $slideshow.removeAttr("style");
      $navItems.addClass("transparent").eq(index).removeClass("transparent");

      if (bgData.src) {
        $slideshow.css({
          backgroundSize: "contain",
          backgroundImage: `url(${bgData.src})`,
          backgroundRepeat: "repeat-x",
        });
      } else {
        const bgColor = $next.data("slideshow-item").bg;
        $slideshow.addClass(bgColor);
      }

      $active.removeClass('active');
      $next.addClass('active').removeClass('next');

      if ($next.next().length) {
        $upcoming = $next.next();
      } else {
        $upcoming = $slideshow.find('li:first');
      }
      $upcoming.addClass('next');
    }

    Array.from(document.querySelectorAll("[data-slideshow]")).forEach((item) => {
      let slideshowInterval = startInterval(item);

      Array.from(item.querySelectorAll("[data-slideshow-target]")).forEach((navItem) => {
        navItem.addEventListener("mousedown", function handler(event) {
          clearInterval(slideshowInterval);
          setActiveSlide($(item), event.target.dataset.slideshowTarget);
          slideshowInterval = startInterval(item);
        }, false);
      });
    });
  }

  slideTo() {
    $(document).on("click", "[data-slideto]", function() {
      const target = $(this).data("slideto");
      $.scrollTo(`#${target}`,{ duration: 1000 });
    });
  }
}

export default new Ui();
