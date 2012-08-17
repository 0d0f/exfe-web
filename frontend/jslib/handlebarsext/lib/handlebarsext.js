/**
 *
 * A collection of helpers for Handlebars.js
 */
define(function (require) {
  var Handlebars = require('handlebars');

  // String
  // -----------------------------

  // Return a copy of the string with its first character capitalized and the rest lowercased.
  Handlebars.registerHelper('capitalize', function (str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
  });


  // Time Compare
  // -----------------------------

});
