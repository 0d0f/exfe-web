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

    ns.editURI = window.location.href;

    ns.cross_time_bubble_status = 0;

    ns.msgSubmitting = false;


    /**
     * display edit bar
     **/
    ns.startEdit = function(){
        // submit
        $('#edit_x_bar').slideDown(300);
        // title
        $('#x_titles').addClass('enable_click');
        $('#x_title').bind('click', odof.x.edit.editTitle);
        // desc
        // 'Write some words about this X.'
        $('#x_desc').addClass('enable_click');
        $('#x_desc').bind('click', odof.x.edit.editDesc);
        // time
        $("#x_time_area").addClass('enable_click');
        $("#x_time_area").bind('click', odof.x.edit.editTime);
        // place
        $('#x_place_area').addClass('enable_click');
        $('#cross_place_area').bind('click', odof.x.edit.editPlace);
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
    $("#submit_data").bind('click', odof.x.edit.submitData);
    $("#edit_icon").bind('click', odof.x.edit.startEdit);

return;


    jQuery("#revert_cross_btn").bind("click",function() {
        odof.cross.edit.revertCross();
    });

    jQuery("#desc_expand_btn").bind("click",function() {
        odof.cross.edit.expandDesc();
    });

    $('.exfee_item').live('mouseenter mouseleave', function(event) {
        odof.cross.edit.showExternalIdentity(event);
    });
    odof.cross.edit.rollingExfee = null;
    odof.cross.edit.exfeeRollingTimer = setInterval(odof.cross.edit.rollExfee, 50);

});










































