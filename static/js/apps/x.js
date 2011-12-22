/**
 * @Description:    Cross index
 * @LastModified:   Nov 1, 2011
 * @CopyRights:     http://www.exfe.com
**/

var moduleNameSpace = 'odof.cross.render';
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns)
{

    ns.x = {};

    ns.crossHtml = '<div id="x_title_area">'
                 +     '<h2 id="x_title" class="x_title_normal"></h2>'
                 +     '<input id="x_title_edit" style="display:none;">'
                 + '</div>'
                 + '<div id="x_desc_area">'
                 +     '<div id="x_desc"></div>'
                 +     '<textarea id="x_desc_edit" style="display:none;"></textarea>'
                 +     '<a id="x_desc_expand" href="javascript:void(0);">Expand</a>'
                 + '</div>'
                 + '<div id="x_rsvp_area">'
                 +     '<span id="x_rsvp_msg">'
                 +         'Your RSVP is "<span id="x_rsvp_status"></span>".'
                 +     '</span>'
                 +     '<a id="x_rsvp_yes"    href="javascript:void(0);" class="x_rsvp_button">Accept</a>'
                 +     '<a id="x_rsvp_no"     href="javascript:void(0);" class="x_rsvp_button">Decline</a>'
                 +     '<a id="x_rsvp_maybe"  href="javascript:void(0);" class="x_rsvp_button">interested</a>'
                 +     '<a id="x_rsvp_change" href="javascript:void(0);">Change?</a>'
                 + '</div>'
                 + '<div id="x_conversation_area">'
                 +     '<h3>Conversation</h3>'
                 +     '<div id="x_conversation_input_area">'
                 +         '<img id="x_conversation_my_avatar" class="x_conversation_avatar">'
                 +         '<textarea id="x_conversation_input"></textarea>'
                 +         '<input id="x_conversation_submit" type="button" title="Say!">'
                 +     '</div>'
                 +     '<ol id="x_conversation_list"></ol>'
                 + '</div>'
                 + '<div id="x_time_area">'
                 +     '<h3   id="x_time_relative"></h3>'
                 +     '<span id="x_time_absolute"></span>'
                 + '</div>'
                 + '<div id="x_place_area">'
                 +     '<h3   id="x_place_line1"></h3>'
                 +     '<span id="x_place_line2"></span>'
                 + '</div>'
                 + '<div id="x_exfee_area"></div>';

    ns.show = function(id, objX)
    {
        this.x = objX;
        $('#' + id).html(this.crossHtml);

    };

})(ns);



var moduleNameSpace = "odof.cross.index";
var ns = odof.util.initNameSpace(moduleNameSpace);

//这个回调函数在后面要被覆盖。
var clickCallBackFunc = function(args){
    window.location.href = odof.cross.index.location_uri;
};

(function(ns){

    ns.cross_id     = 0;
    ns.btn_val      = null;
    ns.token        = null;
    ns.location_uri = null;

    ns.setreadonly = function(callBackFunc) {
        /*
        if(typeof token_expired != "undefined" && token_expired == "false"){
            //Token 还没有过期，用户点击之后弹窗，这个在回调中实现。
        }
        //Token已经过期，用户点击之前弹窗口
        if(typeof token_expired != "undefined" && token_expired == "true"){
        }
        //======End=====Token已经过期，用户点击之前弹窗口
        */
        if(token != ""){
            if(show_idbox == 'setpassword'){//如果要求弹设置密码窗口。
                if(token_expired == "true"){
                    var args = {"identity":external_identity};
                    odof.user.status.doShowCrossPageVerifyDialog(null, args);
                }else{
                    odof.user.status.doShowResetPwdDialog(null, 'setpwd');
                    jQuery("#show_identity_box").val(external_identity);
                }
            }else if(show_idbox == "login"){
                odof.user.status.doShowLoginDialog(null, callBackFunc, external_identity);
            }else{
                args = {"identity":external_identity};
                odof.user.status.doShowCrossPageVerifyDialog(null, args);
            }
        }
    };

    ns.formatCross = function() {
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
    };

    ns.postConversation = function() {
        var comment = odof.util.trim($('textarea[name=comment]').val());

        if (submitting || comment === '') {
            return;
        }
        submitting = true;

        var poststr = "cross_id=" + cross_id + "&comment=" + comment + '&token=' + token;
        $('textarea[name=comment]').activity({outside: true, align: 'right', valign: 'top', padding: 5, segments: 10, steps: 2, width: 2, space: 0, length: 3, color: '#000', speed: 1.5});
        $('#post_submit').css('background', 'url("/static/images/enter_gray.png")');

        $.ajax({
            type: 'POST',
            data: poststr,
            url: site_url + '/conversation/save',
            dataType: 'json',
            success: function(data) {
                if (data != null) {
                    if (data.response.success == 'false') {
                        //$('#pwd_hint').html("<span>Error identity </span>");
                        //$('#login_hint').show();
                    } else if(data.response.success == 'true') {
                        var name   = data.response.identity.name == ''
                                   ? data.response.identity.external_identity
                                   : data.response.identity.name,
                            avatar = data.response.identity.avatar_file_name;
                        var html = '<li><p class="pic40"><img src="'+odof.comm.func.getUserAvatar(avatar, 80, img_url)+'" alt=""></p> <p class="comment"><span>' + name + ':</span>&nbsp;' + data.response.comment+'</p> <p class="times">'+data.response.created_at+'</p></li>';
                        $('#commentlist').prepend(html);
                        $('textarea[name=comment]').val('');
                    }
                    odof.cross.index.setreadonly(clickCallBackFunc);
                }
                $('textarea[name=comment]').activity(false);
                $('textarea[name=comment]').focus();
                $('#post_submit').css('background', 'url("/static/images/enter.png")');
                submitting = false;
            },
            error: function(date) {
                $('textarea[name=comment]').activity(false);
                $('textarea[name=comment]').focus();
                $('#post_submit').css('background', 'url("/static/images/enter.png")');
                submitting = false;
            }
        });
    };

})(ns);




$(document).ready(function() {
return;
    odof.cross.index.cross_id     = cross_id;
    odof.cross.index.token        = token;
    odof.cross.index.location_uri = location_uri;

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

    document.title = 'EXFE - ' + $('#cross_titles').html();

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
