define(function (require) {

  var $ = require('jquery');
  var isFF = $.browser.mozilla;

  $.fn.lastfocus = isFF ?
    function () {
      return this.each(function () {
        var l = this.value.length;
        this.focus();
        this.setSelectionRange(l, l);
      });
    } :
    $.fn.focus;

});
