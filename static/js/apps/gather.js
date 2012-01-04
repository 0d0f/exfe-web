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
            datetime    : ''};


    ns.updateTitle = function() {
        this.x.title = $('#gather_title').val();
        document.title = 'EXFE - ' + this.x.title;
    };
    
    
    ns.updateDescription = function() {
    
    };
    
    
    ns.updateTime = function() {
    
    };
    
    
    ns.updatePlace = function() {
    
    };
    
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
        // login
    }



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
    
    // host
    $('#hostby').focus(function() {
        if ($(this).attr('enter') === 'true') {
            return;
        }
        odof.user.status.doShowLoginDialog(null, afterLogin);
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

    $('#confirmed_all').click(function(e) {
        var check = false;
        if ($(this).attr('check') === 'false') {
            $(this).attr('check', 'true');
            check=true;
        } else {
            $(this).attr('check', 'false');
        }

        $('.exfee_exist').each(function(e) {
            var element_id = $(this).attr('id');
            $('#confirmed_' + element_id).attr('checked',check);
        });
        $('.exfee_new').each(function(e) {
            var element_id = $(this).attr('id');
            $('#confirmed_' + element_id).attr('checked',check);
        });
    });

    $('#post_submit').click(function(e) {
        identity();
    });

    $('.privacy').click(function() {
        $('.privacy > .subinform').html('Sorry, public <span class="x">X</span> is not supported yet, we\'re still working on it.');
    });

    window.curCross = '';
    window.code     = null;
    window.draft_id = 0;
    window.new_identity_id = 0;
    window.completeTimer   = null;
    window.xSubmitting     = false;
    window.autoSubmit      = false;

    setInterval(saveDraft, 10000);

    $('.confirmed_box').live('change', updateExfeeList);

    // getDraft();

    updateExfeeList();

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





















































if (0) {
function hide_exfeedel(e)
{
    e.addClass('bgrond');
    $('.bgrond .exfee_del').show();
}

function show_exfeedel(e)
{
    e.removeClass('bgrond');
    $('.exfee_del').hide();
}

function exfee_del(e)
{
    e.remove();
    updateExfeeList();
}

function getexfee()
{
    var result = [];
    function collect(obj, exist)
    {
        var exfee_identity = $(obj).attr('value'),
            element_id     = $(obj).attr('id'),
            spanHost       = $(obj).parent().children('.lb'),
            item           = {exfee_name     : $(obj).html(),
                              exfee_identity : exfee_identity,
                              confirmed      : $('#confirmed_' + element_id)[0].checked  == true ? 1 : 0,
                              identity_type  : odof.util.parseId(exfee_identity).type,
                              isHost         : spanHost && spanHost.html() === 'host',
                              avatar         : $(obj).attr('avatar')};
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
}

function afterLogin(status) {
    if (status.user_status !== 1) {
        return;
    }
    gTitlesDefaultText = 'Meet ' + status.user_name;
    document.title = 'EXFE - ' + gTitlesDefaultText;
    $("#hostby").attr('disabled', true);
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

function showExternalIdentity(event)
{
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
            rollingExfee = id;
            $('#' + id + ' > .smcomment > div > .ex_identity').fadeIn(100);
            break;
        case 'mouseleave':
            rollingExfee = null;
            $('#' + id + ' > .smcomment > div > .ex_identity').fadeOut(100);
            var rollE = $('#' + id + ' > .smcomment > div');
            rollE.animate({
                marginLeft : '+=' + (0 - parseInt(rollE.css('margin-left')))},
                700
            );
    }
}


function rollExfee()
{
    var maxWidth = 200;
    if (!rollingExfee) {
        return;
    }
    var rollE    = $('#' + rollingExfee + ' > .smcomment > div'),
        orlWidth = rollE.width(),
        curLeft  = parseInt(rollE.css('margin-left')) - 1;
    if (orlWidth <= maxWidth) {
        return;
    }
    curLeft = curLeft <= (0 - orlWidth) ? maxWidth : curLeft;
    rollE.css('margin-left', curLeft + 'px');
}


function chkComplete(strKey)
{
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
                    window.strExfeeCompleteDefault = strId;
                }
                strFound += '<option value="' + strId + '"' + (strFound ? '' : ' selected') + '>' + strName + '</option>';
            }
            if (strFound && completeTimer && $('#exfee').val().length) {
                $('#exfee_complete').html(strFound);
                $('#exfee_complete').slideDown(50);
            } else {
                $('#exfee_complete').slideUp(50);
            }
            clearTimeout(completeTimer);
            completeTimer = null;
        }
    });
}


