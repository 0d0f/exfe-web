define(function (require) {
  // 解决 mozilla firefox input 聚焦是光标 focus 到前面的bug

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
    function () {
      return this.each(function () {
        this.focus();
      });
    };

});
