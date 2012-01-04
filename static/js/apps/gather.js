/**
 * @Description: X gather module
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Jan 2, 2012
 * @CopyRights:  http://www.exfe.com
 */


var moduleNameSpace = 'odof.x.gather',
    ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns) {

    //ns.defaultTitle = 'Edit title here';

    ns.x = {title       : '',
            description : '',
            place_id    : '',
            datetime    : '',
            draft_id    : 0};
            
    ns.curCross        = '';
    
    ns.new_identity_id = 0; // @todo
    
    ns.xSubmitting     = false;
    
    ns.autoSubmit      = false;

    ns.updateTitle = function() {
        this.x.title   = $('#gather_title').val();
        document.title = 'EXFE - ' + this.x.title;
    };


    ns.updateDescription = function() {

    };


    ns.updateTime = function() {

    };


    ns.updatePlace = function() {

    };


    ns.saveDraft = function() {
        var strCross = JSON.stringify(summaryX());

        if (curCross !== strCross) {
            $.ajax({
                type     : 'POST',
                url      : site_url + '/x/savedraft',
                dataType : 'json',
                data     : {draft_id : draft_id,
                            cross    : strCross},
                success  : function (data) {
                    draft_id = data && data.draft_id ? data.draft_id : draft_id;
                }
            });
            curCross = strCross;
        }
    };


    ns.getDraft = function() {
        $.ajax({
            type     : 'GET',
            url      : site_url + '/x/getdraft',
            dataType : 'json',
            success  : function(draft) {
                if (!draft) {
                    return;
                }
                // @todo
                $('#g_title').val(draft.title);
                $('#g_description').val(draft.description);
                $("input[name='datetime']").val(draft.datetime);
                $('#g_place').val(draft.place);
                $('#hostby').val(draft.hostby);
                $('#exfee_pv').html(draft.exfee);
                updateExfeeList();
            }
        });
    };


    ns.submitX = function() {
        if (xSubmitting) {
            return;
        }
        xSubmitting = true;

        $('#gather_submit_ajax').show();
        $('#gather_failed_hint').hide();
        $('#gather_x').removeClass('mouseover');
        $('#gather_x').removeClass('mousedown');
        $('#gather_x').addClass('disabled');
        $('#gather_x').html('');

        var cross = summaryX();
        cross['draft_id'] = draft_id;

        $.ajax({
            type     : 'POST',
            url      : site_url + '/x/gather',
            dataType : 'json',
            data     : cross,
            success  : function(data) {
                if (data) {
                    if (data.success) {
                        location.href = '/!' + data.crossid;
                        return;
                    } else {
                        switch (data.error) {
                            case 'notlogin':
                                autoSubmit = true;
                                odof.user.status.doShowLoginDialog(null, afterLogin);
                                break;
                            case 'notverified':
                                // @todo: inorder to gather X, user must be verified
                                // odof.exlibs.ExDialog.initialize('');
                        }
                    }
                }
                var curTop = parseInt($('#gather_submit_blank').css('padding-top'));
                $('#gather_submit_blank').css(
                    'padding-top',
                    (curTop ? curTop : (curTop + 20)) + 'px'
                );
                $('#gather_submit_ajax').hide();
                $('#gather_failed_hint').show();
                $('#gather_x').removeClass('mouseover');
                $('#gather_x').removeClass('mousedown');
                $('#gather_x').removeClass('disabled');
                $('#gather_x').html('Re-submit');
                xSubmitting = false;
            },
            failure : function(data) {
                $('#gather_submit_ajax').hide();
                $('#gather_failed_hint').show();
                $('#gather_x').removeClass('mouseover');
                $('#gather_x').removeClass('mousedown');
                $('#gather_x').removeClass('disabled');
                $('#gather_x').html('Re-submit');
                xSubmitting = false;
            }
        });
    };


    ns.afterLogin = function(status) {
        // @handaoliang 检查一下登陆后的会掉函数调用是不是有问题？
        confole.log(status);
        if (status.user_status !== 1) {
            return;
        }
        gTitlesDefaultText = 'Meet ' + status.user_name;
        document.title = 'EXFE - ' + gTitlesDefaultText;
        $('#gather_hostby').attr('disabled', true);
        var exfee_pv = [];
        $.ajax({
            type     : 'GET',
            url      : site_url + '/identity/get?identities=' + JSON.stringify([odof.util.parseId($("#hostby").val())]),
            dataType : 'json',
            success  : function(data) {
                for (var i in data.response.identities) {
                    var identity         = data.response.identities[i].external_identity,
                        id               = data.response.identities[i].id,
                        avatar_file_name = data.response.identities[i].avatar_file_name,
                        name             = data.response.identities[i].name;
                    if ($('#exfee_' + id).attr('id') == null) {
                        name = name ? name : identity;
                        exfee_pv.push(
                            '<li id="exfee_' + id + '" class="addjn">'
                          +     '<p class="pic20"><img src="'+odof.comm.func.getUserAvatar(avatar_file_name, 80, img_url)+'" alt="" /></p>'
                          +     '<p class="smcomment">'
                          +         '<span class="exfee_exist" id="exfee_' + id + '" identityid="' + id + '" value="' + identity + '" avatar="' + avatar_file_name + '">'
                          +             name
                          +         '</span>'
                          +         '<input id="confirmed_exfee_' + id + '" class="confirmed_box" type="checkbox" checked/>'
                          +     '</p>'
                          +     '<button class="exfee_del" onclick="javascript:exfee_del($(\'#exfee_' + id + '\'))" type="button"></button>'
                          + '</li>'
                        );
                    }
                }
                while (exfee_pv.length) {
                    var inserted = false;
                    $('#exfee_pv > ul').each(function(intIndex) {
                        var li = $(this).children('li');
                        if (li.length < 4) {
                            $(this).append(exfee_pv.shift());
                            inserted = true;
                        }
                    });
                    if (!inserted) {
                        $('#exfee_pv').append('<ul class="exfeelist">' + exfee_pv.shift() + '</ul>');
                    }
                }
                updateExfeeList();
                if (autoSubmit) {
                    autoSubmit = false;
                    submitX();
                }
            }
        });
    }

})(ns);


