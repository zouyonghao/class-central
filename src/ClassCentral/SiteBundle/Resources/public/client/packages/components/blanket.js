
const ClassName = {
  ANIMATE_HIDDEN: 'animate-hidden',
  ANIMATE_FADE_ENTERED: 'animate-fade-entered',
}

const blanket = (selector, settings) => {
  const $element = $(selector);

  let state = {
    isShown: false,
  }

  const show = () => {
    if (state.isShown) {
      return;
    }
    $element.removeClass(ClassName.ANIMATE_HIDDEN).addClass(ClassName.ANIMATE_FADE_ENTERED);
    state.isShown = true;
  }

  const hide = () => {
    if (!state.isShown) {
      return;
    }
    $element.removeClass(ClassName.ANIMATE_FADE_ENTERED).addClass(ClassName.ANIMATE_HIDDEN);
    state.isShown = false;
  }

  const toggle = () => {
    if (state.isShown) {
      hide();
    } else {
      show();
    }
  }

  $element.on('click', function (e) {
    e.preventDefault();
    if (settings && settings.clickCallback) {
      settings.clickCallback(e);
    }
  })

  return {
    isShown: () => state.isShown,
    element: () => element,
    show: show,
    hide: hide,
  }
};

export default blanket;