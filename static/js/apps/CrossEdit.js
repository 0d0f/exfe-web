/**
 * @Description:    Cross edit module
 * @Author:         HanDaoliang <handaoliang@gmail.com>, Leask Huang <leask@exfe.com>
 * @createDate:     Sup 15,2011
 * @CopyRights:     http://www.exfe.com
**/

var moduleNameSpace = "odof.cross.edit";
var ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns){

    ns.editURI = window.location.href;
    ns.cross_time_bubble_status = 0;

    /**
     * display edit bar
     *
     * */
    ns.showEditBar = function(){
        jQuery("#edit_cross_bar").slideDown(300);
        jQuery("#submit_data").bind("click",function(){
            odof.cross.edit.submitData();
        });
        jQuery("#cross_titles").addClass("enable_click");
        jQuery("#cross_titles").bind("click",function(){
            odof.cross.edit.bindEditTitlesEvent();
        });

        //bind event for cross time container
        jQuery("#cross_times_area").addClass("enable_click");
        jQuery("#cross_times_area").bind("click", odof.cross.edit.bindEditTimesEvent);

        //bind event for cross place container
        jQuery("#cross_place_area").addClass("enable_click");
        jQuery("#cross_place_area").bind("click",odof.cross.edit.bindEditPlaceEvent);

        jQuery("#cross_desc").show();
        jQuery("#cross_desc_short").hide();

        jQuery("#cross_desc").addClass("enable_click");
        jQuery("#cross_desc").bind("click",function(){
            odof.cross.edit.bindEditDescEvent();
        });

        // exfee edit begins
        odof.cross.edit.numNewIdentity = 0;
        odof.cross.edit.exfeeInputTips = $('#exfee_input').val();
        $('#exfee_edit').fadeIn();
        $('#exfee_edit').bind('click', function() {
            odof.cross.edit.exfeeEdit('edit');
        });
        $('#exfee_remove').bind('click', function() {
            odof.cross.edit.exfeeEdit('remove');
        });
        $('#exfee_input').bind('focus', function() {
            $('#exfee_input').val(
                $('#exfee_input').val() === odof.cross.edit.exfeeInputTips ? '' : $('#exfee_input').val()
            );
        });
        $('#exfee_input').bind('blur', function() {
            $('#exfee_input').val(
                $('#exfee_input').val()
              ? $('#exfee_input').val()
              : odof.cross.edit.exfeeInputTips
            );
        });
        $('#exfee_input').keypress(function(e) {
            if ((e.keyCode ? e.keyCode : e.which) == 13) {
                odof.cross.edit.identityExfee();
                e.preventDefault();
            }
        });
        $('#exfee_submit').bind('click', function() {
            odof.cross.edit.identityExfee();
        });
        $('.exfee_del').live('click', function() {
            $(this.parentNode).remove();
        });
        $('#exfee_revert').bind('click', function() {
            odof.cross.edit.revertExfee();
        });
        $('#exfee_done').bind('click', function() {
            odof.cross.edit.exfeeEdit();
        });

    };

    /**
     * while user click titles, show edit textarea.
     *
     * */
    ns.bindEditTitlesEvent = function(){
        jQuery("#cross_titles").hide();
        jQuery("#cross_titles_textarea").show();
        jQuery('#cross_titles_textarea').bind("clickoutside",function(event) {
            if(event.target != jQuery("#cross_titles")[0]){
                jQuery("#cross_titles").html(jQuery("#cross_titles_textarea").val());
                jQuery("#cross_titles_textarea").hide();
                jQuery("#cross_titles_textarea").unbind("clickoutside");
                jQuery("#cross_titles").show();
                formatCross();
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
                if(event.target.parentNode.parentNode != jQuery("#cross_container")[0]){
                    jQuery("#cross_place_bubble").hide();
                    jQuery("#cross_place_bubble").unbind("clickoutside");
                }else{
                    jQuery("#place_content").bind("keyup",function(){
                        var strPlace = $('#place_content').val(),
                            arrPlace = strPlace.split(/\r|\n|\r\n/),
                            prvPlace = [];
                        arrPlace.forEach(function(item, i) {
                            if ((item = odof.util.trim(item))) {
                                prvPlace.push(item);
                            }
                        });
                        $('#pv_place_line1').html(prvPlace.shift());
                        $('#pv_place_line2').html(prvPlace.join('<br />'));
                        if ($('#pv_place_line1').hasClass('pv_place_line1_double') && $('#pv_place_line1').height() < 70) {
                            $('#pv_place_line1').addClass('pv_place_line1_normal').removeClass('pv_place_line1_double');
                        }
                        if ($('#pv_place_line1').hasClass('pv_place_line1_normal') && $('#pv_place_line1').height() > 53) {
                            $('#pv_place_line1').addClass('pv_place_line1_double').removeClass('pv_place_line1_normal');
                        }
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
        jQuery('#cross_desc_textarea').bind("clickoutside",function(event) {
            if(event.target.parentNode != jQuery("#cross_desc")[0]){
                var str = odof.cross.edit.formateString(jQuery("#cross_desc_textarea").val());
                jQuery("#cross_desc").html(str);
                jQuery("#cross_desc_textarea").slideUp(400);
                jQuery("#cross_desc_textarea").unbind("clickoutside");
                jQuery("#cross_desc").show();
            }
        });
    };

    ns.formateString = function(str){
        var strstr = "0107a88030bfca5e5f72346966901d6a";
            str = str.replace(/(\r\n|\n|\r)/gm,strstr);
        var strArr = str.split(strstr);
        var reString = "";
        for(var i=0; i<strArr.length; i++){
            reString += '<p class="text">'+ strArr[i] +'</p>';
        }
        return reString;
    };

    /**
     * while user submit data
     *
     * */
    ns.submitData = function() {
        var title = jQuery("#cross_titles_textarea").val(),
            time  = jQuery("#datetime").val(),
            placeline1 = $('#pv_place_line1').html(),
            placeline2 = $('#pv_place_line2').html().replace(/<br>/g, '\\r'),
            desc  = jQuery("#cross_desc_textarea").val(),
            exfee = JSON.stringify(ns.getexfee());
        jQuery.ajax({
            url:ns.editURI + "/crossEdit",
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
        ns.arrIdentitySub = [];
        var arrIdentityOri = $('#exfee_input').val().split(/,|;|\r|\n|\t/);
        $('#exfee_input').val('');
        for (var i in arrIdentityOri) {
            if ((arrIdentityOri[i] = odof.util.trim(arrIdentityOri[i]))) {
                ns.arrIdentitySub.push(odof.util.parseId(arrIdentityOri[i]));
            }
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
                    name         = data.response.identities[i].name;
                    var avatar_file_name = data.response.identities[i].avatar_file_name;
                    if ($('#exfee_' + id).attr('id') == null) {
                        exfee_pv += '<li id="exfee_'   + id + '" '
                                  +     'identity="'   + identity + '" '
                                  +     'identityid="' + id + '" '
                                  +     'identityname="' + name + '" '
                                  +     'class="exfee_exist exfee_item" '
                                  +     'invited="false">'
                                  +     '<button type="button" class="exfee_del"></button>'
                                  +     '<p class="pic20">'
                                  +         '<img src="/'+odof.comm.func.getHashFilePath("eimgs", avatar_file_name)+'/80_80_' + avatar_file_name + '" alt="">'
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
                                  +         '<em class="c2"></em>'
                                  +     '</p>'
                                  + '</li>';
                    }
                    identifiable[identity] = true;
                }
                for (i in ns.arrIdentitySub) {
                    if (!identifiable[ns.arrIdentitySub[i].id]) {
                        identity = ns.arrIdentitySub[i].id;
                        name     = ns.arrIdentitySub[i].name
                                 ? ns.arrIdentitySub[i].name
                                 : ns.arrIdentitySub[i].id;
                        ns.numNewIdentity++;
                        exfee_pv += '<li id="newexfee_' + ns.numNewIdentity + '" '
                                  +     'identity="'    + identity + '" '
                                  +     'identityname="' + name + '" '
                                  +     'class="exfee_new exfee_item" '
                                  +     'invited="false">'
                                  +     '<button type="button" class="exfee_del"></button>'
                                  +     '<p class="pic20">'
                                  +         '<img src="/eimgs/80_80_default.png" alt="">'
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
                                  +         '<em class="c2"></em>'
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
     // target.className = 'c' + (intC > 4 ? 1 : intC);
        target.className = 'c' + (intC > 2 ? 1 : intC);
        ns.summaryExfee();
        ns.updateCheckAll();
    };

    /**
     * update "check all" status
     * by Leask
     * */
    ns.updateCheckAll = function() {
        if ($('.cs > .c2').length) {
            $('#check_all > span').html('Check all');
            $('#check_all > em').attr('class', 'c2');
        } else {
            $('#check_all > span').html('Uncheck all');
            $('#check_all > em').attr('class', 'c1');
        }
    };

    /**
     * do "check all" or "uncheck all"
     * by Leask
     * */
    ns.checkAll = function() {
        switch ($('#check_all > em')[0].className) {
            case 'c1':
                $('.cs > .c1').attr('class', 'c2');
                break;
            case 'c2':
                $('.cs > .c2').attr('class', 'c1');
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
                                  confirmed      : $('#' + element_id + ' > .cs > em')[0].className === 'c1' ? 1 : 0,
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

})(ns);

jQuery(document).ready(function() {

    jQuery("#edit_icon").bind("click",function() {
        odof.cross.edit.showEditBar();
    });

    jQuery("#revert_cross_btn").bind("click",function() {
        odof.cross.edit.revertCross();
    });

    jQuery("#desc_expand_btn").bind("click",function() {
        odof.cross.edit.expandDesc();
    });

    // exfee edit init
    $('#exfee_edit_box').hide();
    $('#exfee_remove').hide();
    $('#exfee_edit').hide();
    $('.exfee_del').hide();
    $('.ex_identity').hide();
    odof.cross.edit.updateCheckAll();
    $('.cs > em').live('click', function(event) {
        odof.cross.edit.changeRsvp(event.target);
    });
    $('#check_all').bind('click', function() {
        odof.cross.edit.checkAll();
    });
    $('.exfee_item').live('mouseenter mouseleave', function(event) {
        odof.cross.edit.showExternalIdentity(event);
    });
    odof.cross.edit.rollingExfee = null;
    odof.cross.edit.exfeeRollingTimer = setInterval(odof.cross.edit.rollExfee, 50);

});
