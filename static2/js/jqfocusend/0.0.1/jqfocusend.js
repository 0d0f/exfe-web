define(function (require) {

  var jQuery = require('jquery');

  !function ( $ ) {
    // fixed Mozilla Firefox/Opera focus

    $.fn.focusend = ($.browser.mozilla | $.browser.opera) ?
      function () {
        if (!this[0]) return;

        var target = this[0]
          , l = target.value.length;
        target.focus();

        // firefox, input must be show.
        try {
          target.setSelectionRange(l, l);
        } catch (e) {}

      } :
      function () {
        if (!this[0]) return;

        this[0].focus();
      };

  }( jQuery );
});
