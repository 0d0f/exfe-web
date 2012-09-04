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
      background-image: -moz-linear-gradient(center top , #5A7A95, #C3E3FD);
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
    var c = document.getElementById('circle')
      , ctx = c.getContext('2d')
      , centre = c.width / 2
      , img = document.createElement('img')
      , mask = document.getElementById('mask')
      , maskImg = document.getElementById('404mask')
      , mctx = mask.getContext('2d')
      , degress = Math.PI / 180
      , angle = 0;

    img.src = '/static/img/exfe.png';

    img.onload = function () {
      setInterval(function () {
        draw(angle);
        angle += 4;
      }, 20);
    };

    function rads(x) {
      return Math.PI * x / 180;
    }

    function draw(angle) {
      mctx.clearRect(0, 0, 480, 480);
      mctx.save();
      mctx.beginPath();
      mctx.moveTo(240, 240);
      mctx.arc(240, 240, 240, rads(-120 + angle), angle, false);
      mctx.closePath();
      mctx.clip();
      mctx.restore();

      mctx.translate(centre, centre);
      mctx.rotate(angle * degress);
      mctx.drawImage(maskImg, -maskImg.width / 2, -maskImg.width / 2);
      mctx.rotate(-angle * degress);
      mctx.translate(-centre, -centre);
      mctx.restore();

      ctx.clearRect(0, 0, 480, 480);
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

      var p0 = ctx.getImageData(0, 0, 480, 480)
        , d0 = p0.data
        , p1 = mctx.getImageData(0, 0, 480, 480)
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
