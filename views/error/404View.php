<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>404</title>
  <style>
    body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,textarea,p,blockquote,th,td,hr,button,img { margin: 0; padding:0; border: 0; }

    html, body {
      height: 100%;
    }

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

    h1 {
      position: absolute;
      left: 30px;
      top: 250px;
    }

    /*
    #mask, #circle {
      position: absolute;
      margin-top: -170px;
    }
    */

    #mask {
    }

    #circle {
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="outer">
      <canvas id="circle" width="480" height="480"></canvas>
      <canvas id="mask" width="480" height="480" style="display: none;"></canvas>
      <img id="404mask" src="/static/img/radar_mask.jpg" style="display: none;" />
    </div>
  </div>

  <script>
    // http://paulirish.com/2011/requestanimationframe-for-smart-animating/
    window.requestAnimFrame = (function() {
      return window.requestAnimationFrame ||
      window.webkitRequestAnimationFrame ||
      window.mozRequestAnimationFrame ||
      window.oRequestAnimationFrame ||
      window.msRequestAnimationFrame ||
      function(callback, element) {
        window.setTimeout(callback, 1000 / 60);
      };
    })();

    var c = document.getElementById('circle')
      , ctx = c.getContext('2d')
      , w = 480
      , centre = w / 2
      , img = document.createElement('img')
      , mask = document.getElementById('mask')
      , maskImg = document.getElementById('404mask')
      , mctx = mask.getContext('2d')
      , degress = Math.PI / 180
      , angle = 0;

    img.src = '/static/img/exfe.png';

    img.onload = function () {
      function animate () {
        draw(angle);
        angle += 3;
        window.requestAnimFrame(animate, 0);
      }
      animate();

      /*
      setInterval(function () {
        draw(angle);
        angle += 3;
      }, 20);
       */
    };

    function rads(x) {
      return Math.PI * x / 180;
    }

    function draw(angle) {
      mctx.width = mctx.height = w;
      mctx.clearRect(0, 0, w, w);
      mctx.save();
      mctx.beginPath();
      mctx.moveTo(centre, centre);
      mctx.arc(centre, centre, centre, rads(-120 + angle), angle, false);
      mctx.closePath();
      mctx.clip();
      mctx.restore();

      mctx.translate(centre, centre);
      mctx.rotate(angle * degress);
      mctx.drawImage(maskImg, -maskImg.width / 2, -maskImg.width / 2);
      mctx.rotate(-angle * degress);
      mctx.translate(-centre, -centre);
      mctx.restore();

      ctx.clearRect(0, 0, w, w);
      ctx.save();
      ctx.beginPath();
      ctx.moveTo(centre, centre);
      ctx.arc(centre, centre, centre, rads(-120 + angle), angle, false);
      ctx.closePath();
      ctx.clip();
      ctx.restore();

      // draw IMG
      ctx.translate(centre, centre);
      ctx.drawImage(img, -img.width / 2, -img.width / 2);
      ctx.translate(-centre, -centre);
      ctx.restore();

      var p0 = ctx.getImageData(0, 0, w, w)
        , d0 = p0.data
        , p1 = mctx.getImageData(0, 0, w, w)
        , d1 = p1.data
        , width = p1.width
        , height = p1.height;

      for (var i = 0, l = d1.length; i < l; i += 4) {
        d0[i + 3] *= d1[i] / 255;
      }
      ctx.putImageData(p0, 0, 0);
    }

  </script>
</body>
</html>
