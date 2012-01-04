/**
 * @Description: X gather module
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Jan 2, 2012
 * @CopyRights:  http://www.exfe.com
 */


var moduleNameSpace = 'odof.x.gather',
    ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns) {

    ns.x = {title       : '',
            description : '',
            placeline1  : '',
            placeline2  : '',
            datetime    : '',
            draft_id    : 0};
            
    ns.curCross        = '';
    
    ns.new_identity_id = 0; // @todo
    
    ns.xSubmitting     = false;
    
    ns.autoSubmit      = false;

    ns.updateTitle = function() {
        this.x.title   = odof.util.trim($('#gather_title').val());
        if (this.x.title === '') {
            this.x.title = defaultTitle;
        }
        $('#gather_title').val(this.x.title);
        document.title = 'EXFE - ' + this.x.title;
    };


    ns.updateDesc = function() {
        this.x.description = odof.util.trim($('#gather_desc').val());
        $('#gather_desc_x').html(this.x.description === '' ? defaultDesc : '');
    };


    ns.updateTime = function() {
        if (odof.util.trim($('#datetime_original').val()) === '') {
            $('#gather_date_x').html(defaultTime);
        } else {
            $('#datetime_original').val(odof.util.getHumanDateTime(odof.x.gather.x.datetime));
            $('#gather_date_x').html('');
        }
    };


    ns.updatePlace = function() {
        var strPlace = odof.util.parseLocation($('#gather_place').val());
        this.x.placeline1 = strPlace[0];
        this.x.placeline2 = strPlace[1];
        if (this.x.placeline1 + this.x.placeline2 === '') {
            $('#gather_place_x').html(defaultPlace);
        } else {
            $('#gather_place_x').html('');
        }
    };
    
    
    ns.summaryX = function()
    {
        return this.x;
        // exfee       : JSON.stringify(getexfee())
    };


    ns.saveDraft = function() {
        var strCross = JSON.stringify(odof.x.gather.summaryX());
        if (odof.x.gather.curCross !== strCross) {
            $.ajax({
                type     : 'POST',
                url      : site_url + '/x/savedraft',
                dataType : 'json',
                data     : {draft_id : odof.x.gather.draft_id,
                            cross    : odof.x.gather.strCross},
                success  : function (data) {
                    odof.x.gather.draft_id = data && data.draft_id ? data.draft_id : odof.x.gather.draft_id;
                }
            });
            odof.x.gather.curCross = strCross;
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
        if (status.user_status !== 1) {
            return;
        }
        
        // title
        var oldDefaultTitle = defaultTitle;
        defaultTitle = 'Meet ' + status.user_name;
        if (this.x.title === oldDefaultTitle) {
            $('#gather_title').val('');
            odof.x.gather.updateTitle();
        }
        
        // hostby
        $('#gather_hostby').attr('disabled', true);
    
        return;
        // @handaoliang 检查一下登陆后的会掉函数调用是不是有问题？
        confole.log(status);
        
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
    $('#gather_title').bind('focus blur keyup', function(event) {
        switch (event.type) {
            case 'focus':
                $('#gather_title').addClass('gather_focus').removeClass('gather_blur');
                break;
            case 'blur':
                $('#gather_title').addClass('gather_blur').removeClass('gather_focus');
                odof.x.gather.updateTitle();
                break;
            case 'keyup':
                odof.x.gather.updateTitle();
        }
    });
    odof.x.gather.updateTitle();
    $('#gather_title').select();
    $('#gather_title').focus();

    // description
    $('#gather_desc').bind('focus blur keyup', function(event) {
        switch (event.type) {
            case 'focus':
                $('#gather_desc_x').addClass('gather_focus').removeClass('gather_blur');
                break;
            case 'blur':
                $('#gather_desc_x').addClass('gather_blur').removeClass('gather_focus');
                odof.x.gather.updateDesc();
                break;
            case 'keyup':
                odof.x.gather.updateDesc();
        }
    });
    odof.x.gather.updateDesc();

    // datetime
    $('#datetime_original').bind('focus blur keyup', function(event) {
        switch (event.type) {
            case 'focus':
                $('#gather_date_x').addClass('gather_focus').removeClass('gather_blur');
                exCal.initCalendar(
                    $('#datetime_original')[0],
                    'calendar_map_container',
                    function(displayTimeString, standardTimeString) {
                        odof.x.gather.x.datetime = standardTimeString;
                        odof.x.gather.updateTime();
                    }
                );
                // @todo: time format tips
                // .html($('#gather_date_bg').html() === gDateDefaultText ? 'e.g. 6PM Today' : '');
                // @todo: disable time input box for version #oC
                // $('#datetime_original').blur();
                break;
            case 'blur':
                $('#gather_date_x').addClass('gather_blur').removeClass('gather_focus');
                odof.x.gather.updateTime();
            case 'keyup':
                // @todo: 自然语言时间识别
        }
    });
    odof.x.gather.updateTime();

    // place
    $('#gather_place').bind('focus blur keyup', function (event) {
        switch (event.type) {
            case 'focus':
                $('#gather_place_x').addClass('gather_focus').removeClass('gather_blur');
                break;
            case 'blur':
                $('#gather_place_x').addClass('gather_blur').removeClass('gather_focus');
                odof.x.gather.updatePlace();
                break;
            case 'keyup':
                odof.x.gather.updatePlace();
        }
    });
    odof.x.gather.updatePlace();

    // host by
    $('#gather_hostby').focus(function () {
        odof.user.status.doShowLoginDialog(null, odof.x.gather.afterLogin);
    });

    // privacy
    $('#gather_privacy_blank').click(function() {
        $('#gather_privacy_info_desc').html('Sorry, public <span class="x">X</span> is not supported yet, we\'re still working on it.');
    });

    // gather
    $('#gather_submit').bind('mouseenter mouseout mousedown click', function(event) {
        if (odof.x.gather.xSubmitting) {
            return;
        }
        switch (event.type) {
            case 'mouseenter':
                $('#gather_submit').addClass('mouseover');
                $('#gather_submit').removeClass('mousedown');
                break;
            case 'mouseout':
                $('#gather_submit').removeClass('mouseover');
                $('#gather_submit').removeClass('mousedown');
                break;
            case 'mousedown':
                $('#gather_submit').removeClass('mouseover');
                $('#gather_submit').addClass('mousedown');
                break;
            case 'click':
                odof.x.gather.submitX();
        }
    });

    // auto save draft
    setInterval(odof.x.gather.saveDraft, 10000);




return;
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

});
