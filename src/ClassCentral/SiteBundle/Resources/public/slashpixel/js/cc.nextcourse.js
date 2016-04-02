var CC = CC || {
        Class : {}
    }


CC.Class['NextCourse'] = (function(){
    var utilities = CC.Class['Utilities'];

    function init() {

    }

    function showPickSubjectsStep()
    {
        var url = '/next-course/pick-subjects';
        ga('send','event','Meet your next course', 'Pick Subjects','Shown');
        $.ajax({
            url: url,
            cache: false,
            success: function( result ) {
                var response = $.parseJSON(result);
                $(response.modal).appendTo("body");
                $("#next-course-pick-subjects-modal").modal("show");

                // Init and attach event handlers to the follow buttons
                CC.Class['Follow'].init();
                $("#next-course-pick-subjects-modal").find('.tagboard__tag').bind("followingChanged",  updatePickSubjectsFooter);

                // Hookup next and skip buttons
                $('#next-course-pick-subjects__next').click(function(){
                    ga('send','event','Meet your next course', 'Pick Subjects','Next');
                   // onboardingFollowSubjectNextStepButton()
                });


            },
            async: false
        })
    }

    function updatePickSubjectsFooter()
    {
        var nextButton = $('#next-course-pick-subjects__next');
        var numFollows = $("#next-course-pick-subjects-modal").find('.tagboard__tag.active').length;

        var percentage = numFollows*100/5;
        $("#next-course-pick-subjects-modal .meter__bar").width( percentage + '%');

        if(numFollows >= 5) {
            $(nextButton).addClass('active');
            $(nextButton).find("span").text('Pick more subjects or move on to Step 2 (of 2)');
        } else {
            var followsLeft = 5 - numFollows;
            $(nextButton).removeClass('active');
            if( followsLeft == 1) {
                $(nextButton).find("span").text('One more to go...');
            } else {
                $(nextButton).find("span").text('Pick ' + followsLeft + ' or more subjects');
            }
        }
    }

    return {
        init: init,
        showPickSubjectsStep: showPickSubjectsStep
    }
})();

CC.Class['NextCourse'].init();