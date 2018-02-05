class Pagination {
  constructor() {
    const _this = this;

    this.container = $("#course-reviews-container");

    document.addEventListener("DOMContentLoaded", () => {
      if (window.location.search.match(/review-id/)) {
        setTimeout(function() {
          _this.scrollToReviews();
        }, 0);
      }
      $(document).on("click", "[data-paginate]", function(event) {
        event.preventDefault();
        _this.getPage($(this).data());
      });
    });
  }

  getPage(data) {
    const url = `/maestro/course-reviews/${data.courseid}-${data.start}`;
    this.container.find("#reviews-items").addClass("transparent");
    this.container.find(".pagination-block").append('<div class="bg-white width-100 padding-vert-medium absolute left text-center" style="top: -15px;"><span class="loading-pulse inline-block"></span></div>');
    $.ajax({
      method: "get",
      dataType: "json",
      url,
    }).then((response) => {
      if (history && history.pushState) {
        const pageUrl = `${window.location.protocol}//${window.location.host}${window.location.pathname}?start=${data.start}`;
        history.pushState(null, null, pageUrl);
      }
      this.updateView(response.reviewsRendered);
    })
  }

  updateView(reviewsMarkup) {
    this.container.html(reviewsMarkup);
    this.container.scrollTo
    this.scrollToReviews();
  }

  scrollToReviews() {
    $("html, body").stop().animate({
      scrollTop: $(".course-all-reviews").offset().top - 50,
    }, 500, 'swing');
  }
}

export default new Pagination();
