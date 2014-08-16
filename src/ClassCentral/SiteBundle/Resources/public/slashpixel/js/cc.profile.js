var CC = {
    Class : {}
}

CC.Class['Profile'] = (function(){

    var postUrl = '/user/profile/save';
    var button = null;

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

    }

    function handler() {
        // Disable the save profile button
        button.attr('disabled',true);
        var profile = getFormFields();
        console.log(profile);
        console.log( JSON.stringify(profile) );
        var validationError = validate(profile);

        if(!validationError) {
            // Ajax post to save the profile
            save(profile);
        } else {

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
                        // Show a success result
                    } else {
                        // Show an error message
                    }
                }
            );
    }


    return {
        init: init
    };
})();

CC.Class['Profile'].init('#save-profile');
console.log('Profile');