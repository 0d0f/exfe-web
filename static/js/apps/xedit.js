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
        $("#x_time_area").addClass('x_editable');
        $("#x_time_area").bind('click', odof.x.edit.editTime);
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
                    odof.cross.index.setreadonly(clickCallBackFunc);
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

})(ns);


$(document).ready(function()
{
    odof.x.render.show();
    odof.x.edit.cross_id     = cross_id;
    odof.x.edit.token        = token;
    odof.x.edit.location_uri = location_uri;

    $('#private_icon').mousemove(function(){$('#private_hint').show();});
    $('#private_icon').mouseout(function(){$('#private_hint').hide();});
    $('#edit_icon').mousemove(function(){$('#edit_icon_desc').show();});
    $('#edit_icon').mouseout(function(){$('#edit_icon_desc').hide();});
    $('#submit_data').bind('click', odof.x.edit.submitData);
    $('#edit_icon').bind('click', odof.x.edit.startEdit);
    $('#revert_x_btn').bind('click', odof.x.edit.revertX);
});









































if (0) {
    /**
     * while user submit data
     *
     * */
    ns.submitData = function() {
        var title = jQuery("#cross_titles_textarea").val(),
            time  = jQuery("#datetime").val(),
            place = odof.util.trim($('#place_content').val()),
            placeline1 = place ? $('#pv_place_line1').html() : '',
            placeline2 = place ? $('#pv_place_line2').html().replace(/<br>/g, '\\r') : '',
            desc  = jQuery("#cross_desc_textarea").val(),
            exfee = JSON.stringify(ns.getexfee());
        jQuery("#edit_cross_submit_loading").show();
        jQuery.ajax({
            url:location.href.split('?').shift() + '/crossEdit',
            type:"POST",
            dataType:"json",
            data:{
                jrand  : Math.round(Math.random()*10000000000),
                ctitle : title,
                ctime  : time,
                cdesc  : desc,
                cplaceline1 : placeline1,
                cplaceline2 : placeline2,
                exfee  : exfee
            },
            //回调
            success:function(JSONData){
                ns.callbackActions(JSONData);
            },
            complete:function(){
                jQuery("#edit_cross_submit_loading").hide();
            }
        });
        //jQuery("#edit_cross_bar").slideUp(300);
    };

    /**
     * submit call back actions
     *
     * */
    ns.callbackActions = function(JSONData){
        if(JSONData.error){
            $("#error_msg").html(JSONData.msg);
        }

    };

    /**
     * change rsvp status
     * by Leask
     * */
    ns.changeRsvp = function(target) {
        var intC = parseInt(target.className.substr(1)) + 1;
        target.className = 'c' + (intC > 2 ? 0 : intC);
        ns.summaryExfee();
        ns.updateCheckAll();
    };

}
