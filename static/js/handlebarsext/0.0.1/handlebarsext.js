/**
 *
 * A collection of helpers for Handlebars.js
 */
define(function (require) {
  var R = require('rex');
  var Handlebars = require('handlebars');

  // String
  // -----------------------------

  // Return a copy of the string with its first character capitalized and the rest lowercased.
  Handlebars.registerHelper('capitalize', function (str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
  });


  // Time Compare
  // -----------------------------


  Handlebars.registerHelper('printIdentityNameFromInvitations', function (identity_id, invitations) {
    var invitation = getInvitationById(identity_id, invitations)
      , s = '';
    if (invitation) {
      s = invitation.identity.name;
    }
    return s;
  });


  // helpers
  // -----------------------------

  // Exfee invitations Search
  function getInvitationById(id, invitations) {
    return R.find(invitations, function (v) {
      if (v.identity.id === id) {
        return true;
      }
    });
  }
});
