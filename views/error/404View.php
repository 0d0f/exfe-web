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
    overflow: hidden;
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
  .x {
    color: #DBEAF9 !important;
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
          <h1>Missing <span class="x">·X·</span></h1>
          <p>The page you’re requesting does not exist.</p>
        </div>
        <div class="wrapper">
          <canvas id="circle" width="480" height="480"></canvas>
          <canvas id="mask" width="480" height="480" style="display: none;"></canvas>
          <img id="exfelogo" src="/static/img/exfe.png" style="display: none;" />
          <img id="404mask" src="/static/img/radar_mask.jpg" style="display: none;" />
        </div>
      </section>
    </div>
  </div>
  <!--/Container }}}-->

  <noscript>EXFE.com can't load if JavaScript is disabled</noscript>

  <?php include 'share/footer.php'; ?>
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
</body>
</html>
