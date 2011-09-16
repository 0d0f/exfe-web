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
var new_identity_id = 0;

function getexfee()
{
    var result = [];
    function collect(obj, exist)
    {
        var exfee_identity = $(obj).attr("value"),
            element_id     = $(obj).attr("id"),
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
    $('.exfee_exist').each(function(e) {
        collect(this, true);
    });
    $('.exfee_new').each(function(e) {
        collect(this);
    });
    return result;
}

$(document).ready(function() {

    $('#identity_ajax').activity({segments: 8, steps: 3, opacity: 0.3, width: 3, space: 0, length: 4, color: '#0b0b0b', speed: 1.5});
    $('#identity_ajax').hide();
    $("#identity_ajax").ajaxStart(function(){$(this).show();});
    $("#identity_ajax").ajaxStop(function(){$(this).hide();});

    $('input[type="text"], textarea').focus(function () {
        if($(this).attr("enter") != "true")
        {
            defaultText = $(this).val();
            $(this).val('');
        }
    });
    $('input[type="text"], textarea').blur(function () {
        if ($(this).val() == "") {
            $(this).val(defaultText);
        }
        else
        {
            $(this).attr("enter", "true");
        }
    });

    $("#hostby").focus(function() {
        if($(this).attr("enter") != "true")
        {
            var html = showdialog("reg");
            $(html).modal();
            bindDialogEvent("reg");
        }
    });

    $('.addjn').mousemove(function() {
        hide_exfeedel($(this));
    });

    $('.addjn').mouseout(function() {
        show_exfeedel($(this));
    });

    var code = null;
    $('#exfee').keypress(function(e) {
        code = e.keyCode ? e.keyCode : e.which;
        if (code == 13) {
            identity();
            e.preventDefault();
        }
    });

    $('#g_title').keyup(function(e) {
        $('#pv_title').html($('#g_title').val());
    });

    $('#g_description').keyup(function(e) {
        $(this).attr("enter","1");
        $('#pv_description').html($('#g_description').val());
    });

    $('#g_place').keyup(function(e) {
        var place_lines=$('#g_place').val();
        var lines = place_lines.split("\r\n");
        if(lines.length<=1)
            lines = place_lines.split("\n");
        if(lines.length<=1)
            lines = place_lines.split("\r");
        var trim_lines = new Array();
        if(lines.length>1)
            for (var i = 0; i < lines.length; i++)
                if(lines[i] != "")
                    trim_lines.push(lines[i]);

        if(trim_lines.length <= 1)
        {
            $('#pv_place_line1').html(place_lines);
            $('#pv_place_line2').html('');
        }
        else
        {
            $('#pv_place_line1').html(trim_lines[0]);
            var place_line2 = '';
            for (i = 1; i < trim_lines.length; i++)
            {
                if(i == trim_lines.length-1)
                    place_line2 = place_line2 + trim_lines[i];
                else
                    place_line2 = place_line2 + trim_lines[i] + '<br />';
            }
            $('#pv_place_line2').html(place_line2);
        }
    });

    $('#gather_x').click(function(e) {
        $('#gatherxform').submit();
    });

    /* //modified by handaoliang..
    $('#datetime').datepicker({
        duration: '',
        showTime: true,
        constrainInput: false,
        time24h: true,
        dateFormat: 'yy-mm-dd',

        beforeShow: function(input, inst)
        {
            $.datepicker._pos = $.datepicker._findPos(input);
            $.datepicker._pos[0] = 280;
            $.datepicker._pos[1] = 50;
        }
    });
    */
    $('#gatherxform').submit(function(e) {
        if($('#g_description').attr('enter') == '0')
            $('#g_description').html('');
            $('#exfee_list').val(JSON.stringify(getexfee()));
    });

    $('#confirmed_all').click(function(e) {
        var check=false;
        if($(this).attr('check')== 'false')
        {
            $(this).attr('check', 'true');
            check=true;
        }
        else
            $(this).attr('check', 'false');

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

    $('.confirmed_box').live('change', updateExfeeList);

    updateExfeeList();
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
    window.arrIdentitySub = [];
    var arrIdentityOri = $('#exfee').val().split(/,|;|\r|\n|\t/);

    for (var i in arrIdentityOri) {
        if ((arrIdentityOri[i] = trim(arrIdentityOri[i]))) {
            arrIdentitySub.push(parseId(arrIdentityOri[i]));
        }
    }

    $.ajax({
        type: 'GET',
        url: site_url + '/identity/get?identities=' + JSON.stringify(arrIdentitySub),
        dataType: 'json',
        success: function(data) {
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
                    if (li.length < 4) {
                        $(this).append(exfee_pv.shift());
                        inserted = true;
                    }
                });
                if (!inserted) {
                    $('#exfee_pv').append('<ul class="samlcommentlist">' + exfee_pv.shift() + '</ul>');
                }
            }

            updateExfeeList();
        }
    });
    $('#exfee_count').html($('span.exfee_exist').length + $('span.exfee_new').length);
}

function updateExfeeList()
{
    var exfees = getexfee(),
        htmExfeeList = '',
        numConfirmed = 0,
        numSummary   = 0;
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
    $('#samlcommentlist').html(htmExfeeList);
    $('#exfee_confirmed').html(numConfirmed);
    $('#exfee_summary').html(numSummary);
    $('#exfee').val('');
}

function saveDraft()
{
    var cross = {title       : $("#g_title").val(),
                 description : $("#g_description").val(),
                 datetime    : $("input[name='datetime']").val(),
                 place       : $('#g_place').val(),
                 hostby      : $("#hostby").val(),
                 exfee       : getexfee()};

console.log(cross);

}