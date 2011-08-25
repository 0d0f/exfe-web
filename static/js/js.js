function getBak(){
		var oall = document.getElementById("oall");
		var lightBox = document.getElementById("fBox");
		if(oall && lightBox){
			oall.style.display = "block";
			oall.style.height = document.body.scrollHeight + "px";
			lightBox.style.display = "block";
			function reset()
			{
				var d = document.documentElement;
				var x1 = d.scrollLeft;
				var sUserAgent = navigator.userAgent;
				var isChrome = sUserAgent.indexOf("Chrome") > -1 ;
				if(isChrome){
					var y1 = document.body.scrollTop;
				}
				else{
				var y1 = d.scrollTop;
				}
				var w1 = d.clientWidth;
				var h1 = d.clientHeight;


				var w = parseInt(lightBox.offsetWidth);
				var h = parseInt(lightBox.offsetHeight);
				var x = Math.ceil((w1 - w)/2) + x1;
				var y = Math.ceil((h1 - h)/2) + y1;


				lightBox.style.left = x + "px";
				lightBox.style.top = y + "px";
			}
			 window.onresize = reset;
			 window.onscroll = reset;
			 reset();

		}
}

function cancel(){
  var oall = document.getElementById("oall");
  var lightBox = document.getElementById("fBox");
  if(oall && lightBox){
		oall.style.display = "none";
		lightBox.style.display = "none";
	}
}



var $=jQuery;

$(document).ready(function(){
$('.name').mousemove(function(){
  $('#goldLink a').addClass('nameh');
    $('#myexfe').show();
});
  $('.name').mouseout(function(){
    $('#goldLink a').removeClass('nameh');
    $('#myexfe').hide();
});

  $('.newbg').mousemove(function(){
	$(this).addClass('fbg');
	$('.fbg button').show();
});

  $('.newbg').mouseout(function(){
	$(this).removeClass('fbg');
	$('button').hide();
});

  $('.bnone').mousemove(function(){
	$(this).addClass('bdown');
	$('.bdown button').show();
});

  $('.bnone').mouseout(function(){
	$(this).removeClass('bdown');
	$('dd button').hide();
});


  $('.lb').mousemove(function(){
	  $(this).addClass('labtn');
	  $('.labtn button').show();
	  $('.lb span').hide()
});

  $('.lb').mouseout(function(){
	  $(this).removeClass('labtn');
	  $('button').hide();
	  $('.lb span').show()

});

  $('.uplb').mousemove(function(){
	$(this).addClass('uabtn');
	  $('.uabtn button').show();
	  $('.uplb span').hide()
});

  $('.uplb').mouseout(function(){
	  $(this).removeClass('uabtn');
	  $('button').hide();
	  $('.uplb span').show()
});

 $('.lbl').mousemove(function(){
	$('.lt').addClass('lton');
});
  $('.lbl').mouseout(function(){
	$('.lt').removeClass('lton');
});
 $('.lbr').mousemove(function(){
	$('.rt').addClass('rton');
});
  $('.lbr').mouseout(function(){
	$('.rt').removeClass('rton');
});

 $('.addjn').mousemove(function(){
	$(this).addClass('bgrond');
	$('.bgrond button').show();
});

  $('.addjn').mouseout(function(){
	$(this).removeClass('bgrond');
	$('button').hide();
});

 $('.redate').mousemove(function(){
	$(this).addClass('bgdq');
});

  $('.redate').mouseout(function(){
	$(this).removeClass('bgdq');
});

$('.coming').mousemove(function(){
	$(this).addClass('bgcom');
});

  $('.coming').mouseout(function(){
	$(this).removeClass('bgcom');
});

});

