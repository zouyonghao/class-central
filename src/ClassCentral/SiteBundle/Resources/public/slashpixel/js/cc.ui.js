import { formatNumber } from "../../client/packages/utils/format";

class Ui {
  constructor() {
    this.removeParentOnClick();
    this.showTargetOnClick();
    document.addEventListener("DOMContentLoaded", () => {
      this.formatCounts();
      this.slideShow();
      this.select();
      this.formErrors();
    });
  }

  removeParentOnClick() {
    document.addEventListener("click", function handler(event) {
      for (let target = event.target; target && target !== this; target = target.parentNode) {
        if (target.matches("[data-remove-parent]")) {
          const el = target.parentElement;
          el.parentElement.removeChild(el);
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

  showTargetOnClick() {
    document.addEventListener("click", function handler(event) {
      for (let target = event.target; target && target !== this; target = target.parentNode) {
        if (target.matches("[data-show]")) {
          event.preventDefault();
          document.querySelectorAll("[data-show-target]").forEach((item) => {
            item.classList.add('hidden');
          });
          document.querySelectorAll(`[data-show-target="${target.dataset.show}"]`).forEach((item) => {
            item.classList.remove('hidden');
          });
          document.querySelectorAll("[data-show]").forEach((item) => {
            item.dataset.inactiveState.split(" ").forEach((cls) => {
              item.classList.add(cls);
            });
            item.dataset.activeState.split(" ").forEach((cls) => {
              item.classList.remove(cls);
            });
          });
          document.querySelectorAll(`[data-show="${target.dataset.show}"]`).forEach((item) => {
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
    document.querySelectorAll("[data-format-number]").forEach((item) => {
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

      $slideshow.removeClass("cc-gradient-yellow-orange cc-gradient-purple-blue cc-gradient-orange-green")
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

    document.querySelectorAll("[data-slideshow]").forEach((item) => {
      let slideshowInterval = startInterval(item);

      item.querySelectorAll("[data-slideshow-target]").forEach((navItem) => {
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
