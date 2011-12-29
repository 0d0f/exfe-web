/**
 * @Description:    Cross index
 * @LastModified:   Nov 1, 2011
 * @CopyRights:     http://www.exfe.com
**/

var moduleNameSpace = 'odof.x.render';
var ns = odof.util.initNameSpace(moduleNameSpace);

// 这个回调函数在后面要被覆盖 by Handaoliang
var clickCallBackFunc = function(args){
    window.location.href = odof.cross.index.location_uri;
};

(function(ns)
{

    ns.arrRvsp   = ['', 'Accepted', 'Declined', 'Interested'];

    ns.crossHtml = '<div id="x_title_area">'
                 +     '<h2 id="x_title" class="x_title x_title_normal"></h2>'
                 +     '<input id="x_title_edit" class="x_title" style="display:none;">'
                 + '</div>'
                 + '<div id="x_content" class="cleanup">'
                 +     '<div id="x_mainarea">'
                 +         '<div id="x_desc_area">'
                 +             '<div id="x_desc" class="x_desc"></div>'
                 +             '<textarea id="x_desc_edit" class="x_desc" style="display:none;"></textarea>'
                 +             '<a id="x_desc_expand" href="javascript:void(0);">Expand</a>'
                 +         '</div>'
                 +         '<div id="x_rsvp_area">'
                 +             '<span id="x_rsvp_msg">'
                 +                 'Your RSVP is "<span id="x_rsvp_status"></span>".'
                 +             '</span>'
                 +             '<a id="x_rsvp_yes"    href="javascript:void(0);" class="x_rsvp_button">Accept</a>'
                 +             '<a id="x_rsvp_no"     href="javascript:void(0);" class="x_rsvp_button">Decline</a>'
                 +             '<a id="x_rsvp_maybe"  href="javascript:void(0);" class="x_rsvp_button">interested</a>'
                 +             '<a id="x_rsvp_change" href="javascript:void(0);">Change?</a>'
                 +         '</div>'
                 +         '<div id="x_conversation_area">'
                 +             '<h3>Conversation</h3>'
                 +             '<div id="x_conversation_input_area" class="cleanup">'
                 +                 '<img id="x_conversation_my_avatar" class="x_conversation_avatar">'
                 +                 '<textarea id="x_conversation_input"></textarea>'
                 +                 '<input id="x_conversation_submit" type="button" title="Say!">'
                 +             '</div>'
                 +             '<ol id="x_conversation_list"></ol>'
                 +         '</div>'
                 +     '</div>'
                 +     '<div id="x_sidebar">'
                 +         '<div id="x_time_area">'
                 +             '<h3   id="x_time_relative"></h3>'
                 +             '<span id="x_time_absolute"></span>'
                 +         '</div>'
                 +         '<div id="x_place_area">'
                 +             '<h3   id="x_place_line1" class="x_place_line1_normal"></h3>'
                 +             '<span id="x_place_line2"></span>'
                 +         '</div>'
                 +         '<div id="x_exfee_area"></div>'
                 +     '</div>'
                 + '</div>';


    ns.showTitle = function()
    {
        var objTitle = $('#x_title');
        objTitle.html(crossData.title);
        document.title = 'EXFE - ' + crossData.title;
        if (objTitle.hasClass('x_title_double') && objTitle.height() < 112) {
            objTitle.addClass('x_title_normal').removeClass('x_title_double');
        }
        if (objTitle.hasClass('x_title_normal') && objTitle.height() > 70) {
            objTitle.addClass('x_title_double').removeClass('x_title_normal');
        }
    };


    ns.showDesc = function(editing)
    {
        var strDesc = crossData.description === '' && editing
                    ? 'Write some words about this X.'
                    : crossData.description;
        var converter = new Showdown.converter();
        $('#x_desc').html(converter.makeHtml(strDesc));
    };


    ns.showTime = function()
    {
        var strRelativeTime = '',
            strAbsoluteTime = '';
        if (crossData.begin_at === '0000-00-00 00:00:00') {
            strRelativeTime = 'Sometime';
        } else {
            strRelativeTime = odof.util.getRelativeTime(crossData.begin_at);
            strAbsoluteTime = odof.util.getHumanDateTime(crossData.begin_at);
        }
        $('#x_time_relative').html(strRelativeTime);
        $('#x_time_absolute').html(strAbsoluteTime);
    };


    ns.showPlace = function()
    {
        var objPlace = $('#x_place_line1');
        objPlace.html(crossData.place.line1 ? crossData.place.line1 : 'Somewhere');
        $('#x_place_line2').html(crossData.place.line2.replace(/\n/g, '<br>'));
        if (objPlace.hasClass('x_place_line1_double') && objPlace.height() < 70) {
            objPlace.addClass('x_place_line1_normal').removeClass('x_place_line1_double');
        }
        if (objPlace.hasClass('x_place_line1_normal') && objPlace.height() > 53) {
            objPlace.addClass('x_place_line1_double').removeClass('x_place_line1_normal');
        }
    };


    ns.showConversation = function()
    {
        var strMessage = '';
        for (var i in crossData.conversation) {
            strMessage += this.makeMessage(crossData.conversation[i]);
        }
        $('#x_conversation_list').html(strMessage);
    };


    ns.makeMessage = function(objItem)
    {
        return '<li class="cleanup">'
             +     '<img src="' + odof.comm.func.getUserAvatar(
                   objItem.identity.avatar_file_name, 80, img_url)
             +     '" class="x_conversation_avatar">'
             +     '<div class="x_conversation_message">'
             +         '<p class="x_conversation_content_area">'
             +             '<span class="x_conversation_identity">'
             +                 objItem.identity.name + ': '
             +             '</span>'
             +             '<span class="x_conversation_content">'
             +                 objItem.content
             +             '</span>'
             +         '</p>'
             +         '<span class="x_conversation_time">'
             +             odof.util.getRelativeTime(objItem.created_at)
             +         '</span>'
             +     '</div>'
             + '</li>';
    };


    ns.show = function()
    {
        $('#x_view_content').html(this.crossHtml);

        $('#x_conversation_my_avatar').attr('src', odof.comm.func.getUserAvatar(
            myIdentity.avatar_file_name, 80, img_url
        ));
        if (myrsvp) {
            $('#x_rsvp_status').html(this.arrRvsp[myrsvp]);
            $('#x_rsvp_msg').show();
            $('#x_rsvp_yes').hide();
            $('#x_rsvp_no').hide();
            $('#x_rsvp_maybe').hide();
            $('#x_rsvp_change').show();
        } else {
            $('#x_rsvp_msg').hide();
            $('#x_rsvp_yes').show();
            $('#x_rsvp_no').show();
            $('#x_rsvp_maybe').show();
            $('#x_rsvp_change').hide();
        }
        this.showTitle();
        this.showDesc();
        this.showTime();
        this.showPlace();
        this.showConversation();
    };

})(ns);


