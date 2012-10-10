/**
 * efTime.js
 * Relative time
 *
 * Referrer:
 *  https://github.com/fnando/i18n-js
 *  ISO 8601 http://www.w3.org/TR/NOTE-datetime
 *  http://en.wikipedia.org/wiki/ISO_8601
 *  http://tools.ietf.org/html/rfc3339
 *    UTC YYYY-MM-DDTHH:MM:SSZ 2012-10-01T20:30:33+0800
 */
define('eftime', function (require, exports, module) {
  // Set the placeholder format. Accepts `{{placeholder}}` and `%{placeholder}`.
  var PLACEHOLDER = /(?:\{\{|%\{)(.*?)(?:\}\}?)/gm
    , parseTokenTimezone = /([\+\-])(\d\d):(\d\d)/
    , abs = Math.abs
    , millsOfMinute = 6e4         // 1000 * 60
    , millsOfHour = 36e5          // 1000 * 60 * 60
    , millsOfDay = 864e5          // 1000 * 24 * 60 * 60
    , daysOfWeek = 7
    , daysOfMonth = 30
    , daysOfYear = 365.25;        // 365 + .25 取平均值

  var efTime = module.exports = function () {};

  var settings = efTime.settings = {
    weekStartAt: 1
  };

  var EN = {
      weekdays: 'Sunday Monday Tuesday Wednesday Thursday Friday Saturday'.split(' ')
    , '0': function (data) {
        var s = '';
        if ('years' in data) {
          s = '{{years}} years';
        }
        if ('months' in data) {
          s += (s ? ' ' : '') + '{{months}} months';
        }
        return s + ' ago';
      }
    , '1'   : '{{days}} days ago'
    , '2'   : '2 days ago'
    , '3'   : 'Yesterday'
    , '4'   : '{{hours}} hours ago'
    , '5'   : '{{hours}} hours ago'
    , '6'   : function (data, type) {
                return type === 'X' ? 'Just Now' : '{{minutes}} minutes ago'
              }
    , '7'   : function (data, type) {
                return type === 'X' ? 'Now' : '{{minutes}} minutes ago'
              }
    , '8'   : function (data, type) {
                return type === 'X' ? 'Now' : 'Seconds ago';
              }
    , '9'   : 'In {{minutes}} minutes'
    , '10'  : 'In {{hours}} hours'
    , '11'  : 'Today'
    , '12'  : 'Tomorrow'
    , '13'  : 'In 2 days'
    , '14'  : function (data) {
        return EN.weekdays[data.day];
      }
    , '15'  : function (data) {
        return 'Next ' + EN.weekdays[data.day];
      }
    , '16'  : 'In {{days}} days'
    , '17'  : function (data) {
        var s = '';
        if ('years' in data) {
          s = '{{years}} years';
        }
        if ('months' in data) {
          s += (s ? ' ' : '') + '{{months}} months';
        }
        return 'In ' + s;
      }
  };

  efTime.locale = 'en';

  efTime.locales = {
    en: EN
  };

  efTime.timeAgo = function (t, s, type, c) {
    var lang = efTime.locales[efTime.locale]
      , distanceTime = efTime.distanceOfTime(t, s)
      , input = efTime.diff(distanceTime);
    input.type = type;
    var output = efTime.inWords(input, lang);

    if ('function' === typeof c) {
      output = c(output.data);
    }

    return output;
  };

  efTime.inWords = function (input, lang) {
    var token = lang[input.token]
      , type = input.type
      , data = input.data
      , matches
      , placeholder
      , value
      , name
      , regex
      , i = 0
      , message;

    if ('function' === typeof token) {
      message = token(data, type);
    }
    else {
      message = token;
    }

    matches = message.match(PLACEHOLDER);

    if (!matches) {
      return message;
    }

    for (; placeholder = matches[i]; ++i) {
      name = placeholder.replace(PLACEHOLDER, '$1');

      value = data[name];

      regex = new RegExp(placeholder.replace(/\{/gm, '\\{').replace(/\}/gm, '\\}'));
      message = message.replace(regex, value);
    }

    return message;
  };

  efTime.diff = function (distanceTime) {
    var tdate = distanceTime.target
      , sdate = distanceTime.source
      , day
      , milliseconds = distanceTime.distance
      , days = distanceTime.daysDistance
      , months
      , years
      , minutes
      , data
      , output = {
            sign: milliseconds >= 0 ? '+' : '-'
          , data: data = {}
        };

    // -31 days >= x
    if (-31 >= days) {
      days = - days;
      years = floor(days / daysOfYear);
      months = round(days % daysOfYear / daysOfMonth);
      if (years) {
        data.years = years;
      }
      if (months) {
        data.months = months;
      }
      output.token = 0;
    }

    // -30 days <= x <= -3 days
    else if (-30 <= days && days <= -3) {
      data.days = - days;
      output.token = 1;
    }

    // -2 days = x
    else if (-2 === days) {
      data.days = 2;
      output.token = 2;
    }

    // -1 days = x
    else if (-1 === days) {
      data.days = 1;
      output.token = 3;
    }

    // 0 days = x
    else if (0 === days) {
      minutes = round(milliseconds / millsOfMinute);

      // -1439m <= x <= 720m
      if (-720 >= minutes) {
        data.hours = - round(milliseconds / millsOfHour);
        output.token = 4;
      }

      // - 719m <= x <= -60m
      else if (-719 <= minutes && minutes <= -60) {
        data.hours = - round(milliseconds / millsOfHour);
        output.token = 5;
      }

      // -59m <= x <= 31m
      else if (-59 <= minutes && minutes <= -31) {
        data.minutes = - minutes;
        output.token = 6;
      }

      // -30m <= x <= -1m
      else if (-30 <= minutes && minutes <= -1) {
        data.minutes = - minutes;
        output.token = 7;
      }

      // 0m = x
      else if (0 === minutes) {
        data.minutes = 0;
        output.token = 8;
      }

      // 1m <= x <= 59m
      else if (1 <= minutes && minutes <= 59) {
        data.minutes = minutes;
        output.token = 9;
      }

      // 60m <= x <= 749m
      else if (60 <= minutes && minutes <= 749) {
        data.hours = round(milliseconds / millsOfHour);
        output.token = 10;
      }

      // 750m <= x <= 1439m
      else if (750 <= minutes) {
        data.days = 0;
        output.token = 11;
      }

    }

    // 1 days = x
    else if (1 === days) {
      data.days = 1;
      output.token = 12;
    }

    // 2 days = x
    else if (2 === days) {
      data.days = 2;
      output.token = 13;
    }

    // 3 days <= x <= 30 days
    else if (3 <= days && days <= 30) {
      day = sdate.getDay();
      var thisDay = new Date(sdate.getFullYear(), sdate.getMonth(), sdate.getDate() + days)
        , weekFirstDay = new Date(sdate.getFullYear(), sdate.getMonth(), sdate.getDate() + (settings.weekStartAt - day))
        , nextWeekFirstDay
        , nextWeekLastDay;
      // current week
      if (+weekFirstDay <= +sdate) {
        nextWeekFirstDay = new Date(+weekFirstDay);
        nextWeekFirstDay.setDate(weekFirstDay.getDate() + daysOfWeek);
        nextWeekLastDay = new Date(+nextWeekFirstDay);
        nextWeekLastDay.setDate(nextWeekLastDay.getDate() + daysOfWeek);
      }
      // next week
      else {
        nextWeekFirstDay = weekFirstDay;
        nextWeekLastDay = new Date(+nextWeekFirstDay);
        nextWeekLastDay.setDate(nextWeekLastDay.getDate() + daysOfWeek);
        weekFirstDay = new Date(+nextWeekFirstDay);
        weekFirstDay.setDate(weekFirstDay.getDate() - daysOfWeek);
      }

      // current week
      if (+weekFirstDay <= +thisDay && +thisDay < +nextWeekFirstDay) {
        data.day = thisDay.getDay();
        output.week = 'current';
        output.token = 14;
      }
      // next week
      else if(+nextWeekFirstDay <= +thisDay && +thisDay < +nextWeekLastDay) {
        data.day = thisDay.getDay();
        output.week = 'next';
        output.token = 15;
      }
      else {
        data.days = days;
        output.token = 16;
      }
    }

    // 31 <= days
    else if (31 <= days) {
      years = floor(days / daysOfYear);
      months = round(days % daysOfYear / daysOfMonth);
      if (years) {
        data.years = years;
      }
      if (months) {
        data.months = months;
      }
      output.token = 17;
    }

    return output;
  };

  efTime.distanceOfTime = function (t, s) {
    t = parseISO8601(t);

    if (undefined === s) {
      s = new Date();
    }
    else if ('number' === typeof s) {
      s = new Date(s);
    }
    else if ('string' === typeof s) {
      s = parseISO8601(s);
    }

    return {
      // target datetime
        target: t
      // source datetime
      , source: s
      // Milliseconds
      // = d0.getTime() - d1.getTime()
      , distance: +t - +s
      , daysDistance: (+new Date(t.getFullYear(), t.getMonth(), t.getDate()) - +new Date(s.getFullYear(), s.getMonth(), s.getDate())) / millsOfDay
      };
  };

  // ISO8601 datestring = '2012-08-06T23:30:00+0800'
  efTime.parseISO8601 = parseISO8601;

  // Helpers:
  // --------------------------------
  function parseISO8601(datestring) {
    datestring = datestring.replace(/-/, '/').replace(/-/, '/');
    // datestring = '2012-06-09'
    if (datestring.length > 10) {
      datestring = datestring.replace(/\.\d\d\d/, ''); // 0 ~ 999 ms
      datestring = datestring.replace(/T/, ' ');
      datestring = datestring.replace(/([\+\-]\d\d):?(\d\d)/, ' UTC$1$2');
      datestring = datestring.replace(/Z/, ' UTC+0000'); // at UTC
    }
    datestring = new Date(datestring);
    return datestring;
  }

  // "2012-09-12 09:51:04 +0000" => "2012-09-12T09:51:04+0000"
  efTime.printISO8601 = function (datestring) {
    return datestring.replace(/\s/, 'T').replace(/\s/, '');
  };

  // 取整
  function floor(n) {
    return n - n % 1;
  }

  // 7舍8入
  function round(n, _d, _i) {
    _i = n < 0 ? -1 : 1;
    n = abs(n);
    //decimal
    _d = n % 1;
    n -= _d;
    return _i * (_d < 0.8 ? n : n + 1);
  }

});
