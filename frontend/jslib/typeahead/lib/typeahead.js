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
      source: [],
      template: '<div class="popmenu hide"></div>',

      // input target
      target: null,

      parentNode: $BODY,

      viewData: {
        menu: '<ul class="typeahead"></ul>',
        item: '<li></li>'
      }

    },

    isShown: false,

    init: function () {
      this.parentNode = this.options.parentNode;
      this.source = this.options.source;
      this.element.appendTo(this.parentNode);
      this.listen();
    },

    listen: function () {
      this.target = this.target || this.options.target;
      this.target
        .on('blur.typeahead', proxy(this.blur, this))
        .on('keypress.typeahead', proxy(this.keypress, this))
        .on('keyup.typeahead', proxy(this.keyup, this))
        /*
        .on('drop', function (e) {
          var notecard = e.originalEvent.dataTransfer.getData("text/plain");
          console.log(notecard);
          //e.preventDefault();
          //console.log(1);
        })
        */
        .on('focus.typeahead', proxy(this.focus, this));

      if ($.browser.webkit | $.browser.msie | $.browser.mozilla) {
        this.target.on('keydown.typeahead', proxy(this.keypress, this));
      }

      this.element
        .on('click.typeahead', proxy(this.click, this))
        .on('mouseenter.typeahead', 'li', proxy(this.mouseenter, this));
    },

    select: function () {
      var active = this.$('.active'),
          val = active.data('value');
      this.target
        .val(this.updater(val))
        .change();

      this.emit('select', val);
      return this.hide();
    },

    updater: function (item) {
      return item;
    },

    itemRender: function (item, i) {
      return $(this.options.viewData.item).data('value', item).html(item);
    },

    render: function (items) {
      var that = this;
      items = $(items).map(function (i, item) {
        return that.itemRender(item, i)[0];
      });

      //items.first().addClass('active');
      var $ul = $(this.options.viewData.menu).html(items);
      this.element.html($ul);
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
      this.isShown = true;
      return this;
    },

    hide: function () {
      this.element.addClass('hide');
      this.isShown = false;
      return this;
    },

    lookup: function (e) {
      var that = this
        , options = this.options
        , items;

      this.query = $.trim(this.target.val());

      if (!this.query) {
        this.emit('nothing');
        return this.isShown ? this.hide() : this;
      }

      this.emit('search', this.query);
    },

    next: function (e) {
      var active = this.element.find('.active')
        , next = active.next();

      if (!next.length) {
        next = this.element.find('li').first();
      }

      active.removeClass('active');
      next.addClass('active');
    },

    prev: function (e) {
      var active = this.element.find('.active')
        , prev = active.prev();

      if (!prev.length) {
        prev = this.element.find('li').last();
      }

      active.removeClass('active');
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
          if (!this.isShown) return;
          this.select();
          break;

        case 27: // escape
          if (!this.isShown) return;
          this.hide();
          break;

        default:
          this.lookup();
      }

      e.stopPropagation();
      e.preventDefault();
    },

    keypress: function (e, keyCode) {
      if (!this.isShown) return;

      keyCode = e.keyCode;
      switch(keyCode) {
        //case 9: // tab
        case 13: // nete
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

