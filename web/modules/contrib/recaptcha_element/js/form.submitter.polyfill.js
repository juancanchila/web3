// https://gist.github.com/nuxodin/3ae174f2a6a112df3ccad22459237a91
(function () {
  var submitterSelector = 'button[type=submit], input[type=submit], input[type=image]';
  var lastSubmitter = null;

  document.addEventListener('click', function (e) {
    if (!e.target.closest) {
      return;
    }
    lastSubmitter = e.target.closest(submitterSelector);
  }, true);

  document.addEventListener('submit', function (e) {
    if (e.submitter) {
      return;
    }
    var candidates = [document.activeElement, lastSubmitter];
    for (var i = 0; i < candidates.length; i++) {
      var candidate = candidates[i];
      if (!candidate) {
        continue;
      }
      if (!candidate.form) {
        continue;
      }
      if (!candidate.matches(submitterSelector)) {
        continue;
      }
      e.submitter = candidate;
      return;
    }
    e.submitter = e.target.querySelector(submitterSelector)
  }, true);
}());
