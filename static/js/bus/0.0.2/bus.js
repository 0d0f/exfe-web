define('bus', function (require, exports, module) {

  /**
   *
   * 全局通信总线
   *
   * 模块通信的 BUS
   *
   * Usage:
   *          a.js
   *            var Bus = require('bus');
   *            Bus.on('click', function () { ... });
   *          b.js
   *            var Bus = require('bus');
   *            Bus.emit('click', data);
   *
   */

  var Emitter = require('emitter');

  return new Emitter();

});
