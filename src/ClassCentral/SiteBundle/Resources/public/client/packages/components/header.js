'use strict';

import eventNames from "../settings/eventNames";
import changeIconColor from "../utils/changeIconColor";
import getElementProps from "../utils/getElementProps";
import Search from "./Search";
import blanket from "./blanket";
import responsive from "../utils/responsive";

/**
 * --------------------------------------------------------------------------
 * header.js
 * Created by Mats-Joonas mj@codelight.eu on Feb 7, 2018
 * --------------------------------------------------------------------------
 */

const NAME = 'header'
const VERSION = '1.0.0'

const ClassName = {
  SHOWN: 'shown',
  HIDDEN: 'hidden',
  ANIMATE_HIDDEN: 'animate-hidden',
  ANIMATE_ENTERED: 'animate-entered',
  ANIMATE_FADE_ENTERED: 'animate-fade-entered',
}

const dataApi = {
  SUB_MENU_TOGGLE: 'sub-menu-toggle',
  SUB_MENU_CLICK_SHOW: 'sub-menu-click-show',
  SUB_MENU_CLICK_HIDE: 'sub-menu-click-hide',
  SUB_MENU: 'sub-menu',
  BLANKET: 'header-blanket',
  AUTO_ALIGN: 'auto-align',
  STICKY_BAR: 'cc-sticky-top-bar',
}

const Selector = {
  SUB_MENU_TOGGLE: '[data-' + dataApi.SUB_MENU_TOGGLE + ']',
  SUB_MENU_CLICK_SHOW: '[data-' + dataApi.SUB_MENU_CLICK_SHOW + ']',
  SUB_MENU_CLICK_HIDE: '[data-' + dataApi.SUB_MENU_CLICK_HIDE + ']',
  SUB_MENU: '[data-' + dataApi.SUB_MENU + ']',
  BLANKET: '[data-' + dataApi.BLANKET + ']',
  STICKY_BAR: '[data-' + dataApi.STICKY_BAR + ']',
}

const Settings = {
  hoverOutDelay: 300,
}

/*
* subMenuTabs is responsible for controlling and rendering submenu contents
*/
const subMenuTabs = wrapper => {
  const dataApi = {
    TAB_CONTENT: 'tab-content',
    TAB_SELECT: 'tab-nav-select',
    TAB_SELECT_TEXT: 'tab-select-text',
    TAB_NAV_BUTTON: 'tab-nav-button',
  }

  const Selector = {
    TAB_CONTENT: '[data-' + dataApi.TAB_CONTENT + ']',
    TAB_SELECT: '[data-' + dataApi.TAB_SELECT + ']',
    TAB_SELECT_TEXT: '[data-' + dataApi.TAB_SELECT_TEXT + ']',
    TAB_NAV_BUTTON: '[data-' + dataApi.TAB_NAV_BUTTON + ']',
  }

  /*
  * store all jQuery collections in the $element object
  * so we don't have to go through DOM and create a new jQuery object every time
  * we need to reference an element
  */
  const $element = {
    WRAPPER: $(wrapper),
    TAB_CONTENTS: $(wrapper).find(Selector.TAB_CONTENT),
    TAB_SELECT: $(wrapper).find(Selector.TAB_SELECT),
    TAB_SELECT_TEXT: $(wrapper).find(Selector.TAB_SELECT_TEXT),
    TAB_NAV_BUTTON: $(wrapper).find(Selector.TAB_NAV_BUTTON),
  }

  if (!$element.TAB_CONTENTS.length) {
    return;
  }

  let state = {
    currentTab: $(window).innerWidth() <= 640 ? "subject" : "notable",
  }

  const render = state => {
    // render tab contents
    $element.TAB_CONTENTS.each(function () {
      const $this = $(this);
      if ($this.data(dataApi.TAB_CONTENT) === state.currentTab) {
        $this.show();
      } else {
        $this.hide();
      }
    });

    // render tab nav select and it's display text
    const selectOptionValue = state.currentTab;
    $element.TAB_SELECT.val(selectOptionValue);

    // render tab nav select display text
    const selectText = $element.TAB_SELECT.find('option').filter(function () {
      return this.value === selectOptionValue
    }).text();
    $element.TAB_SELECT_TEXT.text(selectText)

    // render data-tab-nav-button active classes
    $element.TAB_NAV_BUTTON.each(function () {
      const $this = $(this);
      if ($this.data(dataApi.TAB_NAV_BUTTON) === state.currentTab) {
        changeIconColor('gray', 'charcoal', this);
        $(this)
        .find(".text--gray")
        .removeClass("text--gray")
        .addClass("text--charcoal border-bottom border--charcoal border--thin");
      } else {
        changeIconColor('charcoal', 'gray', this);
        $(this)
          .find(".text--charcoal.border-bottom.border--charcoal.border--thin")
          .removeClass("text--charcoal border-bottom border--charcoal border--thin")
          .addClass("text--gray");
      }
    });
  }

  // attach event handlers
  $element.TAB_SELECT.on('change', function () {
    state.currentTab = $(this).val();
    // rerender after state change
    render(state);
  });

  $element.TAB_NAV_BUTTON.mouseenter(function () {
    state.currentTab = $(this).data(dataApi.TAB_NAV_BUTTON);
    render(state);
  });

  $element.TAB_NAV_BUTTON.click(function (event) {
    if (window.TOUCHSCREEN) {
      event.preventDefault();
    }
  });

  // initial render
  render(state);
}

