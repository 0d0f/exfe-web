define('user', function (require, exports, module) {
  var $ = require('jquery');
  var R = require('rex');
  var Api = require('api');
  var Bus = require('bus');
  var Util = require('util');
  var Store = require('store');
  var Handlebars = require('handlebars');


  // --------------------------------------------------------------------------
  // User Sign In
  Bus.on('app:user:signin', signIn);


  // `status` 可以跳转到 profile
  function signIn(token, user_id, redirect) {
    getUser(token, user_id
      , function (data) {
          var last_external_username = Store.get('last_external_username')
            , identity
            , user = data.user;

          if (last_external_username) {
            identity = R.filter(user.identities, function (v) {
              var external_username = Util.printExtUserName(v);

              if (last_external_username === external_username) {
                return true;
              }
            })[0];
          }

          if (!identity) {
            identity = user.default_identity;
            Store.set('last_external_username', Util.printExtUserName(identity));
          }

          Store.set('authorization', { token: token, user_id: user_id });
          Store.set('user', user);
          Store.set('lastIdentity', identity);

          // 刷新登录列表
          refreshIdentities(user.identities);

          // cleanup `browsing identity` DOM
          var $browsing = $('#app-browsing-identity');
          if ($browsing.size() && $browsing.attr('data-page') === 'profile') {
            $browsing.remove();
            window.location.href = '/';
            return;
          }

          var hash = decodeURIComponent(window.location.hash);
          if (redirect || (('' === hash
                           || /^#?(invalid)?/.test(hash))
                        && !/^#gather/.test(hash)
                        && !/^#!/.test(hash)
                          )
            ) {
            setTimeout(function () {
              window.location.href = '/#' + Util.printExtUserName(user.default_identity);
            }, 13);
            return;
          }

          Bus.emit('app:page:usermenu', true);

          Bus.emit('app:usermenu:updatenormal', user);

          Bus.emit('app:usermenu:crosslist'
            , token
            , user_id
          );

          Bus.emit('app:user:signin:after', user);
        }
      , function (err) {}
    );
  }


  // --------------------------------------------------------------------------
  // Get User
  Bus.on('app:api:getuser', getUser);


  function getUser(token, user_id, done, fail) {
    // return `Deferred Object`
    Api.request('getUser',
      {
        params: { token: token },
        resources: { user_id: user_id }
      }
      , done || function (data) {}
      , fail || function (err) {}
    );
  }


  // --------------------------------------------------------------------------
  // Get Cross List, User-Menu
  Bus.on('app:usermenu:crosslist', getCrossList);


  function getCrossList(token, user_id, done, fail) {
    Api.request('crosslist',
      {
        params: { token: token },
        resources: { user_id: user_id }
      }
      , done || function (data) {
          var crosses = data.crosses
            , len = crosses.length;

          if (0 === len) { return; }

            // 显示最近5个已确认的 `x`
          var limit = 5
            , list;

          R.map(crosses, function (v, i) {
            if (v.exfee
                && v.exfee.invitations
                && v.exfee.invitations.length) {
              var t = R.filter(v.exfee.invitations, function (v2, j) {
                if ('ACCEPTED' === v2.rsvp_status
                    && user_id === v2.identity.connected_user_id) {
                  return true;
                }
              });

              if (t.length) {
                // lazy
                !list && (list = []);
                list.push(v);
              }
            }
          });

          if (!list) { return; }

          list = list.slice(0, limit);

          if (0 === list.length) { return; }

          var now = +new Date()
            , threeDays = now + 3 * 24 * 60 * 60 * 1000
            , n3 = now + 3 * 60 * 60 * 1000
            , n24 = now + 24 * 60 * 60 * 1000;


          list = R.filter(list, function (v, i) {
            var s = ''
              , beginAt = v.time.begin_at
              , dt = new Date(beginAt.date.replace(/\-/g, '/') + ' ' + beginAt.time).getTime();

              if (now <= dt && dt <= threeDays) {
                return true;
              }
          });

          if (0 === list.length) { return; }

          list.reverse();

          Handlebars.registerHelper('upcoming', function () {
            var s = '<li>'
              , beginAt = this.time.begin_at
              , dt = new Date(beginAt.date.replace(/\-/g, '/') + ' ' + beginAt.time).getTime();

            if (now <= dt && dt < n3) {
              s = '<li class="tag"><i class="icon10-now"></i>';
            } else if (n3 <= dt && dt < n24) {
              s = '<li class="tag"><i class="icon10-24hr"></i>';
            }

            s += '<a data-link data-id="'
                  + this.id +
                '" href="/#!'
                  + this.id +
                '">' + this.title
                  + '</a></li>'

            return new Handlebars.SafeString(s);
          });

          var tpl = '<div>Upcoming:</div>'
                + '<ul class="unstyled crosses">'
                + '{{#each this}}'
                + '{{upcoming}}'
                + '{{/each}}'
                + '</ul>'

          var html = Handlebars.compile(tpl)(list);
          $('#app-user-menu .user-panel .body').append(html);
        }
      , fail || function (err) {}
    );
  }


  // --------------------------------------------------------------------------
  // update user menu
  Bus.on('app:usermenu:updatenormal', updateNormalUserMenu);
  Bus.on('app:usermenu:updatebrowsing', updateBrowsingUserMenu);


  // Update User-Menu
  var userMenuTpls = {
      normal: ''
        + '<div class="dropdown-menu user-panel">'
          + '<div class="header">'
            + '<div class="meta">'
              + '<a class="pull-right avatar" href="{{profileLink}}" data-link>'
                + '<img width="40" height="40" alt="" src="{{avatar_filename}}" />'
              + '</a>'
              + '<a class="attended" href="{{profileLink}}" data-link>'
                + '<span class="attended-nums">{{cross_quantity}}</span>'
                + '<span class="attended-x"><em class="x">·X·</em> attended</span>'
              + '</a>'
            + '</div>'
          + '</div>'
          + '<div class="body">'
            + '{{#unless password}}'
            + '<div class="merge set-up" data-source="{{default_identity.external_username}}" data-widget="dialog" data-dialog-type="setpassword">'
              + '<a href="#">Set Up</a> your <span class="x-sign">EXFE</span> password'
            + '</div>'
            + '{{/unless}}'
            + '{{#if verifying}}'
            + '<div class="merge verify" data-dialog-type="verification_{{default_identity.provider}}" data-widget="dialog" data-identity-id="{{default_identity.id}}">'
              + '<strong>Verify</strong> your identity'
            + '</div>'
            + '{{/if}}'
            + '<div class="list"></div>'
          + '</div>'
          + '<div class="footer">'
            + '<a href="/#gather" class="xbtn xbtn-gather" id="js-gatherax" data-link>Gather a <span class="x">·X·</span></a>'
            + '<div class="spliterline"></div>'
            + '<div class="actions">'
              + '<a href="#" class="pull-right" id="app-signout">Sign out</a>'
              //+ '<a href="#">Settings</a>'
            + '</div>'
          + '</div>'
        + '</div>'

    , browsing_identity: ''
        + '<div class="dropdown-menu user-panel">'
          + '<div class="header">'
            + '<h2>Browsing Identity</h2>'
          + '</div>'
          + '<div class="body">'
            + '{{#with browsing}}'
            + '<div>You are browsing this page as {{capitalize default_identity.provider}} identity:</div>'
            + '<div class="identity">'
              + '<span class="pull-right avatar alt40">'
                + '<img src="{{default_identity.avatar_filename}}" width="20" height="20" alt="" />'
              + '</span>'
              + '<i class="icon16-identity-{{default_identity.provider}}"></i>'
              + '<span class="oblique">{{default_identity.external_username}}</span>'
            + '</div>'
            + '{{#if ../setup}}'
            //+ '<div class="merge set-up" data-source="{{default_identity.external_username}}" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d02">'
            + '<div class="merge set-up" data-user-action="setup" data-widget="dialog" data-dialog-type="setup_{{default_identity.provider}}">'
              + '<a href="#">Set Up</a> new <span class="x-sign">EXFE</span> account with the browsing identity.'
            + '</div>'
            + '{{/if}}'
            + '{{/with}}'
            //+ '{{#if normal}}'
            //+ '<div class="spliterline"></div>'
            //+ '<div class="merge">'
              //+ '<a href="#">Merge</a> with existing identities in your current account:'
            //+ '</div>'
              //+ '{{#each normal.identities}}'
              //+ '{{#ifConnected status}}'
              //+ '<div class="identity">'
                //+ '<span class="pull-right avatar alt40">'
                  //+ '<img src="{{avatar_filename}}" width="20" height="20" alt="" />'
                //+ '</span>'
                //+ '<i class="icon16-identity-{{provider}}"></i>'
                //+ '<span>{{external_username}}</span>'
              //+ '</div>'
              //+ '{{/ifConnected}}'
              //+ '{{/each}}'
            //+ '{{/if}}'
            + '{{#unless setup}}'
            + '<div class="orspliter hide">or</div>'
            + '<div class="merge" data-user-action="signin" data-source="{{browsing.default_identity.external_username}}" data-widget="dialog" data-dialog-type="identification" data-dialog-tab="d00">'
              + '<a href="#">Sign In</a> with browsing identity<br />'
              + '{{#if normal}}'
                + '(sign out from current account)'
              + '{{/if}}'
            + '</div>'
            + '{{/unless}}'
          + '</div>'
          + '<div class="footer">'
          + '</div>'
        + '</div>'
  };

  /**
   *
   * @param: type 'normal' / 'browsing_identity'
   */

  // 添加 `ifVerifying` 判断
  Handlebars.registerHelper('ifConnected', function (status, options) {
    return Handlebars.helpers['if'].call(this, 'CONNECTED' === status, options);
  });

  function updateNormalUserMenu(user) {
    var $appUserMenu = $('#app-user-menu')
      , $appUserName = $('#app-user-name')
      , $nameSpan = $appUserName.find('span')
      , $dropdownWrapper = $appUserMenu.find('.dropdown-wrapper')
      , $userPanel = $dropdownWrapper.find('.user-panel')
      , tplFun
      , profileLink = '/#' + Util.printExtUserName(user.default_identity);

    $('#app-browsing-identity').remove();

    $appUserName.attr('href', profileLink);

    $nameSpan
      .text(user.name || user.nickname)
      .removeClass('browsing-identity');

    tplFun = Handlebars.compile(userMenuTpls.normal);

    $userPanel.size() && $userPanel.remove();

    user.profileLink = profileLink;

    // 新身份未验证时，显示提示信息
    user.verifying = 'VERIFYING' === user.default_identity.status;

    $dropdownWrapper.append(tplFun(user));

    delete user.profileLink;
    delete user.verifying;
  }

  // `sign in` or `set up` 只显示其一
  function updateBrowsingUserMenu(data) {
    var $appUserMenu = $('#app-user-menu')
      , $appUserName = $('#app-user-name')
      , $nameSpan = $appUserName.find('span')
      , $dropdownWrapper = $appUserMenu.find('.dropdown-wrapper')
      , $userPanel = $dropdownWrapper.find('.user-panel')
      , browsing_user = data.browsing
      , tplFun;

    data.browsing.isBrowsing = true;

    $('#app-browsing-identity').remove();
    $(document.body).append(
      $('<div id="app-browsing-identity">')
        .data('settings', data)
        .attr('data-widget', 'dialog')
        .attr('data-dialog-type', 'browsing_identity')
        .attr('data-token-type', data.tokenType)
        .attr('data-token', data.originToken)
        .attr('data-page', data.page)
        .attr('data-action', data.action)
        .attr('data-read-only', data.readOnly)
    );


    $appUserName.attr('href', location.href);

    $nameSpan
      .text(browsing_user.name || browsing_user.nickname)
      .addClass('browsing-identity');

    tplFun = Handlebars.compile(userMenuTpls.browsing_identity);

    $userPanel.size() && $userPanel.remove();

    $dropdownWrapper.append(tplFun(data));

    $('#app-user-menu')
      .find('.set-up')
      .data('source', {
        browsing_user: browsing_user,
        identity: browsing_user.default_identity,
        originToken: data.originToken,
        tokenType: data.tokenType,
        user_name: data.user_name,
        forward: data.forward,
        page: data.page
      }
    );
  }

  Bus.on('app:page:home', switchPage);
  function switchPage(isHome) {
    isHome = !!isHome;
    var $BODY = $(document.body)
      , $appMenubar = $('#app-menubar')
      , $appMain = $('#app-main');

    $appMain.empty();

    $BODY.toggleClass('hbg', isHome);
    $appMenubar.toggleClass('hide', isHome);
  }

  Bus.on('app:page:usermenu', switchUserMenu);
  function switchUserMenu(signed) {
    signed = !!signed;

    var $appUserMenu = $('#app-user-menu')
      , $appSignin = $('#app-signin');

    $appUserMenu.toggleClass('hide', !signed);
    $appSignin.toggleClass('hide', signed);
  }


  function refreshIdentities(identities) {
    var _identities = Store.get('identities') || []
      // clone identities
      , cidentities = _identities.slice(0);

    if (0 === identities.length) {
      _identities = identities;
    }
    else {
      R.each(identities, function (v) {
        var has = R.find(cidentities, function (v2) {
          if (v2.id === v.id) {
            return true;
          }
        });

        if (!has) {
          _identities.push(v);
        }
      });
    }

    // 身份搜索
    Store.set('identities', _identities);
  }

});