$(document).ready(function() {

    return;

    $('#rsvp_loading').activity({
        segments: 8,
        steps: 3,
        opacity: 0.3,
        width: 4,
        space: 0,
        length: 5,
        color: '#0b0b0b',
        speed: 1.5
    });

    $('#changersvp').click(function(e) {
        $('#rsvp_options').show();
        $('#rsvp_submitted').hide();
    });

    odof.cross.index.formatCross();

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
                    odof.cross.index.postConversation();
                    e.preventDefault();
                }
        }
    });

    $('#post_submit').click(function() {
        odof.cross.index.postConversation();
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
                        odof.cross.index.setreadonly(clickCallBackFunc);
                        if (data.response.token_expired == '1' && login_type == 'token') {
                            token_expired = true;
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
                        //var args = {"identity":external_identity};
                        //alert("show login dialog.");
                        //$('#pwd_hint').html("<span>Error identity </span>");
                        //$('#login_hint').show();
                        /*
                        var callBackFunc = function(args){
                            window.location.href=location_uri;
                        }
                        if(show_idbox == "setpassword"){
                            odof.user.status.doShowResetPwdDialog(null, 'setpwd');
                            jQuery("#show_identity_box").val(external_identity);
                        }else{
                            odof.user.status.doShowLoginDialog(null, callBackFunc);
                        }
                        */
                        odof.cross.index.setreadonly();
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

    if(token_expired == 'true') {
        //$('textarea[name=comment]').attr("disabled","disabled");
        //$('textarea[name=comment]').val("pls login");
        $('#rsvp_yes , #rsvp_no , #rsvp_maybe').unbind("click");
        $('#rsvp_yes , #rsvp_no , #rsvp_maybe').click(function(e) {
            ns.btn_val = $(this).attr('value');
            var clickCallBackFunc = function(args){
                var poststr = {
                    cross_id:odof.cross.index.cross_id,
                    rsvp:odof.cross.index.btn_val
                };
                $.ajax({
                    type: 'POST',
                    data: poststr,
                    url: site_url + '/rsvp/save',
                    dataType: 'json',
                    success: function(data) { }
                });
                window.location.href = odof.cross.index.location_uri;
                //console.log(args);
            }

            odof.cross.index.setreadonly(clickCallBackFunc);
        });

        $('textarea[name=comment], #cross_identity_btn').unbind("click");
        $('textarea[name=comment]').blur();
        $('textarea[name=comment], #cross_identity_btn').click(function(e) {
            odof.cross.index.setreadonly(clickCallBackFunc);
        });
    }

    if(token_expired == 'false') {
        $('#cross_identity_btn').unbind("click");
        $('#cross_identity_btn').click(function(e) {
            var clickCallBackFunc = function(args){
                window.location.href = odof.cross.index.location_uri;
            };
            odof.cross.index.setreadonly(clickCallBackFunc);
        });
    }

});
