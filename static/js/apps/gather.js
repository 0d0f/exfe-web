/**
 * @Description: X gather module
 * @Author:      Leask Huang <leask@exfe.com>
 * @createDate:  Jan 2, 2012
 * @CopyRights:  http://www.exfe.com
 */


var moduleNameSpace = 'odof.x.gather',
    ns = odof.util.initNameSpace(moduleNameSpace);

(function(ns) {

    ns.curCross        = '';
    
    ns.draft_id        = 0;

    ns.new_identity_id = 0; // @todo

    ns.xSubmitting     = false;

    ns.autoSubmit      = false;

    ns.updateTitle = function() {
        crossData.title   = odof.util.trim($('#gather_title').val());
        if (crossData.title === '') {
            crossData.title = defaultTitle;
        }
        $('#gather_title').val(crossData.title);
        document.title = 'EXFE - ' + crossData.title;
        odof.x.render.showTitle();
    };


    ns.updateDesc = function() {
        var maxChrt = 33,
            maxLine = 9,
            extSpce = 10,
            objDesc = $('#gather_desc');
        crossData.description = odof.util.trim(objDesc.val());
        odof.x.render.showDesc();
        if (crossData.description === '') {
            $('#gather_desc_x').html(defaultDesc);
        } else {
            var arrDesc = crossData.description.split(/\r|\n|\r\n/),
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
            $('#gather_desc_x').animate({'height' : '+=' + difHeight}, 100);
            $('#gather_desc_blank').animate({'height' : '+=' + difHeight}, 100);
            $('#gather_desc_x').html('');
        }
    };


    ns.updateTime = function() {
        if (odof.util.trim($('#datetime_original').val()) === '') {
            $('#gather_date_x').html(defaultTime);
        } else {
            $('#datetime_original').val(odof.util.getHumanDateTime(crossData.begin_at));
            $('#gather_date_x').html('');
        }
        odof.x.render.showTime();
    };


    ns.updatePlace = function() {
        var strPlace = odof.util.parseLocation($('#gather_place').val());
        crossData.place.line1 = strPlace[0];
        crossData.place.line2 = strPlace[1];
        if (crossData.place.line1 + crossData.place.line2 === '') {
            $('#gather_place_x').html(defaultPlace);
        } else {
            $('#gather_place_x').html('');
        }
        odof.x.render.showPlace();
    };


    ns.summaryX = function()
    {
        var x   = odof.util.clone(crossData);
        x.place = x.place.line1 + "\r" + x.place.line2;
        return x;
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
        if (this.xSubmitting) {
            return;
        }
        this.xSubmitting = true;
        $('#gather_failed_hint').hide();
        $('#gather_submit').removeClass('mouseover');
        $('#gather_submit').removeClass('mousedown');
        $('#gather_submit').addClass('disabled');
        // @todo daisy showing here
        // $('#gather_submit').html('');

        var x = this.summaryX();
        x.draft_id = this.draft_id;

        $.ajax({
            type     : 'POST',
            url      : site_url + '/x/gather',
            dataType : 'json',
            data     : x,
            success  : function(data) {
                if (data) {
                    if (data.success) {
                        location.href = '/!' + data.crossid;
                        return;
                    } else {
                        switch (data.error) {
                            case 'notlogin':
                                odof.x.gather.autoSubmit = true;
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
                // @todo daisy showing here
                $('#gather_failed_hint').show();
                $('#gather_submit').removeClass('mouseover');
                $('#gather_submit').removeClass('mousedown');
                $('#gather_submit').removeClass('disabled');
                $('#gather_submit').html('Re-submit');
                this.xSubmitting = false;
            },
            failure : function(data) {
                // @todo daisy showing here
                $('#gather_failed_hint').show();
                $('#gather_submit').removeClass('mouseover');
                $('#gather_submit').removeClass('mousedown');
                $('#gather_submit').removeClass('disabled');
                $('#gather_submit').html('Re-submit');
                this.xSubmitting = false;
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
        if (crossData.title === oldDefaultTitle) {
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
                if (odof.x.gather.autoSubmit) {
                    odof.x.gather.autoSubmit = false;
                    this.submitX();
                }
            }
        });
    }

})(ns);


$(document).ready(function() {
    // X initialization
    window.crossData = {title       : '',
                        description : '',
                        place       : {line1 : '', line2 : ''},
                        begin_at    : ''};

    // X render
    odof.x.render.show(false);
    
    // Exfee input
    odof.exfee.gadget.make('gatherExfee', [], true);

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
                        crossData.begin_at = standardTimeString;
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

});
