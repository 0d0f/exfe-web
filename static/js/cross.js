if(show_idbox!="")
{

  var html=showdialog(show_idbox);
  $(html).modal({
      position: ['20',]
  });

}

$(document).ready(function(){

    $('#formconversation').submit(function(e){
//	alert("a");
    });

    $('#changersvp').click(function(e){

	$('#rsvp_options').show();
	$('#rsvp_submitted').hide();


    });

    $('#rsvp_yes , #rsvp_no , #rsvp_maybe ').click(function(e){
    
	    var poststr="cross_id="+cross_id+"&rsvp="+$(this).attr("value");
	    $.ajax({
	      type: "POST",
	      data: poststr,
	      url: site_url+"/rsvp/save",
	      dataType:"json",
	      success: function(data){
	    	if(data!=null)
	    	{
	    	    if(data.response.success=="false")
	    	    {
		    
			//$('#pwd_hint').html("<span>Error identity </span>");
	    		//$('#login_hint').show();
	    	    }
	    	    else if(data.response.success=="true")
	    	    {
			if(data.response.state=="yes")
			{
			    $("li#exfee_"+data.response.identity_id+" > .cs > em").removeClass("c2");
			    $("li#exfee_"+data.response.identity_id+" > .cs > em").addClass("c1");
			}
			else if(data.response.state=="no" || data.response.state=="maybe")
			{
			    $("li#exfee_"+data.response.identity_id+" > .cs > em").removeClass("c1");
			    $("li#exfee_"+data.response.identity_id+" > .cs > em").addClass("c2");
			}
			$('#rsvp_options').hide();
			$('#rsvp_submitted').show();
	    	    }
	    	}
	    }
	    });
    e.preventDefault();	
    });
});
