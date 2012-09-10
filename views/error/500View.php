<?php include "share/header.php" ?>
<style>
  body {
    background-attachment: fixed;
    background-color: #84A4BF;
    background-image: -moz-linear-gradient(top, #5A7A95, #C3E3FD);
    background-image: -ms-linear-gradient(top, #5A7A95, #C3E3FD);
    background-image: -moz-linear-gradient(center top , #5A7A95, #C3E3FD);
    background-image: -webkit-linear-gradient(top, #5A7A95, #C3E3FD);
    background-image: -o-linear-gradient(top, #5A7A95, #C3E3FD);
    background-image: linear-gradient(top, #5A7A95, #C3E3FD);
    background-repeat: repeat-x;
  }
  #app-main {
    text-align: center;
  }
  .header {
    margin-top: 24px;
    color: #ffffff;
  }
  .header h1 {
    font-size: 34px;
    font-weight: 300;
    line-height: 40px;
    padding-bottom: 20px;
  }
  .header p {
    font-size: 21px;
    line-height: 27px;
    padding-bottom: 20px;
    margin: 0;
  }
  .wrapper {
      margin: 72px auto 0;
      width: 340px;
      height: 100%;
    }
  .inner {
    -webkit-perspective: 600px;
    -moz-perspective: 600px;
    -ms-perspective: 600px;
    -o-perspective: 600px;
    perspective: 600px;
    position: relative;
    width: 340px;
    height: 340px;
  }
  #exfe {
    /*
    -moz-transition: -moz-transform 144ms ease 0s;
    -webkit-transition: -webkit-transform 144ms ease 0s;
    */
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
  }
  img {max-width: none;}

  #bubble {
    -webkit-transform: translateZ(70px) scale(0.854166666, 0.854166666);
    -moz-transform: translateZ(70px) scale(0.854166666, 0.854166666);
    -os-transform: translateZ(70px) scale(0.854166666, 0.854166666);
    -o-transform: translateZ(70px) scale(0.854166666, 0.854166666);
    transform: translateZ(70px) scale(0.854166666, 0.854166666);
    position: absolute;
    top: -70px;
    left: -70px;
  }
</style>
</head>
<body>
  <?php include "share/nav.php" ?>

  <!-- Container {{{-->
  <div class="container" id="app-container">
    <div role="main" id="app-main">
      <section id="error404" class="x-error404">
        <div class="header">
          <h1>Lost in the Crowd</h1>
          <p>Sorry, something is technically wrong, we’re fixing it up.</p>
        </div>
        <div class="wrapper">
          <div class="inner">
            <img id="exfe" src="/static/img/exfe.png" width="340" height="340" alt="" />
            <img id="bubble" src="/static/img/500_bubble.png" width="480" height="480" alt="" />
          </div>
        </div>
      </section>
    </div>
  </div>
  <!--/Container }}}-->

  <noscript>EXFE.com can't load if JavaScript is disabled</noscript>
  <script type="text/javascript" src="/static/js/jquery/1.8.0/jquery.min.js"></script>
  <script type="text/javascript">
    document.title = 'EXFE - Server Error';
    $('#app-menubar, #app-signin').removeClass('hide');

    var centerX, centerY, pageX, pageY
      , $exfe = $('#exfe')
      , $bubble = $('#bubble')
      , w = 340 , centre = w / 2
      , offset = $exfe.offset()
      , dx , dy , tx = 0 , ty = 0
      , rx = 0 , ry = 0 , st, sr, sr2, ss;

    centerX = offset.left + centre;
    centerY = offset.top + centre;

    $(window).resize(function () {
      offset = $exfe.offset();
      centerX = offset.left + centre;
      centerY = offset.top + centre;
    });

    $(document).on('mousemove touchstart touchmove', function (e) {
      e.preventDefault(); // iPAD, 防止上下滑动
      if (e.type === 'touchmove' || e.type === 'touchstart') {
        var touch = e.originalEvent.touches[0];
        pageX = touch.pageX;
        pageY = touch.pageY;
      }
      else {
        pageX = e.pageX;
        pageY = e.pageY;
      }

      dx = pageX - centerX;
      dy = pageY - centerY;

      tx = -dx / centerX * 5;
      ty = -dy / centerY * 5;

      rx = dx / centerX * 8;
      ry = -dy / centerY * 8;

      sr = 'rotateY(' + rx + 'deg) rotateX(' + ry + 'deg)';
      sr2 = 'rotateY(' + -rx + 'deg) rotateX(' + -ry + 'deg)';
      st = 'translate(' + tx + 'px, ' + ty + 'px)';
      ss = sr2 + ' ' + st;

      $bubble.css({
        '-webkit-transform': 'translateZ(70px) scale(0.854166666, 0.854166666) ' + ss,
        '-moz-transform': 'translateZ(70px) scale(0.854166666, 0.854166666) ' + ss,
        '-ms-transform': 'translateZ(70px) scale(0.854166666, 0.854166666) ' + ss,
        // wtf? Opera css3D
        '-o-transform': st,
        'transform': 'translateZ(70px) scale(0.854166666, 0.854166666) ' + ss
      });

      $exfe.css({
        '-webkit-transform': sr,
        '-moz-transform': sr,
        '-ms-transform': sr,
        '-o-transform': sr,
        'transform': sr
      });
    });
  </script>

<?php
// Google Analytics
if (SITE_URL === 'https://exfe.com') {
echo <<<EOT
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-31794223-2']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
EOT;
}

if (SITE_URL !== 'https://exfe.com') {
  echo "<script>document.getElementsByTagName('body')[0].style.borderTop = '6px solid #D32232';</script>";
}

?>

</body>
</html>

