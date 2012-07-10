define(function (require) {
  var $ = require('jquery');
  $(function() {
    document.title = 'EXFE';
    var winHeight = $(document).outerHeight();
    var minH = 528, maxH = 704,
        imgminH = 450, imgmaxH = 600,
        minWh = 680, maxWh = 980,
        wi, h, hi,
        minL = 177.82, minT = 11.84,
        maxL = 175, maxT = 25, l, t;
    if (winHeight < minWh) {
      hi = 450;
      h = minH;
      l = minL;
      t = minT;
    } else if (winHeight > maxWh) {
      h = maxH;
      hi = imgmaxH;
      l = maxL;
      t = maxT;
    } else {
      var p = (winHeight - minWh) / (maxWh - minWh);
      h = minH + (maxH - minH) * p;
      hi = imgminH + (imgmaxH - imgminH) * p;
      l = minL + (maxL - minL) * p;
      t = minT + (maxT - minT) * p;
    }
    wi = hi;
    $(".x-sign").width(wi).height(hi).show().parent().css({
        'margin-left': -wi / 2,
        'margin-top': (h - hi) / 2 - 10
    });
    $('.cexfee').css({
      'right': -l, 'bottom': t
    });
    $('.cx').css({
      'left': -l, 'top': t
    });
    $('#home_banner').css('height', h);

    $('.xci-l, .xci-r').hover(function (e) {
      $(this).find('.circle-o').stop(true, true).addClass('bounceIn').show();
    }, function (e) {
      $(this).find('.circle-o').delay(233).fadeOut(function () {
        $(this).removeClass('bounceIn');
      });
    });
  });
});
