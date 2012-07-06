ns.convertTimezoneToSecond = function(tz){
    tz = tz ? tz : '';
    var offsetSign = tz.substr(0,1);
    offsetSign = offsetSign==0 ? "+" : offsetSign;
    var offsetDetail = tz.substr(1).split(":");
    var offsetSecond = (parseInt(offsetDetail[0]*60)+parseInt(offsetDetail[1]))*60;
    offsetSecond = parseInt(offsetSign + offsetSecond);

    return offsetSecond;
};


////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

/**
 * Remove a item from Array by item ID
 * @Array, items id;
 * @Return Array
 **/
util.removeArrayItemById = function(myArray, itemIDToRemove){
    if(!util.isArray(myArray) || isNaN(itemIDToRemove)){
        return false;
    }
    myArray.splice(itemIDToRemove, 1);
    return myArray;
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
 * get human datetime
 * by Leask with han
 */
util.getHumanDateTime = function(strTime, offset, lang) {
    // init
    strTime = strTime ? strTime : '';
    var oriDate   = strTime.split(','),
        time_type = '';
    strTime = this.trim(oriDate[0]);
    var withTime  = strTime.split(' ').length > 1;
    // get timetype
    if (oriDate.length > 1) {
        time_type = this.trim(oriDate[1]);
    } else if (!withTime) {
        time_type = 'Anytime';
    }
    if (strTime === '' || strTime === '0000-00-00 00:00:00') {
        return oriDate.length > 1 && time_type ? time_type : 'Sometime';
    }
    // fix timezone offset
    var objDate   = this.getDateFromString(strTime);
    if (objDate === null) {
        return '';
    }
    if (withTime && !time_type) {
        objDate = new Date(objDate.getTime()
                + (typeof offset !== 'undefined' ? offset
                : odof.comm.func.convertTimezoneToSecond(odof.comm.func.getTimezone())) * 1000);
    }
    // rebuild time
    var arrMonth = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        month    = arrMonth[objDate.getMonth()],
        date     = objDate.getDate(),
        year     = objDate.getFullYear(),
        stdDate  = '',
        stdTime  = '';
    if (withTime) {
        var hour24 = objDate.getHours(),
            hour12 = hour24 > 12 ? (hour24 - 12) : hour24,
            ampm   = hour24 < 12 ? 'AM'          : 'PM',
            minute = objDate.getMinutes();
        minute = minute < 10 ? ('0' + minute) : minute;
    }
    // return
    switch (lang) {
        case 'en':
        default:
            stdDate = [month, date].join(' ') + ', ' + year;
            stdTime = withTime ? (hour12 + ':' + minute + ' ' + ampm) : '';
            return (time_type ? time_type : stdTime) + ', ' + stdDate;
    }
};


/**
 * parse location string
 * by Leask
 */
util.parseLocation = function(strPlace) {
    var arrPlace = strPlace.split(/\r|\n|\r\n/),
        prvPlace = [],
        i = 0, l = arrPlace.length, item;
    for (; i < l, item = arrPlace[i]; i++) {
        if (item !== '') {
            prvPlace.push(item);
        }
    }
    return prvPlace.length
         ? [prvPlace.shift(), prvPlace.join("\r")]
         : ['', ''];
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




