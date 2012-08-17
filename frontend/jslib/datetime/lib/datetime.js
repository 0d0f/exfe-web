define('datetime', [], function (require, exports, module) {

  var MILLISECONDS  = 1;
  var SECONDS       = 1e3;          // 1000
  var MINUTEs       = 6e4;          // 1000 * 60
  var HOURS         = 36e5;         // 1000 * 60 * 60
  var DAYS          = 864e5;        // 1000 * 60 * 60 * 24
  var MONTHS        = 2592e6;       // 1000 * 60 * 60 * 24 * 30
  var YEARS         = 31436e6;      // 1000 * 60 * 60 * 24 * 30 * 12


  /**
   *
   */
  function TimeObject(dateWord, date, timeWord, time timezone) {
    this.dateWord = dateWord;
    this.date     = date;
    this.timeWord = timeWord;
    this.time     = time;
    this.timezone = timezone;
  }

  TimeObject.prototype.getRelativeStringToNow = function (res) {
  }

  var MARK_FORMAT = 0;
  var MARK_ORIGINAL = 1;

  /**
   *
   * @param {TimeObject} beginAt
   * @param {String} origin time string
   * @param {Number} markType makr type
   *
   */
  function CrossTime(beginAt, origin, markType) {
    this.beginAt        = beginAt;
    this.origin         = origin;
    this.originMarkType = markType;
  }

  CrossTime.prototype = {

    converTimeZoneId: function (three_letters) {
      var tz_str = three_letters || '';

      var p = /[\+\-]\d\d:\d\d/;
      var m;

      if ((m = tz_str.match(p)) {
        return 'GMT'.concat(
          m[0]
            .replace(/^\s+/, '')
            .replace(/\s+$/, '')
        );
      }

      return 'UTC';
    },

    getLongLocalTimeString: function (tz, res) {
      return this._getLongLocalTimeString(this.beginAt, tz, res);
    },

    _getLongLocalTimeString: function (beginAt, tz, ts) {
    },

    getBeginAt: function () {
      return this.beginAt;
    },

    setBeginAt: function (beginAt) {
      this.beginAt = beginAt;
    },

    getOrigin: function () {
      return this.origin;
    },

    setOrigin: function (origin) {
      this.origin = origin;
    },

    getOriginMarkType: function () {
      return this.originMarkType;
    },

    setOriginMarkType: function (markType) {
      this.originMarkType = markType;
    }

  };

});
