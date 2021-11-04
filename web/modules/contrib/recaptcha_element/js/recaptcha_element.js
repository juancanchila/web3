(function ($) {
  'use strict';

  // Handle ajax forms.
  // By wrapping the actual jQuery.ajaxSubmit() function we can delay ajax form
  // submits until recaptcha elements have been provisioned.
  if ($.fn && $.fn.ajaxSubmit) {
    var originalAjaxSubmit = $.fn.ajaxSubmit;
    $.fn.ajaxSubmit = function () {
      var self = this;
      var args = arguments;
      provisionRecaptchaElements(this[0], true, function () {
        originalAjaxSubmit.apply(self, args);
      });
    };
  }

  // Handle regular form submits.
  // By using a submit event listener (on the capture phase to prevent the
  // original form submit as soon as possible) on the document we can delay form
  // submits until recaptcha elements have been provisioned.
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.requestSubmit) {
      return;
    }

    // Prevent infinite loop.
    if (form.hasAttribute('data-recaptcha-element-provisioned')) {
      form.removeAttribute('data-recaptcha-element-provisioned');
      return;
    }

    var provisioning = provisionRecaptchaElements(form, false, function () {
      form.setAttribute('data-recaptcha-element-provisioned', '');
      form.requestSubmit(event.submitter);
    });

    if (provisioning) {
      event.preventDefault();
      event.stopPropagation();
    }
  }, true);

  function provisionRecaptchaElements(form, callbackAlways, callback) {
    var recaptchaElements = form.querySelectorAll('[data-recaptcha-element]');

    if (!recaptchaElements.length) {
      if (callbackAlways) {
        callback();
      }
      return false;
    }

    waitForGoogleRecaptcha(callback, function (grecaptcha) {
      // We can't use Promises (because IE11) so use a simple alternative.
      var recaptchaElementsProvisioning = recaptchaElements.length;
      for (var i = 0; i < recaptchaElements.length; i++) {
        provisionRecaptchaElement(grecaptcha, recaptchaElements[i], function() {
          recaptchaElementsProvisioning--;
          if (recaptchaElementsProvisioning === 0) {
            callback();
          }
        });
      }
    });
    return true;
  }

  function waitForGoogleRecaptcha(skipProvisioningCallback, callback, waitCount) {
    // First, wait for grecaptcha to be loaded.
    if (typeof grecaptcha === 'undefined') {
      waitCount = waitCount || 0;
      // But only wait for a maximum of 2 seconds.
      if (waitCount > 20) {
        skipProvisioningCallback();
      }
      else {
        setTimeout(waitForGoogleRecaptcha.bind(this, skipProvisioningCallback, callback, ++waitCount), 100);
      }
    }
    // Next, wait for grecaptcha to be ready.
    else {
      grecaptcha.ready(callback.bind(this, grecaptcha));
    }
  }

  function provisionRecaptchaElement(grecaptcha, element, callback) {
    grecaptcha.execute(element.getAttribute('data-recaptcha-element-site-key'), {
      action: element.getAttribute('data-recaptcha-element-action')
    }).then(function (token) {
      element.value = token;

      callback();
    });
  }

}(jQuery));
