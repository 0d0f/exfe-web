define('util', function (require, exports, module) {
  var trimLeft = /^\s+/
    , trimRight = /\s+$/
    , zh_CN = /[^0-9a-zA-Z_\u4e00-\u9fa5\ \'\.]+/g
    , trim = String.prototype.trim;

  var Util = {

    // Display Name: 0-9 a-zA-Z _ CJK字符 ' . 空格
    zh_CN: zh_CN,

    // 30个字符，并且删除中文字符
    cut30length: function (s) {
      return s.replace(zh_CN, ' ').substring(0, 30);
    },

    // https://gist.github.com/2762686
    utf8length: function (s) {
      var len = s.length, u8len = 0, i = 0, c;
      for (; i < len; i++) {
        c = s.charCodeAt(i);
        if (c < 0x007f) { // ASCII
          u8len++;
        } else if (c <= 0x07ff) {
          u8len += 2;
        } else if (c <= 0xd7ff || 0xe000 <= c) {
          u8len += 3;
        } else if (c <= 0xdbff) { // high-surrogate code
          c = s.charCodeAt(++i);
          if (c < 0xdc00 || 0xdfff < c) {// Is trailing char low-surrogate code?
            throw "Error: Invalid UTF-16 sequence. Missing low-surrogate code.";
          }
          u8len += 4;
        } else /*if (c <= 0xdfff)*/ { // low-surrogate code
          throw "Error: Invalid UTF-16 sequence. Missing high-surrogate code.";
        }
      }
      return u8len;
    },

    // Remove whitespace
    trim: trim ?
      function (s) {
        return s === null ?
          '':
          trim.call(s);
      } :
      function (s) {
        return s === null ?
          '' :
          s.toString().replace(trimLeft, '').replace(trimRight, '');
    },

    // 解析 用户身份
    parseId: function () {
      var facebook = /^([a-z0-9_]{1,15})@facebook$/i
        , twitter = /^@([a-z0-9_]{1,15})$|^@?([a-z0-9_]{1,15})@twitter$/i
        , normal = /^[a-z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i
        , enormal = /^[^@]*<[a-z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?>$/i;

      return p;

      function p(strid) {
        var res = {}, m;
        strid = Util.trim(strid);

        // facebook
        if ((m = strid.match(facebook))) {
          res.name = m[1];
          res.external_identity = m[1] + '@facebook';
          res.external_username = m[1];
          res.provider = 'fackbook';

        // twitter
        } else if ((m = strid.match(twitter))) {
          res.name = m[1] || m[2];
          res.external_identity = '@' + res.name + '@twitter';
          res.external_username = res.name;
          res.provider = 'twitter';

        // normal
        } else if (normal.test(strid)) {
          res.name = Util.cut30length(strid.split('@')[0]);
          res.external_identity = strid;
          res.provider = 'email';

        // enormal
        } else if (enormal.test(strid)) {
          var iLt = strid.indexOf('<')
            , iGt = strid.indexOf('>');
          res.name = Util.cut30length(strid.substring(0, iLt).replace(/^"|^'|"$|'$/g, ''));
          res.external_identity = strid.substring(++iLt, iGt);
          res.provider = 'email';

        } else {
          res.name = strid;
          res.provider = null;
        }

        return res;
      }

    }(),

    tokenRegExp: /token=([a-zA-Z0-9]{32})/,

    printExtUserName: function (identity) {
      var username = identity.external_username
        , provider = identity.provider;

      if (provider === 'twitter') {
        username = '@' + username;
      }

      return username;
    }

  };

  return Util;

});
