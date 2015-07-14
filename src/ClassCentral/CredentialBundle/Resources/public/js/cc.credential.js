var CC = CC || {
    Class : {}
}

CC.Class['Credential'] = (function(){

    var utilities = CC.Class['Utilities'];
    var user = CC.Class["User"];

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
        var topicCoverage = $('#cr-topic-coverage').raty('score');
        var jobReadiness = $('#cr-job-readiness').raty('score');
        var support = $('#cr-support').raty('score');
        var effort = $('#cr-effort').val();
        var duration = $('#cr-duration').val();

        return {
            'rating' : rating,
            'title': title,
            'reviewText': reviewText,
            'progress' : progress,
            'certificateLink' : certificateLink,
            'topicCoverage': topicCoverage,
            'jobReadiness' : jobReadiness,
            'support' : support,
            'effort' : effort,
            'duration' : duration,
            'name' : $('#cr-name').val(),
            'email' : $('#cr-email').val(),
            'jobTitle': $('#cr-job-title').val(),
            'highestDegree' : $('#cr-highest-degree').val(),
            'fieldOfStudy' : $('#cr-field-of-study').val()
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

        // Validate email if the user is not logged in
        if( !$('#loggedin').data('value') ) {
            $('#cr-error-email').hide();
            if(!utilities.validateEmail(review.email) ) {
                validationError = true;
                $('#cr-error-email').show();
            }
        }

        return validationError;
    }

    function saveReview(event) {
        event.preventDefault();
        //$('#cr-save-review').attr('disabled', true);

        var review = getReviewFormFields();
        var validationError = validateReviewForm( review );
        if( !validationError ) {
            $.ajax({
                type : "post",
                url  : "/certificate/review/save/" + $('#credentialid').data('value'),
                data : JSON.stringify(review)
            })
                .done(
                    function(result) {
                        result = JSON.parse(result);
                        if(result['success']) {
                           // Check if user is logged in.
                           if(user.isLoggedIn()) {
                               // Redirect to Credential page
                           } else {
                               // Show a signup form
                               $('#signupModal').modal('show');
                           }
                           console.log("Saved Successfully");
                        } else {
                            // Show an error message
                        }
                    }
                );

        } else {
            $('#cr-save-review').attr('disabled', false);
        }
    }

    return {
        'init' : init
    };
})();

CC.Class['Credential'].init();