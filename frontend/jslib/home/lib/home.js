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

      , TOY = $X.find('#js-exfe-toy')
      , TOY_SHADOW = $X.find('.exfe-toy-shadow')
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
      , rc = $circles.eq(0).width() / 2

      , timer = null
      , $titles = PH.find('div.title')
      , l = $titles.length
      , i = 0
      , j = 0

      // 可以触发行为
      , loaded = false;

    WIN.data('home', true);

    //
    calculate();

    WIN.resize(function () {
      calculate();
    });


    // animations queue
    TOY
      .addClass('exfe-init')
      .delay(500, 'starter')
      .delay(500, 'show-title')
      .delay(233, 'show-circles')
      .delay(233, 'show-start')
      .delay(233, 'ender')
      // 入场动画
      .queue('starter', function (next) {
          TOY.addClass('exfe-scaleOn');
          TOY.dequeue('show-title');
        })
      // 显示 title
      .queue('show-title', function (next) {
        TOY_SHADOW.removeClass('hide');
        PH.animate({
          opacity: 1
        }, 233);
        TOY.dequeue('show-circles');
      })
      // 显示圆圈
      .queue('show-circles', function (next) {
        CIRCLES.animate({
          opacity: 1
        }, 233);
        TOY.dequeue('show-start');
      })
      // 显示按钮
      .queue('show-start', function (next) {
        PF.animate({
          opacity: 1
        }, 233)
        TOY.dequeue('ender');
      })
      // 最后，初始化定时器等
      .queue('ender', function () {
        if (timer) {
          cleanup(timer);
        }

        createTimer();

        // clean up class
        $titles
          .eq(0)
          .addClass('t-fadeIn')
          .nextAll()
          .addClass('t-fadeOut')
          .removeClass('hide');

        loaded = true;
      })
      // 开始动画
      .dequeue('starter');

    DOC
      .off('hover.home', '#js-exfe-toy, div.circle')
      .on('hover.home', '#js-exfe-toy, div.circle', function (e) {
        if (!loaded) {
          return;
        }

        cleanup(timer);

        var $that = $(this)
          , isEnter = e.type === 'mouseenter'
          , n = $that.index('div.circle')

        if (n === -1) n = 0;
        else n++;


        if (isEnter) {

          // reennter return;
          if (n === i) return;

          prev(i);

          i = n;

          next(i);

        } else {
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

      t_position = TOY.position();

      TOY_SHADOW
        .width(nt_w)
        .height(nt_w)
        .css(t_position);

      PF.css({
        paddingTop: pf_nPaddingTop,
        paddingBottom: pf_nPaddingBottom
      });

      var c0_a = Math.sin(45 * Math.PI / 180) * nr;
      var c0_b = Math.sin(45 * Math.PI / 180) * nr;

      c0_top = t_position.top - (c0_a - nt_w / 2) - rc;
      c0_left = t_position.left - (c0_b - nt_w / 2) - rc;

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

      c3_top = t_position.top + (c3_a + nt_w / 2) - rc;
      c3_left = t_position.left - (c3_b - nt_w / 2) - rc;

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
    }

    function createTimer() {
      timer = setInterval(function () {
        i++;
        if (i === l) i = 0;
        if ((j = i - 1) < 0) j = l - 1;

        // prev
        prev(j);

        // next
        next(i);
      }, 6765);
    }

    function cleanup(timer) {
      clearInterval(timer);
      timer = null;
    }

    function prev(n) {
      $titles
        .eq(n)
        .removeClass('t-fadeIn')
        .addClass('t-fadeOut');

      if (n === 0) {
        TOY_SHADOW
          .removeClass('exfe-scaleOut');
        return;
      }

      $circles
        .eq(n - 1)
        .removeClass('fadeIn')
        .addClass('fadeOut');
    }

    function next(n) {
      $titles
        .eq(n)
        .removeClass('hide t-fadeOut')
        .addClass('t-fadeIn');

      if (n === 0){
        TOY_SHADOW
          .addClass('exfe-scaleOut');
        return;
      }

      $circles
        .eq(n - 1)
        .removeClass('fadeOut')
        .addClass('fadeIn');
    }

  });

});
