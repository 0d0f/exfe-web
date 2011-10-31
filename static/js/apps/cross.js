function setreadonly()
{
    $('textarea[name=comment]').attr("disabled","disabled");
    $('textarea[name=comment]').val("pls login");
    $('#rsvp_yes , #rsvp_no , #rsvp_maybe').unbind("click");
    $('#rsvp_yes , #rsvp_no , #rsvp_maybe').click(function(e) {
        //alert("pls login");
        var callBackFunc = function(args){
            window.location.href=location_uri;
            //console.log(args);
        }
        odof.user.status.doShowLoginDialog(null, callBackFunc);
    });
}

function formatCross()
{
    // format title
    if ($('#cross_titles').hasClass('pv_title_double') && $('#cross_titles').height() < 112) {
        $('#cross_titles').addClass('pv_title_normal').removeClass('pv_title_double');
    }
    if ($('#cross_titles').hasClass('pv_title_normal') && $('#cross_titles').height() > 70) {
        $('#cross_titles').addClass('pv_title_double').removeClass('pv_title_normal');
    }

    // format address
    if ($('#pv_place_line1').hasClass('pv_place_line1_double') && $('#pv_place_line1').height() < 70) {
        $('#pv_place_line1').addClass('pv_place_line1_normal').removeClass('pv_place_line1_double');
    }
    if ($('#pv_place_line1').hasClass('pv_place_line1_normal') && $('#pv_place_line1').height() > 53) {
        $('#pv_place_line1').addClass('pv_place_line1_double').removeClass('pv_place_line1_normal');
    }
}

$(document).ready(function() {

    $('#rsvp_loading').activity({segments: 8, steps: 3, opacity: 0.3, width: 4, space: 0, length: 5, color: '#0b0b0b', speed: 1.5});

    document.title = 'EXFE - ' + $('#cross_titles').html();

    $('#formconversation').submit(function(e) {
        // alert("a");
    });

    $('#changersvp').click(function(e) {
        $('#rsvp_options').show();
        $('#rsvp_submitted').hide();
    });

    formatCross();

    window.submitting = false;
    window.arrRvsp    = ['', 'Accepted', 'Declined', 'Interested'];

    $('#rsvp_status').html(arrRvsp[myrsvp]);

    $('textarea[name=comment]').focus();

    $('textarea[name=comment]').keydown(function(e) {
        switch (e.keyCode) {
            case 9:
                $('#post_submit').focus();
                e.preventDefault();
                break;
            case 13:
                if (!e.shiftKey) {
                    $('#formconversation').submit();
                }
        }
    });

    $('#rsvp_yes , #rsvp_no , #rsvp_maybe').click(function(e) {

        $("#rsvp_loading").ajaxStart(function() {
            $(this).show();
        });
        $("#rsvp_loading").ajaxStop(function() {
            $(this).hide();
        });
        var poststr = 'cross_id=' + cross_id + '&rsvp=' + $(this).attr('value') + '&token=' + token;

        $.ajax({
            type: 'POST',
            data: poststr,
            url: site_url + '/rsvp/save',
            dataType: 'json',
            success: function(data) {
                if (data != null) {
                    if (data.response.success === 'true') {
                        if (data.response.token_expired == '1' && login_type == 'token') {
                            token_expired = true;
                            setreadonly();
                        }
                        var objChkbox = $('li#exfee_' + data.response.identity_id + ' > .cs > em');
                        objChkbox.removeClass('c0');
                        objChkbox.removeClass('c1');
                        objChkbox.removeClass('c2');
                        objChkbox.removeClass('c3');
                        switch (data.response.state) {
                            case 'yes':
                                objChkbox.addClass('c1');
                                break;
                            case 'no':
                                objChkbox.addClass('c2');
                                break;
                            case 'maybe':
                                objChkbox.addClass('c3');
                        }
                        odof.cross.edit.summaryExfee();
                        myrsvp = {yes : 1, no : 2, maybe : 3}[data.response.state];
                        $('#rsvp_status').html(arrRvsp[myrsvp]);
                        $('#rsvp_options').hide();
                        $('#rsvp_submitted').show();
                    } else {
                        var args = {"identity":external_identity};
                        odof.user.status.doShowVerificationDialog(null, args);
                        //alert("show login dialog.");
                        //$('#pwd_hint').html("<span>Error identity </span>");
                        //$('#login_hint').show();
                    }
                }
                $('#rsvp_loading').hide();
                $('#rsvp_loading').unbind('ajaxStart ajaxStop');
            },
            error: function(data) {
                $('#rsvp_loading').hide();
                $('#rsvp_loading').unbind('ajaxStart ajaxStop');
            }
        });
        e.preventDefault();
    });

    $('#formconversation').submit(function() {

        if (submitting) { return false; }

        submitting = true;

        var comment = $('textarea[name=comment]').val();
        var poststr = "cross_id=" + cross_id + "&comment=" + comment;
        $('textarea[name=comment]').activity({outside: true, align: 'right', valign: 'top', padding: 5, segments: 10, steps: 2, width: 2, space: 0, length: 3, color: '#000', speed: 1.5});
        $('#post_submit').css('background', 'url("/static/images/enter_gray.png")');

        $.ajax({
            type: 'POST',
            data: poststr,
            url: site_url + '/conversation/save',
            dataType: 'json',
            success: function(data) {
                if (data != null)
                {
                    if (data.response.success == "false")
                    {
                        //$('#pwd_hint').html("<span>Error identity </span>");
                        //$('#login_hint').show();
                    }
                    else if(data.response.success == "true")
                    {
                        var name = data.response.identity.name;
                        if(name == "")
                            var name = data.response.identity.external_identity;
                            var avatar = data.response.identity.avatar_file_name;
                        var html = '<li><p class="pic40"><img src="'+odof.comm.func.getHashFilePath(img_url,avatar)+'/80_80_' + avatar + '" alt=""></p> <p class="comment"><span>' + name + ':</span>' + data.response.comment+'</p> <p class="times">'+data.response.created_at+'</p></li>';
                        $("#commentlist").prepend(html);
                        $("textarea[name=comment]").val("");
                    }
                }
                $('textarea[name=comment]').activity(false);
                $('#post_submit').css('background', 'url("/static/images/enter.png")');
                submitting = false;
            },
            error: function(date) {
                $('textarea[name=comment]').activity(false);
                $('#post_submit').css('background', 'url("/static/images/enter.png")');
                submitting = false;
            }
        });
        return false;
    });

    if(token_expired == 'true') {
        setreadonly();
    }

});
