/**
 * Exfe's DatePanel Widget.
 * 日期控件
 */
define('datepanel', function (require, exports, module) {
  var $ = require('jquery');
  var Api = require('api');
  var Panel = require('panel');

  var DatePanel = Panel.extend({

      options: {

          template: ''
          + '<div class="panel date-panel" tabindex="-1" data-widget="panel" id="date-panel">'
            + '<div class="panel-header"><input type="text" name="date-string" id="date-string" /></div>'
            + '<div class="panel-body">'
                + '<div class="pull-right date-timeline"><ul class="unstyled"></ul></div>'
                + '<div class="date-container" tabindex="-1">'
                  + '<ul class="unstyled clearfix" id="date-head"><li>Sun</li><li>Mon</li><li>Tue</li><li>Wed</li><li>Thu</li><li>Fri</li><li>Sat</li></ul>'
                  + '<div class="full-month"></div>'
                  + '<table class="table" id="date-table"><tbody></tbody></table>'
              + '</div>'
            + '</div>'
            + '<div class="panel-footer"></div>'
          + '</div>'

        , parentNode: null

        , srcNode: null

        , eftime: null

      }

    , init: function () {
        var options = this.options
          , eftime;
        this.eftime = eftime = options.eftime;
        delete options.eftime;
        this.render();
        this.dateInput = new DateInput(this.$('#date-string'), this);
        this.dateInput.eftime = eftime;

        var sss = '',  d;
        if (eftime.outputformat) {
          sss = eftime.origin;
          d = new Date();
        } else {
          var d = parseISO8601(eftime.begin_at.date + '' + (eftime.begin_at.time ? ('T' + eftime.begin_at.time) : '') + 'Z');
          sss = d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate() + (eftime.begin_at.time ? ' ' + d.getHours() + ':' + d.getMinutes() + ':' + d.getSeconds() : '');
        }

        this.dateInput.input(sss);
        this.calendarTable = new CalendarTable(this.$('.date-container'), this, {
            // 日历最多显示 3 行
            maxRows: 3
          , cursor: 0
          , date: d
        });
        this.dataController = new DataController();

        this.on('updateDate', function (date, time) {
          this.dateInput.input(date + (date ? ' ' : '') + time);
        });

        this.on('refresh', function (date) {
          this.calendarTable.clean();
          var ds = date.split('-');
          this.calendarTable.date = new Date(ds[0], (ds[1] - 1), ds[2]);
          this.calendarTable.refresh();
        });

        this.generateTimeLine();

        this.on('enter', function (date) {
          $('body').trigger('click');
        });
      }

    , generateTimeLine: function () {
        var self = this;
        var ul = this.$('.date-timeline ul');
        var l = 24, i = 0, s = '';
        for (; i < 24; ++i) {
          s = i < 10 ? '0' + i : i;
          ul.append('<li>' + s + ':00</li><li>' + s + ':30</li>');
        }
        ul
          .find('li')
          .click(function (e) {
            var str = $(this).text();
            self.emit('updateDate', '', str);
          });
        setTimeout(function () {this.$('.date-timeline').scrollTop(360);}, 0);
      }

    , showBefore: function () {
        this.element.attr('editarea', 'date-panel')
      }

    , showAfter: function () {
        var srcNode = this.srcNode;
        if (srcNode) {
          var offset = srcNode.offset();
          var width = this.element.outerWidth();
          this.element
            .css({
                left: offset.left - width - 15
              , top: offset.top
            })
        }
        this.dateInput.el.focus();
      }

    , destory: function () {
        this.element.off();
        this.element.remove();
        this._destory();
      }

  });

  /**
   * 日期输入组件
   */
  var DateInput = function (element, component, options) {
    this.component = component;
    this.el = element;
    this.el.on('keypress.dateinput', $.proxy(this.keypress, this));
    this.el.on('keyup.dateinput', $.proxy(this.keyup, this));
    if ($.browser.webkit | $.browser.msie | $.browser.mozilla) {
      this.el.on('keydown.dateinput', $.proxy(this.keypress, this));
    }
    this.befer = null;
    this.timezone = this.el.data('timezone');
    if (!this.timezone) {
      this.timezone = getTimezone();
    }
  };

  DateInput.prototype = {

      input: function (datestring) {
        if (/^\d\d:\d\d$/.test(datestring)) {
          datestring = (this.component.element.find('table td.selected').data('date') || this.component.element.find('table td.today').data('date')) + ' ' + datestring;
        }
        this.el.val($.trim(datestring));
        this.oldVal = datestring;
      }

    , output: function () {
        return $.trim(this.el.val());
      }

    , lookup: function () {
        var self = this
          , component = self.component;
        self.befer && self.befer.abort();
        var date_string = $.trim(self.el.val());
        if (!date_string) {
          return;
        }
        if (self.oldVal === date_string) {
          return;
        }
        self.befer = Api.request('recognize'
          , {
              type: 'POST'
            , data: {
                  time_string: date_string
                , timezone: self.timezone
              }
            }
          , function (data) {
              var eftime = data.cross_time, date;
              if (eftime.begin_at.date) {
                date = eftime.begin_at.date;
              } else {
                var d = new Date();
                date = d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
              }
              component.emit('refresh', date);
            }
          , function (data) {
            }
        );
      }

    , keyup: function (e, keyCode) {
        //e.stopPropagation();
        var self = this;
        keyCode = e.keyCode;
        switch(keyCode) {
          case 40: // down arrow
          case 38: // up arrow
            break;

          case 9: //tab
            break;
          case 13: //enter
            break;

          case 27: // escape
            break;

          default:
            self.lookup();
        }
        //e.stopPropagation();
        e.preventDefault();
      }

    , keypress: function (e, keyCode) {
        var self = this;
        keyCode = e.keyCode;
        switch(keyCode) {
          case 9: // tab
            self.el.parent().next().find('.date-container').focus();
            e.preventDefault();
            break;
          case 13: // enete
            var date = self.output();
            self.component.emit('enter', date);
            break;
          case 27: // escape
            e.preventDefault();
            break;

          case 38: // up arrow
            break;

          case 40: //down break
            break;
        }
        //e.stopPropagation();
      }

  };

  /**
   * 日期表格组件
   */
  var months = 'January February March April May June July August September October November December'.split(' ');
  var monthsShort = 'Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec'.split(' ');
  /*
    <tr>
      <td> <span class="m">Sep</span> <span class="d">28</span> </td>
      <td> <span class="m">Sep</span> <span class="d">29</span> </td>
      <td class="hover today"> <span class="m">Sep</span> <span class="d">Today</span> </td>
      <td> <span class="m">Sep</span> <span class="d">31</span> </td>
      <td> <span class="m">Sep</span> <span class="d">1</span> </td>
      <td class="selected"> <span class="m">Sep</span> <span class="d">2</span> </td>
      <td> <span class="m">Sep</span> <span class="d">3</span> </td>
    </tr>
  */
  var CalendarTable = function (element, component, options) {
    this.el = element;
    this.component = component;

    this.length = 0;
    this.lines = 0;
    this.columns = 7;

    this.options = options;
    this.options || (this.options = {});
    options = this.options;
    options.maxRows || (options.maxRows = 3);
    options.cursor || (options.cursor = 0);

    this.today = new Date();
    this.date = options.date || new Date();
    this.todayString = format(this.today);

    this.nextPage(this.date);
    this.initCursor(format(this.date));
    this.setCursorClass();
    this.select();

    this.lines = options.maxRows;
    this.line = 0;

    this.column = this.date.getDay();

    this.table = this.el.find('table');

    this.keyDown();
    this.handlers();
  };

  CalendarTable.prototype = {

      refresh: function () {
        var options = this.options;
        this.length = 0;
        this.nextPage(this.date);
        this.initCursor(format(this.date));
        this.setCursorClass();
        this.select();
        this.lines = options.maxRows;
        this.line = 0;
        this.column = this.date.getDay();
        this.scrollTop(0);
      }

    , scrollTop: function (d) {
        this.table
          .css('margin-top', d);
      }

    , handlers: function () {
        var self = this
          , el = this.el
          , options = self.options;

        el.on('mouseleave.calendar', 'table', function (e) {
          var td = self.getSelected();
          self.setCursor(td);
          self.line = td.parent().index();
          self.column = td.index();
        });

        el.on('hover.calendar', 'table td', function (e) {
          el.find('.full-month').text('');
          e.preventDefault();
          var t = e.type
            , isMouseEnter = t === 'mouseenter'
            , td = $(this);
          if (isMouseEnter) {
            self.setCursor(td);
            self.setCursorClass();
            self.line = td.parent().index();
            self.column = td.index();
          } else {
            self.currentCursor
              .removeClass('hover')
              .find('.m').addClass('hide');
          }
        })
          .on('click.calendar', 'table td', function (e) {
            var td = $(this)
              , date = td.data('date');

            self.el.find('td.selected').removeClass('selected');
            td.addClass('selected');
            self.component.emit('updateDate', date, '');
          });
      }

    , keyDown: function () {
        var self = this
          , el = this.el
          , options = self.options;
        el.on('keydown.calendar', function (e) {
          var kc = e.keyCode
            , shift = e.shiftKey;
          var ltrb = false;
          e.preventDefault();
          switch (kc) {
            // spacing
            case 32:
                self.el.find('td.selected').removeClass('selected');
                self.select();
                var date = self.currentCursor.data('date');
                self.component.emit('updateDate', date, '');
              break;
            case 13:
                self.el.find('td.selected').removeClass('selected');
                self.select();
                var date = self.currentCursor.data('date');
                self.component.emit('updateDate', date, '');
                self.component.emit('enter', date);
              break;
            // left
            case 37:
              ltrb = true;
              if (0 === self.column) {
                self.column = 6;
                if (0 === self.line) {
                  self.prevPage();
                  self.scrollTop(0);
                  self.line = 2;
                } else {
                  if (0 === self.line % 3) {
                    self.scrollTop('+=132');
                  }
                  self.line--;
                }
              } else {
                self.column--;
              }
              self.left();
              break;
            // top
            case 38:
              ltrb = true;
              if (0 === self.line) {
                self.prevPage();
                self.scrollTop(0);
                self.line = 2;
              } else {
                if (0 === self.line % 3) {
                  self.scrollTop('+=132');
                }
                self.line--;
              }
              self.top();
              break;
            case 39:
              ltrb = true;
              if (6 === self.column) {
                self.column = 0;
                if (self.line === self.lines - 1) {
                  self.nextPage();
                  self.scrollTop('-=132');
                } else {
                  if (0 === (self.line + 1) % 3) {
                    self.scrollTop('-=132');
                  }
                }
                self.line++;
              } else {
                self.column++;
              }
              self.right();
              break;
            case 40:
              ltrb = true;
              if (self.line === self.lines - 1) {
                self.nextPage();
                self.scrollTop('-=132');
              } else {
                if (0 === (self.line + 1) % 3) {
                  self.scrollTop('-=132');
                }
              }
              self.line++;
              self.bottom();
              break;
            case 9:
              self.el.parent().prev().find('input#date-string').focus();
              break;
          }
          self.el.find('.full-month').text(ltrb ? months[self.date.getMonth()] : '');
        });
      }

    , generateHTML: function (printDate) {
        var options = this.options
          , maxRows = options.maxRows
          , todayString = this.todayString
          , tdStr = '<span class="m hide">{{m}}</span><span class="d">{{d}}</span>';

        var tbody = '';

        this.lines += maxRows;

        for (var i = 0; i < maxRows; ++i) {

          var tr = '<tr>'
            , td = ''
            , fs = ''
            , isToday;

          for (var j = 0; j < 7; j++) {
            fs = format(printDate);

            isToday = fs === todayString;

            td += '<td data-date="' + fs + '"' + (isToday ? ' class="today"' : '') + '>';

            var ts = tdStr;

            td += ts
                    .replace('{{m}}', monthsShort[printDate.getMonth()])
                    .replace('{{d}}', isToday ? 'Today' : printDate.getDate());

            td += '</td>';
            printDate.setDate(printDate.getDate() + 1);
          }

          tr += td + '</tr>';

          tbody += tr;
        }
        return tbody;
      }

    , prevPage: function () {
        var options = this.options
          , maxRows = options.maxRows
          , startOf = this.startOf
          , printDate;

        this.startOf = startOf = new Date(startOf.getFullYear(), startOf.getMonth(), startOf.getDate() - 7 * maxRows);

        printDate = new Date(startOf.getFullYear(), startOf.getMonth(), startOf.getDate());

        this.el.find('tbody').prepend(this.generateHTML(printDate));
      }

    , nextPage: function (thisDate) {
        var options = this.options
          , endOf = this.endOf;

        if (thisDate) {
          var date = thisDate.getDate()
            , day = thisDate.getDay();
          this.startOf = new Date(thisDate.getFullYear(), thisDate.getMonth(), date - day);
          this.endOf = new Date(thisDate.getFullYear(), thisDate.getMonth(), date - day);
        }

        this.length++;

        this.el.find('tbody').append(this.generateHTML(this.endOf));
      }

    , getSelected: function () {
        return this.el.find('td.selected, td.today').eq(0);
      }

    , initCursor: function (datestring) {
        this.setCursor(
          this.el.find('td[data-date="' + datestring + '"]')
        );
      }

    , setCursor: function (td) {
        this.currentCursor = td;
        var d = this.currentCursor.data('date');
        this.date = new Date(d);
      }

    , select: function () {
        this.currentCursor.addClass('selected');
      }

    , setCursorClass: function () {
        this.el.find('td.hover, td.today').removeClass('hover')
          .find('.m').addClass('hide');
        this.currentCursor.addClass('hover')
          .find('.m').removeClass('hide');
      }

    , move: function () {}

    , moveX: function () {}

    , moveY: function () {}

    , left: function () {
        var td;
        if (6 !== this.column) {
          td = this.currentCursor.prev();
        } else {
          td = this.currentCursor.parent().prev().find('td').eq(this.column);
        }
        this.setCursor(td);
        this.setCursorClass();
      }

    , top: function () {
        var td = this.currentCursor.parent().prev().find('td').eq(this.column);
        this.setCursor(td);
        this.setCursorClass();
      }

    , right: function () {
        var td;
        if (0 === this.column) {
          td = this.currentCursor.parent().next().find('td').eq(this.column);
        } else {
          td = this.currentCursor.next();
        }
        this.setCursor(td);
        this.setCursorClass();
      }

    , bottom: function () {
        var td = this.currentCursor.parent().next().find('td').eq(this.column);
        this.setCursor(td);
        this.setCursorClass();
      }

    , clean: function () {
        this.el.find('tbody').empty();
      }

  };

  /**
   * 日期数据交互组件
   */
  var DataController = function () {};

  DataController.prototype = {};


  /*
   * Helpers
   */
  // get time zone
  var getTimezone = function () {
    var s = (new Date()).toString()
      , tz = s.replace(/^.*([\+\-]\d\d):?(\d\d).*$/, '$1:$2')
      , ts = s.replace(/^.*\(([a-z]*)\).*$/i, '$1');
    ts = (ts === 'UTC' || ts === 'GMT') ? '' : ts;
    return tz + (ts ? (' ' + ts) : '');
  };

  // 2012-11-12
  var format = function (date) {
    return date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
  };

  // days in a month.
  var daysInMonth = function (year, month) {
    var date = new Date(0);
    date.setUTCFullYear(year, month, 0);
    return date.getUTCDate();
  };

  // day of the week of the first of a month.
  var firstDayOfMonth = function (year, month) {
    var date = new Date(0);
    date.setUTCFullYear(year, month - 1, 1);
    return date.getUTCDay();
  };

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

  return DatePanel;
});
