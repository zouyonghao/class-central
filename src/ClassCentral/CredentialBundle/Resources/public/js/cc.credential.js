var CC = CC || {
    Class : {}
}

CC.Class['Credential'] = (function(){

    var utilities = CC.Class['Utilities'];

    function init() {
       ;
        // Attach handle to save button
        $('#cr-save-review').click(saveReview);
    }

    // =====================================================
    //      Credential - Create Review
    // ======================================================

    function getReviewFormFields() {
        var rating = $('#cr-rating').raty('score');
        var title = $('#cr-title').val();
        var reviewText = $('#cr-review-text').val();
        var progress = $('#cr-progress').val();
        var certificateLink = $('#cr-certificate-link').val();

        return {
            'rating' : rating,
            'title': title,
            'reviewText': reviewText,
            'progress' : progress,
            'certificateLink' : certificateLink
        };
    }

    function validateReviewForm( review ) {

        var validationError = false;

        // Rating cannot be empty
        if(review.rating === undefined) {
            $('#cr-error-rating').show();
            validationError = true;
        } else {
            $('#cr-error-rating').hide();
        }

        // progress cannot be empty
        if(review.progress === undefined || utilities.isEmpty(review.progress)) {
            $('#cr-error-progress').show();
            validationError = true;
        } else {
            $('#cr-error-progress').hide();
        }

        if(!utilities.isEmpty(review.reviewText)) {
            // Non empty review. Should be 20 words long
            var words = review.reviewText.split(' ');
            if(words.length < 20) {
                $('#cr-error-review-text').show();
                validationError = true;
            } else {
                $('#cr-error-review-text').hide();
            }
        } else {
            $('#cr-error-review-text').hide();
        }

        if(!utilities.isEmpty(review.reviewText)) {
            if(utilities.isEmpty(review.title)) {
                $('#cr-error-title').show();
                 validationError = true;
            } else {
                $('#cr-error-title').hide();
            }
        }


        return validationError;
    }

    function saveReview(event) {
        event.preventDefault();
        $('#cr-save-review').attr('disabled', true);

        var review = getReviewFormFields();
        var validationError = validateReviewForm( review );
        if( !validationError ) {

        } else {
            $('#cr-save-review').attr('disabled', false);
        }
    }

    return {
        'init' : init
    };
})();

CC.Class['Credential'].init();