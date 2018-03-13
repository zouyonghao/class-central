import eventNames from "../settings/eventNames";
import _debounce from "lodash.debounce";
import { formatNumber } from "../utils/format.es6";
import responsive from "../utils/responsive";

/**
 * --------------------------------------------------------------------------
 * search.js
 * Created by Mats-Joonas mj@codelight.eu on Feb 13, 2018
 * --------------------------------------------------------------------------
 */

const Default = {
  fetchPath: "/autocomplete/",
  searchPath: "/search?q=",
  showResultsCallback: null,
  hideResultsCallback: null,
  debounceDelay: 300,
  maxResults: 7,
  inputPlaceholder: {
    short: 'Search',
    full: 'What do you want to learn?'
  }
};

const ClassName = {
  HIDDEN: 'hidden',
  ANIMATE_HIDDEN: 'animate-hidden',
  ANIMATE_ENTERED: 'animate-entered',
  SEARCH_ICON: 'icon-search-charcoal',
  RESULTS_SHOWN: 'search-results-shown',
};

const DataApi = {
  SEARCH_FORM: 'cc-search',
  SEARCH_INPUT: 'cc-search-input',
  SEARCH_SHOW: 'cc-search-show',
  SEARCH_HIDE: 'cc-search-hide',
  HIDE_IF_SHOWN: 'hide-if-search-shown',
  SHOW_IF_SHOWN: 'show-if-search-shown',
  SEARCH_BUTTON: 'cc-search-button',
  LOADER: 'cc-search-loader',
  RESULTS_CONTAINER: 'cc-search-results',
  RESULTS_LIST: 'cc-search-results-list',
  SEARCH_QUERY_TEXT: 'cc-search-query-text',
  SEARCH_LINK: 'cc-search-link',
  HEADER: 'cc-header',
};

const Selector = {
  SEARCH_FORM: '[data-' + DataApi.SEARCH_FORM + ']',
  SEARCH_INPUT: '[data-' + DataApi.SEARCH_INPUT + ']',
  SEARCH_SHOW: '[data-' + DataApi.SEARCH_SHOW + ']',
  SEARCH_HIDE: '[data-' + DataApi.SEARCH_HIDE + ']',
  HIDE_IF_SHOWN: '[data-' + DataApi.HIDE_IF_SHOWN + ']',
  SHOW_IF_SHOWN: '[data-' + DataApi.SHOW_IF_SHOWN + ']',
  SEARCH_BUTTON: '[data-' + DataApi.SEARCH_BUTTON + ']',
  LOADER: '[data-' + DataApi.LOADER + ']',
  RESULTS_CONTAINER: '[data-' + DataApi.RESULTS_CONTAINER + ']',
  RESULTS_LIST: '[data-' + DataApi.RESULTS_LIST + ']',
  SEARCH_QUERY_TEXT: '[data-' + DataApi.SEARCH_QUERY_TEXT + ']',
  SEARCH_LINK: '[data-' + DataApi.SEARCH_LINK + ']',
};

const KeyCodes = {
  'left': 37,
  'up': 38,
  'right': 39,
  'down': 40,
  'esc': 27,
}

const ActionKeys = [KeyCodes.left, KeyCodes.up, KeyCodes.right, KeyCodes.down, KeyCodes.esc];

