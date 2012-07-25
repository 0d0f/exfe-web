define(function (require) {
  var $ = require('jquery');

  $(function () {

    var $DOC = $(document)
      // titles
      , $titles = $('.page-header  div.title')
      // cirles
      , $circles = $('div.circle')
      , i = 0
      , j = 0
      , l = $circles.length
      , isNotFirst = false
      , timer;

    $DOC.on('hover', 'div.circle', function (e) {
      var $that = $(this)
        , isEnter = e.type === 'mouseenter'
        , n = $that.index();

      clearInterval(timer);
      timer = null;

      if (isEnter) {

        i--;
        if (i < 0) i = l - 1;
        if ((j = i - 1) < 0) j = l - 1;

        if (isNotFirst) {
          $titles
            .eq(i)
            .removeClass('tanimate');
            //.stop(true, true)
            //.fadeOut();

          $circles
            .eq(i)
            .removeClass('fadeIn')
            .addClass('fadeOut');
        }

        i = n;

        $titles
          .eq(i)
          .addClass('tanimate');
          //.stop(true, true)
          //.fadeIn();

        $that
          .removeClass('fadeOut')
          .addClass('fadeIn');

        i++;
      }
      else {
        if (!$that.hasClass('fadeIn')) return;
        $titles
          .eq(n)
          .removeClass('tanimate');
          //.stop(true, true)
          //.fadeOut();

        $that
          .removeClass('fadeIn')
          .addClass('fadeOut');
        if (!timer) createTimer();
      }
    });

    createTimer();

    function createTimer() {
      timer = setInterval(function () {
        if (i === l) i = 0;
        if ((j = i - 1) < 0) j = l - 1;

        if (isNotFirst) {
          $titles
            .eq(j)
            .removeClass('tanimate');
            //.stop(true, true)
            //.fadeOut();

          $circles
            .eq(j)
            .removeClass('fadeIn')
            .addClass('fadeOut');
        }

        $titles
          .eq(i)
          .addClass('tanimate');
          //.stop(true, true)
          //.fadeIn();

        $circles
          .eq(i)
          .removeClass('fadeOut')
          .addClass('fadeIn');

        !isNotFirst && (isNotFirst = true);

        i++;
      }, 2584/*1597*/);
    }


  });

});
