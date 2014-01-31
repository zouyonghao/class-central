jQuery(function($) {

    $.ajaxSetup ({
        cache: false
    });
    // Handle calls to add/remove courses to users library
    $('input[class="course-list-checkbox"]').change(function(){
        var clicked = this;
        // Check if the user is logged in
        $.ajax({
            url: "/ajax/isLoggedIn",
            cache: true
        })
        .done(function(result){
            var loggedInResult = $.parseJSON(result);
            if(loggedInResult.loggedIn) {
                var name = $(clicked).attr("name");
                if($(clicked).is(':checked')) {
                    $('span[id=' + name + ']').html("-");
                    // uncheck the rest
                    $('input[name=' + name +']:checked').each(function(){
                        $(this).attr('checked',false);
                    });
                    // check this one back
                    $(clicked).attr('checked',true);
                } else {
                    $('span[id=' + name + ']').html("+");
                }

                addRemoveCourse($(clicked).val(), $(clicked).data('course-id'),$(clicked).is(':checked'));
            } else {
                // redirect to signup page
                window.location.replace("/signup/cc/" +$(clicked).data('course-id')+ "/"+ $(clicked).val());
            }
        });
    });

    function addRemoveCourse(listId, courseId, checked) {
        _gaq.push(['_trackEvent','My Courses - Add', listId.toString(),  courseId.toString() ]);
        try{
         if(checked){
             _gaq.push(['_trackEvent','My Courses - Add',listId.toString(), courseId.toString()]);
         }else {
             _gaq.push(['_trackEvent','My Courses - Remove', listId.toString(),  courseId.toString() ]);
         }
        }catch(err){}
        if(checked){
            $.ajax( "/ajax/user/course/add?c_id=" +courseId +"&l_id="+ listId)
                .done(
                function(result){
                    //console.log("jquery" + result);
                }
            );
        } else {
            $.ajax( "/ajax/user/course/remove?c_id=" +courseId +"&l_id="+ listId)
                .done(
                function(result){
                    //console.log("jquery" + result);
                }
            );
        }
    }

    // Select dropdown on course pages
    $('#sessionOptions').change(function() {
        var selected = $(this).find('option:selected');
        var url = selected.data("url");
        var sessionType = selected.data('sessiontype');
        var sessionStatus = selected.data('sessionstatus');
        var sessionStartDate = selected.data('sessionstartdate');

        // Update the user of the href tag
        $('#btnProviderCoursePage').attr("href",url);

        // Update the add to calendar button
        $('._start').html(sessionStartDate);
        $('._end').html(sessionStartDate);
        if(sessionType=='upcoming' && sessionStatus == '1') {
            $('.btnAddToCalendar').show();
        }
        else {
            $('.btnAddToCalendar').hide();
        }

    });


    // relevant to course information page and course tables
    // stop dropdown from closing when its inside elements are clicked on
    $('.course-button-group .dropdown-menu').bind('click', function (e) {
       //e.stopPropagation();
    });

    $('.table .dropdown-menu').bind('click', function (e) {
        //e.stopPropagation();
    });

    /**
     * User preferences - Newsletter
     */
    $('input[class="user-newsletter-checkbox"]').change(function(){
        var clicked = this;
        // Check if the user is logged in
        $.ajax({
            url: "/ajax/isLoggedIn",
            cache: true
        })
            .done(function(result){
                var loggedInResult = $.parseJSON(result);
                if(loggedInResult.loggedIn) {
                    updateSubscription($(clicked).val(), $(clicked).is(':checked'));
                } else {
                    // redirect to loginpage page
                    window.location.replace("/login");
                }
            });
    });

    var updateSubscription = function(code, checked) {
        try{
            if(checked){
                _gaq.push(['_trackEvent','Newsletter Preferences','Subscribed', code]);
            }else {
                _gaq.push(['_trackEvent','Newsletter Preferences','Unsubscribed', code]);
            }
        }catch(err){}

        if(checked){
            $.ajax( "/ajax/newsletter/subscribe/"+code)
                .done(
                function(result){
                    //console.log("jquery" + result);
                }
            );
        } else {
            $.ajax("/ajax/newsletter/unsubscribe/"+code)
                .done(
                function(result){
                    //console.log("jquery" + result);
                }
            );
        }
    }

    /**
     * User Preferences - MOOC Tracker preferences
     */
    $('input[class="mooc-tracker-checkbox"]').change(function(){
        var clicked = this;
        // Check if the user is logged in
        $.ajax({
            url: "/ajax/isLoggedIn",
            cache: true
        })
            .done(function(result){
                var loggedInResult = $.parseJSON(result);
                if(loggedInResult.loggedIn) {
                    updateUserPreference($(clicked).val(), $(clicked).is(':checked'));
                } else {
                    // redirect to loginpage page
                    window.location.replace("/login");
                }
            });
    });

    var updateUserPreference = function(prefId, checked) {
        try{
            if(checked){
                _gaq.push(['_trackEvent','MOOC Tracker Preferences','Checked', prefId]);
            }else {
                _gaq.push(['_trackEvent','MOOC Tracker Preferences','UnChecked', prefId]);
            }
        }catch(err){}
        if(checked){
            $.ajax( "/ajax/user/pref/"+ prefId + "/1")
                .done(
                function(result){
                   // console.log("jquery" + result);
                }
            );
        } else {
            $.ajax("/ajax/user/pref/"+ prefId + "/0")
                .done(
                function(result){
                    //console.log("jquery" + result);
                }
            );
        }
    }

    /**
     * Reviews and ratings
     */
    var isEmpty = function(str) {
        return (!str || 0 === str.length);
    }

    $('#review-text').autosize();

    var ratyDefaults = {
        starHalf    : '/bundles/classcentralsite/slashpixel/images/star-half.png',
        starOff     : '/bundles/classcentralsite/slashpixel/images/star-off.png',
        starOn      : '/bundles/classcentralsite/slashpixel/images/star-on.png',
        hints       : ['','','','',''],
        size        : 21,
        score       : function() {
            return $(this).attr('data-score');
        }
    };

    $('#rating').raty(ratyDefaults);

    $('.course-rating').raty($.extend(
        {
            readOnly: true
        },
        ratyDefaults
    ));

    $('.user-rating').raty($.extend(
        {
            readOnly: true
        },
        ratyDefaults
    ));

    /**
     * Review course
     */
    $('#review-form').submit(function(event){
        event.preventDefault();
        $('#review-form').attr('disabled',true);

        // Get all the fields
        var rating = $('#rating').raty('score');
        var reviewText = $('textarea[name=review-text]').val();
        var effort = $('input:text[name=effort]').val();
        var progress = $('input:radio[name=progress]:checked').val();
        var difficulty = $('input:radio[name=difficulty]:checked').val();
        var level = $('input:radio[name=level]:checked').val();
        var offeringId = $('#sessionOptions').val();
        var status = $('#reviewStatus').val();
        var reviewId = $('#reviewid').data("value");

        // Validate the form
        var validationError = false;

        // Rating cannot be empty
        if(rating === undefined) {
            $('#rating-error').show();
            validationError = true;
        } else {
            $('#rating-error').hide();
        }

        // progress cannot be empty
        if(progress === undefined) {
            $('#progress-error').show();
            validationError = true;
        } else {
            $('#progress-error').hide();
        }

        // Review if exits should be atleast 20 words long
        if(!isEmpty(reviewText)) {
            // Non empty review. Should be 20 words long
            var words = reviewText.split(' ');
            if(words.length < 20) {
                $('#review-text-error').show();
                validationError = true;
            } else {
                $('#review-text-error').hide();
            }
        } else {
            $('#review-text-error').hide();
        }

       if(!validationError) {
           var review = {
               'rating': rating,
               'reviewText': reviewText,
               'effort': effort,
               'progress': progress,
               'difficulty': difficulty,
               'level':level,
               'offeringId':offeringId,
               'status':status,
               'reviewId':reviewId
           };

           $.ajax({
               type:"post",
               url:"/user/review/create/" + $('#courseId').data("value"),
               data:JSON.stringify(review)
           })
               .done(
               function(result){
                   result = JSON.parse(result);
                   if(result['success']) {
                       // Redirect to the course page
                       window.location.href = $('#courseUrl').data("value");
                   } else {
                       // Show an error message
                   }
               }
           );

       } else {
           $('#review-form').attr('disabled',false);
       }

    });


    // Review feedback
    $('.review-feedback').bind('click',function(e){
        e.preventDefault();

        var helpful = $(this).text();
        var reviewId = $(this).data('reviewid');
        var feedback = (helpful === 'NO') ? 0 : 1;

        // Check if the user is logged in
        $.ajax({
            url: "/ajax/isLoggedIn",
            cache: true
        })
        .done(function(result){
            var loggedInResult = $.parseJSON(result);
            if(loggedInResult.loggedIn) {
                $.ajax("/ajax/review/feedback/"+ reviewId+"/"+feedback)
                    .done(function(result){
                        $('#review-feedback-'+reviewId).text("Thank you for your feedback.");
                    });
            } else {
                // redirect to login page
                window.location.replace("/login");
            }
        });

    });

    // Default notification false
    $.pnotify.defaults.history = false;

    // Pines notification
    $('.flash-message').each(function(index,element){

         $.pnotify({
            title: $(element).data('title'),
            text: $(element).text(),
            type: $(element).data('type')
	});
});

});
