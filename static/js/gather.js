var exfee_list=new Array;
function addexfee(identity)
{
    //exfee_list.put(identity);
    alert(identity);
}
$(document).ready(function(){
        var code =null;
        $('#exfee').keypress(function(e){
            code= (e.keyCode ? e.keyCode : e.which);
            if (code == 13)
	    {
	    //a="http://api.local.exfe.com/v1/identity/get?identity="+$('#exfee').val();
	     $.ajax({
    	     type: "GET",
    	     url: "http://local.exfe.com/v1/identity/get?identity="+$('#exfee').val(), 
    	     dataType:"json",
    	     success: function(data){
		if(data.response.identity!=null)
		{
		    var identity=data.response.identity.external_identity;
		    var id=data.response.identity.id;
		    var name=data.response.identity.name;
		    var avatar_file_name=data.response.identity.avatar_file_name;
		    addexfee(identity);

		    if($('#exfee_'+id).attr("id")==null)
		    {
			if(name=="")
		    	    name=identity;
		    	var exfee_pv=$('#exfee_pv').html();
		    	exfee_pv=exfee_pv+"<div id='exfee_"+id+"'>"+"<img width=16 height=16 src='/eimgs/"+avatar_file_name+"'>"+name+"  <span id='rmexfee'>X</span></div>";
		    	$('#exfee_pv').html(exfee_pv);
		    }
		}
	     }

	     });
	    e.preventDefault();
	    }
        });

        $('#g_title').keyup(function(e){
	    $('#pv_title').html($('#g_title').val());
	});
    
        $('#g_description').keyup(function(e){
	    $('#pv_description').html($('#g_description').val());
	});

        $('#g_place').keyup(function(e){
	    var place_lines=$('#g_place').val();
	    var lines = place_lines.split("\r\n");
	    if(lines.length<=1)
	    {
		lines = place_lines.split("\n");
	    }
	    if(lines.length<=1)
	    {
		lines = place_lines.split("\r");
	    }
	    var trim_lines=new Array();
	    if(lines.length>1)
	    {
		    for (var i=0;i<lines.length;i++)
		    {
			if(lines[i]!="")
			{
			    trim_lines.push(lines[i]);
			}
		    }
		
	    }

	    if(trim_lines.length<=1)
	    {
		$('#pv_place_line1').html(place_lines);
		$('#pv_place_line2').html("");
	    }
	    else
		{
		    $('#pv_place_line1').html(trim_lines[0]);
		    var place_line2="";
		    for (var i=1;i<trim_lines.length;i++)
		    {
			if(i==trim_lines.length-1)
			    place_line2=place_line2+trim_lines[i];
			else
			    place_line2=place_line2+trim_lines[i]+"<br />";

		    }
		    $('#pv_place_line2').html(place_line2);
		}
	});
//    $('#identity').blur(function() {
//	$.ajax({
//	  type: "GET",
//	  //url: "http://localhost/exfe/index.php?class=s&action=IfIdentityExist&identity="+$('#identity').val(),
//	  url: "http://local.exfe.com/s/IfIdentityExist?identity="+$('#identity').val(),
//	  //data: "examid="+$(this).val(),
//	  dataType:"json",
//	  success: function(data){
//		  if(data!=null)
//		  {
//		    if(data.response.identity_exist=="false")
//		    {
//		    //identity
//			$('#hint').show();
//			$('#retype').show();
//			$('#displayname').show();
//			$('#resetpwd').hide();
//		    }
//		    else if(data.response.identity_exist=="true")
//		    {
//			$('#hint').hide();
//			$('#retype').hide();
//			$('#displayname').hide();
//			$('#resetpwd').show();
//		    }
//		}
//	}
//	});
//
//
//    });
});



