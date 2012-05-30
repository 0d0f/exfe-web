define(function (require) {
  // 解决 mozilla firefox input 聚焦是光标 focus 到前面的bug

  var $ = require('jquery');
  var isFF = $.browser.mozilla;

  $.fn.lastfocus = isFF ?
    function () {
      var target = this[0]
        , l = target.value.length;
      target.focus();
      target.setSelectionRange(l, l);
    } :
    function () {
      this[0].focus();
    };

});
