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
                              identity_type  : parseId(exfee_identity).type,
                              isHost         : spanHost && spanHost.html() === 'host'};
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

$(document).ready(function() {

    $('#identity_ajax').activity({segments: 8, steps: 3, opacity: 0.3, width: 3, space: 0, length: 4, color: '#0b0b0b', speed: 1.5});
    $('#identity_ajax').hide();

    // title
    var gTitlesDefaultText = $('#gather_title_bg').html();
    $('#g_title').focus();
    $('#g_title').keyup(function() {
        if ($(this).val()) {
            $('#gather_title_bg').html('');
            $('#pv_title').html($(this).val());
        } else {
            $('#gather_title_bg').html(gTitlesDefaultText);
            $('#pv_title').html(gTitlesDefaultText);
        }
    });
    $('#g_title').focus(function() {
        $('#gather_title_bg').addClass('gather_focus').removeClass('gather_blur');
    });
    $('#g_title').blur(function () {
        $('#gather_title_bg').addClass('gather_blur').removeClass('gather_focus')
                             .html($(this).val() ? '' : gTitlesDefaultText);
    });

    // description
    var gDescDefaultText = $('#gather_desc_bg').html();
    $('#g_description').keyup(function() {
        var objDesc = $(this);
        if (objDesc.val()) {
            $('#gather_desc_bg').html('');
            $('#pv_description').html($(this).val());
            var strDesc = $(this).val(),
                arrDesc = strDesc.split(/\r|\n|\r\n/),
                intLine = arrDesc.length;
            for (var i in arrDesc) {
                intLine += arrDesc[i].length / 33 | 0;
            }
            console.log((intLine > 9 ? 9 : intLine));
            var difHeight = parseInt(objDesc.css('line-height'))
                          * (intLine ? (intLine > 9 ? 9 : intLine) : 1)
                          + 10 - objDesc.height();
            if (difHeight <= 0) {
                return;
            }
            objDesc.animate({'height' : '+=' + difHeight}, 300);
            $('#gather_desc_bg').animate({'height' : '+=' + difHeight}, 300);
            $('#gather_desc_blank').animate({'height' : '+=' + difHeight}, 300);
        } else {
            $('#gather_desc_bg').html(gDescDefaultText);
            $('#pv_description').html(gDescDefaultText);
        }
    });
    $('#g_description').focus(function () {
        $('#gather_desc_bg').addClass('gather_focus').removeClass('gather_blur');
    });
    $('#g_description').blur(function () {
        $('#gather_desc_bg').addClass('gather_blur').removeClass('gather_focus')
                            .html($(this).val() ? '' : gDescDefaultText);
    });
//
//    $('#g_description').autoResize({
//        maxHeight  : 200,
//        minHeight  : 100,
//        extraSpace : 30,
//        animate    : {
//            duration : 0,
//            complete : function () {
//                var intHeight = $('#g_description').height() - $('#gather_desc_bg').height();
//                $('#gather_desc_bg').animate({'height' : '+=' + intHeight}, 300);
//                $('#gather_desc_blank').animate({'height' : '+=' + intHeight}, 300);
//            }
//        }
//    });

    // date
    var gDateDefaultText = $('#gather_date_bg').html();
    $('#datetime_original').keyup(function(e) {
        if ((e.keyCode ? e.keyCode : e.which) === 9) {
            return;
        }
        if ($(this).val()) {
            $('#gather_date_bg').html('');
        }else {
            $('#gather_date_bg').html(gDateDefaultText);
        }
        // $('#pv_time').html($('#gather_date_bg').html());
    });
    $('#datetime_original').focus(function () {
        $('#gather_date_bg').addClass('gather_focus').removeClass('gather_blur')
                            .html($('#gather_date_bg').html() === gDateDefaultText
                                  ? 'e.g. 6PM Today' : '');
    });
    $('#datetime_original').blur(function () {
        $('#gather_date_bg').addClass('gather_blur').removeClass('gather_focus')
                            .html($(this).val() ? '' : gDateDefaultText);
    });

    // place
    var gPlaceDefaultText = $('#gather_place_bg').html();
    $('#g_place').keyup(function() {
        if ($(this).val()) {
            $('#gather_place_bg').html('');
        } else {
            $('#gather_place_bg').html(gPlaceDefaultText);
        }
        var place_lines = $('#g_place').val(),
            lines       = place_lines.split("\r\n");
        if (lines.length <= 1) {
            lines = place_lines.split("\n");
        }
        if (lines.length <= 1) {
            lines = place_lines.split("\r");
        }
        var trim_lines = new Array();
        if (lines.length > 1) {
            for (var i = 0; i < lines.length; i++) {
                if (lines[i] != '') {
                    trim_lines.push(lines[i]);
                }
            }
        }
        if (trim_lines.length <= 1) {
            $('#pv_place_line1').html(place_lines);
            $('#pv_place_line2').html('');
        } else {
            $('#pv_place_line1').html(trim_lines[0]);
            var place_line2 = '';
            for (i = 1; i < trim_lines.length; i++) {
                if (i == trim_lines.length - 1) {
                    place_line2 = place_line2 + trim_lines[i];
                } else {
                    place_line2 = place_line2 + trim_lines[i] + '<br />';
                }
            }
            $('#pv_place_line2').html(place_line2);
        }
    });
    $('#g_place').focus(function () {
        $('#gather_place_bg').addClass('gather_focus').removeClass('gather_blur');
    });
    $('#g_place').blur(function () {
        $('#gather_place_bg').addClass('gather_blur').removeClass('gather_focus')
                             .html($(this).val() ? '' : gPlaceDefaultText);
    });

    // host
    $('#hostby').focus(function() {
        if ($(this).attr('enter') === 'true') {
            return;
        }
        var html = showdialog('reg');
        $(html).modal({onClose : function() {
            $("#hostby").attr('disabled', true);
            var exfee_pv = [];
            $.ajax({
                type     : 'GET',
                url      : site_url + '/identity/get?identities=' + JSON.stringify([parseId($("#hostby").val())]),
                dataType : 'json',
                success  : function(data) {
                    for (var i in data.response.identities) {
                        var identity         = data.response.identities[i].external_identity,
                            id               = data.response.identities[i].id,
                            avatar_file_name = data.response.identities[i].avatar_file_name,
                            name             = data.response.identities[i].name;
                        if ($('#exfee_' + id).attr('id') == null) {
                            name = (name ? name : identity).replace('<', '&lt;').replace('>', '$gt;');
                            exfee_pv.push(
                                '<li class="addjn" ><p class="pic20"><img src="/eimgs/80_80_' + avatar_file_name + '" alt="" /></p> <p class="smcomment"><span class="exfee_exist" id="exfee_' + id + '" identityid="' + id + '"value="' + identity + '">' + name + '</span><input id="confirmed_exfee_' + id + '" class="confirmed_box" checked=true type="checkbox"/><span class="lb">host</span></p> <button class="exfee_del" type="button"></button> </li>'
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
                }
            });
            $.modal.close();
        }});
        odof.user.identification.bindDialogEvent('reg');
    });

    // exfee
    var gExfeeDefaultText = $('#gather_exfee_bg').html();
    $('#exfee').keyup(function(e) {
        if ($(this).val()) {
            $('#gather_exfee_bg').html('');
        } else {
            $('#gather_exfee_bg').html(gExfeeDefaultText);
        }
        if ((e.keyCode ? e.keyCode : e.which) === 13) {
            identity();
            e.preventDefault();
        }
    });
    $('#exfee').focus(function () {
        $('#gather_exfee_bg').addClass('gather_focus').removeClass('gather_blur');
    });
    $('#exfee').blur(function () {
        $('#gather_exfee_bg').addClass('gather_blur').removeClass('gather_focus')
                            .html($(this).val() ? '' : gExfeeDefaultText);
    });


    $('.addjn').mousemove(function() {
        hide_exfeedel($(this));
    });

    $('.addjn').mouseout(function() {
        show_exfeedel($(this));
    });

    $('#gather_x').click(submitX);

    $('#confirmed_all').click(function(e) {
        var check=false;
        if ($(this).attr('check')== 'false') {
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

    window.curCross = '';
    window.code     = null;
    window.draft_id = 0;
    window.new_identity_id = 0;

    setInterval('saveDraft()', 10000);

    $('.confirmed_box').live('change', updateExfeeList);

    //getDraft();

    updateExfeeList();


    //added by handaoliang
    jQuery("#datetime_original").bind("focus", function(){
        var displayTextBox = document.getElementById("datetime_original");
        var calendarCallBack = function(displayTimeString, standardTimeString){
            document.getElementById("datetime").value = standardTimeString;
        };
        exCal.initCalendar(displayTextBox, 'calendar_map_container', calendarCallBack);
    })
});


function trim(str)
{
    return str.replace(/^\s+|\s+$/g, '');
}


function parseId(strId)
{
    if (/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/.test(strId)) {
        var iLt = strId.indexOf('<'),
            iGt = strId.indexOf('>');
        return {name : trim(strId.substring(0,     iLt)),
                id   : trim(strId.substring(++iLt, iGt)),
                type : 'email'};
    } else if (/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/.test(strId)) {
        return {id   : trim(strId),
                type : 'email'};
    } else {
        return {id   : trim(strId),
                type : 'unknow'};
    }
}


function identity()
{
    $('#identity_ajax').show();

    window.arrIdentitySub = [];
    var arrIdentityOri = $('#exfee').val().split(/,|;|\r|\n|\t/);

    for (var i in arrIdentityOri) {
        if ((arrIdentityOri[i] = trim(arrIdentityOri[i]))) {
            arrIdentitySub.push(parseId(arrIdentityOri[i]));
        }
    }

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
                if ($('#exfee_' + id).attr('id') == null) {
                    name = (name ? name : identity).replace('<', '&lt;').replace('>', '$gt;');
                    exfee_pv.push(
                        '<li id="exfee_' + id + '" class="addjn" onmousemove="javascript:hide_exfeedel($(this))" onmouseout="javascript:show_exfeedel($(this))"> <p class="pic20"><img src="/eimgs/80_80_'+avatar_file_name+'" alt="" /></p> <p class="smcomment"><span class="exfee_exist" id="exfee_'+id+'" identityid="'+id+'"value="'+identity+'">'+name+'</span><input id="confirmed_exfee_'+ id +'" class="confirmed_box" type="checkbox"/></p> <button class="exfee_del" onclick="javascript:exfee_del($(\'#exfee_'+id+'\'))" type="button"></button> </li>'
                    );
                }
                identifiable[identity] = true;
            }
            for (i in arrIdentitySub) {
                if (!identifiable[arrIdentitySub[i].id]) {
                    switch (arrIdentitySub[i].type) {
                        case 'email':
                            name =  arrIdentitySub[i].name
                                 ? (arrIdentitySub[i].name + ' <'  + arrIdentitySub[i].id + '>')
                                 :  arrIdentitySub[i].id;
                            break;
                        default:
                            name =  arrIdentitySub[i].id;
                    }
                    name = name.replace('<', '&lt;').replace('>', '&gt;');
                    new_identity_id++;
                    exfee_pv.push(
                        '<li id="newexfee_' + new_identity_id + '" class="addjn" onmousemove="javascript:hide_exfeedel($(this))" onmouseout="javascript:show_exfeedel($(this))"> <p class="pic20"><img src="/eimgs/80_80_default.png" alt="" /></p> <p class="smcomment"><span class="exfee_new" id="newexfee_' + new_identity_id + '" value="' + arrIdentitySub[i].id + '">' + name + '</span><input id="confirmed_newexfee_' + new_identity_id +'" class="confirmed_box" type="checkbox"/></p> <button class="exfee_del" onclick="javascript:exfee_del($(\'#newexfee_' + new_identity_id + '\'))" type="button"></button> </li>'
                    );
                }
            }

            while (exfee_pv.length) {
                var inserted = false;
                $('#exfee_pv > ul').each(function(intIndex) {
                    var li = $(this).children('li');
                    console.log(li.length);
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
        },
        error: function() {
            $('#identity_ajax').hide();
        }
    });
    $('#exfee_count').html($('span.exfee_exist').length + $('span.exfee_new').length);
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
        htmExfeeList += '<li>'
                      +     '<span class="pic20"><img alt="" src="/eimgs/1.png"></span>'
                      +     '<span class="smcomment">'
                      +         exfees[i].exfee_name
                      +        (exfees[i].isHost ? '<span class="lb">host</span>' : '')
                      +     '</span>'
                      +     '<p class="cs"><em class="c' + (exfees[i].confirmed ? 1 : 2) + '"></em></p>'
                      + '</li>';
    }
    $('#exfeelist').html(htmExfeeList);
    $('#exfee_confirmed').html(numConfirmed);
    $('#exfee_summary').html(numSummary);
    $('#exfee').val('');
}

function summaryX()
{
    return {title       : $('#g_title').val(),
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
    var cross = summaryX();
    cross['draft_id'] = draft_id;

    $.ajax({
        type     : 'POST',
        url      : site_url + '/x/gather',
        dataType : 'json',
        data     : cross,
        success  : function(data) {
            if (data && data.success) {
                location.href = '/!' + data.crossid;
            }
        },
        failure : function(data) {

        }
    });
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
