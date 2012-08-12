define(function (require) {

  var Store = require('store');

  // Profile Page, Newbie Guide

  var newbieGuide = [
    '<div class="newbie nbg-0">Gather a <span class="x">·X·</span> here</div>',

    '<div class="newbie nbg-1">'
      + '<div class="newbie-close"><i class="icon14-clear"></i></div>'
      + '<p>Identities listed above are your representative online. Email,'
        + 'mobile #, web accounts from Twitter, <span>Facebook, Google and others</span>'
        + '(still working on these), any of these would be.</p>'
      + '<p class="toggle hide">Please set up following items for easier use of <span class="x-sign">EXFE</span>:</p>'
      + '<ul class="toggle unstyled hide">'
        + '<li>· Set account password for security.</li>'
        + '<li>· Set a portrait that your friends can recognize.</li>'
        + '<li>· Add more frequently used identities that may use for gathering.</li>'
        + '</ul>'
      + '<p class="toggle hide"><span class="x-sign">EXFE</span> is ready for your iPhone, and Android soon.</p>'
      + '<div class="pull-right arrow"><div class="rb"></div></div>'
    + '</div>',

    '<div class="newbie nbg-2">'
      + '<div class="newbie-close"><i class="icon14-clear"></i></div>'
      + '<h4>List of <span class="x">·X·</span></h4>'
      + '<p><span class="x">·X·</span> (cross) is a gathering of people.</p>'
      + '<p><span class="x">·X·</span> is private by default, everything inside is<br />'
      + 'accessible to only attendees, including your<br />'
      + 'identity details.</p>'
      + '<p>Got empty list? Invite friends for something<br />'
      + 'like meals, meetings, parties, hangouts,<br />'
      + 'datings, sports, trips, etc. For any intent you<br />'
      + 'need to gather people, <span class="x-sign">EXFE</span> them!'
      + '</p>'
      //+ '<p><span class="gatherax"><span class="bb">Gather a </span><span class="bb x">·X·</span></span> now!</p>'
    + '</div>',

    '<div class="newbie nbg-3">'
      + '<div class="newbie-close"><i class="icon14-clear"></i></div>'
      + '<p>No invitation for you,<br /> yet.</p>'
    + '</div>'

  ];

  $(newbieGuide[0]).insertBefore($('.user-panel .xbtn-gather'));

  $(newbieGuide[1]).insertAfter($('.settings-panel'));

  $(newbieGuide[2]).appendTo($('.gr-a'));

  $(newbieGuide[3]).appendTo($('.gr-b .invitations').removeClass('hide'));

  var $BODY = $(document.body);

  $BODY.on('hover.newbie', '.newbie', function (e) {
    var t = e.type, z = $(this).data('ozIndex');
    if (t === 'mouseenter') {
      !z && $(this).data('ozIndex', $(this).css('z-index'));
      $(this).css('z-index', z + 2);
    } else {
      $(this).css('z-index', z);
    }
  });

  $BODY.on('click.newbie', '.nbg-1', function (e) {
    e.preventDefault();
    var $e = $(this);
    $e.find('.arrow div').toggleClass('rb lt');
    $e.find('.toggle')
      .toggleClass('hide');
  });

  $BODY.on('click.newbie', '.newbie > .newbie-close', function (e) {
    e.preventDefault();
    var authorization = Store.get('authorization');
    if (!authorization) return;
    var user_id = authorization.user_id;
    Store.set('newbie_guide:' + user_id, 1);
    var p = $(this).parent();
    p.fadeOut(function () {

      if (p.hasClass('nbg-3')) {
        if (!p.parent().find('.cross-list').length) {
          p.parent().addClass('hide');
        }
      }

      $(this).remove();
    });

  });

  $BODY.on('click.newbie', '.newbie .gatherax', function (e) {
    e.preventDefault();
    $('.navbar .dropdown-wrapper').trigger('mouseenter.dropdown');
    $(window).scrollTop(0);
    $('.nbg-0')
      .stop(true, true)
      .addClass('bouncey')
      .delay(2100)
      .queue(function () {
        $(this).removeClass('bouncey');
      });
  });

});
