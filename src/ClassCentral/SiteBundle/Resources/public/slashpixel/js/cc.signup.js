var CC = CC || {
        Class : {}
    }

CC.Class['Signup'] = (function(){

    var utilities = CC.Class['Utilities'];

    function init() {
        $('form[name="cc-signup-form"]').submit( signupFormSubmit);
    }

    function signupFormSubmit(e) {
        e.preventDefault();
        if (isFormValid( $(this) ,getSignupFormValues($(this))) ) {
            console.log("Form is valid");
        } else {
            console.log("Form is invalid");
        }

    }

    function isFormValid ( form, formValues ) {

        console.log( $(form).find('.cc-signup-form-error-message') );
        $(form).find('.cc-signup-form-error-message').html("");
        // Front end check
        if( utilities.isEmpty (formValues.email) || !utilities.validateEmail(formValues.email) ){
            // email is invalid
            $(form).find('.cc-signup-form-error-message').html("Invalid Email");
            return false;
        }

        if( utilities.isEmpty (formValues.name) ) {
            // name cannot be empty
            $(form).find('.cc-signup-form-error-message').html("Name is required");
            return false;
        }

        if( utilities.isEmpty (formValues.password) ) {
            // password cannot be empty
            $(form).find('.cc-signup-form-error-message').html("Password cannot be empty");
            return false;
        }

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
        init: init
    }
})();

CC.Class['Signup'].init();