function chkExfeeFormat()
{
    window.arrIdentitySub = [];
    var strExfees = $('#exfee').val().replace(/\r|\n|\t/, '');
    $('#exfee').val(strExfees);
    var arrIdentityOri = strExfees.split(/,|;/);
    for (var i in arrIdentityOri) {
        if ((arrIdentityOri[i] = odof.util.trim(arrIdentityOri[i]))) {
            var exfee_item = odof.util.parseId(arrIdentityOri[i]);
            if (exfee_item.type !== 'email') {
                return false;
            }
            arrIdentitySub.push(exfee_item);
        }
    }
    return arrIdentitySub.length > 0;
}


function complete()
{
    var strValue = $('#exfee_complete').val();
    if (strValue === '') {
        return;
    }
    var arrInput = $('#exfee').val().split(/,|;|\r|\n|\t/);
    arrInput.pop();
    $('#exfee').val(arrInput.join('; ') + (arrInput.length ? '; ' : '') + strValue);
    clearTimeout(completeTimer);
    completeTimer = null;
    $('#exfee_complete').slideUp(50);
    identity();
    $('#exfee').focus();
}


function identity()
{
    if (!chkExfeeFormat()) {
        return;
    }

    $('#identity_ajax').show();

    $.ajax({
        type     : 'GET',
        url      : site_url + '/identity/get?identities=' + JSON.stringify(arrIdentitySub),
        dataType : 'json',
        success  : function(data) {
            $('#identity_ajax').hide();
            var exfee_pv     = [],
                name         = '',
                identifiable = {};
            for (var i in data.response.identities) {
                var identity         = data.response.identities[i].external_identity,
                    id               = data.response.identities[i].id,
                    avatar_file_name = data.response.identities[i].avatar_file_name;
                    name             = data.response.identities[i].name;
                if (!$('#exfee_' + id).length) {
                    name = name ? name : identity.split('@')[0].replace(/[^0-9a-zA-Z_\u4e00-\u9fa5\ \'\.]+/g, ' ');
                    while (odof.comm.func.getUTF8Length(name) > 30) {
                        name = name.substring(0, name.length - 1);
                    }
                    exfee_pv.push(
                        '<li id="exfee_' + id + '" class="addjn" onmousemove="javascript:hide_exfeedel($(this))" onmouseout="javascript:show_exfeedel($(this))">'
                      +     '<p class="pic20"><img src="'+odof.comm.func.getUserAvatar(avatar_file_name, 80, img_url)+'" alt="" /></p>'
                      +     '<p class="smcomment">'
                      +         '<span class="exfee_exist" id="exfee_' + id + '" identityid="' + id + '" value="' + identity + '" avatar="' + avatar_file_name + '">'
                      +             name
                      +         '</span>'
                      +         '<input id="confirmed_exfee_' + id + '" class="confirmed_box" type="checkbox"/>'
                      +     '</p>'
                      +     '<button class="exfee_del" onclick="javascript:exfee_del($(\'#exfee_' + id + '\'))" type="button"></button>'
                      + '</li>'
                    );
                }
                identifiable[identity.toLowerCase()] = true;
            }
            for (i in arrIdentitySub) {
                var idUsed = false;
                $('.exfee_new').each(function() {
                    if ($(this).attr('value').toLowerCase() === arrIdentitySub[i].id.toLowerCase()) {
                        idUsed = true;
                    }
                });
                if (!identifiable[arrIdentitySub[i].id.toLowerCase()] && !idUsed) {
                    switch (arrIdentitySub[i].type) {
                        case 'email':
                            name =  arrIdentitySub[i].name ? arrIdentitySub[i].name : arrIdentitySub[i].id;
                            break;
                        default:
                            name =  arrIdentitySub[i].id;
                    }
                    new_identity_id++;
                    exfee_pv.push(
                        '<li id="newexfee_' + new_identity_id + '" class="addjn" onmousemove="javascript:hide_exfeedel($(this))" onmouseout="javascript:show_exfeedel($(this))">'
                      +     '<p class="pic20"><img src="'+img_url+'/web/80_80_default.png" alt="" /></p>'
                      +     '<p class="smcomment">'
                      +         '<span class="exfee_new" id="newexfee_' + new_identity_id + '" value="' + arrIdentitySub[i].id + '">'
                      +             name
                      +         '</span>'
                      +         '<input id="confirmed_newexfee_' + new_identity_id + '" class="confirmed_box" type="checkbox"/>'
                      +     '</p>'
                      +     '<button class="exfee_del" onclick="javascript:exfee_del($(\'#newexfee_' + new_identity_id + '\'))" type="button"></button>'
                      + '</li>'
                    );
                }
            }

            while (exfee_pv.length) {
                var inserted = false;
                $('#exfee_pv > ul').each(function(intIndex) {
                    var li = $(this).children('li');
                    if (li.length < 4) {
                        // @todo: remove this in next version
                        if (($('.exfee_exist').length + $('.exfee_new').length) < 12) {
                            $(this).append(exfee_pv.shift());
                        } else {
                            exfee_pv.shift();
                            $('#exfee_warning').show();
                        }
                        inserted = true;
                    }
                });
                if (!inserted) {
                    // @todo: remove this in next version
                    if (($('.exfee_exist').length + $('.exfee_new').length) < 12) {
                        $('#exfee_pv').append('<ul class="exfeelist">' + exfee_pv.shift() + '</ul>');
                    } else {
                        exfee_pv.shift();
                        $('#exfee_warning').show();
                    }
                }
            }
            $('#exfee_pv').css('width', 300 * $('#exfee_pv > ul').length + 'px');
            updateExfeeList();
        },
        error: function() {
            $('#identity_ajax').hide();
        }
    });
}

function updateExfeeList()
{
    var exfees        = getexfee(),
        htmExfeeList  = '',
        numConfirmed  = 0,
        numSummary    = 0;
    for (var i in exfees) {
        numConfirmed += exfees[i].confirmed;
        numSummary++;
        var avatarFile = exfees[i].avatar ? exfees[i].avatar : 'default.png';
        htmExfeeList += '<li id="exfee_list_item_' + numSummary + '" class="exfee_item">'
                      +     '<p class="pic20"><img alt="" src="'+odof.comm.func.getUserAvatar(avatarFile, 80, img_url)+'"></p>'
                      +     '<div class="smcomment">'
                      +         '<div>'
                      +             '<span class="ex_name' + (exfees[i].exfee_name === exfees[i].exfee_identity ? ' external_identity' : '') + '">'
                      +                 exfees[i].exfee_name
                      +             '</span>'
                      +             (exfees[i].isHost ? '<span class="lb">host</span>' : '')
                      +             '<span class="ex_identity external_identity"> '
                      +                 (exfees[i].exfee_name === exfees[i].exfee_identity ? '' : exfees[i].exfee_identity)
                      +             '</span>'
                      +         '</div>'
                      +     '</div>'
                      +     '<p class="cs">'
                      +         '<em class="c' + (exfees[i].confirmed ? 1 : 2) + '"></em>'
                      +     '</p>'
                      + '</li>';
    }
    $('#exfeelist').html(htmExfeeList);
    $('#exfee_confirmed').html(numConfirmed);
    $('#exfee_summary').html(numSummary);
    $('#exfee_count').html(numSummary);
    $('#exfee').val('');
    $('.ex_identity').hide();
}

function summaryX()
{
    return {title       : $('#g_title').val() ? $('#g_title').val() : gTitlesDefaultText,
            description : $('#g_description').val(),
            datetime    : $('#datetime').val(),
            place       : $('#g_place').val(),
            hostby      : $('#hostby').val(),
            exfee       : JSON.stringify(getexfee())};
}

function saveDraft()
{
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
}

function submitX()
{
    if (xSubmitting) { return; }
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
}

function updateRelativeTime()
{
    if ($('#datetime_original').val()) {
        $('#gather_date_bg').html('');
        $('#pv_relativetime').html(
            odof.util.getRelativeTime(
                Date.parse(odof.util.getDateFromString($('#datetime').val())) / 1000
            )
        );
        $('#pv_origintime').html($('#datetime_original').val());
    } else {
        $('#gather_date_bg').html(gDateDefaultText);
        $('#pv_relativetime').html(gDateDefaultText);
        $('#pv_origintime').html('');
    }
}

/**
 * Pending
 *
function adjustExfeeBox()
{
    var maxLen = [];
    $('#exfee_pv > ul').each(function() {
        maxLen.push($(this).children('li').length);
    });
    maxLen = Math.max.apply(Math, maxLen);
    console.log(maxLen);
}
 */

/**
 * disable currently
 *
function getDraft()
{
    $.ajax({
        type     : 'GET',
        url      : site_url + '/x/getdraft',
        dataType : 'json',
        success  : function(draft) {
            if (!draft) {return;}

            $('#g_title').val(draft.title);
            $('#g_description').val(draft.description);
            $("input[name='datetime']").val(draft.datetime);
            $('#g_place').val(draft.place);
            $('#hostby').val(draft.hostby);
            $('#exfee_pv').html(draft.exfee);

            updateExfeeList();
        }
    });
}
 *
 */

}