const ratingTemplate = (rating, reviewsCount) => {
  const Star = {
    full: '<i class="icon-star icon--xxsmall"></i>',
    half: '<i class="icon-star-half icon--xxsmall"></i>',
    empty: '<i class="icon-star-gray-light icon--xxsmall"></i>',
  }

  let reviewString = 'Reviews';
  if (reviewsCount === 1) {
    reviewString = 'Review';
  }

  const generateStars = (rating) => {
    if (isNaN(rating)) {
      return;
    }
    const round = (value) => Number((Math.round(value * 2) / 2).toFixed(1));
    const roundedRating = round(rating);

    const starCreator = (rating, currentString, counter) => {
      currentString = currentString || '';
      counter = counter || 1;

      if (counter <= Math.floor(rating)) {
        currentString += Star.full;
      } else if ((counter - Math.floor(rating)) === 1 && rating % 1) {
        currentString += Star.half;
      } else {
        currentString += Star.empty;
      }

      counter++;
      if (counter > 5) {
        return currentString;
      } else {
        return starCreator(rating, currentString, counter);
      }
    }

    return starCreator(roundedRating);
  }

  let mobileStar = '<i class="icon-star-gray icon--xsmall" />';
  if (rating >= 1) {
    mobileStar = '<i class="icon-star icon--xsmall" />';
  }

  const mobileRating = rating => {
    if (rating < 1) {
      return "0";
    }
    return (Math.round(rating * 10) / 10).toFixed(1);
  };

  return (
    `<div class="width-1-5 col xsmall-only-hidden small-only-hidden medium-only-hidden">
        <div class="review-rating text-5 text--charcoal width-text-right">
          <div>
            <span style="position: relative; top: 3px;">
              <span class=" inline-block padding-right-xxsmall" style="height: 15px; line-height: 18px;">${reviewsCount} ${reviewString}</span>
            </span>
            <div class="inline-block">
              ${generateStars(rating)}
            </div>
          </div>
        </div>
      </div>
      <div class="absolute top right margin-right-xsmall margin-top-medium text--charcoal text--bold large-up-hidden">
        <span>${mobileRating(rating)}</span>
        <span class="relative" style="top: -2px;">${mobileStar}</span>
      </div>`
  );
};

const iconTemplate = type => {
  let iconName = type;
  if (type === "mooc_report_article") {
    iconName = "paper";
  }
  if (type === "institution") {
    iconName = "university";
  }
  return `<i class="icon--small icon-${iconName}-charcoal"></i>`;
};

const followersTemplate = followersCount => {
  return `<span>/ <span class="text--italic">${formatNumber(followersCount).shortHand} followers</span></span>`;
}

const metaDataTemplates = (() => {
  const course = data => {
    const providerdMeta = provider => `<strong>Course</strong> via ${provider}`;
    return (
      `<p class="text-4 text--charcoal">
        ${data.provider ? providerdMeta(data.provider) : ""}
      </p>`
    );
  };

  const mooc_report_article = data => {
    const createdMeta = dateCreated => `<span>Published ${dateCreated}</span>`;
    return (
      `<p class="text-4 text--charcoal">
        ${data.dateCreated ? createdMeta(data.dateCreated) : ""}
      </p>`
    );
  };

  const credential = () => `<p class="text-4 text--charcoal">Earn a Credential</p>`;

  const provider = data => {
    return (
      `<p class="text-4 text--charcoal">
        <strong>Provider</strong> with ${data.count || "0"} courses
        ${data.numFollows ? followersTemplate(data.numFollows) : ""}
      </p>`
    );
  };

  const institution = data => {
    return (
      `<p class="text-4 text--charcoal">
        <strong>Institution</strong> with ${data.count || "0"} courses
        ${data.numFollows ? followersTemplate(data.numFollows) : ""}
      </p>`
    );
  };

  const subject = data => {
    return (
      `<p class="text-4 text--charcoal">
        <strong>Subject</strong> with ${data.count || "0"} courses
        ${data.numFollows ? followersTemplate(data.numFollows) : ""}
      </p>`
    );
  };

  return {
    course: course,
    mooc_report_article: mooc_report_article,
    credential: credential,
    provider: provider,
    institution: institution,
    subject: subject,
  };
})();

