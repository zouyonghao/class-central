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


    //toggle = true;

    $(".mobile-filter-btn").click(function() {

        var filterWrap = $("#filter-wrap");
        var catWrap = $("#filter-wrap .cat-filter-wrap");

        //if (toggle) {
        if (catWrap.hasClass("opened")) {
            catWrap.removeClass("opened");
        }   else   {
            catWrap.addClass("opened");
        }

        if (filterWrap.hasClass("opened")) {
            //toggle = false;
            //setTimeout(function() {
            filterWrap.removeClass("opened");
            //toggle = true;
            //}, 900);

        }   else   {
            filterWrap.addClass("opened");
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
        toggleActive(e, $(this));
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

    var tableTypes = ['subjectstable','searchtable','statustable','providertable','institutiontable','languagetable'];

    var lists = {};
    for(var i = 0; i < tableTypes.length; i++)
    {
        var tableType = tableTypes[i];
        var listClass = 'table-body-' + tableType;
        if($('.' +listClass)[0])
        {
            var options = {
                valueNames: [ 'course-name','subjectSlug','languageSlug','table-uni-list', 'sessionSlug'],
                searchClass: ['filter-search'],
                listClass: [listClass],
                sortClass: ['sort-button'],
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
                valueNames: [ 'course-name','subjectSlug','languageSlug','table-uni-list','sessionSlug'],
                searchClass: ['filter-search'],
                listClass: [listClass],
                sortClass: ['sort-button'],
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
        var filterCats = [];
        // Sub subjects
        $(".filter-subjects .active > .sort").each(function() {
            filterCats.push($.trim($(this).data("subject")));
        });

        // Parent subjects
        $(".filter-subjects .ticked + .sub-category").each(function() {
            var parentCat = $.trim($(this).data("subject"));
            filterCats.push(parentCat);
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


        // Go through all the lists and filter the courses which don't
        // have subjects in filterCats
        for (var key in lists)
        {
            if (!lists.hasOwnProperty(key)) {

                continue;
            }
            var list = lists[key];
            list.filter(function (item) {
                // Match subjects
                var subMatch = true;
                if (filterCats.length > 0) {
                    var subject = $.trim(item.values().subjectSlug);

                    if ($.inArray(subject, filterCats) == -1) {
                        subMatch = false;
                    }
                }

                // Match languages
                var langMatch = true;
                if (filterLang.length > 0) {
                    var language = $.trim(item.values().languageSlug);
                    if ($.inArray(language, filterLang) == -1) {
                        langMatch = false;
                    }
                }

                var sessionMatch = true;
                if (sessions.length > 0) {
                    sessionMatch = false;
                    var itemSessionsStr = $.trim(item.values().sessionSlug);
                    var itemSessions = itemSessionsStr.split(',');

                    for(i = 0; i < itemSessions.length; ++i)
                    {
                        if ($.inArray(itemSessions[i], sessions) > -1) {
                            sessionMatch = true;
                            break;
                        }
                    }
                }

                return subMatch && langMatch && sessionMatch;
            });

            var tableWrapper = $('#' + key + '-table-wrapper');
            if(courseLists.length > 0 )
            {
                if( $.inArray(key, courseLists) != -1) {
                    tableWrapper.show();
                } else {
                    tableWrapper.hide();
                }
            } else {
                tableWrapper.show();
            }
        }
    }


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
    filterCourses();

    function gaqPush(type, value) {
        try {
            _gaq.push(['_trackEvent','Filters',type, value]);
        }catch (err) {}
    }
});

