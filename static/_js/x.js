ns.convertTimezoneToSecond = function(tz){
    tz = tz ? tz : '';
    var offsetSign = tz.substr(0,1);
    offsetSign = offsetSign==0 ? "+" : offsetSign;
    var offsetDetail = tz.substr(1).split(":");
    var offsetSecond = (parseInt(offsetDetail[0]*60)+parseInt(offsetDetail[1]))*60;
    offsetSecond = parseInt(offsetSign + offsetSecond);

    return offsetSecond;
};


/**
 * test and verify string is chinese chars
 * @string str
 * @Return True || False
 * */
util.isCN = function(str) {
    var strREG = /^[u4E00-u9FA5]+$/;
    var re = new RegExp(strREG);
    if(!re.test(str)){
        return false;
    }
    return true;
};


/**
 * toDBC
 * @string.trim;
 * @Return String
 **/
util.toDBC = function(str) {
    var DBCStr = "";
    for(var i=0; i<str.length; i++){
        var c = str.charCodeAt(i);
        if(c == 12288) {
            DBCStr += String.fromCharCode(32);
            continue;
        }
        if (c > 65280 && c < 65375) {
            DBCStr += String.fromCharCode(c - 65248);
            continue;
        }
        DBCStr += String.fromCharCode(c);
    }
    return DBCStr;
};


/**
 * get browser available size
 * by Leask
 */
util.getClientSize = function() {
    return {
        width  : window.innerWidth  || document.documentElement.clientWidth,
        height : window.innerHeight || document.documentElement.clientHeight
    };
};


ns.showDesc = function(editing)
{
    if (!this.expended && $('#x_desc').height() > 200) {
        $('#x_desc_expand').show();
    } else {
        $('#x_desc_expand').hide();
    }
    $('#x_desc_expand').bind('click', this.expendDesc);
};


ns.expendDesc = function() {
    var expanded = !!odof.x.render.expanded,
        act = (expanded ? 'remove' : 'add') + 'Class';
    $('#x_desc')[act]('expanded');
    $(this).find('div.triangle-bottomright')[act]('triangle-topleft');
    $(this).find('>span').html(expanded ? 'More' : 'Less');
    odof.x.render.expanded = !expanded;
};


ns.showTime = function()
{

    var maxDiff = 30*60, localUtc = Math.round(new Date().getTime()/1000);
    window.timeValid = Math.abs(window.utcDiff = localUtc - utc) < 15 * 60;
    window.timeOffset = odof.comm.func.convertTimezoneToSecond(odof.comm.func.getTimezone());});;


    var strRelativeTime = '',
        strAbsoluteTime = '';
    if (!crossData.begin_at || crossData.begin_at === '0000-00-00 00:00:00') {
        strRelativeTime = 'Sometime';
    } else {
        var crossOffset = crossData.timezone ? odof.comm.func.convertTimezoneToSecond(crossData.timezone) : 0;
        if (crossOffset === window.timeOffset && window.timeValid) {
            strRelativeTime = odof.util.getRelativeTime(crossData.begin_at);
            strAbsoluteTime = odof.util.getHumanDateTime(crossData.begin_at);
            if (!strRelativeTime || !strAbsoluteTime) {
                crossData.begin_at = '';
                strRelativeTime = 'Sometime';
                strAbsoluteTime = '';
            }
        } else {
            var strTime = odof.util.parseHumanDateTime(crossData.origin_begin_at ? crossData.origin_begin_at : '', crossOffset);
            strRelativeTime = odof.util.getRelativeTime(strTime);
            strAbsoluteTime = odof.util.getHumanDateTime(strTime, crossOffset);
            if (!strRelativeTime || !strAbsoluteTime) {
                strRelativeTime = 'Sometime';
                strAbsoluteTime = '';
            } else {
                strAbsoluteTime += ' ' + crossData.timezone;
            }
        }
    }
    $('#x_time_relative').html(strRelativeTime);
    $('#x_time_absolute').html(strAbsoluteTime);
};


ns.showConversation = function()
{
    var tmpData    = this.sortConversationAndHistory(),
        strMessage = '';
    if (tmpData.length) {
        var self = this,
            g = tmpData.length - 1,
            identity = tmpData[g].by_identity || tmpData[g].identity || {},
            gather = '<li class="cleanup xhistory gather">'
                + 'Gathered' + (identity ? (' by <span class="bold">'
                + identity.name + '</span>.'
                + '<img alt="" width="20px" height="20px" src="'
                + identity.avatar_file_name + '" />') : '.');
        $.each(tmpData, function (i, v) {
            strMessage += g === i ? gather : (v.time ? (v.by_identity ? self.makeHistory(v) : '') : self.makeMessage(v));
        });
        tmpData = null;
    }
    $('#x_conversation_list').html(strMessage);
};


ns.makeHistory = function (o) {
    var str = '', info = '', c = '';
    switch (o.action) {
        case 'description':
            info = 'Description changed to <span class="bold">'
                + o.new_value.substr(0, 10)
                + '</span> by <span class="bold">'
                + o.by_identity.name
                + '</span>';
            break;
        case 'interested':
        case 'confirmed':
        case 'declined':
            info = '<span class="bold">'
                + o.by_identity.name
                + '</span> '
                + o.action;
            c = 'user';
            break;
        case 'place':
            info = 'Place changed to <span class="bold">'
                + o.new_value.line1.substr(0, 10)
                + '</span> by <span class="bold">'
                + o.by_identity.name
                + '</span>';
            c = 'place';
            break;
        case 'begin_at':
            info = 'Time changed to <span class="bold">'
                + odof.util.getRelativeTime(o.new_value.begin_at)
                + '</span> by <span class="bold">'
                + o.by_identity.name
                + '<span>';
            c = 'clock';
            break;
        case 'title':
            info = 'Title changed to <span class="bold">'
                + o.new_value.substr(0, 10)
                + '</span> by <span class="bold">'
                + o.by_identity.name
                + '</span>';
            break;
        case 'addexfee':
            info = '<span class="oblique">'
                + o.to_identity[0].external_identity
                + '</span> is invited by '
                + '<span class="bold">'
                + o.by_identity.name
                + '</span>.';
            c = 'user';
            break;
        default:
            return '';
    }

    str += info;
    str += '<img alt="" width="20px" height="20px" src="' + o.by_identity.avatar_file_name + '" />';
    return '<li class="cleanup xhistory' + (c ? (' ' + c) : '') +'">' + str + '</li>';
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
