var CC = CC || {
        Class : {}
    }

CC.Class['Signup'] = (function(){

    var utilities = CC.Class['Utilities'];
    var profile = CC.Class['Profile'];

    function init() {
        $('form[name="cc-signup-form"]').submit( signupFormSubmit);
    }

    function signupFormSubmit(e) {
        e.preventDefault();
        var form = $(this);
        if (isFormValid( $(this) ,getSignupFormValues($(this))) ) {
            console.log("Form is valid");

            showOnboardingProfileStep();
            return;
            // Submit the form using post
            var actionurl = e.currentTarget.action;
            $.ajax({
                url: actionurl,
                type: 'post',
                dataType: 'json',
                data: $(this).serialize(),
                success: function(result) {
                  if(result.success) {
                    // Signup successful. Show onboarding
                      location.reload();
                  } else {
                    // Signup failed
                      showErrorMessage( form,result.message);
                  }
                }
            });
        } else {
            console.log("Form is invalid");
            // Error message is shown by the validate function. Do nothing
        }

    }

    function showOnboardingProfileStep()
    {
        var profileStepUrl = '/user/onboarding/profile';
        $.ajax({
            url: profileStepUrl,
            cache: false,
            success: function( result ) {
                var response = $.parseJSON(result);
                $('.cc-page').append(response.modal);
                $("#onboarding-profile-modal").modal("show");
                updateProfileProgess();
                $('#onboarding-profile-modal form :input').each( function(){
                    $(this).focusout(  updateProfileProgess );
                });
                $('#onboarding-profile-modal form :select').each( function(){
                    $(this).focusout(  updateProfileProgess );
                });
            },
            async: false
        })
    }

    function updateProfileProgess() {
        updateOnbardingFooterProgressBar( profile.profileCompletenessPercentage() )
    }

    function updateOnbardingFooterProgressBar( percentage ) {
        $('.meter__bar').width( percentage + '%');
    }

    function isFormValid ( form, formValues ) {

        hideErrorMesssage( form );
        // Front end check
        if( utilities.isEmpty (formValues.email) || !utilities.validateEmail(formValues.email) ){
            // email is invalid
            showErrorMessage(form,"Invalid Email");
            return false;
        }

        if( utilities.isEmpty (formValues.name) ) {
            // name cannot be empty
            showErrorMessage(form,"Name is required");
            return false;
        }

        if( utilities.isEmpty (formValues.password) ) {
            // password cannot be empty
            showErrorMessage(form,"Password cannot be empty");
            return false;
        }

        return true;
    }

    function showErrorMessage(form, message) {
        $(form).find('.cc-signup-form-error-message').html(message);
    }

    function hideErrorMesssage(form) {
        $(form).find('.cc-signup-form-error-message').html("");
    }
    function createAccount() {

    }

    function getSignupFormValues( form ){
        var email = $(form).find("#classcentral_sitebundle_signuptype_email"  ).val();
        var name  = $(form).find("#classcentral_sitebundle_signuptype_name"  ).val();
        var password = $(form).find("#classcentral_sitebundle_signuptype_password"  ).val();

        return {
            name : name,
            email : email,
            password: password
        }
    }

    function isEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }

    return {
        init: init,
        'profileOnboarding' : showOnboardingProfileStep
    }
})();

CC.Class['Signup'].init();