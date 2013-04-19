<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en" dir="ltr"> <!--<![endif]-->
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1" />
  <title>404 · EXFE.COM</title>
  <meta name="author" content="EXFE Inc." />
  <meta name="robots" content="index, follow" />
  <meta name="keywords" content="EXFE, ·X·, cross, exfee, gather, Gather a ·X·, hangout, gathering, invite, RSVP" />
  <meta name="description" content="EXFE, a utility for hanging out with friends." />
  <meta name="copyright" content="Copyright 2012 EXFE Inc" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <link rel="shortcut icon" href="/static/img/favicon.png" />
  <link rel="apple-touch-icon" href="/static/img/favicon.png" />
  <style>
    body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,textarea,p,blockquote,th,td,hr,button,img { margin: 0; padding:0; border: 0; }
    html, body { height: 100%; }
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
      font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
      font-size: 14px;
      line-height: 20px;
    }
    .container {
      display: table;
      margin: 0 auto;
      width: 340px;
      height: 100%;
    }
    .outer {
      display: table-cell;
      vertical-align: middle;
      position: relative;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="outer">
      <canvas id="circle" width="480" height="480"></canvas>
      <canvas id="mask" width="480" height="480" style="display: none;"></canvas>
      <img id="exfelogo" src="/static/img/exfe.png" style="display: none;" />
      <img id="404mask" src="/static/img/radar_mask.jpg" style="display: none;" />
    </div>
  </div>

  <script type="text/javascript">
    document.title = 'EXFE - 404';
    // http://paulirish.com/2011/requestanimationframe-for-smart-animating/
    var requestAnimFrame = function() {
      return window.requestAnimationFrame ||
        window.webkitRequestAnimationFrame ||
        window.mozRequestAnimationFrame ||
        window.oRequestAnimationFrame ||
        window.msRequestAnimationFrame ||
        function(callback, element) {
          window.setTimeout(callback, 1000 / 60);
        };
    }();

    var c = document.getElementById('circle')
      , ctx = c.getContext('2d')
      , w = 480
      , centre = w / 2
      , exfe = document.getElementById('exfelogo')
      , ew = 340
      , mask = document.getElementById('mask')
      , maskImg = document.getElementById('404mask')
      , mctx = mask.getContext('2d')
      , degress = Math.PI / 180
      , angle = 0
      , p0
      , d0
      , p1
      , d1
      , l0
      , l1
      , l2
      , l3
      , j
      , i = 0;

    animate();

    function animate () {
      draw(angle);
      angle += 3;
      requestAnimFrame(animate, 0);
    }

    function draw(angle) {
      mctx.clearRect(0, 0, w, w);
      mctx.translate(centre, centre);
      mctx.rotate(angle * degress);
      mctx.drawImage(maskImg, -maskImg.width / 2, -maskImg.width / 2);
      mctx.rotate(-angle * degress);
      mctx.translate(-centre, -centre);

      // draw IMG
      ctx.clearRect(0, 0, w, w);
      ctx.translate(centre, centre);
      ctx.drawImage(exfe, -ew / 2, -ew / 2);
      ctx.translate(-centre, -centre);

      p0 = ctx.getImageData(0, 0, w, w);
      d0 = p0.data;

      p1 = mctx.getImageData(0, 0, w, w);
      d1 = p1.data;
      l0 = d1.length >> 5;
      l1 = d1.length >> 4;
      l2 = d1.length >> 3;
      l3 = d1.length >> 2;

      for (; i < l0; ++i) {
        j = i * 4;
        d0[j + 3] *= d1[j] / 255;
      }

      for (; i < l1; ++i) {
        j = i * 4;
        d0[j + 3] *= d1[j] / 255;
      }

      for (; i < l2; ++i) {
        j = i * 4;
        d0[j + 3] *= d1[j] / 255;
      }

      for (; i < l3; ++i) {
        j = i * 4;
        d0[j + 3] *= d1[j] / 255;
      }

      ctx.putImageData(p0, 0, 0);
      d1.length = d0.length = l0 = l1 = l2 = l3 = i = j = 0;
      p1 = p0 = null;
    }
  </script>
<?php if (SITE_URL === 'https://exfe.com'): ?>
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
<?php endif; ?>
</body>
</html>
