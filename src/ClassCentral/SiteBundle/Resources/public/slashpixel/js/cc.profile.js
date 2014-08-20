var CC = CC || {
    Class : {}
}

CC.Class['Profile'] = (function(){

    var postUrl = '/user/profile/save';
    var button = null;
    var utilities = CC.Class['Utilities'];

    function getFormFields() {
        var aboutMe =  $('textarea[name=about-me]').val();
        var name    =  $('input:text[name=full-name]').val();
        var location = $('input:text[name=location]').val();
        var fieldOfStudy = $('input:text[name=field-of-study]').val();
        var highestDegree = $('select[name=highest-degree]').val();

        // Social
        var twitter = $('input:text[name=profile-twitter]').val();
        var coursera = $('input:text[name=profile-coursera]').val();
        var linkedin= $('input:text[name=profile-linkedin]').val();
        var website = $('input:text[name=profile-website]').val();
        var facebook = $('input:text[name=profile-facebook]').val();
        var gplus = $('input:text[name=profile-gplus]').val();

        return {
            aboutMe: aboutMe,
            name: name,
            location: location,
            fieldOfStudy:fieldOfStudy,
            highestDegree:highestDegree,
            twitter: twitter,
            coursera:coursera,
            linkedin: linkedin,
            website: website,
            gplus: gplus,
            facebook: facebook
        };
    }

    /**
     *
     * @param button id of the save profile button
     */
    function init( btn_id ) {
        // Attach event handler
        button = $(btn_id);
        button.click( handler );
    }

    function validate( profile ){
        var validationError = false;
        // Name cannot be empty and should be
        // atleast 3 letters long
        if(utilities.isEmpty(profile.name) && profile.name.length < 3 ) {
            validationError = true;
            $('#full-name-error').show();
        } else {
            $('#full-name-error').hide();
        }
        return validationError;
    }

    function handler(event) {
        event.preventDefault();
        // Disable the save profile button
        button.attr('disabled',true);
        var profile = getFormFields();
        var validationError = validate(profile);

        if(!validationError) {
            // Ajax post to save the profile
            save(profile);
        } else {
            utilities.notify(
                "Profile Validation Error",
                "Please make sure to enter only valid values in the form",
                "error"
            );
        }

    }

    /**
     * Function to save the validated profile
     * @param profile
     */
    function save(profile) {
        $.ajax({
            type:"post",
            url: postUrl,
            data: JSON.stringify(profile)
        })
            .done(
                function(result) {
                    result = JSON.parse(result);
                    if( result['success'] ){
                        // Refresh the page
                        location.reload(true);
                    } else {
                        // Show an error message
                        utilities.notifyWithDelay(
                            'Error saving profile',
                            'Some error occurred, please try again later',
                            'error',
                            60
                        );
                    }
                }
            );
    }


    return {
        init: init
    };
})();

CC.Class['Profile'].init('#save-profile');