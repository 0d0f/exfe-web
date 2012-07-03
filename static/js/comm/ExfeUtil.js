ns.verifyDisplayName = function(dname){
    if(typeof dname == "undefined" || dname == ""){
        return false;
    }
    var nameLength = ns.getUTF8Length(dname);
    //var nameREG = "^[0-9a-zA-Z_\ \'\.]+$";
    //var nameREG = "^(?!_)(?!.*?_$)[a-zA-Z0-9_\u4e00-\u9fa5]+$";
    var nameREG = "^[0-9a-zA-Z_\u4e00-\u9fa5\ \'\.]+$";
    var re = new RegExp(nameREG);
    if(!re.test(dname) || nameLength > 30){
        return false;
    }
    return true;

};


ns.convertTimezoneToSecond = function(tz){
    tz = tz ? tz : '';
    var offsetSign = tz.substr(0,1);
    offsetSign = offsetSign==0 ? "+" : offsetSign;
    var offsetDetail = tz.substr(1).split(":");
    var offsetSecond = (parseInt(offsetDetail[0]*60)+parseInt(offsetDetail[1]))*60;
    offsetSecond = parseInt(offsetSign + offsetSecond);

    return offsetSecond;
};


ns.getTimezone = function() {
    var rawTimezone = Date().toString().replace(/^.+([a-z]{3}[+-]\d{4}).+$/i, '$1'),
        tagTimezone = rawTimezone.replace(/^([a-z]{3}).+$/i, '$1'),
        numTimezone = rawTimezone.replace(/^[a-z]{3}([+-])(\d{2})(\d{2})$/i, '$1$2:$3');
    return numTimezone + (tagTimezone === 'UTC' ? '' : (' ' + tagTimezone));
};


////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////


/**
 * Create a rand element id (string)
 * @None
 **/
util.createRandElementID = function() {
    var now = new Date().getTime ();
    return now + ":" + Math.floor(Math.random() * 100000000);
};


/**
 * Remove a item from Array by item value
 * @Array, items;
 * @Return Array
 **/
util.removeArrayItemByVal = function(myArray, itemToRemove) {
    var j = 0;
    while (j < myArray.length) {
        if (myArray[j] == itemToRemove) {
            myArray.splice(j, 1);
        }
        j++;
    }
    return myArray;
};


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
 * count object items
 * by Leask
 */
util.count = function(object) {
    var num = 0;
    for (var i in object) {
        num++;
    }
    return num;
};


/**
 * parses sql datetime string and returns javascript date object
 * input has to be in this format: 1989-06-04 00:00:00
 * by Leask
 */
util.getDateFromString = function(strTime) {
    strTime = strTime ? strTime : '';
    var regex = /^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/,
        parts = (strTime.length > 10 ? strTime : (strTime + ' 00:00:00')).replace(regex, "$1 $2 $3 $4 $5 $6").split(' '),
        oDate = new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4], parts[5]);
    return oDate.toString() === 'Invalid Date' ? null : oDate;
};


/**
 * get relative time
 * by Leask
 */
util.getRelativeTime = function(strTime, lang) {
    var objTime   = this.getDateFromString(strTime),
        timestamp = objTime
                  ? (Math.round(objTime.getTime() / 1000)
                  + odof.comm.func.convertTimezoneToSecond(odof.comm.func.getTimezone()))
                  : -1;
    if (timestamp < 0) {
        return '';
    }
    var difference = Date.parse(new Date()) / 1000 - timestamp - window.utcDiff,
        periods    = ['sec', 'min', 'hour', 'day', 'week', 'month', 'year', 'decade'],
        lengths    = ['60', '60', '24', '7', '4.35', '12', '10'],
        ending     = '';
    if (difference > 0) {
        // this was in the past
        ending     = 'ago';
    } else {
        // this was in the future
        difference = -difference;
        ending     = 'later';
    }
    for (var i = 0; difference >= lengths[i]; i++) {
        if (lengths[i] == 0) {
            difference  = 0;
            break;
        } else {
            difference /= lengths[i];
        }
    }
    difference = Math.round(difference);
    if (difference != 1) {
        periods[i] += 's';
    }
    var text = difference + ' ' + periods[i] + ' ' + ending;
    return text;
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
 * parse human datetime
 * by Leask
 */
util.parseHumanDateTime = function(strTime, offset) {
    function db(num) {
        return num < 10 ? ('0' + num.toString()) : num.toString();
    }
    var arrTime = strTime.split(/-|\ +|:/);
    if (arrTime.length === 5) {
        arrTime.push('am');
    }
    if (arrTime.length === 3 || arrTime.length === 6) {
        var month   = parseInt(arrTime[0], 10),
            day     = parseInt(arrTime[1], 10),
            year    = parseInt(arrTime[2], 10),
            hour    = 0,
            minute  = 0,
            time    = '',
            strTime = [year, db(month), db(day)].join('/');
        if (arrTime.length === 6) {
            hour    = parseInt(arrTime[3], 10)
            hour   += (arrTime[5].toLowerCase() === 'pm' && hour !== 12 ? 12 : 0);
            minute  = parseInt(arrTime[4], 10),
            time    = ' ' + [db(hour), db(minute)].join(':');
            if (typeof offset !== 'undefined') {
                var objTime = new Date(new Date(strTime + time).getTime() - offset * 1000);
                month  = objTime.getMonth() + 1;
                day    = objTime.getDate();
                year   = objTime.getFullYear();
                hour   = objTime.getHours();
                minute = objTime.getMinutes();
                time   = ' ' + [db(hour), db(minute)].join(':');
            }
        }
        strTime = [year, db(month), db(day)].join('/') + time;
        if (new Date(strTime).toString() !== 'Invalid Date') {
            return [year, db(month), db(day)].join('-')
                 + (arrTime.length === 6 ? (time + ':00') : '');
        }
    }
    return null;
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




