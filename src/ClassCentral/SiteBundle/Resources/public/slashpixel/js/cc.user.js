var CC = CC || {
    Class : {}
}

CC.Class['User'] = (function(){
    /**
     * Checks whether the user is logged in.
     * @param redirect if true redirects the user to login
     */
    function isLoggedIn(redirect) {
        redirect = redirect || false;
        $.ajax({
            url: "/ajax/isLoggedIn",
            cache: false,
            success: function( result ) {
                var loggedInResult = $.parseJSON(result);
                if(!loggedInResult.loggedIn && redirect) {
                    // Redirect the user to the login page
                    window.location.replace('/login');
                }
                return loggedInResult.loggedIn;
            },
            async: false
        })
    }

    function googleAuth(token) {

        var data = {
            "token": token
        }
        $.ajax({
            url : '/google-auth',
            type: 'post',
            dataType: 'json',
            data: JSON.stringify(data),
            success: function(result) {
                console.log(result);
                if (result.success) {
                    location.reload();
                }
            }
        });
    }

    return {
        isLoggedIn: isLoggedIn,
        googleAuth: googleAuth
    }

})();