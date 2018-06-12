import tippy from 'tippy.js'

class Overlay {
  constructor() {
    const _this = this;
    document.addEventListener("DOMContentLoaded", () => {
      const tip = tippy('[data-overlay-trigger]', {
        html: "#cc-overlay",
        interactive: true,
        theme: "bg-white",
        animateFill: false,
        placement: "right",
        zIndex: 2147483647,
        onShow: _this.onShow,
        wait: _this.onWait,
      });
    });
  }
  onWait(show) {
    if (window.innerWidth > 915) {
      show();
    }
  }
  onShow(instance) {
    try {
      const data = JSON.parse(instance.reference.dataset.overlayTrigger);
      const content = this.querySelector('.tippy-content')
      $.ajax({
        url: `/maestro/overlay/${data.type}/${data.id}`,
        method: "get",
        dataType: "json",
        success: function(data) {
          content.innerHTML = data.html;
          $('[data-button-follow]').each(function() {
            new window.CC.Class.FollowBtn($(this));
          });

          var trackEl = $(".tippy-content").find("[data-track-event]");
          CC.track(trackEl.data("track-event"), trackEl.data("track-props"));
        },
        error: function() {
        }
      })
    } catch (e) {};
  }
}

export default new Overlay();