if (0) {
    /**
     * while user click titles, show edit textarea.
     *
     * */
    ns.bindEditTitlesEvent = function(){
        jQuery('#cross_titles').hide();
        jQuery('#cross_titles_textarea').show();
        jQuery('#cross_titles_textarea').bind('clickoutside', function(event) {
            if(event.target != $('#cross_titles')[0]){
                var strTitle = odof.util.trim($('#cross_titles_textarea').val());
                strTitle = strTitle ? strTitle : ('Meet ' + id_name);
                $('#cross_titles_textarea').hide();
                $('#cross_titles_textarea').unbind('clickoutside');
                $('#cross_titles').show();
                $('#cross_titles_textarea').val(strTitle);
                $('#cross_titles').html(strTitle);
                document.title = 'EXFE - ' + strTitle;
                odof.cross.index.formatCross();
            }
        });
    };

    /**
     * user edit time, show edit time area.
     *
     * */
    ns.bindEditTimesEvent = function(){
        //check if had bind a event for #cross_time_bubble
        var eventTemp = jQuery("#cross_time_bubble").data("events");
        //console.log(eventTemp);
        if(!eventTemp){
            jQuery('#cross_time_bubble').bind("clickoutside",function(event) {
                //console.log(event.target.parentNode);
                if(event.target.parentNode != jQuery("#cross_times_area")[0]){
                    //console.log("aaaa");
                    jQuery("#cross_time_bubble").hide();
                    jQuery("#cross_time_bubble").unbind("clickoutside");
                }else{
                    //console.log("bbbb");
                    jQuery("#cross_time_bubble").show();
                }
            });
        }

        /*
        jQuery(document).bind('click',function(e){
            console.log(e.target.parentNode);
        });
        */
        var calCallBack = function(displayCalString, standardTimeString) {
            $('#cross_times').html(displayCalString);
            $('#datetime').val(standardTimeString);
            $('#cross_times_area > h3').html(
                odof.util.getRelativeTime(
                    Date.parse(odof.util.getDateFromString(standardTimeString)) / 1000
                )
            );
        };

        var timeDisplayContainer = document.getElementById('cross_datetime_original');
        exCal.initCalendar(timeDisplayContainer, 'cross_time_container', calCallBack);
    };

    /**
     * user edit place, show edit place area.
     *
     * */
    ns.bindEditPlaceEvent = function(){
        var placeEventTemp = jQuery("#cross_place_bubble").data("events");
        //console.log(placeEventTemp);
        if(!placeEventTemp){
            jQuery('#cross_place_bubble').bind("clickoutside",function(event) {
                if(event.target.parentNode != jQuery("#cross_place_area")[0]){
                    jQuery("#cross_place_bubble").hide();
                    jQuery("#cross_place_bubble").unbind("clickoutside");
                }else{
                    jQuery("#place_content").bind("keyup",function(){
                        var strPlace = $('#place_content').val(),
                            arrPlace = strPlace.split(/\r|\n|\r\n/),
                            prvPlace = [],
                            strLine1 = 'Somewhere',
                            strLine2 = '';
                        if (strPlace) {
                            arrPlace.forEach(function(item, i) {
                                if ((item = odof.util.trim(item))) {
                                    prvPlace.push(item);
                                }
                            });
                            strLine1 = prvPlace.shift();
                            strLine2 = prvPlace.join('<br />');
                        }
                        $('#pv_place_line1').html(strLine1);
                        $('#pv_place_line2').html(strLine2);
                        odof.cross.index.formatCross();
                    });
                    jQuery("#cross_place_bubble").show();
                }
            });
        }
    };

    /**
     * User Edit cross description
     *
     * */
    ns.bindEditDescEvent = function(){
        jQuery("#cross_desc").hide();
        jQuery("#cross_desc_textarea").slideDown(400);
        jQuery('#cross_desc_textarea').bind('clickoutside', function(event) {
            var target = event.target;
            while (target.id !== 'cross_desc' && target.parentNode) {
                target = target.parentNode;
            }
            if (target.id === 'cross_desc') {
                return;
            }
            var str = odof.cross.edit.formateString(jQuery("#cross_desc_textarea").val());
            $('#cross_desc').html(
                $('#cross_desc_textarea').val() ? str : 'Write some words about this X.'
            );
            jQuery("#cross_desc_textarea").slideUp(400);
            jQuery("#cross_desc_textarea").unbind("clickoutside");
            jQuery("#cross_desc").show();
        });
    };

    ns.formateString = function(str){
        var converter = new Showdown.converter();
        return converter.makeHtml(str);
    };

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
        if(!JSONData.error){
            window.location.href = ns.editURI;
        }else{
            jQuery("#error_msg").html(JSONData.msg);
        }

    };

    /**
     * revert cross page
     *
     * */
    ns.revertCross = function(){
        window.location.href=ns.editURI;
    };

    /**
     * expand cross description
     *
     * */
    ns.expandDesc = function(){
        jQuery("#cross_desc").show();
        jQuery("#cross_desc_short").hide();
    };

    /**
     * change exfee editing mode
     * by Leask
     * */
    ns.exfeeEdit = function(status){
        ns.exfeeEditStatus = status;
        switch (status) {
            case 'edit':
                if (!$('.editing').length) {
                    $('#exfee_area').toggleClass('editing');
                }
                $('#exfee_edit_box').fadeIn();
                $('#exfee_remove').fadeIn();
                $('#exfee_edit').hide();
                $('#exfee_remove').attr('disabled', false);
                $('#exfee_area').bind('clickoutside', function(event) {
                    if ($(event.target).hasClass('exfee_del')) {
                        return;
                    }
                    odof.cross.edit.exfeeEdit();
                });
                $('.exfee_del').hide();
                ns.exfees = $('#exfee_area > .samlcommentlist').html();
                break;
            case 'remove':
                $('#exfee_remove').attr('disabled', true);
                $('#exfee_area').bind('click', function(event) {
                    if (event.target.id === 'exfee_remove' || event.target.className === 'exfee_del') {
                        return;
                    }
                    odof.cross.edit.exfeeEdit('edit');
                });
                $('.exfee_del').show();
                break;
            default:
                $('#exfee_area').toggleClass('editing', false);
                $('#exfee_edit_box').fadeOut();
                $('#exfee_remove').hide();
                $('#exfee_edit').fadeIn();
                $('#exfee_edit_box').unbind('clickoutside');
                $('.exfee_del').hide();
                $('#exfee_input').val(odof.cross.edit.exfeeInputTips);
        }
        if (status !== 'remove') {
            $('#exfee_area').unbind('click');
        }
    };

    /**
     * revert exfee
     * by Leask
     * */
    ns.revertExfee = function() {
        $('#exfee_area > .samlcommentlist').html(ns.exfees);
        $('#exfee_input').val(odof.cross.edit.exfeeInputTips);
    };

    /**
     * identity exfee from server
     * by Leask
     * */
    ns.identityExfee = function() {
        if (!ns.chkExfeeFormat()) {
            return;
        }

        $.ajax({
            type     : 'GET',
            url      : site_url + '/identity/get?identities=' + JSON.stringify(ns.arrIdentitySub),
            dataType : 'json',
            success  : function(data) {
                var exfee_pv     = '',
                    identifiable = {},
                    id           = '',
                    identity     = '',
                    name         = '';
                for (var i in data.response.identities) {
                    id           = data.response.identities[i].id;
                    identity     = data.response.identities[i].external_identity;
                    name         = data.response.identities[i].name
                                 ? data.response.identities[i].name
                                 : data.response.identities[i].external_identity;
                    var avatar_file_name = data.response.identities[i].avatar_file_name;
                    if ($('#exfee_' + id).attr('id') == null) {
                        exfee_pv += '<li id="exfee_'   + id + '" '
                                  +     'identity="'   + identity + '" '
                                  +     'identityid="' + id + '" '
                                  +     'identityname="' + (name === identity ? '' : name) + '" '
                                  +     'class="exfee_exist exfee_item" '
                                  +     'invited="false">'
                                  +     '<button type="button" class="exfee_del"></button>'
                                  +     '<p class="pic20">'
                                  +         '<img src="'+odof.comm.func.getUserAvatar(avatar_file_name, 80, img_url)+'" alt="">'
                                  +     '</p>'
                                  +     '<div class="smcomment">'
                                  +         '<div>'
                                  +             '<span class="ex_name' + (name === identity ? ' external_identity' : '') + '">'
                                  +                 name
                                  +             '</span>'
                                  +             '<span class="ex_identity external_identity">'
                                  +                 (identity === name ? '' : identity)
                                  +             '</span>'
                                  +         '</div>'
                                  +     '</div>'
                                  +     '<p class="cs">'
                                  +         '<em class="c0"></em>'
                                  +     '</p>'
                                  + '</li>';
                    }
                    identifiable[identity.toLowerCase()] = true;
                }
                for (i in ns.arrIdentitySub) {
                    var idUsed = false;
                    $('.exfee_new').each(function() {
                        if ($(this).attr('identity').toLowerCase() === ns.arrIdentitySub[i].id.toLowerCase()) {
                            idUsed = true;
                        }
                    });
                    if (!identifiable[ns.arrIdentitySub[i].id.toLowerCase()] && !idUsed) {
                        identity = ns.arrIdentitySub[i].id;
                        name     = ns.arrIdentitySub[i].name
                                 ? ns.arrIdentitySub[i].name
                                 : ns.arrIdentitySub[i].id;
                        ns.numNewIdentity++;
                        exfee_pv += '<li id="newexfee_' + ns.numNewIdentity + '" '
                                  +     'identity="'    + identity + '" '
                                  +     'identityname="' + (name === identity ? '' : name) + '" '
                                  +     'class="exfee_new exfee_item" '
                                  +     'invited="false">'
                                  +     '<button type="button" class="exfee_del"></button>'
                                  +     '<p class="pic20">'
                                  +         '<img src="'+img_url+'/web/80_80_default.png" alt="">'
                                  +     '</p>'
                                  +     '<div class="smcomment">'
                                  +         '<div>'
                                  +             '<span class="ex_name' + (name === identity ? ' external_identity' : '') + '">'
                                  +                 name
                                  +             '</span>'
                                  +             '<span class="ex_identity external_identity">'
                                  +                 (identity === name ? '' : identity)
                                  +             '</span>'
                                  +         '</div>'
                                  +     '</div>'
                                  +     '<p class="cs">'
                                  +         '<em class="c0"></em>'
                                  +     '</p>'
                                  + '</li>';
                    }
                }
                $('#exfee_area > .samlcommentlist').html($('#exfee_area > .samlcommentlist').html() + exfee_pv);
                switch (ns.exfeeEditStatus) {
                    case 'edit':
                        $('.exfee_del').hide();
                        break;
                    case 'remove':
                        $('.exfee_del').show();
                }
                ns.summaryExfee();
                $('.ex_identity').hide();
                $('#exfee_input').val('');
            }
        });
    };

    /**
     * summary exfee
     * by Leask
     * */
    ns.summaryExfee = function() {
        $('.bignb').html($('.cs > .c1').length);
        $('.malnb').html($('.samlcommentlist > li').length);
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

    /**
     * update "check all" status
     * by Leask
     * */
    ns.updateCheckAll = function() {
        if ($('.cs > .c1').length < $('.samlcommentlist > li').length) {
            $('#check_all > span').html('Check all');
            $('#check_all > em').attr('class', 'c1');
        } else {
            $('#check_all > span').html('Uncheck all');
            $('#check_all > em').attr('class', 'c0');
        }
        // submit
        jQuery.ajax({
            url : location.href.split('?').shift() + '/crossEdit',
            type : 'POST',
            dataType : 'json',
            data : {ctitle     : $('#cross_titles_textarea').val(),
                    exfee_only : true,
                    exfee      : JSON.stringify(ns.getexfee())},
            success:function(data){
                if (!data) {
                    return;
                }
                if (!data.success) {
                    switch (data.error) {
                        case 'token_expired':
                            odof.cross.index.setreadonly();
                    }
                }
            }
        });
    };

    /**
     * do "check all" or "uncheck all"
     * by Leask
     * */
    ns.checkAll = function() {
        switch ($('#check_all > em')[0].className) {
            case 'c0':
                $('.cs > em').attr('class', 'c0');
                break;
            case 'c1':
                $('.cs > em').attr('class', 'c1');
        }
        ns.updateCheckAll();
    };

    /**
     * get exfee editing result
     * by Leask
     * */
    ns.getexfee = function() {
        var result = [];
        function collect(obj, exist)
        {
            var exfee_identity = $(obj).attr('identity'),
                element_id     = $(obj).attr('id'),
                item           = {exfee_name     : $(obj).attr('identityname'),
                                  exfee_identity : exfee_identity,
                                  confirmed      : parseInt($('#' + element_id + ' > .cs > em')[0].className.substr(1)),
                                  identity_type  : odof.util.parseId(exfee_identity).type};
            if (exist) {
                item.exfee_id  = $(obj).attr('identityid');
            }
            result.push(item);
        }
        $('.exfee_exist').each(function() {
            collect(this, true);
        });
        $('.exfee_new').each(function() {
            collect(this);
        });
        return result;
    };

    /**
     * show external identity
     * by Leask
     * */
    ns.showExternalIdentity = function(event) {
        var target = $(event.target);
        while (!target.hasClass('exfee_item')) {
            target = $(target[0].parentNode);
        }
        var id     = target[0].id;
        if (!id) {
            return;
        }
        switch (event.type) {
            case 'mouseenter':
                ns.rollingExfee = id;
                $('#' + id + ' > .smcomment > div > .ex_identity').fadeIn(100);
                break;
            case 'mouseleave':
                ns.rollingExfee = null;
                $('#' + id + ' > .smcomment > div > .ex_identity').fadeOut(100);
                var rollE = $('#' + id + ' > .smcomment > div');
                rollE.animate({
                    marginLeft : '+=' + (0 - parseInt(rollE.css('margin-left')))},
                    700
                );
        }
    };

    /**
     * roll the exfee that with long name
     * by Leask
     * */
    ns.rollExfee = function() {
        var maxWidth = 200;
        if (!ns.rollingExfee) {
            return;
        }
        var rollE    = $('#' + ns.rollingExfee + ' > .smcomment > div'),
            orlWidth = rollE.width(),
            curLeft  = parseInt(rollE.css('margin-left')) - 1;
        if (orlWidth <= maxWidth) {
            return;
        }
        curLeft = curLeft <= (0 - orlWidth) ? maxWidth : curLeft;
        rollE.css('margin-left', curLeft + 'px');
    };

    /**
     * get auto complete infos of exfees from server
     * by Leask
     */
    ns.chkComplete = function(strKey) {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/identity/complete?key=' + strKey,
            dataType : 'json',
            success  : function(data) {
                var strFound = '';
                for (var item in data) {
                    var spdItem = odof.util.trim(item).split(' '),
                        strId   = spdItem.pop(),
                        strName = spdItem.length ? (spdItem.join(' ') + ' &lt;' + strId + '&gt;') : strId;
                    if (!strFound) {
                        odof.cross.edit.strExfeeCompleteDefault = strId;
                    }
                    strFound += '<option value="' + strId + '"' + (strFound ? '' : ' selected') + '>' + strName + '</option>';
                }
                if (strFound && odof.cross.edit.completeTimer && $('#exfee_input').val().length) {
                    $('#exfee_complete').html(strFound);
                    $('#exfee_complete').slideDown(50);
                } else {
                    $('#exfee_complete').slideUp(50);
                }
                clearTimeout(odof.cross.edit.completeTimer);
                odof.cross.edit.completeTimer = null;
            }
        });
    };

    /**
     * check exfee format
     * by Leask
     */
    ns.chkExfeeFormat = function() {
        ns.arrIdentitySub = [];
        var strExfees = $('#exfee_input').val().replace(/\r|\n|\t/, '');
        $('#exfee_input').val(strExfees);
        var arrIdentityOri = strExfees.split(/,|;/);
        for (var i in arrIdentityOri) {
            if ((arrIdentityOri[i] = odof.util.trim(arrIdentityOri[i]))) {
                var exfee_item = odof.util.parseId(arrIdentityOri[i]);
                if (exfee_item.type !== 'email') {
                    return false;
                }
                ns.arrIdentitySub.push(exfee_item);
            }
        }
        return ns.arrIdentitySub.length > 0;
    };

    /**
     * auto complete for exfees
     * by Leask
     */
    ns.complete = function() {
        var strValue = $('#exfee_complete').val();
        if (strValue === '') {
            return;
        }
        var arrInput = $('#exfee_input').val().split(/,|;|\r|\n|\t/);
        arrInput.pop();
        $('#exfee_input').val(arrInput.join('; ') + (arrInput.length ? '; ' : '') + strValue);
        clearTimeout(odof.cross.edit.completeTimer);
        odof.cross.edit.completeTimer = null;
        $('#exfee_complete').slideUp(50);
        ns.identityExfee();
        $('#exfee_input').focus();
    };
}
