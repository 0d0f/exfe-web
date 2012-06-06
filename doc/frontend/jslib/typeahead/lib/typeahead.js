define('typeahead', [], function (require, exports, module) {

  /**
   * Typeahead
   *  - popmenu
   *  - autocomplete
   *
   * Dependence:
   *  - jQuery
   *  - Widget
   *
   * Thanks to:
   *  - https://github.com/twitter/bootstrap/blob/master/js/bootstrap-typeahead.js
   */

  var $ = require('jquery');
  var Widget = require('widget');

  var $BODY = document.body;

  var Typeahead = Widget.extend({

    options: {
      template: '<div class="popmenu hide"></div>',

      // input target
      target: null,

      parentNode: $BODY,

      viewData: {
        menu: '<ul class="typeahead dropdown-menu"></ul>',
        item: '<li></li>'
      }

    },

    shown: false,

    init: function () {
      this.parentNode = this.options.parentNode;
      this.element.appendTo(this.parentNode);
      this.listen();
    },

    listen: function () {
      this.target = this.target || this.options.target;
      this.target
        .on('blur.delegateEvents', proxy(this.blur, this))
        .on('keypress.delegateEvents', proxy(this.keypress, this))
        .on('keyup.delegateEvents', proxy(this.keyup, this))
        /*
        .on('drop', function (e) {
          var notecard = e.originalEvent.dataTransfer.getData("text/plain");
          console.log(notecard);
          //e.preventDefault();
          //console.log(1);
        })
        */
        .on('focus.delegateEvents', proxy(this.focus, this));

      if ($.browser.webkit || $.browser.msie) {
        this.target.on('keydown.delegateEvents', proxy(this.keypress, this));
      }

      this.element
        .on('click.delegateEvents', proxy(this.click, this))
        .on('mouseenter', 'li', proxy(this.mouseenter, this));
    },

    select: function () {
      var val = this.$('.active').attr('data-value');
      this.target
        .val(this.updater(val))
        .change();

      return this.hide();
    },

    updater: function (item) {
      return item;
    },

    render: function (items) {
      var that = this;
      items = $(items).map(function (i, item) {
      });

      items.first().addClass('active');
      this.element.html(items);
      return this;
    },

    show: function () {
      var pos = $.extend({}, this.target.offset(), {
        height: this.target[0].offsetHeight
      });

      this.element.css({
        top: pos.top + pos.height,
        left: pos.left
      });

      this.element.removeClass('hide');
      this.shown = true;
      return this;
    },

    hide: function () {
      this.element.addClass('hide');
      this.shown = false;
      return this;
    },

    lookup: function (e) {
      var that = this
        , options = this.options
        , items;

      this.query = $.trim(this.target.val());

      if (!this.query) {
        this.emit('nothing');
        return this.shown ? this.hide() : this;
      }

      this.emit('search', this.query);
    },

    next: function (e) {
      var active = this.element.find('.active').removeClass('active')
        , next = active.next();

      if (!next.length) {
        next = this.element.find('li')[0];
      }

      next.addClass('active');
    },

    prev: function (e) {
      var active = this.element.find('.active').removeClass('active')
        , prev = active.prev();

      if (!prev.length) {
        prev = this.element.find('li').last();
      }

      prev.addClass('active');
    },

    focus: function (e) {},

    keyup: function (e, keyCode) {
      keyCode = e.keyCode;
      switch(keyCode) {
        case 40: // down arrow
        case 38: // up arrow
          break;

        case 9: //tab
        case 13: //enter
          if (!this.shown) return;
          this.select();
          break;

        case 27: // escape
          if (!this.shown) return;
          this.hide();
          break;

        default:
          this.lookup();
      }

      e.stopPropagation();
      e.preventDefault();
    },

    keypress: function (e, keyCode) {
      if (!this.shown) return;

      keyCode = e.keyCode;
      switch(keyCode) {
        case 9: // tab
        case 13: // neter
        case 27: // escape
          e.preventDefault();
          break;

        case 38: // up arrow
          if (e.type !== 'keydown') break;
          e.preventDefault();
          this.prev();
          break;

        case 40: //down break
          if (e.type !== 'keydown') break;
          e.preventDefault();
          this.next();
          break;
      }

      e.stopPropagation();
    },

    blur: function (e) {
      var that = this;
      setTimeout(function () {that.hide();}, 150);
    },

    click: function (e) {
      e.stopPropagation();
      e.preventDefault();
      this.select();
    },

    mouseenter: function (e) {
      this.element.find('.active').removeClass('active');
      $(e.currentTarget).addClass('active');
    }

  });

  return Typeahead;

  // simple proxy function, $.proxy
  function proxy(f, c) {
    if (!f) return undefined;
    return cb;
    function cb(e) {
      return f.call(c, e);
    }
  }
});

