/**
 * Created by dhawal on 12/7/13.
 */
jQuery(function($) {

    $(".list-button").addClass("active");

    $(".tiles-button").click(function() {
        var listButton = $(this).parent().find(".list-button");
        listButton.removeClass("active");
        $(this).addClass("active");
        var list = $(this).parent().find("table");
        var tiles = $(this).parent().find(".tiles-view");
        list.hide();
        tiles.show();
    });

    $(".list-button").click(function() {
        var tilesButton = $(this).parent().find(".tiles-button");
        tilesButton.removeClass("active");
        $(this).addClass("active");
        var list = $(this).parent().find("table");
        var tiles = $(this).parent().find(".tiles-view");
        tiles.hide();
        list.show();
    });


    $(".mobile-filter-btn").click(function(event) {
        event.preventDefault();
        var filterWrap = $("#filter-wrap");
        var catWrap = $("#filter-wrap .cat-filter-wrap");

        //if (toggle) {
        if (catWrap.hasClass("show-filter")) {
            catWrap.removeClass("show-filter");
        }   else   {
            catWrap.addClass("show-filter");
        }

        if (filterWrap.hasClass("show-filter")) {
            //toggle = false;
            //setTimeout(function() {
            filterWrap.removeClass("show-filter");
            //toggle = true;
            //}, 900);

        }   else   {
            filterWrap.addClass("show-filter");
        }
        //}

    });

    function toggleActive(e, current) {
        e.preventDefault();
        var parent = current.parent();
        parent.toggleClass("active");
    }

    $(".tick-wrap .tick").click(function() {
        $(this).toggleClass("ticked");
        // Deselect all children
        var parentLi = $(this).parent().parent();
        if(parentLi.find('.filter-dropdown')[0])
        {
            // It has children. Deselect them all
            parentLi.find('.filter-dropdown li').removeClass("active");
        }
        var node = $(this).parent().children('a');
        var type = node.data('type');
        var value = node.data(type);
        filterCourses();
        gaqPush(type, value);
    });

    $(".main-category").click(function(e) {
        toggleActive(e, $(this));
    });


    $(".sub-category").click(function(e) {
        e.preventDefault();
        var span = $(this).parent().find('span')[0];
        var parentLi = $(span).parent().parent();
        if(parentLi.find('.filter-dropdown')[0]) {
            // Has sub categories. Expand collapse the sub categories
            toggleActive(e, $(this));
        } else{
            // No sub-categories. Check the box and filter the courses
            $(span).toggleClass('ticked');
            var type = $(this).data('type');
            var value = $(this).data(type);
            filterCourses();
            gaqPush(type, value);
        }
    });

    $(".sort").click(function(e) {
        // Remove the parent tick
        var parentLi = $(this).parent().parent().parent();
        var tickBox = parentLi.find('.tick-wrap .tick');
        tickBox.removeClass('ticked');
        // Toggle the activate for the current one
        var node = $(this);
        var type = node.data('type');
        var value = node.data(type);
        toggleActive(e, $(this));
        filterCourses();
        gaqPush(type, value);
    });

    var tableTypes = ['subjectstable','searchtable','statustable','providertable','institutiontable','languagetable','tagtable'];

    var lists = {};
    for(var i = 0; i < tableTypes.length; i++)
    {
        var tableType = tableTypes[i];
        var listClass = 'table-body-' + tableType;
        if($('.' +listClass)[0])
        {
            var options = {
                valueNames: [ 'course-name','subjectSlug','languageSlug','table-uni-list', 'sessionSlug', 'course-startdate', "course-rating-value","course-provider"],
                searchClass: ['filter-search'],
                listClass: [listClass],
                page:2000
            };
            if($('#course-table-wrapper').length > 0)
            {
                var list = new List('course-table-wrapper',options);
            }
            else
            {
                var list = new List('filter-wrap',options);
            }

            lists[tableType] = list;
            try {
                // No filters on the homepage
                list.on("updated",updated(tableType));
            } catch(err) {

            }

        }
    }

    // Callback thats called whenver the results are updated
    // Updates the count among other things
    function updated(tableType) {
        return function() {
            var count = lists[tableType].visibleItems.length;
            $('#' + tableType + "-count").html(count);
            var listTable = $('#' + tableType + 'list');
            if(count == 0) {
                listTable.hide();
            } else {
                listTable.show();
            }

            // Update the count on course listing pages
            $('#number-of-courses').html( count );

        }
    }

    /**
     * Glogal function for my courses tables
     * @param tableType
     */
    listifyTable = function (tableType)
    {

        var listClass = 'table-body-' + tableType;
        if($('.' +listClass)[0]) {
            var options = {
                valueNames: [ 'course-name','subjectSlug','languageSlug','table-uni-list','sessionSlug','course-startdate', "course-rating-value","course-provider"],
                searchClass: ['filter-search'],
                listClass: [listClass],
                page:2000
            };

            var list = new List('filter-wrap',options);
            lists[tableType] = list;
            try {
                // No filters on the homepage
                list.on("updated",updated(tableType));
            } catch(err) {

            }
        }
    }

    function filterCourses() {

        if(!$('.cat-filter-wrap').length) {

            return;
        }
        var filterCats = [];
        var tickedSubjects = []; // for the pushstate url
        // Sub subjects
        $(".filter-subjects .active > .sort").each(function() {
            filterCats.push($.trim($(this).data("subject")));
            tickedSubjects.push($.trim($(this).data("subject")));
        });

        // Parent subjects
        $(".filter-subjects .ticked + .sub-category").each(function() {
            var parentCat = $.trim($(this).data("subject"));
            filterCats.push(parentCat);
            tickedSubjects.push(parentCat);
            // Get the subjects for this parent category
            $("a[data-parent='" + parentCat +"']").each(function(){
               filterCats.push( $.trim($(this).data("subject"))) ;
            });
        });

        // Languages
        var filterLang = [];
        $(".filter-languages .ticked + .sub-category").each(function() {
            filterLang.push($.trim($(this).data("lang")));
        });

        // Course Lists
        var courseLists = [];
        $(".filter-courses .ticked + .sub-category").each(function () {
            courseLists.push($.trim($(this).data("course-list")));
        });

        // Session list
        var sessions = [];
        $(".filter-sessions .ticked + .sub-category").each(function () {
            sessions.push($.trim($(this).data("session")));
        });

        // updates the url
        updateUrl(tickedSubjects, filterLang, sessions);

        // Ajax query
        $.ajax({
            url: "/maestro/provider/udacity",
            cache: true
        })
            .done(function(result){
                $('.tables-wrap').html( result );
            });
    }


    // for page load done with filters

    // Parse the url for filters
    // Session filters
    var qSessionsParam = $.url().param('session');
    if( qSessionsParam ) {
        var qSessions = qSessionsParam.split(',');
        for(i = 0; i < qSessions.length; i++) {
            $('#session-'+qSessions[i]).find('.tick').addClass('ticked');
        }
    }

    // language filters
    var qLanguageParam = $.url().param('lang');
    if( qLanguageParam ) {
        var qLang = qLanguageParam.split(',');
        for(i=0; i < qLang.length; i++) {
            $('#lang-'+qLang[i]).find('.tick').addClass('ticked');
        }
    }

    // subject filters
    var qSubjectParam = $.url().param('subject');
    if( qSubjectParam ) {
        var qSubject = qSubjectParam.split(',');
        for(i=0;i < qSubject.length; i++) {
            // Check if it is a parent subject
            subNode = $('#subject-' + qSubject[i]);
            if($(subNode).data('type') == 'parent-sub') {
                $(subNode).find('.tick').addClass('ticked');
            } else {
                $(subNode).addClass('active');
                // Expand the parent
                var parentSlug = $(subNode).find('a').data('parent');
                $('#subject-' + parentSlug).find('.tick-wrap').addClass('active');
            }

        }
    }




    // SORTING
    var sortDescClass = 'headerSortUp';
    var sortAscClass = 'headerSortDown';
    $('th.sorting').click(function(){
        var table = $(this).parent().parent().parent().attr('id');
        var list = table.substring(0, table.indexOf('list'));
        var sortBy = $(this).data('sort');
        if(!$(this).hasClass(sortAscClass)) {
            lists[list].sort(sortBy,{'asc':true});
            $(this).removeClass(sortDescClass);
            $(this).addClass(sortAscClass);
        } else {
            lists[list].sort(sortBy,{'desc':true});
            $(this).removeClass(sortAscClass);
            $(this).addClass(sortDescClass);
        }

    });

    /**
     * Updates the url to reflect the filters using pushstate
     * @param subjects
     * @param langs
     * @param sessions
     */
    function updateUrl(subjects, langs, sessions) {
        var params = {};
        if( subjects.length > 0 ) {
            params['subject'] = subjects.join();
        }
        if( sessions.length > 0 ) {
            params['session'] = sessions.join();
        }

        var lowerCaseLangs = [];
        if( langs.length > 0 ) {
            for(i=0; i < langs.length; i++) {
                lowerCaseLangs.push(langs[i].toLowerCase());
            }
            params['lang'] = lowerCaseLangs.join();
        }
        try{
            // Check if there is a search param
            $qParams = $.url().param();
            for(var param in $qParams) {
                if($.inArray(param,['session','subject','lang']) == -1 ) {
                    params[param ] = $qParams[param];
                }
            }

            if( $.isEmptyObject(params) ) {
                history.replaceState(null, null, $.url().attr('path'));
            } else {
                history.replaceState(null, null, $.url().attr('path') + '?' + $.param(params));
            }
        } catch(e){};

    }

    function gaqPush(type, value) {
        try {
            _gaq.push(['_trackEvent','Filters',type, value]);
        }catch (err) {}
    }
});