$(document).ready(function() {
    // title
    $('#gather_title').focus(function() {
        $('#gather_title').addClass('gather_focus').removeClass('gather_blur');
    });
    $('#gather_title').blur(function () {
        var strTitle = $(this).val();
        $('#gather_title').addClass('gather_blur').removeClass('gather_focus');
        // update title
    });
    $('#gather_title').val(defaultTitle);
    $('#gather_title').select();
    $('#gather_title').focus();

    // description
    $('#gather_desc').focus(function() {
        $('#gather_desc_x').addClass('gather_focus').removeClass('gather_blur');
    });
    $('#gather_desc').blur(function() {
        $('#gather_desc_x').addClass('gather_blur').removeClass('gather_focus');
        // .html($(this).val() ? '' : gDescDefaultText);
    });

    // datetime
    $('#datetime_original').focus(function() {
        $('#gather_date_x').addClass('gather_focus').removeClass('gather_blur').html('');
        // @todo: time format tips
        // .html($('#gather_date_bg').html() === gDateDefaultText ? 'e.g. 6PM Today' : '');
        // @todo: disable time input box for version #oC
        // $('#datetime_original').blur();
    });
    $('#datetime_original').blur(function () {
        $('#gather_date_x').addClass('gather_blur').removeClass('gather_focus');
        // .html($(this).val() ? '' : gDateDefaultText);
    });

    // place
    $('#gather_place').focus(function () {
        $('#gather_place_x').addClass('gather_focus').removeClass('gather_blur');
    });
    $('#gather_place').blur(function () {
        $('#gather_place_x').addClass('gather_blur').removeClass('gather_focus');
        // .html($(this).val() ? '' : gPlaceDefaultText);
    });

    // host by
    $('#gather_hostby').focus(function () {
        odof.user.status.doShowLoginDialog(null, odof.x.gather.afterLogin);
    });



return;
    $('#identity_ajax').activity({segments: 8, steps: 3, opacity: 0.3, width: 3, space: 0, length: 4, color: '#0b0b0b', speed: 1.5});
    $('#identity_ajax').hide();

    $('#gather_submit_ajax').activity({segments: 8, steps: 3, opacity: 0.3, width: 3, space: 0, length: 4, color: '#0b0b0b', speed: 1.5});
    $('#gather_submit_ajax').hide();

    // title
    window.gTitlesDefaultText = $('#g_title').val();
    $('#g_title').keyup(function() {
        var objTitle = $(this),
            strTitle = objTitle.val();
        if (strTitle) {
            $('#pv_title').html(strTitle);
            if ($('#pv_title').hasClass('pv_title_double') && $('#pv_title').height() < 112) {
                $('#pv_title').addClass('pv_title_normal').removeClass('pv_title_double');
            }
            if ($('#pv_title').hasClass('pv_title_normal') && $('#pv_title').height() > 70) {
                $('#pv_title').addClass('pv_title_double').removeClass('pv_title_normal');
            }
            document.title = 'EXFE - ' + strTitle;
        } else {
            $('#pv_title').html(gTitlesDefaultText);
            document.title = 'EXFE - ' + gTitlesDefaultText;
        }
    });

    // desc
    var gDescDefaultText = $('#gather_desc_bg').html();
    var converter = new Showdown.converter();
    $('#g_description').keyup(function() {
        var maxChrt = 33,
            maxLine = 9,
            objDesc = $(this),
            extSpce = 10,
            strDesc = objDesc.val();
        if (strDesc) {
            $('#gather_desc_bg').html('');
            $('#pv_description').html(converter.makeHtml(strDesc));
            var arrDesc = strDesc.split(/\r|\n|\r\n/),
                intLine = arrDesc.length;
            for (var i in arrDesc) {
                intLine += arrDesc[i].length / maxChrt | 0;
            }
            var difHeight = parseInt(objDesc.css('line-height'))
                          * (intLine ? (intLine > maxLine ? maxLine : intLine) : 1)
                          + extSpce - (objDesc.height());
            if (difHeight <= 0) {
                return;
            }
            objDesc.animate({'height' : '+=' + difHeight}, 100);
            $('#gather_desc_bg').animate({'height' : '+=' + difHeight}, 100);
            $('#gather_desc_blank').animate({'height' : '+=' + difHeight}, 100);
        } else {
            $('#gather_desc_bg').html(gDescDefaultText);
            $('#pv_description').html(gDescDefaultText);
        }
    });


    // date
    var gDateDefaultText = $('#gather_date_bg').html();
    $('#datetime_original').keyup(function(e) {
        if ((e.keyCode ? e.keyCode : e.which) === 9) {
            return;
        }
        updateRelativeTime();
    });

    // exfee
    $('.ex_identity').hide();
    $('.exfee_item').live('mouseenter mouseleave', function(event) {
        showExternalIdentity(event);
    });
    window.rollingExfee = null;
    window.exfeeRollingTimer = setInterval(rollExfee, 50);

    // place
    var gPlaceDefaultText = $('#gather_place_bg').html();
    $('#g_place').keyup(function() {
        var strPlace = $('#g_place').val(),
            arrPlace = strPlace.split(/\r|\n|\r\n/),
            prvPlace = [];
        arrPlace.forEach(function(item, i) {
            if ((item = odof.util.trim(item))) {
                prvPlace.push(item);
            }
        });
        if (prvPlace.length) {
            $('#gather_place_bg').html('');
            $('#pv_place_line1').html(prvPlace.shift());
            $('#pv_place_line2').html(prvPlace.join('<br />'));
            if ($('#pv_place_line1').hasClass('pv_place_line1_double') && $('#pv_place_line1').height() < 72) {
                $('#pv_place_line1').addClass('pv_place_line1_normal').removeClass('pv_place_line1_double');
            }
            if ($('#pv_place_line1').hasClass('pv_place_line1_normal') && $('#pv_place_line1').height() > 53) {
                $('#pv_place_line1').addClass('pv_place_line1_double').removeClass('pv_place_line1_normal');
            }
        } else {
            $('#gather_place_bg').html(gPlaceDefaultText);
            $('#pv_place_line1').html('Somewhere');
            $('#pv_place_line2').html(''); // @todo: gps city here
        }
    });

    $('#gather_x').bind('mouseenter mouseout mousedown', function(event) {
        if (xSubmitting) {
            return;
        }
        switch (event.type) {
            case 'mouseenter':
                $('#gather_x').addClass('mouseover');
                $('#gather_x').removeClass('mousedown');
                break;
            case 'mouseout':
                $('#gather_x').removeClass('mouseover');
                $('#gather_x').removeClass('mousedown');
                break;
            case 'mousedown':
                $('#gather_x').removeClass('mouseover');
                $('#gather_x').addClass('mousedown');
        }
    });

    $('#gather_x').click(submitX);

    $('#post_submit').click(function(e) {
        identity();
    });

    $('.privacy').click(function() {
        $('.privacy > .subinform').html('Sorry, public <span class="x">X</span> is not supported yet, we\'re still working on it.');
    });

    

    setInterval(saveDraft, 10000);

    $('.confirmed_box').live('change', updateExfeeList);

    // added by handaoliang
    jQuery('#datetime_original').bind('focus', function(){
        var displayTextBox = document.getElementById('datetime_original');
        var calendarCallBack = function(displayTimeString, standardTimeString){
            document.getElementById('datetime').value = standardTimeString;
            updateRelativeTime();
        };
        exCal.initCalendar(displayTextBox, 'calendar_map_container', calendarCallBack);
    })
});
