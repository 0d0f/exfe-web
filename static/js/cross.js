function setreadonly()
{
    $('textarea[name=comment]').attr("disabled","disabled");
    $('textarea[name=comment]').val("pls login");
    $('#rsvp_yes , #rsvp_no , #rsvp_maybe').unbind("click");
    $('#rsvp_yes , #rsvp_no , #rsvp_maybe ').click(function(e){
            alert("pls login");
    });
}
$(document).ready(function(){
        $('#rsvp_loading').activity({segments: 8, steps: 3, opacity: 0.3, width: 4, space: 0, length: 5, color: '#0b0b0b', speed: 1.5});

        $('#formconversation').submit(function(e){
            //	alert("a");
            });

        $('#changersvp').click(function(e){

            $('#rsvp_options').show();
            $('#rsvp_submitted').hide();


            });

        $('#rsvp_yes , #rsvp_no , #rsvp_maybe ').click(function(e){

            $("#rsvp_loading").ajaxStart(function(){  $(this).show(); });
            $("#rsvp_loading").ajaxStop(function(){  $(this).hide(); });
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
                    $("#rsvp_loading").hide();
                    $("#rsvp_loading").unbind("ajaxStart ajaxStop");
            },

            error:function(data){
                      $("#rsvp_loading").hide();
                      $("#rsvp_loading").unbind("ajaxStart ajaxStop");
                  }
            });
            e.preventDefault();
    });

$('#formconversation').submit(function() {

        var comment=$('textarea[name=comment]').val();
        var poststr="cross_id="+cross_id+"&comment="+comment;
        $.ajax({
            type: "POST",
            data: poststr,
            url: site_url+"/conversation/save",
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
                    var name=data.response.identity.name;
                    if(name=="")
                        name=data.response.identity.external_identity;
                    var html='<li><p class="pic40"><img src="/eimgs/'+data.response.identity.avatar_file_name+'" alt=""></p> <p class="comment"><span>'+name+':</span>'+data.response.comment+'</p> <p class="times">'+data.response.created_at+'</p></li>'; 
                    $("#commentlist").prepend(html);
                    $("textarea[name=comment]").val("");
                }
            }
        }
    });
return false;
});

if(token_expired=='true')
    setreadonly();
});
