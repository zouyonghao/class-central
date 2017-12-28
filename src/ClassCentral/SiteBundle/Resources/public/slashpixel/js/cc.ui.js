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

      $(".bfh-number-btn.inc").addClass('btn-small btn-white icon-chevron-up icon--center');
      $(".bfh-number-btn.dec").addClass('btn-small btn-white icon-chevron-down icon--center');

      $("#news-banner a").click(function() {
        Cookies.set("news_banner", 1);
      });

      if (window.location.search.match(/news_banner=1/)) {
        Cookies.set("news_banner", 1);
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
            Cookies.set($(target).data('cookie'), 1);
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
      const family = $(this).data("tab-family");
      $("[data-tab-target]").filter(`[data-tab-family=${family}]`).addClass("hidden");
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
      const bgColor = $next.data("slideshow-item").bg;
      let $upcoming;

      $slideshow.removeClass("bg-cc-gradient cc-gradient-gold cc-gradient-green-blue cc-gradient-yellow-orange cc-gradient-purple-blue cc-gradient-orange-green")
      $navItems.addClass("transparent").eq(index).removeClass("transparent");
      $slideshow.addClass(bgColor);
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
}

export default new Ui();