const subMenu = (element, context) => {

  const $element = $(element);

  let state = {
    isOpen: false,
  }

  const show = (triggerElement) => {
    if (state.isOpen) {
      return;
    }
    $element.removeClass(ClassName.ANIMATE_HIDDEN).addClass(ClassName.ANIMATE_ENTERED);
    /*
    * auto center submenus
    */
    if ($element.data(dataApi.AUTO_ALIGN)) {
      const subMenuProps = getElementProps(element);
      const triggerProps = getElementProps(triggerElement);
      $element.css({
        'left': triggerProps.left + triggerProps.width / 2,
        'margin-left': -subMenuProps.width / 2,
      });
    };
    state.isOpen = true;
  }

  const hide = () => {
    if (!state.isOpen) {
      return;
    }
    $element.removeClass(ClassName.ANIMATE_ENTERED).addClass(ClassName.ANIMATE_HIDDEN);
    state.isOpen = false;
  }

  const toggle = () => {
    if (state.isOpen) {
      hide();
    } else {
      show();
    }
  }

  return {
    isOpen: () => state.isOpen,
    element: () => element,
    name: () => $element.data(dataApi.SUB_MENU),
    show: show,
    hide: hide,
  }
};

const stickyBar = (context) => {
  const $element = {
    CONTEXT: $(context),
    STICKY_BAR: $(context).find(Selector.STICKY_BAR),
  }

  $(window).on('scroll.cc.stickybar', function(e) {
    if ($(window).scrollTop() > 95) {
      $element.STICKY_BAR.removeClass(ClassName.ANIMATE_HIDDEN).addClass(ClassName.ANIMATE_FADE_ENTERED);
    } else {
      $element.STICKY_BAR.removeClass(ClassName.ANIMATE_FADE_ENTERED).addClass(ClassName.ANIMATE_HIDDEN);
    }
  });

  $(window).trigger('scroll.cc.stickybar');
}

