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


    jQuery(function () {

        $("#nav-search-form").click(function() {
            toggleSlide(this);
        });
    });

    function toggleSlide(element) {
        var drawer = jQuery(element).parent().find(".cc-home-form");
        var content = jQuery(element).parent().find(".cc-home-form form");

        if (drawer.hasClass('open')) {
            drawer.removeClass("open");
        }
        else
        {
            drawer.addClass("open");
        }
    }

    $(".test-button").click(function() {
        $(this).toggleClass("redtest");
        console.log("test-button");
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
        var parent = current.parent()
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

        var filterCats = [];

        $(".active > .sort").each(function() {
            filterCats.push($(this).data("category"));
        });

        $(".ticked + .sub-category").each(function() {
            filterCats.push($(this).data("category"));
        });

        if (filterCats.length > 0) {
            $("#filter-wrap .table tbody tr").hide();

            $.each(filterCats, function() {
                var matches = $("#filter-wrap .table [data-category='" + this + "']").closest("tr");
                matches.show();
            });
        } else {
            $("#filter-wrap .table tbody tr").show();
        }
    });

    $(".tick-wrap .tick").click(function(event) {

        var filterCats = [];

        $(".active > .sort").each(function() {
            filterCats.push($(this).data("category"));
        });

        $(".ticked + .sub-category").each(function() {
            filterCats.push($(this).data("category"));
        });

        if (filterCats.length > 0) {
            $("#filter-wrap .table tbody tr").hide();

            $.each(filterCats, function() {
                var matches = $("#filter-wrap .table [data-category='" + this + "']").closest("tr");
                matches.show();
            });
        } else {
            $("#filter-wrap .table tbody tr").show();
        }
    });

});

var options = {
    valueNames: [ 'course-name'],
    searchClass: ['filter-search'],
    listClass: ['table-body'],
    sortClass: ['sort-button']

};

var subjectList = new List('filter-wrap', options);