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
        <img class="x-sign" src="/static/images/X-sign.jpg" alt=""/>
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
              <h3>EXFE <span>[ˈɛksfi]</span></h3>
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
    var minH = 349, maxH = 465,
        minWh = 680, maxWh = 960,
        w = 470, h;
    if (winHeight < minWh) {
      h = minH;
    } else if (winHeight > maxWh) {
      h = maxH;
    } else {
      h = minH + (maxH - minH) * ((winHeight - minWh) / (maxWh - minWh));
    }
    w *= h / maxH;
    w = Math.ceil(w);
    $(".x-sign").width(w).height(h).parent().css('margin-left', -w/2);
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