const resultItem = data => {
  const { payload } = data;
  return (
    `<li class="border-bottom border--thin border--gray-light">
      <a
        data-track-click="nav_click"
        data-track-props='{ "type": "Search", "title": "${payload.name}", "search_result_type" : "${payload.type}" }'
        href="${payload.url}"
        class="search-item block unit-horz padding-small hover-bg-gray relative"
      >
        <div class="unit-block padding-right-xsmall xsmall-only-hidden small-only-hidden medium-only-hidden">
          ${iconTemplate(payload.type)}
        </div>
        <div class="unit-block unit-fill">
          <div class="row">
            <div class="${(payload.type === "course") ? "large-up-width-4-5" : "100"} col">
              <h4 class="xsmall-only-padding-right-xlarge small-only-padding-right-xlarge medium-only-padding-right-xlarge text-3 text--charcoal ${payload.type === "course" ? "" : "text--bold"}" style="line-height: 18px;">
                ${payload.name}
              </h4>
              ${metaDataTemplates[payload.type](data.payload)}
            </div>
            ${ payload.type === "course" ? ratingTemplate(payload.rating, payload.reviewsCount) : ""}
          </div>
        </div>
      </a>
    </li>`
  );
}

const noResultItem = queryString => {
  return (
    `<li class="border-bottom border--thin border--gray-light">
      <div class="block unit-horz padding-medium text-center">
        No suggestions for ${queryString}
      </div>
    </li>`
  );
}

class ResultsHandler {
  constructor(context, config) {
    this._config = config;
    this._context = context;
    this._results = null;
    this._queryString = null;
    this._$element = {
      RESULTS_CONTAINER: $(context).find(Selector.RESULTS_CONTAINER),
      RESULTS_LIST: $(context).find(Selector.RESULTS_LIST),
      SEARCH_QUERY_TEXT: $(context).find(Selector.SEARCH_QUERY_TEXT),
      SEARCH_LINK: $(context).find(Selector.SEARCH_LINK),
      HEADER: $(context).closest('[data-' + DataApi.HEADER + ']'),
    }
    this._keyboardNavList = [this._$element.SEARCH_LINK];
    this._giveFocusCallback = null;
    this.isShown = false;

    this.init();
  }

  clearNavList = () => {
    this._keyboardNavList = [];
  }

  addToNavList = $item => {
    this._keyboardNavList.push($item);
  }

  receiveFocus = giveFocusCallback => {
    if (this._keyboardNavList.length) {
      this._keyboardNavList[0].focus();
    }
    this._giveFocusCallback = giveFocusCallback;
  }

  keyboardNavigation = () => {
    const _self = this;
    this._keyboardNavList.map(($item, index, thisArray) => {
      $item.on('keyup keydown', function (e) {
        if (e.keyCode === KeyCodes.up || e.keyCode === KeyCodes.down) {
          e.preventDefault();
        }
      });
      // if only item in the array
      if (thisArray.length === 1) {
        $item.on('keydown', function (e) {
          if (e.keyCode === KeyCodes.up) {
            if (_self._giveFocusCallback) {
              _self._giveFocusCallback();
            }
          }
        });
        // if last item in the array
      } else if (index + 1 === thisArray.length) {
        $item.on('keydown', function (e) {
          if (e.keyCode === KeyCodes.up) {
            thisArray[index - 1].focus();
          }
        });
        // if first item in the array
      } else if (index === 0) {
        $item.on('keydown', function (e) {
          if (e.keyCode === KeyCodes.up) {
            if (_self._giveFocusCallback) {
              _self._giveFocusCallback();
            }
          } else if (e.keyCode === KeyCodes.down) {
            thisArray[index + 1].focus();
          }
        });
      } else {
        $item.on('keydown', function (e) {
          if (e.keyCode === KeyCodes.up) {
            thisArray[index - 1].focus();
          } else if (e.keyCode === KeyCodes.down) {
            thisArray[index + 1].focus();
          }
        });
      }
    });
  }

  maxItems = () => responsive.getMediaSize().matching.includes('mediumUp') ? 7 : 5;

