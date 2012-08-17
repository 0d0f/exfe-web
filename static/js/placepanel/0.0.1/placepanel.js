/**
 * Place Panel
 */
define('placepanel', function (require, exports, module) {

  var $ = require('jquery');
  var Widget = require('widget');

  var body = document.body;

  // template
  var tpl = ''
    + '<div class="placepanel">'
      + '<div class="textfield">'
        + '<i class="icon24-place"></i>'
        + '<textarea></textarea>'
      + '</div>'
      //<div class="map"></div>
    + '</div>';

  var PlacePanel = Widget.extend({

    options: {

      template: tpl

    },

    init: function () {
      this.element.appendTo(body);
    },

    offSrcNode: function () {
      if (this.options.srcNode) {
        this.options.srcNode.data('placepanel', null);
      }
    },

    hide: function (e) {
      var $e = this.element;
      this.offSrcNode();
      this.destory();
      $e.remove();
    }

  });

  return PlacePanel;
});
