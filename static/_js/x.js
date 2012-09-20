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