  renderResults(results) {
    const _self = this;
    const options = results.autocomplete[0].options;
    _self._$element.RESULTS_LIST.html('');
    _self.clearNavList();
    let items = null;

    if (options.length < 1) {
      _self._$element.RESULTS_LIST.append($(noResultItem(_self._queryString)));
    } else {
      items = options.slice();
      items.length = _self.maxItems();
      items.map(item => {
        const $resultItem = $(resultItem(item));
        _self._$element.RESULTS_LIST.append($resultItem);
        _self.addToNavList($resultItem.find('a'));
      });
    }
    _self.addToNavList(_self._$element.SEARCH_LINK);
    _self.keyboardNavigation();
  }

  renderQueryTexts(query) {
    this._$element.SEARCH_QUERY_TEXT.text(`"${query || ""}"`);
    this._$element.SEARCH_LINK.attr('href', this._config.searchPath + query);
  }

  loadingAnimationStart() {
    this._$element.RESULTS_LIST.css('opacity', 0.3);
  }

  loadingAnimationEnd() {
    this._$element.RESULTS_LIST.css('opacity', 1);
  }

  updateQuery(query) {
    this._queryString = query || null;
    this.renderQueryTexts(query);
  }

  receiveResults(results) {
    this._results = results;
    this.loadingAnimationEnd();
    this.renderResults(results);
  }

  get shown() {
    return this.isShown;
  }

  show() {
    this._$element.RESULTS_CONTAINER.removeClass(ClassName.ANIMATE_HIDDEN);
    this._$element.RESULTS_CONTAINER.addClass(ClassName.ANIMATE_ENTERED);
    this._$element.HEADER.addClass(ClassName.RESULTS_SHOWN);
    this.isShown = true;
    if (this._config.showResultsCallback) {
      this._config.showResultsCallback();
    }
  }

  hide() {
    this._$element.RESULTS_CONTAINER.removeClass(ClassName.ANIMATE_ENTERED);
    this._$element.RESULTS_CONTAINER.addClass(ClassName.ANIMATE_HIDDEN);
    this._$element.HEADER.removeClass(ClassName.RESULTS_SHOWN);
    this.isShown = false;
    if (this._config.hideResultsCallback) {
      this._config.hideResultsCallback();
    }
  }

  init() {
    const _self = this;
    const debouncedRenderResults = _debounce(function () {
      if (_self._results) {
        _self.renderResults(_self._results);
      }
    }, 100)
    $(window).on("resize", debouncedRenderResults);
  }
};

class Search {
  constructor(context, config) {
    this._config = this._getConfig(config);
    this._context = context;
    this._isFetching = false;
    this._$element = {
      SEARCH_FORM: $(context).find(Selector.SEARCH_FORM),
      SEARCH_INPUT: $(context).find(Selector.SEARCH_INPUT),
      SEARCH_SHOW: $(context).find(Selector.SEARCH_SHOW),
      SEARCH_HIDE: $(context).find(Selector.SEARCH_HIDE),
      HIDE_IF_SHOWN: $(context).find(Selector.HIDE_IF_SHOWN),
      SHOW_IF_SHOWN: $(context).find(Selector.SHOW_IF_SHOWN),
      SEARCH_BUTTON: $(context).find(Selector.SEARCH_BUTTON),
      LOADER: $(context).find(Selector.LOADER),
    }
    this._resultsHandler = null;
    this._debouncedGetResults = null;
    this._xhrRequest = null;
    this._queryString = null;
    this.init();
  }

  getResults(queryString) {
    const _self = this;
    this._isfetching = true;
    this._xhrRequest = $.ajax({
      url: _self._config.fetchPath + queryString,
      cache: false,
      dataType: 'json',
    })
      .done(data => {
        _self._resultsHandler.receiveResults(data);
        _self._resultsHandler.show();
      })
      .always(() => {
        this._isfetching = false;
        _self.loadingAnimationEnd();
      });
  }

  loadingAnimationStart() {
    this._$element.LOADER.removeClass(ClassName.HIDDEN);
    this._$element.SEARCH_INPUT.removeClass(ClassName.SEARCH_ICON);
    this._$element.SEARCH_BUTTON.addClass(ClassName.HIDDEN);
  }

