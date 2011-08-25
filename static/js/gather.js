//var exfee_list=new Array;
var new_identity_id=0;
//function addexfee(identity)
//{
//    exfee_list.push(identity);
//}

function getexfee()
{
    var result="";
    $('.exfee_item').each(function(e){
	var exfee_identity=$(this).attr("value");
	if(typeof(exfee_identity)!= 'undefined')
	{
	    result=result+exfee_identity+",";
	//    alert(exfee_identity);
	}
    });
    return result;
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
    	     url: site_url+"/v1/identity/get?identity="+$('#exfee').val(), 
    	     dataType:"json",
    	     success: function(data){
		//test
		var exfee_pv="";
		if(data.response.identity!=null)
		{
		    var identity=data.response.identity.external_identity;
		    var id=data.response.identity.id;
		    var name=data.response.identity.name;
		    var avatar_file_name=data.response.identity.avatar_file_name;
		    //addexfee(identity);
		    if($('#exfee_'+id).attr("id")==null)
		    {
			if(name=="")
		    	    name=identity;
		    	//var exfee_pv=$('#exfee_pv').html();
		    	//exfee_pv=exfee_pv+"<div class='exfee_item' id='exfee_"+id+"' value='"+identity+"'>"+"<img width=16 height=16 src='/eimgs/"+avatar_file_name+"'>"+name+"  <span id='rmexfee'>X</span></div>";

			exfee_pv=exfee_pv+'<li class="addjn"> <p class="pic20"><img src="static/images/img.jpg" alt="" /></p> <p class="smcomment"><span>'+name+'</span><span class="lb">host</span></p> <button type="button"></button> </li>';

		   // 	$('#exfee_pv').html(exfee_pv);
		    }
		}
		else
		{
			var name=$('#exfee').val();
		    	//var exfee_pv=$('#exfee_pv').html();
			new_identity_id=new_identity_id+1;
			//addexfee(name);
		    	//exfee_pv=exfee_pv+"<div class='exfee_item' id='exfee_new_"+new_identity_id+"' value='"+name+"'>"+"<img width=16 height=16 src='/eimgs/"+avatar_file_name+"'>"+name+"  <span id='rmexfee'>X</span></div>";
			exfee_pv=exfee_pv+'<li class="addjn"> <p class="pic20"><img src="static/images/'+avatar_file_name+'" alt="" /></p> <p class="smcomment"><span>'+name+'</span><span class="lb">host</span></p> <button type="button"></button> </li>';
	//	    	$('#exfee_pv').html(exfee_pv);
		}

		var inserted=false;
		$("#exfee_pv > ul").each(function( intIndex ){
		    var li=$(this).children('li');
		    if(li.length<4)
		    {
			$(this).append(exfee_pv);
			inserted=true;
		    }
		});
		if(inserted==false)
		   $("#exfee_pv").append('<ul class="samlcommentlist">'+exfee_pv+'</ul>');
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

        $('#gather_x').click(function(e){
	  //
	  $('#exfee_list').val(getexfee());
	  $('#gatherxform').submit();  
	});
        //$('#gatherxform').submit(function(e){
	//    alert("aa");
	//    return false;
	//});
    $('#datetime').datepicker({
    	duration: '',
        showTime: true,
        constrainInput: false,
	time24h: true,
	dateFormat: 'yy-mm-dd',

    	beforeShow: function(input, inst)
    	{
    	    //inst.dpDiv.css({marginTop: -input.offsetHeight + 'px', marginLeft: input.offsetWidth + 'px'});
	    $.datepicker._pos = $.datepicker._findPos(input);
	     $.datepicker._pos[0] =280;
	     $.datepicker._pos[1] = 50;
    	}
     });
	
});



