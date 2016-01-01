var CC = CC || {
        Class : {}
}

CC.Class['Follow'] = (function(){

    function init() {
        $('.btn-follow-item').click(followClicked);
    }

    function followClicked(e) {
        e.preventDefault();
        var self = $(this);
        var item = $(this).data('item');
        var itemId = $(this).data('item-id');
        var itemName = $(this).data('item-name');

        $.ajax({
            url: "/ajax/isLoggedIn",
            cache: false,
            success: function( result ) {
                var loggedInResult = $.parseJSON(result);
                if( loggedInResult.loggedIn ){

                    // Follow the item
                    var followUrl = '/ajax/follow/' + item +'/' + itemId;

                    $.ajax({
                        url: followUrl,
                        cache:false,
                        success: function(r) {
                            var result = JSON.parse(r);
                            if(result['success']) {
                                // update the state to followed
                                $(self).addClass('active');
                                $(self).find('.action-button__unit:eq(1)').html('Following <i>' + itemName + '</i>');

                                // Show a success notification

                            } else {
                                // Show a error notification
                            }
                        }
                    });


                } else {
                    // Show signup modal
                    //$('#signupModal-create_credential_review').modal('show');
                }
            }
        });
    }

    return {
        init: init
    }
})();


CC.Class['Follow'].init();
