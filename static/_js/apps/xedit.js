/**
 * @Description: X edit module
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Dec 30, 2011
 * @CopyRights:  http://www.exfe.com
 */

var moduleNameSpace = 'odof.x.edit',
    ns = odof.util.initNameSpace(moduleNameSpace);

// 这个回调函数在后面要被覆盖 by Handaoliang
var clickCallBackFunc = function(args) {
    window.location.href = odof.x.edit.location_uri;
};

(function(ns) {

    ns.cross_id      = 0;

    ns.rsvpAction    = null;

    ns.token         = null;

    ns.location_uri  = null;

    ns.cross_time_bubble_status = 0;

    ns.msgSubmitting = false;

    ns.xBackup       = null;


    ns.startEdit = function() {
        // backup current x
        odof.x.edit.xBackup = odof.util.clone(crossData);
        // edit bar
        $('#edit_x_bar').slideDown(300);
        // title
        $('#x_title').addClass('x_editable');
        $('#x_title').bind('click', odof.x.edit.editTitle);
        $('#x_title_edit').val(crossData.title);
        // desc
        odof.x.render.showDesc(true);
        $('#x_desc').addClass('x_editable');
        $('#x_desc').bind('click', odof.x.edit.editDesc);
        $('#x_desc_edit').val(crossData.description);
        // time
        $('#x_time_area').addClass('x_editable');
        $('#x_time_area').bind('click', odof.x.edit.editTime);
        // place
        $('#x_place_area').addClass('x_editable');
        $('#x_place_area').bind('click', odof.x.edit.editPlace);
    };


    ns.revertX = function() {
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

        odof.record.reset();
        odof.x.edit.xBackup = null;
        odof.x.edit.freeze();

        odof.x.render.showComponents();
    };


    ns.postMessage = function() {
        var objMsg  = $('#x_conversation_input'),
            message = odof.util.trim(objMsg.val());
        if (this.msgSubmitting || message === '') {
            return;
        }
        this.msgSubmitting = true;
        //$('#post_submit').css('background', 'url("/static/images/enter_gray.png")');

        if (typeof window.mapRequest !== 'undefined') {
            window.mapRequest.abort();
        }

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
        var _external_username = myIdentity.external_username + (myIdentity.provider === 'email' ? '' : ('@' + myIdentity.provider));
        if (token != "") {
            if (show_idbox == 'setpassword') {//如果要求弹设置密码窗口。
                if (token_expired == "true") {
                    var args = {"identity" : _external_username};
                    odof.user.status.doShowCrossPageVerifyDialog(null, args);
                } else {
                    odof.user.status.doShowResetPwdDialog(null, 'setpwd');
                    jQuery("#show_identity_box").val(_external_username);
                }
            } else if (show_idbox == "login"){
                odof.user.status.doShowLoginDialog(null, callBackFunc, _external_username);
            } else {
                args = {"identity" : _external_username};
                odof.user.status.doShowCrossPageVerifyDialog(null, args);
            }
        }
    };


    ns.editTitle = function(event) {
        if (event) {
            $('#x_title').hide();
            $('#x_title_edit').val(crossData.title);
            $('#x_title_edit').show();
            $('#x_title_area').bind('clickoutside', function(event) {
                if (event.target.id === 'revert_x_btn') {
                    return;
                }
                odof.x.edit.saveTitle();
            });
            $('#x_title_edit').bind('keydown', function(event) {
                switch (event.keyCode) {
                    case 9:
                        odof.x.edit.saveTitle();
                        odof.x.edit.editDesc(true);
                        event.preventDefault();
                }
            });
            $('#x_title_edit').focus();
        } else {
            $('#x_title').removeClass('x_editable');
            $('#x_title').unbind('click');
            $('#x_title_edit').hide();
            $('#x_title_area').unbind('clickoutside');
            $('#x_title').show();
            $('#x_title_edit').unbind('keydown');
        }
    };


    ns.editDesc = function(event) {
        if (event) {
            $('#x_desc').hide();
            $('#x_desc_edit').val(crossData.description);
            $('#x_desc_edit').show();
            $('#x_desc_area').bind('clickoutside', function(event) {
                if (event.target.id === 'revert_x_btn') {
                    return;
                }
                odof.x.edit.saveDesc();
            });
            $('#x_desc_edit').bind('keydown', function(event) {
                switch (event.keyCode) {
                    case 9:
                        odof.x.edit.saveDesc();
                        odof.x.edit.editTime(true);
                        event.preventDefault();
                }
            });
            $('#x_desc_expand').hide();
            $('#x_desc_edit').focus();
        } else {
            $('#x_desc').removeClass('x_editable');
            $('#x_desc').unbind('click');
            $('#x_desc_edit').hide();
            $('#x_desc_area').unbind('clickoutside');
            $('#x_desc').show();
            $('#x_desc_edit').unbind('keydown');
        }
    };


    ns.updateTime = function(displaytime, typing) {
        var objTimeInput = $('#x_datetime_original');
        if (displaytime) {
            objTimeInput.val(displaytime);
        }
        if ((crossData.origin_begin_at = odof.util.trim(objTimeInput.val())) === '') {
            crossData.begin_at = '';
        } else {
            var strTime = odof.util.parseHumanDateTime(
                    crossData.origin_begin_at,
                    odof.comm.func.convertTimezoneToSecond(odof.comm.func.getTimezone())
                );
            crossData.begin_at = strTime ? strTime : '';
        }
        if (crossData.begin_at === null) {
            objTimeInput.addClass('error');
        } else {
            objTimeInput.removeClass('error');
        }
        odof.x.render.showTime();
    };


    ns.editTime = function(event) {
        if (event) {
            // check if had bind a event for #cross_time_bubble
            if (!$('#x_time_bubble').data('events')) {
                $('#x_time_bubble').bind('clickoutside', function(event) {
                    if (event.target.id === 'revert_x_btn') {
                        return;
                    }
                    if (event.target.parentNode === $('#x_time_area')[0]) {
                        $('#x_time_bubble').show();
                        $('#x_datetime_original').focus();
                    } else {
                        odof.x.edit.saveTime();
                    }
                });
                $('#x_datetime_original').bind('focus keydown keyup blur', function(event) {
                    switch (event.type) {
                        case 'keydown':
                            switch (event.keyCode) {
                                case 9:
                                    odof.x.edit.saveTime();
                                    odof.x.edit.editPlace(true);
                                    event.preventDefault();
                                    return;
                            }
                    }
                    odof.x.edit.updateTime(null, event.type !== 'blur');
                });
            }
            // init calendar
            var strStdTime = '';
            if (crossData.begin_at && crossData.begin_at !== '0000-00-00 00:00:00') {
                var objStdTime = odof.util.getDateFromString(crossData.begin_at);
                if (!crossData.time_type) {
                    objStdTime = new Date(
                        objStdTime.getTime()
                      + odof.comm.func.convertTimezoneToSecond(odof.comm.func.getTimezone())
                      * 1000
                    );
                }
                var year       = objStdTime.getFullYear(),
                    month      = objStdTime.getMonth() + 1,
                    date       = objStdTime.getDate(),
                    hour24     = objStdTime.getHours(),
                    hour12     = hour24 > 12 ? (hour24 - 12) : hour24,
                    ampm       = hour24 < 12 ? 'AM'          : 'PM',
                    minute     = objStdTime.getMinutes();
                month  = month  < 10 ? ('0' + month)  : month;
                minute = minute < 10 ? ('0' + minute) : minute;
                strStdTime     = month  + '-' + date   + '-' + year
                               + (crossData.begin_at.split(' ').length === 2
                               ? (' ' + hour12 + ':' + minute + ' ' + ampm) : '');
            }
            $('#x_datetime_original').val(strStdTime);
            exCal.initCalendar(
                $('#x_datetime_original')[0],
                'x_time_container',
                function(displayCalString, standardTimeString) {
                    odof.x.edit.updateTime(displayCalString);
                }
            );
            if (event === true) {
                $('#x_time_bubble').show();
                $('#x_datetime_original').focus();
            }
         } else {
            $('#x_time_area').removeClass('x_editable');
            $('#x_time_area').unbind('click');
            $('#x_time_bubble').hide();
            $('#x_time_bubble').unbind('clickoutside');
            $('#x_datetime_original').unbind('keydown');
        }
    };


    ns.editPlace = function(event) {
        if (event) {
            if (!$('#x_place_bubble').data('events')) {
                $('#x_place_bubble').bind('clickoutside', function(event) {
                    if (event.target.id === 'revert_x_btn') {
                        return;
                    }
                    if (event.target.parentNode === $('#x_place_area')[0]) {
                        $('#place_content').bind('keyup', function() {
                            var arrPlace = odof.util.parseLocation($('#place_content').val());
                            if (crossData.place.line1 !== arrPlace[0]) {
                                crossData.place.lat         = '';
                                crossData.place.lng         = '';
                                crossData.place.external_id = '';
                                crossData.place.provider    = '';
                            }
                            crossData.place.line1 = arrPlace[0];
                            crossData.place.line2 = arrPlace[1];
                            odof.x.render.showPlace();
                            //place search
                            odof.apps.maps.getLocation('place_content', 'google_maps_cotainer', 'edit_cross');
                        });
                        $('#place_content').html(
                            crossData.place.line1 !== '' || crossData.place.line2 !== ''
                         ? (crossData.place.line1 + '\r' +  crossData.place.line2) :  ''
                        );
                        $('#x_place_bubble').show();
                        $('#place_content').focus();
                    } else {
                        odof.x.edit.savePlace();
                    }
                });
                $('#place_content').bind('keydown', function(event) {
                    switch (event.keyCode) {
                        case 9:
                            odof.x.edit.savePlace();
                            odof.x.edit.editTitle(true);
                            event.preventDefault();
                    }
                });
            }
            if (event === true) {
                $('#x_place_bubble').show();
                $('#place_content').focus();
            }
        } else {
            $('#x_place_area').removeClass('x_editable');
            $('#x_place_area').unbind('click');
            $('#x_place_bubble').hide();
            $('#x_place_bubble').unbind('clickoutside');
            $('#place_content').unbind('keydown');
        }
    };


    ns.saveTitle = function() {
        crossData.title = odof.util.trim($('#x_title_edit').val());
        crossData.title = crossData.title === ''
                        ? ('Meet ' + id_name) : crossData.title;
        odof.x.render.showTitle();
        $('#x_title_edit').hide();
        $('#x_title_area').unbind('clickoutside');
        $('#x_title').show();
        odof.x.edit.freeze(true);
    };


    ns.saveDesc = function() {
        crossData.description = odof.util.trim($('#x_desc_edit').val());
        odof.x.render.showDesc(true);
        $('#x_desc_edit').hide();
        $('#x_desc_area').unbind('clickoutside');
        $('#x_desc').show();
        odof.x.edit.freeze(true);
    };


    ns.saveTime = function() {
        $('#x_time_bubble').hide();
        $('#x_time_bubble').unbind('clickoutside');
        odof.x.render.showTime();
        odof.x.edit.freeze(true);
    };


    ns.savePlace = function() {
        $('#x_place_bubble').hide();
        $('#x_place_bubble').unbind('clickoutside');
        odof.x.render.showPlace();
        odof.x.edit.freeze(true);
    };


    ns.conversationKeydown = function(event) {
        switch (event.keyCode) {
            case 9:
                // $('#post_submit').focus();
                // event.preventDefault();
                break;
            case 13:
                if (!event.shiftKey) {
                    odof.x.edit.postMessage();
                    event.preventDefault();
                }
        }
    };


    ns.editRsvp = function(event) {
        $('#x_rsvp_area').removeClass('x_rsvp_area_status');
        if (event.target.id === 'x_rsvp_change') {
            $('#x_rsvp_msg').hide();
            //$('#x_rsvp_change').hide();
            $('#x_rsvp_typeinfo').hide();
            //$('.x_rsvp_button').show();
            //$('#x_exfee_by_user').show();
            $('#x_rsvp_btns').show();
            $('#x_exfee_users').show();
            $('#x_rsvp_typeinfo').hide();
        } else {
            switch (event.target.id) {
                case 'x_rsvp_yes':
                case 'x_rsvp_no':
                case 'x_rsvp_maybe':
                    var strRsvp = event.target.id.split('_')[2];
                    if (token_expired == 'true') {
                        odof.x.edit.rsvpAction = strRsvp;
                        odof.x.edit.setreadonly(function() {
                            $.ajax({
                                type : 'POST',
                                data : {cross_id : odof.x.edit.cross_id,
                                        rsvp     : odof.x.edit.rsvpAction},
                                url  : site_url + '/rsvp/save',
                                dataType : 'json',
                                success  : function(data) {
                                    if (data != null && data.response.success === 'true') {
                                        var new_myrsvp = {yes : 1, no : 2, maybe : 3}[data.response.state];
                                        odof.x.render.changeConfirmed(new_myrsvp);
                                        odof.exfee.gadget.changeRsvp(
                                            'xExfeeArea', myIdentity.external_identity,
                                            myrsvp = new_myrsvp
                                        );
                                        odof.x.render.showRsvp();
                                    }
                                }
                            });
                        });
                        return;
                    }

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
                                    var new_myrsvp = {yes : 1, no : 2, maybe : 3}[data.response.state];
                                    odof.x.render.changeConfirmed(new_myrsvp);
                                    odof.exfee.gadget.changeRsvp(
                                        'xExfeeArea', myIdentity.external_identity,
                                        myrsvp = new_myrsvp
                                    );
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
            //$('#x_rsvp_change').show();
            $('#x_rsvp_typeinfo').show();
            $('#x_rsvp_btns').hide();
            //$('.x_rsvp_button').hide();
            event.preventDefault();
        }
    };


    ns.submitData = function() {
        // title
        odof.x.edit.editTitle(false);
        odof.x.edit.saveTitle();
        // desc
        odof.x.edit.editDesc(false);
        odof.x.edit.saveDesc();
        // time
        odof.x.edit.editTime(false);
        odof.x.edit.saveTime();
        // place
        odof.x.edit.editPlace(false);
        odof.x.edit.savePlace();
        // exfee = JSON.stringify(ns.getexfee());
        // $('#edit_cross_submit_loading').show();

        odof.record.reset();
        odof.x.edit.xBackup = null;
        odof.x.edit.freeze();

        if (typeof window.mapRequest !== 'undefined') {
            window.mapRequest.abort();
        }

        $.ajax({
            url  : location.href.split('?').shift() + '/crossEdit',
            type : 'POST',
            dataType : 'json',
            data : {rand            : Math.round(Math.random() * 10000000000),
                    title           : crossData.title,
                    time            : crossData.begin_at,
                    timezone        : crossData.timezone,
                    origin_begin_at : crossData.origin_begin_at,
                    desc            : crossData.description,
                    place           : JSON.stringify(crossData.place),
                    exfee           : JSON.stringify(odof.exfee.gadget.getExfees('xExfeeArea'))},
            success : function(data) {
                if (data.error) {
                    $('#error_msg').html(data.msg);
                }
            },
            complete : function() {
                $('#edit_x_bar').slideUp(300);
             // $('#edit_cross_submit_loading').hide();
            }
        });
    };


    ns.submitExfee = function() {
        if (!odof.x.edit.skipFreeze) {
            odof.x.edit.freeze();
        }
        for (var i in odof.exfee.gadget.exfeeInput['xExfeeArea']) {
            if (parseInt(odof.exfee.gadget.exfeeInput['xExfeeArea'][i].identity_id)
            === parseInt(myIdentity.id)) {
                myrsvp = odof.exfee.gadget.exfeeInput['xExfeeArea'][i].rsvp;
                odof.x.render.showRsvp();
                break;
            }
        }
        if (typeof window.mapRequest !== 'undefined') {
            window.mapRequest.abort();
        }
        $.ajax({
            url  : location.href.split('?').shift() + '/crossEdit',
            type : 'POST',
            dataType : 'json',
            data : {
                title      : crossData.title,
                exfee_only : true,
                exfee      : JSON.stringify(odof.exfee.gadget.getExfees('xExfeeArea'))
            },
            success : function(data) {
                if (odof.exfee.gadget.left) {
                    location.href = '/s/profile';
                }
                if (!data) {
                    return;
                }
                if (!data.success) {
                    switch (data.error) {
                        case 'token_expired':
                            odof.x.edit.setreadonly();
                    }
                }
            }
        });
    };


    ns.freeze = function(xOnly) {
        var lastX = odof.record.last(),
            curX  = {title           : crossData.title,
                     description     : crossData.description,
                     begin_at        : crossData.begin_at,
                     time_type       : crossData.time_type,
                     timezone        : crossData.timezone,
                     origin_begin_at : crossData.origin_begin_at,
                     state           : crossData.state,
                     place           : crossData.place};
        if (xOnly && lastX && JSON.stringify(lastX) === JSON.stringify(curX)) {
            return;
        }
        odof.record.push({cross : curX, exfee : odof.exfee.gadget.exfeeInput['xExfeeArea']});
    };


    ns.record = function(item) {
        if (!item) {
            return;
        }
        if (odof.x.edit.xBackup) {
            for (var i in item.cross) {
                crossData[i] = odof.util.clone(item.cross[i]);
            }
            odof.x.render.showTitle();
            odof.x.render.showDesc(true);
            odof.x.render.showTime();
            odof.x.render.showPlace();
        }
        crossExfee = odof.util.clone(item.exfee);
        odof.x.edit.skipFreeze = true;
        odof.exfee.gadget.make('xExfeeArea', crossExfee, true, odof.x.edit.submitExfee);
        odof.x.edit.skipFreeze = false;
    };

})(ns);


$(document).ready(function() {
    // fix anytime
    if (crossData.time_type === 'Anytime') {
        crossData.begin_at = crossData.begin_at.split(' ')[0];
    }

    odof.record.init(odof.x.edit.record);
    odof.x.render.show(true);
    odof.x.edit.cross_id     = cross_id;
    odof.x.edit.token        = token;
    odof.x.edit.location_uri = location_uri;
    odof.exfee.gadget.make('xExfeeArea', crossExfee, true, odof.x.edit.submitExfee, true);
    odof.x.edit.freeze();

    $('#private_icon').mousemove(function() { $('#private_hint').show(); });
    $('#private_icon').mouseout(function() { $('#private_hint').hide(); });
    $('#edit_icon').mousemove(function() { $('#edit_icon_desc').show(); });
    $('#edit_icon').mouseout(function() { $('#edit_icon_desc').hide(); });
    $('#x_rsvp_change').bind('click', odof.x.edit.editRsvp)
    $('.x_rsvp_button').bind('click', odof.x.edit.editRsvp)
    $('#submit_data').bind('click', odof.x.edit.submitData);
    $('#edit_icon').bind('click', odof.x.edit.startEdit);
    $('#revert_x_btn').bind('click', odof.x.edit.revertX);

    if (token_expired == 'true') {
        $('#x_conversation_input').bind('click', function(e) {
            odof.x.edit.setreadonly(clickCallBackFunc);
        })
    } else {
        $('#x_conversation_input').focus();
        $('#x_conversation_input').bind('keydown', odof.x.edit.conversationKeydown);
    }

    if (token_expired == 'false') {
        $('#cross_identity_btn').unbind('click');
        $('#cross_identity_btn').bind('click', function() {
            odof.x.edit.setreadonly(function() {
                window.location.href = odof.x.edit.location_uri;
            });
        });
    }

    odof.x.render.setXTitleBackground();

});