const navBar = (context) => {
  let subMenus = [];
  let blanketInstance = null;

  class State {
    constructor() {
      this.openSubMenu = {
        name: null,
        triggerElement: null,
      };
    }

    updateActiveSubMenu(name, triggerElement) {
      this.openSubMenu.name = name;
      this.openSubMenu.triggerElement = triggerElement;
    }

    removeActiveSubMenu() {
      this.openSubMenu.name = null;
      this.openSubMenu.triggerElement = null;
    }

    get activeSubMenu() {
      return this.openSubMenu;
    }
  }

  const state = new State();

  // create the blanket instance
  blanketInstance = blanket(Selector.BLANKET, {
    clickCallback: e => {
      state.removeActiveSubMenu();
      render(state);
      $(context).trigger(eventNames.BLANKET_CLICK, e);
    }
  });

  // create and store the submenu instances
  $(context).find(Selector.SUB_MENU).each(function () {
    subMenus.push(subMenu(this, context));
  });

  // init tabs inside submenus
  subMenus.map(subMenu => {
    return subMenuTabs(subMenu.element());
  });

  // init search
  const search = new Search(context, {
    showResultsCallback: function() {
      blanketInstance.show();
    },
    hideResultsCallback: function() {
      blanketInstance.hide();
    }
  });

  const render = state => {
    $(context).find(Selector.SUB_MENU_CLICK_SHOW).removeClass(ClassName.HIDDEN);
    $(context).find(Selector.SUB_MENU_CLICK_HIDE).hide();

    if (state.activeSubMenu.name) {
      blanketInstance.show();
      $(context).find(`[data-${dataApi.SUB_MENU_CLICK_SHOW}=${state.activeSubMenu.name}]`).addClass(ClassName.HIDDEN);
      $(context).find(`[data-${dataApi.SUB_MENU_CLICK_HIDE}=${state.activeSubMenu.name}]`).show();
    } else {
      blanketInstance.hide();
    }

    subMenus.forEach(subMenu => {
      if (subMenu.name() === state.activeSubMenu.name) {
        subMenu.show(state.activeSubMenu.triggerElement);
      } else {
        subMenu.hide();
      }
    });
  };

  const shouldHaveHover = () => responsive.getMediaSize().matching.includes('mediumUp');


  // add event handlers
  $(Selector.SUB_MENU_CLICK_SHOW).on('click', function (e) {
    e.preventDefault();
    state.updateActiveSubMenu($(this).data(dataApi.SUB_MENU_CLICK_SHOW), this);
    render(state);
  });

  $(Selector.SUB_MENU_CLICK_HIDE).on('click', function (e) {
    e.preventDefault();
    state.removeActiveSubMenu();
    render(state)
  });

  $(Selector.SUB_MENU_TOGGLE).on('click', function (e) {
    e.preventDefault();
  });


  $(Selector.SUB_MENU_TOGGLE).hover(
    function (e) {
      state.updateActiveSubMenu($(this).data(dataApi.SUB_MENU_TOGGLE), this);
      render(state)
    },
    function (e) {
      const origSubMenu = state.activeSubMenu.name;
      state.removeActiveSubMenu();
      /*
      * Leave a short grace period to allow cursor movement from this nav link to the opened submenu
      */
      window.setTimeout(function () {
        if (origSubMenu !== state.activeSubMenu.name) {
          render(state)
        }
      }, Settings.hoverOutDelay)
    }
  );


  $(Selector.SUB_MENU).hover(
    function (e) {
      if (!shouldHaveHover()) {
        return;
      }
      state.updateActiveSubMenu($(this).data(dataApi.SUB_MENU), this);
    },
    function (e) {
      if (!shouldHaveHover()) {
        return;
      }
      const origSubMenu = state.activeSubMenu.name;
      state.removeActiveSubMenu();
      /*
      * Leave a short grace period to allow cursor movement from this sub menu to its nav link
      */
      window.setTimeout(function () {
        if (origSubMenu !== state.activeSubMenu.name) {
          render(state)
        }
      }, Settings.hoverOutDelay)
    }
  );

  // init sticky menu
  stickyBar(context)
}



const header = (selector) => {
  const navBarInstance = navBar($(selector)[0]);
}

export default header('[data-cc-header]');