  loadingAnimationEnd() {
    const $button = this._$element.SEARCH_BUTTON;
    this._$element.LOADER.addClass(ClassName.HIDDEN);

    if (this._queryString) {
      $button.attr('data-track-props', JSON.stringify({
        "type": "Search",
        "title": "Search Input Button",
        "query": this._queryString
      }));
      $button.removeClass(ClassName.HIDDEN);
    } else {
      this._$element.SEARCH_INPUT.addClass(ClassName.SEARCH_ICON);
      $button.addClass(ClassName.HIDDEN);
    }
  }

  renderPlaceholder() {
    const $input = this._$element.SEARCH_INPUT;
    $input.attr('placeholder', this._config.inputPlaceholder.short);
    if ($input.innerWidth() > 300) {
      $input.attr('placeholder', this._config.inputPlaceholder.full);
    }
  }

  showSearchForm() {
    const _self = this;
    _self._$element.SEARCH_FORM.show();
    _self.renderPlaceholder();
    _self._$element.SEARCH_INPUT.focus();
    _self._$element.SEARCH_HIDE.removeClass(ClassName.HIDDEN);
    _self._$element.SEARCH_SHOW.addClass(ClassName.HIDDEN);
    _self._$element.HIDE_IF_SHOWN.addClass(ClassName.HIDDEN);
    _self._$element.SHOW_IF_SHOWN.removeClass(ClassName.HIDDEN);
  }

  hideSearchForm() {
    const _self = this;
    _self._$element.SEARCH_FORM.hide();
    _self._$element.SEARCH_INPUT.blur();
    _self._$element.SEARCH_HIDE.addClass(ClassName.HIDDEN);
    _self._$element.SEARCH_SHOW.removeClass(ClassName.HIDDEN);
    _self._$element.HIDE_IF_SHOWN.removeClass(ClassName.HIDDEN);
    _self._$element.SHOW_IF_SHOWN.addClass(ClassName.HIDDEN);
    _self.reset();
  }

  reset() {
    this._resultsHandler.hide();
    this._resultsHandler.updateQuery(null)
    this._$element.SEARCH_INPUT.val("");
    this._queryString = null;
    this.loadingAnimationEnd();
    if (this._debouncedGetResults) {
      this._debouncedGetResults.cancel();
    }
    if (this._xhrRequest) {
      this._xhrRequest.abort();
    }
  }

  init() {
    const _self = this;

    this.renderPlaceholder();
    $(window).on("resize", _debounce(_self.renderPlaceholder.bind(_self), 100));

    this._resultsHandler = new ResultsHandler(this._context, this._config);
    this._$element.SEARCH_SHOW.on('click', function (e) {
      e.preventDefault();
      _self.showSearchForm();
    });

    this._$element.SEARCH_HIDE.on('click', function (e) {
      e.preventDefault();
      _self.hideSearchForm();
    });


    _self._debouncedGetResults = _debounce(_self.getResults.bind(_self), _self._config.debounceDelay);

    this._$element.SEARCH_INPUT.on('keyup', function (e) {
      const queryString = $(this).val().trim();

      if (ActionKeys.indexOf(e.keyCode) === -1) {
        _self._resultsHandler.updateQuery(queryString)
        _self.loadingAnimationStart();
        if (!queryString) {
          _self.reset();
          return;
        }
        _self._resultsHandler.loadingAnimationStart();
        _self._queryString = queryString;
        _self._debouncedGetResults(queryString);
      }

      if (e.keyCode === KeyCodes.down) {
        if (_self._resultsHandler.shown) {
          _self._resultsHandler.receiveFocus(() => {
            _self._$element.SEARCH_INPUT.focus();
          });
        }
      }

    });



    $(this._context).on(eventNames.BLANKET_CLICK, function () {
      _self.reset();
    });

    $(this._context).on('keyup', function (e) {
      if (e.keyCode === KeyCodes.esc) {
        _self.reset();
      }
    });
  }


  _getConfig(config) {
    config = {
      ...Default,
      ...config
    }
    return config
  }
};

export default Search;
