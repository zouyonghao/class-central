class Trailer {
  constructor() {
    document.addEventListener("DOMContentLoaded", () => {
      const _this = this;
      $(document).on("click", "[data-trailer]", function() {
        _this.handlePlay($(this).data("trailer"));
      })
    });
  }

  handlePlay(data) {
    if (data.type === "coursera") {
      this.playCourseraVideo(data.url);
    }
    if (data.type === "youtube") {
      this.playYouTubeVideo(data.url);
    }
  }

  playYouTubeVideo(url) {
    $(".video-button").remove();
    $(".video-container").html(`
      <iframe class="height-100 width-100 ytb-video-frame"
        src="${url}&amp;autohide=1&amp;showinfo=0&amp;autoplay=1"
        frameborder="0"
        allowfullscreen
        wmode="Opaque">
      </iframe>`
    );
  }

  playCourseraVideo(url) {
    $(".video-button")
      .find("button").removeClass("icon--center icon-play")
      .find(".loading-pulse").removeClass("hidden");
    setTimeout(() => {
      const vid = $(".video-container").html(`
        <video class="height-100 width-100" autoplay controls>
          <source type="video/mp4" src="${url}full/540p/index.mp4">
          <source type="video/webm" src="${url}full/540p/index.webm">
        </video>`
      ).find("video").get(0);

      const checkIfVideoIsStarted = () => {
        $(".video-button").remove();
        vid.removeEventListener("timeupdate", checkIfVideoIsStarted, true);
      }

      vid.addEventListener("timeupdate", checkIfVideoIsStarted, true);
    }, 0);
  }
}

export default new Trailer();
