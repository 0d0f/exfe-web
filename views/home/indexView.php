<?php include "share/header.php" ?>
    <link type="text/css" rel="stylesheet" href="/static/css/home.css" />
    <script>
        var pageFlag = "home_page";
    </script>
</head>
<body id="home">
    <?php include "share/nav.php" ?>
    <div class="home_banner" id="home_banner">
      <div id="x_code_img">
        <img style="display:none;" class="x-sign" src="/static/images/X-sign.jpg" alt=""/>
        <div class="xci-l">
          <div class="circle-o cx">
            <div class="circle-i">
              <h3>X <span>(cross)</span></h3>
              <p class="explain">is a gathering<br />of people,<br />on purpose or not.</p>
              <p class="additional">All <span class="x-blue">X</span> are private by<br />default, accessible to<br />only attendees.</p>
            </div>
          </div>
        </div>
        <div class="xci-r">
          <div class="circle-o cexfee">
            <div class="circle-i">
              <h3>EXFE <span>[ˈ<em>ɛ</em>ksfi]</span></h3>
              <p class="explain">is an utility<br />for hanging out<br />with friends.</p>
              <p class="additional">Stop calling up every<br />one RSVP, losing in endless<br />emails and messages<br />off the point.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="home_bottom">
        <div class="gather_btn">
            <!--<a href="/x/gather"><img src="/static/images/home_gather_btn.png" alt="Gather" title="Gather" /></a>-->
            <a href="/x/gather">Gather a <span>X</span></a>
        </div>
        <div class="home_bottom_btn"></div>
    </div>
    <img id="pre_load_btn" style="display:none;" />
    <img id="pre_load_icons" style="display:none;" />
</body>
<script>
  $(function() {
    document.title = 'EXFE';
    var winSize = odof.util.getWindowSize();
    winHeight = winSize.height;
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

    $("#pre_load_btn")[0].src = "/static/images/btn.png";
    $("#pre_load_icons")[0].src = "/static/images/icons.png";

    $('.xci-l, .xci-r').hover(function (e) {
      $(this).find('.circle-o').stop(true, true).addClass('bounceIn').show();
    }, function (e) {
      $(this).find('.circle-o').delay(233).fadeOut(function () {
        $(this).removeClass('bounceIn');
      });
    });
});
</script>
</html>
