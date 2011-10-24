function setreadonly()
{
    $('textarea[name=comment]').attr("disabled","disabled");
    $('textarea[name=comment]').val("pls login");
    $('#rsvp_yes , #rsvp_no , #rsvp_maybe').unbind("click");
    $('#rsvp_yes , #rsvp_no , #rsvp_maybe').click(function(e) {
        alert("pls login");
    });
}

function formatCross()
{
    // format title
    if ($('#cross_title').hasClass('pv_title_double') && $('#cross_title').height() < 112) {
        $('#cross_title').addClass('pv_title_normal').removeClass('pv_title_double');
    }
    if ($('#cross_title').hasClass('pv_title_normal') && $('#cross_title').height() > 70) {
        $('#cross_title').addClass('pv_title_double').removeClass('pv_title_normal');
    }

    // format address
}

$(document).ready(function() {

    $('#rsvp_loading').activity({segments: 8, steps: 3, opacity: 0.3, width: 4, space: 0, length: 5, color: '#0b0b0b', speed: 1.5});

    $('#formconversation').submit(function(e) {
        // alert("a");
    });

    $('#changersvp').click(function(e) {
        $('#rsvp_options').show();
        $('#rsvp_submitted').hide();
    });

    formatCross();

    window.submitting = false;
    window.arrRvsp    = ['Interested', 'Accepted', 'Declined', 'Interested'];

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

        $("#rsvp_loading").ajaxStart(function(){ $(this).show(); });
        $("#rsvp_loading").ajaxStop(function(){ $(this).hide(); });
        var poststr = 'cross_id=' + cross_id + '&rsvp=' + $(this).attr('value')+'&token='+token;

        $.ajax({
            type: 'POST',
            data: poststr,
            url: site_url + '/rsvp/save',
            dataType: 'json',
            success: function(data) {
                if (data != null) {
                    if (data.response.success === 'true') {
                        if(data.response.token_expired=='1' && login_type=='token')
                        {
                            token_expired=true;
                            setreadonly();
                        }
                        switch (data.response.state) {
                            case 'yes':
                                $("li#exfee_" + data.response.identity_id + " > .cs > em").removeClass("c2");
                                $("li#exfee_" + data.response.identity_id + " > .cs > em").addClass("c1");
                                if (myrsvp !== 1) {
                                    $('.bignb').html(parseInt($('.bignb').html()) + 1);
                                }
                                break;
                            case 'no':
                            case 'maybe':
                                $("li#exfee_" + data.response.identity_id+" > .cs > em").removeClass("c1");
                                $("li#exfee_" + data.response.identity_id+" > .cs > em").addClass("c2");
                                if (myrsvp === 1) {
                                    $('.bignb').html(parseInt($('.bignb').html()) - 1);
                                }
                        }
                        myrsvp = {yes : 1, no : 2, maybe : 3}[data.response.state];
                        $('#rsvp_status').html(arrRvsp[myrsvp]);
                        $('#rsvp_options').hide();
                        $('#rsvp_submitted').show();
                    } else {
                        alert("show login dialog");
                        //$('#pwd_hint').html("<span>Error identity </span>");
                        //$('#login_hint').show();
                    }
                }
                $("#rsvp_loading").hide();
                $("#rsvp_loading").unbind("ajaxStart ajaxStop");
            },
            error: function(data) {
                $("#rsvp_loading").hide();
                $("#rsvp_loading").unbind("ajaxStart ajaxStop");
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
                            name = data.response.identity.external_identity;
                        var html = '<li><p class="pic40"><img src="/eimgs/80_80_' + data.response.identity.avatar_file_name + '" alt=""></p> <p class="comment"><span>' + name + ':</span>' + data.response.comment+'</p> <p class="times">'+data.response.created_at+'</p></li>';
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
