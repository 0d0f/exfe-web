define(function (require) {
  // 解决 mozilla firefox / opera input 聚焦是光标 focus 到前面的bug

  var $ = require('jquery');
  var isFFOrOP = $.browser.mozilla | $.browser.opera;

  $.fn.lastfocus = isFFOrOP ?
    function () {
      if (!this[0]) return;
      var target = this[0]
        , l = target.value.length;
      target.focus();
      target.setSelectionRange(l, l);
    } :
    function () {
      if (!this[0]) return;
      this[0].focus();
    };

});
