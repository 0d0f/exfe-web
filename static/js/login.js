$(document).ready(function(){
    $('#identity').blur(function() {
	$.ajax({
	  type: "GET",
	  url: site_url+"/s/IfIdentityExist?identity="+$('#identity').val(),
	  dataType:"json",
	  success: function(data){
		  if(data!=null)
		  {
		    if(data.response.identity_exist=="false")
		    {
		    //identity
			$('#hint').show();
			$('#retype').show();
			$('#displayname').show();
			$('#resetpwd').hide();
		    }
		    else if(data.response.identity_exist=="true")
		    {
			$('#hint').hide();
			$('#retype').hide();
			$('#displayname').hide();
			$('#resetpwd').show();
		    }
		}
	}
	});


    });
});


