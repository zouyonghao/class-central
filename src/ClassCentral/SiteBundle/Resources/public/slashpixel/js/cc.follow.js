var CC = CC || {
        Class : {}
}

CC.Class['Follow'] = (function(){

    var utilities = CC.Class['Utilities'];

    function init() {
        $('.btn-follow-item').click(followClicked);
    }

    function followClicked(e) {
        e.preventDefault();
        var self = $(this);
        var item = $(this).data('item');
        var itemId = $(this).data('item-id');
        var itemName = $(this).data('item-name');
        var showItemName = $(this).data('show-item-name');

        $.ajax({
            url: "/ajax/isLoggedIn",
            cache: false,
            success: function( result ) {
                var loggedInResult = $.parseJSON(result);
                if( loggedInResult.loggedIn ){
                    ga('send','event','Follow',"Logged in", item);
                    // Follow the item
                    var followUrl = '/ajax/follow/' + item +'/' + itemId;

                    $.ajax({
                        url: followUrl,
                        cache:false,
                        success: function(r) {
                            var result = JSON.parse(r);
                            if(result['success']) {
                                // update the state to followed
                                var itemClass = '.btn-follow-item-' + item + '-' + itemId;
                                var btnText = "Following";
                                if(showItemName) {
                                    btnText = btnText + " <i>" + itemName + "</i>";
                                }
                                $(itemClass).addClass('active');
                                $(itemClass).find('.action-button__unit:eq(1)').html( btnText );

                                // Show a success notification
                                utilities.notify(
                                    "Following " + itemName,
                                    "You will receive regular course notifications and reminders about " + itemName,
                                    "success"
                                );

                            } else {
                                // Show a error notification
                                utilities.notify(
                                    "Following Failed" + itemName,
                                    "There was some error while following " + itemName + ". Please try again later.",
                                    "error"
                                );
                            }
                        }
                    });


                } else {

                    ga('send','event','Follow',"Logged Out", item);

                    // Save the follow info in session
                    $.ajax({
                        url: '/ajax/pre_follow/' + item +'/' + itemId,
                        cache: false,
                        success: function(r){
                            // do nothing
                        }
                    });

                    // Show signup modal
                    $('#signupModal-btn_follow').modal('show');
                }
            }
        });
    }

    return {
        init: init
    }
})();


CC.Class['Follow'].init();
