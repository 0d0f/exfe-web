/**
 * Exfe's MatePanel Widget.
 * 日期控件
 */
define('mappanel', function (require, exports, module) {

  var $ = require('jquery');
  var R = require('rex');
  var Config = require('config');

  var Panel = require('panel');

  var SPLITTER = /[\r\n]+/g;

  var CR = '\r';

  var geolocation = window.navigator.geolocation;

  var MapPanel = Panel.extend({

      options: {

          template: ''
          + '<div class="panel map-panel" tabindex="-1" data-widget="panel" id="map-panel">'
            //<div class="panel-header"></div>
            + '<div class="panel-body">'
              + '<div class="map-container">'
                + '<div class="map-box" id="gmap"></div>'
                + '<div class="map-place">'
                  + '<div class="place-editor">'
                    + '<i class="pointer icon24-enter place-submit"></i>'
                    + '<textarea class="normal" name="place-text" id="place-text" placeholder="Enter place here."></textarea>'
                  + '</div>'
                  + '<div class="map-places hide">'
                    + '<ul class="unstyled places-list" tabindex="-1"></ul>'
                  + '</div>'
                + '</div>'
              + '</div>'
            + '</div>'
            //<div class="panel-footer"></div>
          + '</div>'

        , parentNode: null

        , srcNode: null

        // place object
        , place: null

      }

    , isGeoSupported: !!geolocation

    , getGeoPosition: function (position) {
        this.emit('set-geo', position);
      }

    , getGeoPositionError: function (msg) {
        this.emit('set-geo', { coords: Config.location });
      }

    , getGeo: function (position) {
        this.position = position;
        this.xmap.initMap();
      }

    , init: function () {
        var options = this.options;

        this.render();

        // save origin place data
        this.originPlace = options.place;
        this.place = $.extend({}, options.place);
        delete options.place;

        this.placeInput = new PlaceInput(this, '#place-text');
        this.placesList = new PlacesList(this, '.places-list');
        this.xmap = new XMap(this, '#gmap');
        this.listen();
      }

    , listen: function () {
        var self = this
          , place = this.place;

        this.on('update-place', this.update);

        this.on('change-place', this.change);

        this.on('set-geo', this.getGeo);

        this.on('search-completed', this.searchCompleted);

        this.on('placeinput-tab', this.placeInputTab);
        this.on('placeslist-tab', this.placesListTab);

        this.element.on('click.mappanel', '.place-submit', function (e) {
          // NOTE: 先用老事件触发保存
          $('body').click();
        });

        this.element.on('keydown.mappanel', $.proxy(this.keydown, this));
      }

    , save: function () {
        this.$('.place-submit')
          .trigger('click.mappanel');
      }

    , keydown: function (e) {
        var self = this;
        // escape
        if (27 === e.keyCode) {
          self.revert();
        }
        else if (e.ctrlKey && 13 === e.keyCode) {
          self.emit('update-place', self.place);
          self.save();
          //self.hide();
        }
      }

    , searchCompleted: function (places) {
        this.placesList.update(places);
      }

    , placeInputTab: function () {
        var placesList = this.placesList;
        var placeInput = this.placeInput;
        var s = placesList.status;
        if (s) {
          // firefox hack
          var t = setTimeout(function () {
            placeInput.$element.blur();
            placesList.$element.focus();
            clearTimeout(t);
          }, 0);
        } else {
          //this.placeInput.$element.blur();
          //this.element.focus();
        }
      }

    , placesListTab: function () {
        var self = this;
        // firefox hack
        var t = setTimeout(function () {
          self.placeInput.$element.focusend();
          clearTimeout(t);
        }, 0);
      }

    , change: function (place, searchable) {
        searchable = !!searchable
        this.place.title = place.title;
        this.place.description = place.description;
        this.place.lat = place.lat || '';
        this.place.lng = place.lng || '';
        this.emit('update-place', this.place);
        if (searchable) {
          this.xmap.textSearch(place.title);
        } else {
          this.placeInput.$element
          .val(printPlace(place.title, place.description));
          this.xmap.updateCenter(place);
        }
      }

    , revert: function (e) {
        this.emit('update-place', this.originPlace);
      }

    , showPlace: function () {
        var place = this.place
          , title = place.title
          , description = place.description
          // 只要 `title` 和 `description` 都没有就显示 `Enter place here.`
          , sc = !title && !description
          , hasLatLng = place.lat && place.lng
          , $placeText = this.$('#place-text');

        $placeText
          .val(printPlace(title, description));

        if (sc) {
          $placeText.focusend();
        }

        if (hasLatLng) {
          this.getGeoPosition({ coords: { latitude: place.lat, longitude: place.lng } });
        } else if (!hasLatLng && this.isGeoSupported) {
          geolocation
            .getCurrentPosition(
                $.proxy(this.getGeoPosition, this)
              , $.proxy(this.getGeoPositionError, this)
          );
        } else {
          this.getGeoPositionError();
        }
      }

    , showBefore: function () {
        this.element.attr('editarea', 'date-panel');
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
            });
        }
        this.showPlace();
      }

    , destory: function () {
        this.element.off();
        this.element.remove();
        this._destory();
      }

  });


  /**
   * PlaceInput
   */
  var PlaceInput = function (component, selector) {
    this.component = component
    this.$container = this.component.element;
    this.selector = selector;
    this.$element = this.component.$(selector);
    this.listen();
  };

  PlaceInput.prototype = {

      listen: function () {
        var $container = this.$container
          , selector = this.selector;
        $container
          .on('blur.mappanel', selector, $.proxy(this.blur, this))
          .on('keypress.mappanel', selector, $.proxy(this.keypress, this))
          .on('keyup.mappanel', selector, $.proxy(this.keyup, this))
          .on('keydown.mappanel', selector, $.proxy(this.keydown, this))
          .on('focus.mappanel', selector, $.proxy(this.focus, this));
      }

    , lookup: function () {
        var value = $.trim(this.$element.val())
          , place = parsePlace(value);

        this.component.emit('change-place', place, true);
      }

    , click: function (e)  {}

    , blur: function (e) {
        this.$element.addClass('normal');
      }

    , focus: function (e) {
        this.$element.removeClass('normal');
      }

    , mouseenter: function (e) {}

    , keyup: function (e) {
        switch (e.keyCode) {
          case 40: // down arrow
          case 38: // up arrow
          case 16: // shift
          case 17: // ctrl
          case 18: // alt
          case  9: // tab
          case 13: // enter
          case 27: //escape
            break;

          default:
            this.lookup();
        }
        e.stopPropagation();
        e.preventDefault();
      }

    , keyHandler: function (e) {
        var component = this.component;
        var kc = e.keyCode;
        switch (kc) {
          case 9: //tab
            // 监听 tab, 自定义事件
            component.emit('placeinput-tab');
            e.preventDefault();
            break;
        }
      }

    , keypress: function (e) {
        if (this.suppressKeyPressRepeat) {
          return;
        }
        this.keyHandler(e);
      }

    , keydown: function (e) {
        this.suppressKeyPressRepeat = !!~R.indexOf([9], e.keyCode);
        this.keyHandler(e);
      }

  };


  /**
   *
   */
  var PlacesList = function (component, selector) {
    this.template = ''
              + '<li class="place-item" data-latitude="{{lat}}" data-longitude="{{lng}}">'
                + '<div class="rank">{{i}}</div>'
                + '<address><div class="title">{{title}}</div><div class="description">{{address}}</div></address>'
              + '</li>'
    this.component = component
    this.$container = this.component.element;
    this.selector = selector;
    this.$element = this.component.$(selector);

    this.$items = null;
    this.len = 0;
    this.curr = 0;

    this.viewportRows = 12;
    this.viewportIndex = 0;
    this.scrollIndexs = [0, 11]
    this.scrollNum = 1;
    this.itemPX = 36;

    this.listen();
  };

  PlacesList.prototype = {

      listen: function () {
        var $container = this.$container
          , selector = this.selector;
        $container
          .on('blur.mappanel', selector, $.proxy(this.blur, this))
          .on('keypress.mappanel', selector, $.proxy(this.keypress, this))
          .on('keyup.mappanel', selector, $.proxy(this.keyup, this))
          .on('keydown.mappanel', selector, $.proxy(this.keydown, this))
          .on('focus.mappanel', selector, $.proxy(this.focus, this))
          .on('click.mappanel', selector + ' > li', $.proxy(this.click, this));
      }

    , update: function (places) {
        this.status = !!places.length;
        this.$element.empty();
        this.curr = 0;
        var html = '', li, template = this.template;
        if (this.status) {
          R.each(places, function (v, i) {
            li = template;
            html += li.replace('{{i}}', i + 1)
              .replace('{{title}}', v.name)
              .replace('{{address}}', v.formatted_address)
              .replace('{{lat}}', v.geometry.location.Ya)
              .replace('{{lng}}', v.geometry.location.Za);
          });

          this.$element.html(html);
        }
        this.$element.parent().toggleClass('hide', !this.status);
      }

    , blur: function () {
        this.removeCurrStyle('hover');
      }

    , focus: function (e) {
        this.$items = this.$element.find(' > li');
        this.len = this.$items.length;
        this.addCurrStyle('hover');
      }

    , addCurrStyle: function (c) {
        this.$items
          .eq(this.curr)
          .addClass(c);
      }

    , removeCurrStyle: function (c) {
        this.$items
          .eq(this.curr)
          .removeClass(c);
      }

    , setPlace: function () {
        var $li = this.$items.eq(this.curr);
        var place = {
            title: $li.find('div.title').text()
          , description: $li.find('div.description').text()
          , lat: String($li.data('latitude'))
          , lng: String($li.data('longitude'))
        };
        this.component.emit('change-place', place, false);
      }

    , scroll: function (arrow) {
        var si = this.scrollIndexs
          , l = this.viewportRows
          , len = this.len
          , n = this.scrollNum
          , h = this.itemPX
          , i = this.curr
          , row = this.viewportIndex += arrow;

        if (row === si[1] + 1 && i === len - 1) {
          this.$element.scrollTop(0);
          this.viewportIndex = 0;
        }

        else if (row === si[0] - 1 && i === 0) {
          this.$element.scrollTop((len - l) * h);
          this.viewportIndex = 11;
        }

        else if ((row === si[0] - 1 && i > si[0]) ||
            (i < len - (l - si[1]) && row === si[1] + 1)) {
          var t = this.$element.scrollTop();
          this.$element.scrollTop(t += arrow * h * n);
          this.viewportIndex = si[(arrow + 1) / 2];
        }
      }

    , prev: function () {
        this.removeCurrStyle('hover');
        if (0 === this.curr) {
          this.curr = this.len;
        }
        this.curr--;
        this.addCurrStyle('hover');
      }

    , next: function () {
        this.removeCurrStyle('hover');
        this.curr++;
        if (this.len === this.curr) {
          this.curr = 0;
        }
        this.addCurrStyle('hover');
      }

    , keyup: function (e) {
        e.stopPropagation();
        e.preventDefault();
      }

    , keyHandler: function (e) {
        var self = this
          , component = self.component;
        var kc = e.keyCode;
        switch (kc) {
          case 9: //tab
            e.preventDefault();
            // 监听 tab, 自定义事件
            component.emit('placeslist-tab');
            break;
          case 38:
            self.scroll(-1);
            self.prev();
            e.preventDefault();
            break;
          case 40:
            self.scroll(1);
            self.next();
            e.preventDefault();
            break;
          case 13:
            e.preventDefault();
            self.setPlace();
            self.component.save();
            break;
          case 32:
            e.preventDefault();
            self.setPlace();
            break;
        }
      }

    , keypress: function (e) {
        if (this.suppressKeyPressRepeat) {
          return;
        }
        this.keyHandler(e);
      }

    , keydown: function (e) {
        this.suppressKeyPressRepeat = !!~R.indexOf([9, 38, 40, 32, 13], e.keyCode);
        this.keyHandler(e);
      }

    , click: function (e) {
        this.curr = $(e.currentTarget).index();
        this.setPlace();
        this.component.save();
      }
  };


  /**
   * X Map.
   */
  var XMap = function (component, selector) {
    this.component = component;
    this.selector = selector;
    this.$element = this.component.$(selector);
    this.cbid = 0;
  };

  XMap.prototype = {

      initMap: function () {
        this.position = this.component.position;
        var coords = this.position.coords;

        this._center = new google.maps.LatLng(coords.latitude, coords.longitude);

        this._request = {
            radius: 50000
          //, location: ''
          , location: this._center
        };

        this._map = new google.maps.Map(this.$element[0]
          , {
              zoom: 16
            , center: this._center
            , MapTypeId: google.maps.MapTypeId.ROADMAP
            , zoomControl: false
            , mapTypeControl: false
          }
        );

        this._marker = new google.maps.Marker({
            position: this._center
          , map: this._map
          , title: coords.title || ''
        });

        this._service = new google.maps.places.PlacesService(this._map);
      }

    , updateCenter: function (place) {
        this._center = new google.maps.LatLng(place.lat, place.lng);
        this._map.setCenter(this._center);
        this._marker.setPosition(this._center);
      }

    , textSearch: function (query) {
        var self = this
          , component = self.component
          , service = self._service
          , request = self._request
          , cb;
        if (query && query !== request.query) {
          request.query = query;
          cb = function (results, status) {
            if (cb.id === self.cbid && status === google.maps.places.PlacesServiceStatus.OK) {
              self.cbid = 0;
              component.emit('search-completed', results);
            }
          };
          // 避免多异步回调问题
          cb.id = ++self.cbid;
          service.textSearch(request, cb);
        } else {
          component.emit('search-completed', []);
        }
      }

  };


  // Helpers:
  var parsePlace = function (placeString) {
    placeString || (placeString = '');
    var ps = placeString.split(SPLITTER)
      , title = ps.length ? ps.shift() : ''
      , description = ps.join(CR);

    return {
        title: title
      , description: description
    };
  };

  var printPlace = function (title, description) {
    return title + (description ? CR + description.replace(SPLITTER, CR) : '');
  };

  return MapPanel;
});
