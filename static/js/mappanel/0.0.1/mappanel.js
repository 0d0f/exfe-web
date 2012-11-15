/**
 * Exfe's MatePanel Widget.
 * 日期控件
 */
define('mappanel', function (require, exports, module) {

  var Panel = require('panel');

  var SPLITTER = /[\r\n]+/g;

  var MapPanel = Panel.extend({

      options: {

          template: ''
          + '<div class="panel map-panel" tabindex="-1" data-widget="panel" id="map-panel">'
            //<div class="panel-header"></div>
            + '<div class="panel-body">'
              + '<div class="map-container">'
                + '<div class="map-box" id="gmap"></div>'
                + '<div class="map-place">'
                  + '<div class="place-content hide"></div>'
                  + '<div class="place-editor hide">'
                    + '<i class="pointer icon24-enter place-submit"></i>'
                    + '<textarea name="place-text" id="place-text" placeholder="Enter place here."></textarea>'
                  + '</div>'
                  + '<div class="map-places hide">'
                    + '<ul class="unstyled places-list"></ul>'
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

    , init: function () {
        var options = this.options;
        this.render();

        this.place = options.place;
        delete options.place;
        this.showPlace();
        this.handlers();
      }

    , handlers: function () {
        var self = this
          , place = this.place;

        this.element.on('click.mappanel', '.place-content', function (e) {
          $(this).addClass('hide');
          self.$('.place-editor')
            .removeClass('hide')
            .find('#place-text')
            .text(place.title + (place.description ? '\r' + place.description : ''))
            .focusend();
        });

        this.element.on('click.mappanel', '.place-submit', function (e) {
          // 先用老事件触发保存
          $('body').click();
        });
      }

    , showPlace: function () {
        var place = this.place
          , title = place.title
          , description = place.description
          , sc = !title && !description;

        this.$(sc ? '.place-editor' : '.place-content').removeClass('hide');

        if (!sc) {
          this.$('.place-content')
            .html(title + (description ? '</br>' + description.replace(SPLITTER, '</br>') : ''));
        } else {
          this.$('#place-text').focusend();
        }
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
      }

    , destory: function () {
        this.element.off();
        this.element.remove();
        this._destory();
      }

  });


  // Helpers:
  var parsePlace = function (placeString) {
    placeString || (placeString = '');
    var ps = placeString.split(SPLITTER)
      , title = ps.length ? ps.shift() : ''
      , description = ps.join('\r');

    return {
        title: title
      , description: description
    };
  };

  return MapPanel;
});
