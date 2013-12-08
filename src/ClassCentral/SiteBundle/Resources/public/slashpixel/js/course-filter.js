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
        var tiles = $(this).parent().find(".tiles-view")
        console.log(list);
        list.hide();
        tiles.show();
    });

    $(".list-button").click(function() {
        var tilesButton = $(this).parent().find(".tiles-button");
        tilesButton.removeClass("active");
        $(this).addClass("active");
        var list = $(this).parent().find("table");
        var tiles = $(this).parent().find(".tiles-view")
        console.log(tiles);
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

    function toggleActive(e) {
        e.preventDefault();
        var parent = current.parent();
        if (parent.hasClass("active")) {
            parent.removeClass("active");
        } else {
            parent.addClass("active");
        }
    }

    $(".tick-wrap .tick").click(function() {
        $(this).toggleClass("ticked");
    });

    $(".main-category").click(function(e) {
        current = $(this);
        toggleActive(e);
    });


    $(".sub-category").click(function(e) {
        current = $(this);
        toggleActive(e);
    });

    $(".sort").click(function(e) {
        current = $(this);
        toggleActive(e);
        subjectFilter();
    });

    var tableTypes = ['recent','recentlyAdded','ongoing','upcoming','selfpaced','past'];

    var lists = {};
    for(var i = 0; i < tableTypes.length; i++)
    {
        var tableType = tableTypes[i];
        var listClass = 'table-body-' + tableType;
        if($('.' +listClass)[0])
        {
            var options = {
                valueNames: [ 'course-name','subjectSlug'],
                searchClass: ['filter-search'],
                listClass: [listClass],
                sortClass: ['sort-button']
            };
            lists[tableType] = new List('filter-wrap',options);
        }

    }


    $(".tick-wrap .tick").click(function(event) {
        subjectFilter();
    });

    function subjectFilter() {
        var filterCats = [];

        $(".active > .sort").each(function() {
            filterCats.push($.trim($(this).data("category")));
        });

        $(".ticked + .sub-category").each(function() {
            filterCats.push($.trim($(this).data("category")));
        });

        for(var i = 0; i <= tableTypes.length; i++)
        {
            var tableType = tableTypes[i];

            if(tableType in lists)
            {
                var list = lists[tableType];

                list.filter(function(item){
                    if(filterCats.length > 0)
                    {
                        var subject = $.trim(item.values().subjectSlug);

                        if($.inArray(subject,filterCats) != -1)
                        {
                            return true;
                        }
                        return false;
                    }
                    return true;
                });
            }
        }
    }

});

