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

});
