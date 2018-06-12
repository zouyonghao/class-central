const Follow = require("./cc.follow.js").default;
const Profile = require("./cc.profile.js").default;

const Onboarding = (function() {
    let eventsBound = false;
    const steps = {
      loggedIn: {
        subjects: {
          url: "/user/onboarding/follow-subjects",
          title: "Follow Subjects",
          completedCopy: "Pick more subjects or click Next to continue",
          completedCTA: "Next",
          skipCTA: "Skip",
        },
        providers: {
          url: "/user/onboarding/follow-institutions",
          title: "Follow Providers",
          completedCopy: "Pick more providers or click Next to continue",
          completedCTA: "Next",
          skipCTA: "Skip",
        },
        courses: {
          url: "/user/onboarding/follow-courses",
          title: "Follow Courses",
          completedCopy: "Pick more courses or click Next to continue",
          completedCTA: "Next",
          skipCTA: "Skip",
        },
        profile: {
          url: "/user/onboarding/profile",
          title: "Profile",
          completedCopy: "Click Save to update your profile",
          completedCTA: "Save & Finish",
          skipCTA: "Skip & Finish",
        },
      },
      loggedOut: {
        subjects: {
          url: "/next-course/pick-subjects",
          title: "Follow Subjects",
          completedCopy: "Pick more subjects or click Next to continue",
          completedCTA: "Next",
          skipCTA: "Skip",
        },
        providers: {
          url: "/next-course/pick-providers",
          title: "Follow Providers",
          completedCopy: "Pick more providers or click Generate to continue",
          completedCTA: "Generate Recommendations",
          skipCTA: "Skip & Generate",
        },
      },
    };

    function init() {
      $( document).ready(function(event) {
        $('[data-action="meet-your-next-course"]').on("click", function(event) {
          event.preventDefault();
          showStep("loggedOut", "subjects");
        });
      });
    }

    function bindEvents() {
      if (eventsBound) {
        return;
      }

      eventsBound = true;
      $(document).on("followingChanged", () => {
        const currentStep = extractStep($("[data-modal]").data("modal"));
        updateFooter(currentStep.scope, currentStep.name);
      });

      $(document).on("click", "[data-modal] [data-ob-action-next]", function(event) {
        const currentStep = extractStep($("[data-modal]").data("modal"));
        const nextStep = extractStep($(event.target).data("ob-action-next"));
        const $modalEl = $("[data-modal]");
        const $nextButtonEl = $modalEl.find("[data-ob-action-next]");

        if ($nextButtonEl.html().indexOf("Skip") === -1) {
          $(event.currentTarget).html("Saving...");
        }

        gaEvent(currentStep.scope, currentStep.data.title, "Next");
        setTimeout(function() {
          showStep(nextStep.scope, nextStep.name);
        }, 0);
      });
      $(document).on("click", "[data-modal] [data-ob-action-back]", function(event) {
        const currentStep = extractStep($("[data-modal]").data("modal"));
        const nextStep = extractStep($(event.target).data("ob-action-back"));

        gaEvent(currentStep.scope, currentStep.data.title, "Back");
        showStep(nextStep.scope, nextStep.name);
      });
    }

    function extractStep(str) {
      const data = str.split(".");
      return {
        scope: data[0],
        name: data[1],
        data: steps[data[0]][data[1]],
      };
    }

    function showStep(scope, name, callback) {
      bindEvents();

      if (name === "generate") {
        return showLoadingScreenStep(scope);
      }
      if (name === "saveProfile") {
        return saveProfile();
      }

      if (!CC.Class.Modal.isOpen()) {
        CC.Class.Modal.open();
      }
      const step = steps[scope][name];
      $("[data-modal]").data("modal", `${scope}.${name}`);
      gaEvent(scope, step, "Shown");
      $.ajax({
        url: step.url,
        cache: false,
        dataType: "json",
        success: function(response) {
          if (window.localStorage) {
            window.localStorage.setItem("nextcourse", true);
          }
          CC.Class.Modal.content({ body: response.modal, closeButton: "Not right now, thanks." });
          Follow.init();
          if (name === "profile") {
            bindProfileEvents();
          } else {
            updateFooter(scope, name);
          }
        },
        async: false
      });
    }

    function updateFooter(stepScope, stepName) {
      const step = steps[stepScope][stepName];

      const $modalEl = $("[data-modal]");
      const $nextButtonEl = $modalEl.find("[data-ob-action-next]");
      const $meterBarEl = $modalEl.find("[data-ob-meterbar] span");
      const $copyEl = $modalEl.find("[data-ob-info]");

      const numberFollows = $modalEl.find('.btn-follow-item.active').length;
      const percentage = numberFollows * 100 / 5;
      $meterBarEl.width(`${percentage > 100 ? 100 : percentage}%`);

      if (numberFollows >= 5) {
        $nextButtonEl.removeClass('btn-white').addClass('btn-blue').text(step.completedCTA);
        $copyEl.html(step.completedCopy);
      } else {
        $nextButtonEl.removeClass('btn-blue').addClass('btn-white').text(step.skipCTA);
        const followsLeft = 5 - numberFollows;

        if (followsLeft == 1) {
          $copyEl.html("One more to go...");
        } else {
          $copyEl.html(`Pick ${followsLeft} or more ${stepName}`);
        }
      }
    }

    function bindProfileEvents() {
      const $modalEl = $("[data-modal]");
      const $nextButtonEl = $modalEl.find("[data-ob-action-next]");
      const $meterBarEl = $modalEl.find("[data-ob-meterbar] span");
      const $copyEl = $modalEl.find("[data-ob-info]");

      const percentage = Profile.profileCompletenessPercentage();
      $meterBarEl.width(`${percentage > 100 ? 100 : percentage}%`);

      const update = () => {
        const percentage = Profile.profileCompletenessPercentage();
        $meterBarEl.width(`${percentage > 100 ? 100 : percentage}%`);
        $copyEl.html("Click Save to update your profile");
        $nextButtonEl.removeClass('btn-white').addClass('btn-blue').text("Save and Finish");
      }

      $('#modal-onboarding-profile form').change(update);
      $('#onboarding-profile-modal form :input').each(function() {
        $(this).focusout(update);
      });
    }

    function showLoadingScreenStep(scope) {
      gaEvent(scope, "Loading Screen", "Shown");
      $.ajax({
        url: '/next-course/loading-screen',
        cache: false,
        dataType: "json",
        success: function(response) {
          CC.Class.Modal.content({ body: response.modal, closeButton: "Not right now, thanks." });

          const $modalEl = $("[data-modal]");
          const $meterBarEl = $modalEl.find("[data-ob-meterbar] span");
          const $copyEl = $modalEl.find("[data-ob-info]");
          let width = 0;

          const loading = setInterval(function() {
            width = width + 20;
            $meterBarEl.width(`${width}%`);
            if (width >= 100) {
              clearInterval(loading);
              window.location = '/course-recommendations';
            }
          }, 400);
        },
        async: false
      })
    }

    function saveProfile() {
      ga('send','event','Onboarding Nav', 'Profile', 'Update');
      if (Profile.validateAndSaveProfile()) {
        location.reload();
      } else {
        showStep("loggedIn", "profile");
      }
    }

    function gaEvent(scope, title, event) {
      ga('send', 'event', scope === "loggedIn" ? "Onboarding Nav" : "Meet your next course", title, event);
    }

    init();

    return {
      init: init,
      showStep,
      showPickSubjectsStep: () => showStep("loggedOut", "subjects"),
      showPickProvidersStep: () => showStep("loggedOut", "providers"),
      showLoadingScreenStep: showLoadingScreenStep
    }
})();

export default Onboarding;
