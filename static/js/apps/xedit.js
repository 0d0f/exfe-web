/**
 * @Description:    X edit module
 * @Author:         HanDaoliang <handaoliang@gmail.com>, Leask Huang <leask@exfe.com>
 * @createDate:     Sup 15,2011
 * @CopyRights:     http://www.exfe.com
**/

var moduleNameSpace = 'odof.x.edit';
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){

    ns.cross_id     = 0;

    ns.btn_val      = null;

    ns.token        = null;

    ns.location_uri = null;

    ns.cross_time_bubble_status = 0;

    ns.msgSubmitting = false;

    ns.xBackup      = {};


    ns.startEdit = function(){
        // backup current x
        odof.x.edit.xBackup = odof.util.clone(crossData);
        // edit bar
        $('#edit_x_bar').slideDown(300);
        // title
        $('#x_title').addClass('x_editable');
        $('#x_title').bind('click', odof.x.edit.editTitle);
        // desc
        odof.x.render.showDesc(true);
        $('#x_desc').addClass('x_editable');
        $('#x_desc').bind('click', odof.x.edit.editDesc);
        // time
        $('#x_time_area').addClass('x_editable');
        $('#x_time_area').bind('click', odof.x.edit.editTime);
        // place
        $('#x_place_area').addClass('x_editable');
        $('#x_place_area').bind('click', odof.x.edit.editPlace);
    };


    ns.revertX = function()
    {
        // edit bar
        $('#edit_x_bar').slideUp(300);
        // title
        odof.x.edit.editTitle(false);
        // desc
        odof.x.edit.editDesc(false);
        // time
        odof.x.edit.editTime(false);
        // place
        odof.x.edit.editPlace(false);
        // restore x from backup
        crossData = odof.util.clone(odof.x.edit.xBackup);
        odof.x.render.showComponents();
    };


    ns.postMessage = function()
    {
        var objMsg  = $('#x_conversation_input'),
            message = odof.util.trim(objMsg.val());
        if (this.msgSubmitting || message === '') {
            return;
        }
        this.msgSubmitting = true;
        //$('#post_submit').css('background', 'url("/static/images/enter_gray.png")');
        $.ajax({
            type : 'POST',
            data : {cross_id : cross_id,
                    message  : message,
                    token    : token},
            url  : site_url + '/conversation/save',
            dataType : 'json',
            success  : function(data) {
                if (data) {
                    switch (data.response.success) {
                        case 'true':
                            $('#x_conversation_list').prepend(
                                odof.x.render.makeMessage(data.response)
                            );
                            objMsg.val('');
                            break;
                        case 'false':
                            // $('#pwd_hint').html("<span>Error identity </span>");
                            // $('#login_hint').show();
                    }
                    odof.x.edit.setreadonly(clickCallBackFunc);
                }
                objMsg.focus();
                //$('#post_submit').css('background', 'url("/static/images/enter.png")');
                odof.x.edit.msgSubmitting = false;
            },
            error: function(date) {
                objMsg.focus();
                //$('#post_submit').css('background', 'url("/static/images/enter.png")');
                odof.x.edit.msgSubmitting = false;
            }
        });
    };

    /**
     * by Handaoliang
     */
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


    ns.editTitle = function(event)
    {
        if (event) {
            $('#x_title').hide();
            $('#x_title_edit').val(crossData.title);
            $('#x_title_edit').show();
            $('#x_title_area').bind('clickoutside', function(event) {
                if (event.target.id === 'revert_x_btn') {
                    return;
                }
                crossData.title = odof.util.trim($('#x_title_edit').val());
                crossData.title = crossData.title === ''
                                ? ('Meet ' + id_name) : crossData.title;
                odof.x.render.showTitle();
                $('#x_title_edit').hide();
                $('#x_title_area').unbind('clickoutside');
                $('#x_title').show();
            });
        } else {
            $('#x_title').removeClass('x_editable');
            $('#x_title').unbind('click');
            $('#x_title_edit').hide();
            $('#x_title_area').unbind('clickoutside');
            $('#x_title').show();
        }
    };


    ns.editDesc = function(event)
    {
        if (event) {
            $('#x_desc').hide();
            $('#x_desc_edit').val(crossData.description);
            $('#x_desc_edit').show();
            $('#x_desc_area').bind('clickoutside', function(event)
            {
                if (event.target.id === 'revert_x_btn') {
                    return;
                }
                crossData.description = odof.util.trim($('#x_desc_edit').val());
                odof.x.render.showDesc(true);
                $('#x_desc_edit').hide();
                $('#x_desc_area').unbind('clickoutside');
                $('#x_desc').show();
            });
        } else {
            $('#x_desc').removeClass('x_editable');
            $('#x_desc').unbind('click');
            $('#x_desc_edit').hide();
            $('#x_desc_area').unbind('clickoutside');
            $('#x_desc').show();
        }
    };


    ns.editTime = function(event)
    {
        if (event) {
            // check if had bind a event for #cross_time_bubble
            if (!$('#x_time_bubble').data('events')) {
                $('#x_time_bubble').bind('clickoutside', function(event) {
                    if (event.target.id === 'revert_x_btn') {
                        return;
                    }
                    if (event.target.parentNode === $('#x_time_area')[0]) {
                        $('#x_time_bubble').show();
                    } else {
                        $('#x_time_bubble').hide();
                        $('#x_time_bubble').unbind('clickoutside');
                    }
                });
            }
            // init calendar
            exCal.initCalendar(
                document.getElementById('x_datetime_original'),
                'x_time_container',
                function(displayCalString, standardTimeString) {
                    crossData.begin_at = standardTimeString;
                    odof.x.render.showTime();
                }
            );
        } else {
            $("#x_time_area").removeClass('x_editable');
            $("#x_time_area").unbind('click');
            $('#x_time_bubble').hide();
            $('#x_time_bubble').unbind('clickoutside');
        }
    };


    ns.editPlace = function(event)
    {
        if (event) {
            if (!$('#x_place_bubble').data('events')) {
                $('#x_place_bubble').bind('clickoutside', function(event)
                {
                    if (event.target.id === 'revert_x_btn') {
                        return;
                    }
                    if (event.target.parentNode === $('#x_place_area')[0]) {
                        $('#place_content').bind('keyup', function()
                        {
                            var strPlace = $('#place_content').val(),
                                arrPlace = strPlace.split(/\r|\n|\r\n/),
                                prvPlace = [];
                            arrPlace.forEach(function(item, i) {
                                if ((item = odof.util.trim(item)) !== '') {
                                    prvPlace.push(item);
                                }
                            });
                            if (prvPlace.length) {
                                crossData.place.line1 = prvPlace.shift();
                                crossData.place.line2 = prvPlace.join("\n");
                            } else {
                                crossData.place.line1 = '';
                                crossData.place.line2 = '';
                            }
                            odof.x.render.showPlace();
                        });
                        $('#x_place_bubble').show();
                    } else {
                        $('#x_place_bubble').hide();
                        $('#x_place_bubble').unbind('clickoutside');
                    }
                });
            }
        } else {
            $('#x_place_area').removeClass('x_editable');
            $('#x_place_area').unbind('click');
            $('#x_place_bubble').hide();
            $('#x_place_bubble').unbind('clickoutside');
        }
    };


    ns.editRsvp = function(event)
    {
        if (event.target.id === 'x_rsvp_change') {
            $('#x_rsvp_msg').hide();
            $('#x_rsvp_change').hide();
            $('.x_rsvp_button').show();
        } else {
            switch (event.target.id) {
                case 'x_rsvp_yes':
                case 'x_rsvp_no':
                case 'x_rsvp_maybe':
                    var strRsvp = event.target.id.split('_')[2];
                    $.ajax({
                        type : 'POST',
                        data : {cross_id : cross_id, rsvp : strRsvp, token : token},
                        url  : site_url + '/rsvp/save',
                        dataType : 'json',
                        success : function(data) {
                            if (data != null) {
                                if (data.response.success === 'true') {
                                    // by handaoliang {
                                    odof.x.edit.setreadonly(clickCallBackFunc);
                                    if (data.response.token_expired == '1' && login_type == 'token') {
                                        token_expired = true;
                                    }
                                    // }
                                    myrsvp = {yes : 1, no : 2, maybe : 3}[data.response.state];
                                    odof.x.render.showRsvp();
                                } else {
                                    // by handaoliang {
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
                                    odof.x.edit.setreadonly();
                                    // }
                                }
                            }
                            // $('#rsvp_loading').hide();
                            // $('#rsvp_loading').unbind('ajaxStart ajaxStop');
                        },
                        error: function(data) {
                            // $('#rsvp_loading').hide();
                            // $('#rsvp_loading').unbind('ajaxStart ajaxStop');
                        }
                    });
            }
            $('#x_rsvp_msg').show();
            $('#x_rsvp_change').show();
            $('.x_rsvp_button').hide();
            event.preventDefault();
        }
    };
 
 
    ns.submitData = function() {
        // exfee = JSON.stringify(ns.getexfee());
        // $('#edit_cross_submit_loading').show();
        $.ajax({
            url  : location.href.split('?').shift() + '/crossEdit',
            type : 'POST',
            dataType : 'json',
            data:{
                jrand       : Math.round(Math.random() * 10000000000),
                ctitle      : crossData.title,
                ctime       : crossData.begin_at,
                cdesc       : crossData.description,
                cplaceline1 : crossData.place.line1,
                cplaceline2 : crossData.place.line2 //,
             // exfee       : exfee
            },
            success : function(data) {
                if(data.error){
                    $('#error_msg').html(data.msg);
                }
            },
            complete : function() {
             // $('#edit_x_bar').slideUp(300);
             // $('#edit_cross_submit_loading').hide();
            }
        });
    };

})(ns);


$(document).ready(function()
{
    odof.x.render.show();
    odof.x.edit.cross_id     = cross_id;
    odof.x.edit.token        = token;
    odof.x.edit.location_uri = location_uri;

    $('#private_icon').mousemove(function() { $('#private_hint').show(); });
    $('#private_icon').mouseout(function() { $('#private_hint').hide(); });
    $('#edit_icon').mousemove(function() { $('#edit_icon_desc').show(); });
    $('#edit_icon').mouseout(function() { $('#edit_icon_desc').hide(); });
    $('#x_rsvp_change').bind('click', odof.x.edit.editRsvp)
    $('.x_rsvp_button').bind('click', odof.x.edit.editRsvp)
    $('#submit_data').bind('click', odof.x.edit.submitData);
    $('#edit_icon').bind('click', odof.x.edit.startEdit);
    $('#revert_x_btn').bind('click', odof.x.edit.revertX);
});







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

