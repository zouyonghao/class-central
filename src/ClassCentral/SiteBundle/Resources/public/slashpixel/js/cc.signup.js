const Utilities = require("./cc.utilities.js").default;
const Profile = require("./cc.profile.js").default;

const Signup = (function(){
    var promptShownCookie = 'signup_prompt'; // is set when a user is shown signup form for the first time

    function init() {
      $(document).ready(function () {
        $('form[name="classcentral_sitebundle_signuptype"]').submit( signupFormSubmit);
      });
    }

    function signupFormSubmit(event) {
      event.preventDefault();
      const $form = $(this);
      const $submitButton = $("button[data-save]");

      $submitButton.attr('disabled',true).html("Creating account...");
      if (isFormValid($form)) {
        // Submit the form using post
        $.ajax({
          url: event.currentTarget.action,
          type: 'post',
          dataType: 'json',
          data: $form.serialize(),
          success: function(result) {
            if (result.success) {
              const userSignedUpSrc = $form.find("[data-signup-modal-src]").text();
              ga('send','event','New User Created', userSignedUpSrc);
              // Signup successful.
              CC.Class.Modal.loading();
              CC.Class.Modal.disableClose();
              Cookies.set("follow_personalized_page_prompt", 1, { expires :30 } );
              if (window.localStorage && window.localStorage.getItem("nextcourse")) {
                CC.Class.Onboarding.showStep("loggedIn", "courses");
              } else {
                CC.Class.Onboarding.showStep("loggedIn", "subjects");
              }
            } else {
              // Signup failed
              showErrorMessage($form, result.message);
              $submitButton.attr("disabled", false).html("Sign Up");
            }
          }
        });
      }
    }

    function showSignupModal(src) {
      if (!CC.Class.Modal.isOpen()) {
        CC.Class.Modal.open();
      }
      Cookies.set(promptShownCookie, 1, { expires: 30 });
      $.ajax({
        url: "/ajax/isLoggedIn",
        cache: true,
        dataType: "json",
      })
      .done(function(result) {
        if ( !result.loggedIn) {
          requestSignupModal(src);
        }
      });
    }

    function requestSignupModal(src, params) {
      if (!CC.Class.Modal.isOpen()) {
        CC.Class.Modal.open();
      }
      $.ajax({
        url: '/ajax/signup/' + src,
        type: "POST",
        data: params || {},
        dataType: "json",
        cache: false,
        success: function(response) {
          CC.Class.Modal.content(response.modal, () => {
            CC.Class.Ui.slideShow();
          });
          $('form[name="classcentral_sitebundle_signuptype"]').submit(signupFormSubmit);
        }
      })
    }

    function showSignupPrompt(delay) {
      const self = this;
      if (!isMobile.phone && Cookies.get(promptShownCookie) === undefined) {
        $.ajax({
          url: "/ajax/isLoggedIn",
          cache: true,
          dataType: "json",
        }).done(function(result){
          if (!result.loggedIn) {
            // Show the signup form
            setTimeout(function() {
              // Check the cookie again
              if (Cookies.get(promptShownCookie) === undefined ) {
                self.showSignupModal("ask_for_signup");
                Utilities.hideWidgets();
              }
            }, delay);
          }
        });
      }
    }

    function showErrorMessage(form, message) {
      $(form).find('[data-signup-errors]').removeClass("hidden").html(message);
    }

    function hideErrorMesssage(form) {
      $(form).find('[data-signup-errors]').addClass("hidden").html("");
    }

    function isFormValid (form) {
      const formValues = {
        email: $(form).find("#classcentral_sitebundle_signuptype_email"  ).val(),
        name : $(form).find("#classcentral_sitebundle_signuptype_name"  ).val(),
        password: $(form).find("#classcentral_sitebundle_signuptype_password"  ).val(),
      };

      hideErrorMesssage(form);

      if (Utilities.isEmpty (formValues.email) || !Utilities.validateEmail(formValues.email) ){
        // email is invalid
        showErrorMessage(form,"Invalid Email");
        return false;
      }

      if (Utilities.isEmpty (formValues.name) ) {
        // name cannot be empty
        showErrorMessage(form,"Name is required");
        return false;
      }

      if (Utilities.isEmpty (formValues.password) ) {
        // password cannot be empty
        showErrorMessage(form,"Password cannot be empty");
        return false;
      }
      return true;
    }

    init();

    return {
      init: init,
      "showSignupPrompt" : showSignupPrompt,
      "showSignupModal" : showSignupModal,
      "requestSignupModal" : requestSignupModal,
    }
})();

export default Signup;
