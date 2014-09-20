var CC = CC || {
    Class : {}
}

CC.Class['Profile'] = (function(){

    var postUrl = '/user/profile/save';
    var button = null;
    var utilities = CC.Class['Utilities'];
    var user = CC.Class['User'];
    var cords = {
        x: 0,
        y: 0,
        w: 100,
        h: 100
    }
    var cropProfilePicSettings = {
        imgDiv: "profile-pic-crop",
        modal: '#crop-photo-modal',
        spinner:'searching_spinner_center',
        spinnerWrapper:'#spinner-wrapper'

    }

    var jcropApi = null;


    function readURL(input) {
        var $prev = $(input).parent().find('img');

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $prev.attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);

        } else {
            $prev.attr('src', '/bundles/classcentralsite/slashpixel/images/profile-pic-placeholder.png');
        }
    }

    $('#edit-profile-photo').on('change',function(){
        readURL(this);
    });



    // Function to add a class for styling purposes to select list
    function colorizeSelect(){
        if($(this).val() == "") $(this).addClass("empty");
        else $(this).removeClass("empty")
    }

    $(".js-colorize-select")
        .on('change keyup', colorizeSelect)
        .change();




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
    function init( btn_id, profile_image_upload_btn_id, btn_crop ) {
        // Attach event handler
        button = $(btn_id);
        button.click( handler );

        $(profile_image_upload_btn_id).fileupload({
            maxFileSize: 1000000
        });

        // Bind fileupload plugin callbacks
        $(profile_image_upload_btn_id)
            .bind('fileuploadstart', function(){
                // Check if the user is logged in
                user.isLoggedIn(true); // Redirects the user to login if not logged in

                showSpinner(); // Show loading
                $('#crop-photo-modal .modal-title').text("Crop Photo");
                $(cropProfilePicSettings.modal).modal('show');

            })
            .bind('fileuploaddone', postStep1)
            .bind('fileuploadfail', function (e, data) {
                // File upload failed. Show an error message
                utilities.notify(
                    "Error",
                    "Error uploading file. Max file size is 1mb",
                    "error"
                );
            });

        // Crop button
        $(btn_crop).click(cropButtonHandler);

        $(cropProfilePicSettings.modal).on('hidden.bs.modal', clearImage);
    }

    function showCoords(c) {
        // variables can be accessed here as
        // c.x, c.y, c.x2, c.y2, c.w, c.h
        cords.x = c.x;
        cords.y = c.y;
        cords.w = c.w;
        cords.h = c.h;

    };

    function showSpinner() {
        var opts = {
            lines: 13, // The number of lines to draw
            length: 20, // The length of each line
            width: 10, // The line thickness
            radius: 30, // The radius of the inner circle
            corners: 1, // Corner roundness (0..1)
            rotate: 0, // The rotation offset
            direction: 1, // 1: clockwise, -1: counterclockwise
            color: '#000', // #rgb or #rrggbb or array of colors
            speed: 1, // Rounds per second
            trail: 60, // Afterglow percentage
            shadow: false, // Whether to render a shadow
            hwaccel: false, // Whether to use hardware acceleration
            className: 'spinner', // The CSS class to assign to the spinner
            zIndex: 2e9, // The z-index (defaults to 2000000000)
            top: 'auto', // Top position relative to parent in px
            left:'auto' // Left position relative to parent in px
        };
        var target = document.getElementById(cropProfilePicSettings.spinner);
        var spinner = new Spinner(opts).spin(target);
        $(target).data('spinner', spinner);
        $(cropProfilePicSettings.spinnerWrapper).show();
    }

    function hideSpinner() {
        $('#'+ cropProfilePicSettings.spinner).data('spinner').stop();
        $(cropProfilePicSettings.spinnerWrapper).hide();
    }


    /**
     * This function is called after the step 1 of profile image
     * upload is executed on the backend
     */
    function postStep1(e,data ){
        var result = JSON.parse(data.result);

        if(!result.success){
            utilities.notify(
                "Profile photo upload error",
                result.message,
                "error"
            )
        } else {
            // Image uploaded. Load the crop plugin
            var imgUrl = result.message.imgUrl;
            $("<img src='" + imgUrl+"' id='" + cropProfilePicSettings.imgDiv + "'/>").load(function() {
                // Hide the spinner
                hideSpinner();
                
                // Show the image
                $(this).appendTo(cropProfilePicSettings.modal + " .modal-body");
                $('#'+cropProfilePicSettings.imgDiv).Jcrop({
                        minSize:      [200,200],
                        maxSize:      [800,800],
                        bgColor:      'black',
                        boxWidth:     400,
                        bgOpacity:   .4,
                        aspectRatio: 1,
                        setSelect:   [0,0,400,400],
                        onSelect:    showCoords,
                        onChange:    showCoords
                    },function(){
                        jcropApi = this;
                    });
                });
        }
    }

    /**
     * Handles the click event for crop button
     */
    function cropButtonHandler(){
        // Check if the user is logged in
        user.isLoggedIn(true); // Redirects the user to login if not logged in
        
        // Remove the photo
        clearImage();

        // Update the modal title
        $('#crop-photo-modal .modal-title').text("Cropping...");
        // Show the spinner
        showSpinner();

        // Post the co-ordinates  to the server
        $.ajax({
            type:"post",
            url: "/user/profile/image/step2",
            data: JSON.stringify(cords)
        }).done(function(result){
            result = JSON.parse(result);
            if( result['success'] ){
                // Refresh the page
                location.reload(true);
            } else {
                // Show an error message
                utilities.notifyWithDelay(
                    'Error Cropping photo',
                    'Some error occurred, please try again later',
                    'error',
                    60
                );
                hideSpinner();
            }
        });
    }

    /**
     * Its clears the image in the modal
     * the jcrop plugin
     *
     */
    function clearImage(){
        jcropApi.destroy();
        // Remove the image from the dom
        $('#'+ cropProfilePicSettings.imgDiv).remove();
    }

    /**
     * Validates the profile form fields and shows
     * the respective error messages
     * @param profile
     * @returns {boolean}
     */
    function validate( profile ){
        var validationError = false;
        // Name cannot be empty and should be
        // atleast 3 letters long
        if(utilities.isEmpty(profile.name) || profile.name.length < 3 ) {
            validationError = true;
            $('#full-name-error').show();
        } else {
            $('#full-name-error').hide();
        }
        return validationError;
    }

    /**
     * handler which is called when save profile button is clicked
     * @param event
     */
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
            button.attr('disabled',false);
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
                        button.attr('disabled',false);
                    }
                }
            );
    }

    // =====================================================
    //      Edit Profile - Private form
    // ======================================================


    /**
     * Initialize private info
     * @param privateFormSubmit id of the form button
     */
    function initPrivateForm( privateFormSubmit ) {
        $(privateFormSubmit).click( savePrivateForm );
    }

    function getPrivateDataFormValues() {
        var currentEmail = $('input[name=edit-profile-email]').data('current-email') || '';
        var email = $('input[name=edit-profile-email]').val() || '';
        var curPassword = $('input:password[name=edit-profile-current-password]').val() || '';
        var newPassword = $('input:password[name=edit-profile-new-password]').val() || '';
        var confirmPassword = $('input:password[name=edit-profile-confirm-password]').val() || '';

        return {
            currentEmail: currentEmail.trim(),
            email: email.trim(),
            currentPassword: curPassword.trim(),
            newPassword: newPassword.trim(),
            confirmPassword: confirmPassword.trim()
        }
    }

    function showPrivateFormError(msg) {
        $('#private-form-error').html( msg );
        $('#private-form-error').removeClass('hide');
    }

    function hidePrivateFormError() {
        $('#private-form-error').addClass('hide');
    }

    function savePrivateForm( event ){
        event.preventDefault();
        var pInfo = getPrivateDataFormValues();
        console.log( pInfo );

        // Check if it is an email change
        var isEmailChange = ( pInfo.currentEmail != pInfo.email ) ;

        // Check if password is being changed
        var isPasswordChange = (pInfo.newPassword != null && pInfo.newPassword.trim() != '');
        hidePrivateFormError(); // Hide the error
        if(isEmailChange || isPasswordChange) {
            if(!pInfo.currentPassword) {
                showPrivateFormError("Current password cannot be empty");
            } else {
                if(isPasswordChange) {
                    // Check if the new and old passwords are equal
                    if( pInfo.newPassword != pInfo.confirmPassword ) {
                        // Show an error message
                        showPrivateFormError("New Password and Verify Password do not match");
                    } else {
                        // Call the api to change password
                        updatePassword( pInfo );
                    }
                } else {
                    // Call the api to update email address
                    updateEmail( pInfo );
                }
            }
        } else {
            // Nothing is being changed
            showPrivateFormError("Nothing to update");
        }
    }

    function updatePassword( pInfo ){
        $.ajax({
            type:"post",
            url: "/user/profile/updatePassword",
            data: JSON.stringify(pInfo)
        }).done(function(result){
            result = JSON.parse(result);
            if( result['success'] ){
                // Refresh the page
                location.reload(true);
            } else {
                showPrivateFormError( result['message'] );
            }
        });
    }

    function updateEmail(pInfo){
        $.ajax({
            type: "post",
            url: "/user/profile/updateEmail",
            data: JSON.stringify(pInfo)
        }).done( function(result){
            result = JSON.parse( result );
            if( result['success'] ){
                // Refersh the page
                location.reload(true);
            } else {
                showPrivateFormError( result.message );
            }
        });
    }

    return {
        init: init,
        initPrivateForm: initPrivateForm
    };
})();

CC.Class['Profile'].init('#save-profile','#profile-photo-upload','#btn-crop');
CC.Class['Profile'].initPrivateForm( '#save-profile-private' );