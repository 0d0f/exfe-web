define(function (require) {
  var $ = require('jquery');

  $(function () {


    var WIN = $(window)
      , DOC = $(document)
      , winMin = 680
      , winMax = 980
      , winDist = winMax - winMin

      , $X = $('.x-home')

      , PH = $X.find('.page-header')
      , ph_minPaddingTop = 40
      , ph_maxPaddingTop = 140
      , ph_paddingTopDist = ph_maxPaddingTop - ph_minPaddingTop
      , ph_nPaddingTop

      , TOY = $('#js-exfe-toy')
      // 原始边长
      , t_w = TOY.width()
      //, t_h = TOY.height()
      // 图片最小占原来的 80%
      , proportion = .8
      // 图片新边长
      , nt_w
      , t_position

      , PF = $X.find('.page-footer')
      , pf_minPaddingTop = 64
      , pf_maxPaddingTop = 108
      , pf_paddingTopDist = pf_maxPaddingTop - pf_minPaddingTop
      , pf_nPaddingTop
      , pf_minPaddingBottom = 60
      , pf_maxPaddingBottom = 132
      , pf_paddingBottomDist = pf_maxPaddingBottom - pf_minPaddingBottom
      , pf_nPaddingBottom

      , r = 288
      , rmin = r * proportion
      , nr

      , CIRCLES = $X.find('div.circles')
      , $circles = CIRCLES.find('div.circle')
      , c0_top
      , c0_left
      , c3_top
      , c3_left

      , timer
      , $titles = PH.find('div.title')
      , i = 0
      , j = 0
      , l = $titles.length
      , state = true;

    WIN.data('home', true);

    //
    calculate();

    WIN.resize(function () {
      calculate();
    });

    setTimeout(function () {
      TOY.addClass('exfe-scaleOn');
      /*
      var bound = false;
       * wtf? webkit fired twice , mozilla fired thrice, FUCK!
      TOY.on('webkitTransitionEnd oTransitionEnd msTransitionEnd transitionEnd transitionend', function cb(e) {
        if (bound) {
          TOY.off('webkitTransitionEnd', cb);
          return;
        }
        bound = true;

        PH.animate({
          opacity: 1
        }, 250);
        CIRCLES.animate({
          opacity: 1
        }, 250);
        PF.animate({
          opacity: 1
        }, 250)
      });
      return false;
      */
     setTimeout(function () {
       $titles
        .addClass('opacity0')
        .removeClass('hide');

        next(i);

        PH.animate({
          opacity: 1
        }, 250);
        CIRCLES.animate({
          opacity: 1
        }, 250);
        PF.animate({
          opacity: 1
        }, 250, function () {
          if (WIN.data('home')) {
            clearInterval(timer);
            timer = null;
          }
          createTimer();
        })
     }, 500);
    }, 501);

    DOC
      .off('hover.home', '#js-exfe-toy, div.circle')
      .on('hover.home', '#js-exfe-toy, div.circle', function (e) {
        var $that = $(this)
          , isEnter = e.type === 'mouseenter'
          , n = $that.index('div.circle');

        $titles.removeClass('tanimate1 tanimate');
        state = false;

        if (n === -1) n = 0;
        else n++;

        clearInterval(timer);
        timer = null;

        if (isEnter) {

          // prev
          prev(j+1);

          i = n;

          // next
          next(n);

        }
        else {
          // current
          prev(i = n);

          if (!timer) createTimer();
        }
      });

    DOC
    .off('click.home')
    .on('click.home', function (e) {
      var $e = $(e.target);
      if (!$e.hasClass('gather-wrapper')
          && !$e.parent().hasClass('gather-wrapper')
          && !$('.modal-id').length && $.contains($('.modal-id'), $e)
         ) {
        $('.x-signin').trigger('click.dialog.data-api');
      }
    });

    // Helpers
    // ------------------
    function calculate() {
      var winHeight = WIN.height();

      if (winMin > winHeight) {
        ph_nPaddingTop = ph_minPaddingTop;

        nt_w = t_w * proportion;

        pf_nPaddingTop = pf_minPaddingTop;
        pf_nPaddingBottom = pf_minPaddingBottom;

        nr = rmin;
      } else if (winMin <= winHeight && winMax > winHeight) {
        var pr = (winHeight - winMin) / winDist;
        ph_nPaddingTop = ph_minPaddingTop + ph_paddingTopDist * pr;

        nt_w = t_w * proportion + t_w * (1 - proportion) * pr;

        pf_nPaddingTop = pf_minPaddingTop + pf_paddingTopDist * pr;
        pf_nPaddingBottom = pf_minPaddingBottom + pf_paddingBottomDist * pr;

        nr = r * proportion + r * (1 - proportion) * pr;
      } else {
        ph_nPaddingTop = ph_maxPaddingTop;

        nt_w = t_w;

        pf_nPaddingTop = pf_maxPaddingTop;
        pf_nPaddingBottom = pf_maxPaddingBottom;

        nr = r;
      }

      PH.css({
        paddingTop: ph_nPaddingTop
      });

      TOY
        .width(nt_w)
        .height(nt_w);

      PF.css({
        paddingTop: pf_nPaddingTop,
        paddingBottom: pf_nPaddingBottom
      });

      t_position = TOY.position();

      var c0_a = Math.sin(45 * Math.PI / 180) * nr;
      var c0_b = Math.sin(45 * Math.PI / 180) * nr;

      c0_top = t_position.top - (c0_a - nt_w / 2) - 25.5;
      c0_left = t_position.left - (c0_b - nt_w / 2) - 25.5;

      $circles
        .eq(0)
        .css({
          left: c0_left,
          top: c0_top
        });

      $circles
        .eq(2)
        .css({
          right: c0_left,
          bottom: c0_top
        });

      var c3_a = Math.sin(30 * Math.PI / 180) * nr;
      var c3_b = Math.cos(30 * Math.PI / 180) * nr;

      c3_top = t_position.top + (c3_a + nt_w / 2) - 25.5;
      c3_left = t_position.left - (c3_b - nt_w / 2) - 25.5;

      $circles
        .eq(3)
        .css({
          left: c3_left,
          top: c3_top
        });

      $circles
        .eq(1)
        .css({
          right: c3_left,
          bottom: c3_top
        });

      TOY.addClass('exfe-init');
    }

    function createTimer() {
      timer = setInterval(function () {
        if (i === l) i = 0;
        if ((j = i - 1) < 0) j = l - 1;

        if (state && i === 1) state = false;

        // prev
        prev(j);

        // next
        next(i);

        i++;
      }, 6765/*4181*//*2584*//*1597*/);
    }

    function prev(n) {
      //console.log('prev', n, state);
      $titles.not($titles.get(n)).removeClass('tanimate1 tanimate');
      $titles
        .eq(n)
        .addClass('tanimate1')
        .removeClass('tanimate');

      if (n === 0 || state) {
        if (n === 0) {
          TOY.removeClass('exfe-scale2');
        }
        return;
      }
      $circles
        .eq(n - 1)
        .removeClass('fadeIn')
        .addClass('fadeOut');
    }

    function next(n) {
      //console.log('next', n);
      $titles
        .eq(n)
        .removeClass('hide tanimate1')
        .addClass('tanimate');

      if (n === 0){
        if (n === 0 && !state) {
          TOY.addClass('exfe-scale2');
        }
        return;
      }
      $circles
        .eq(n - 1)
        .removeClass('fadeOut')
        .addClass('fadeIn');
    }

  });

});
