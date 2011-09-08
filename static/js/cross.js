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

        window.submitting = false;

        $('textarea[name=comment]').keydown(function(e){
            if (e.keyCode === 13 && !e.shiftKey) {
                $('#formconversation').submit();
            }
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
                success: function(data) {
                    if (data != null) {
                        if (data.response.success === 'true') {
                            switch (data.response.state) {
                                case 'yes':
                                    $("li#exfee_"+data.response.identity_id+" > .cs > em").removeClass("c2");
                                    $("li#exfee_"+data.response.identity_id+" > .cs > em").addClass("c1");
                                    if (myrsvp !== 1) {
                                        $('.bignb').html(parseInt($('.bignb').html()) + 1);
                                    }
                                    break;
                                case 'no':
                                case 'maybe':
                                    $("li#exfee_"+data.response.identity_id+" > .cs > em").removeClass("c1");
                                    $("li#exfee_"+data.response.identity_id+" > .cs > em").addClass("c2");
                                    if (myrsvp === 1) {
                                        $('.bignb').html(parseInt($('.bignb').html()) - 1);
                                    }
                            }
                            myrsvp = {yes : 1, no : 2, maybe : 3}[data.response.state];
                            $('#rsvp_options').hide();
                            $('#rsvp_submitted').show();
                        } else {
                            //$('#pwd_hint').html("<span>Error identity </span>");
                            //$('#login_hint').show();
                        }
                    }
                    $("#rsvp_loading").hide();
                    $("#rsvp_loading").unbind("ajaxStart ajaxStop");
            },
            error : function(data) {
                $("#rsvp_loading").hide();
                $("#rsvp_loading").unbind("ajaxStart ajaxStop");
            }
        });
        e.preventDefault();
    });

$('#formconversation').submit(function() {

    if (submitting) { return false; }

    submitting = true;

    var comment=$('textarea[name=comment]').val();
    var poststr="cross_id="+cross_id+"&comment="+comment;
    $('textarea[name=comment]').activity({outside: true, align: 'right', valign: 'top', padding: 5, segments: 10, steps: 2, width: 2, space: 0, length: 3, color: '#000', speed: 1.5});
    $('#post_submit').css('background', 'url("/static/images/enter_gray.png")');

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
                    var html='<li><p class="pic40"><img src="/eimgs/80_80_'+data.response.identity.avatar_file_name+'" alt=""></p> <p class="comment"><span>'+name+':</span>'+data.response.comment+'</p> <p class="times">'+data.response.created_at+'</p></li>'; 
                    $("#commentlist").prepend(html);
                    $("textarea[name=comment]").val("");
                }
            }
            $('textarea[name=comment]').activity(false);
            $('#post_submit').css('background', 'url("/static/images/enter.png")');
            submitting = false;
        },
        error: function(date){
            $('textarea[name=comment]').activity(false);
            $('#post_submit').css('background', 'url("/static/images/enter.png")');
            submitting = false;
        }
    });
    return false;
});

if(token_expired=='true')
    setreadonly();
});
