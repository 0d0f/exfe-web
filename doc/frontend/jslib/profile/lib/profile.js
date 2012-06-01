define(function (require, exports, module) {
  // TODO: 临时解决 Router 问题
  if (! /^\/profile(_iframe)?\..*/.test(window.location.pathname)) return;

  var $ = require('jquery');
  var Store = require('store');
  var Handlebars = require('handlebars');
  var R = require('rex');
  var Util = require('util');
  var Bus = require('bus');
  var Moment = require('moment');

  // 覆盖默认 `each` 添加 `__index__` 属性
  Handlebars.registerHelper('each', function (context, options) {
    var fn = options.fn
      , inverse = options.inverse
      , ret = ''
      , i, l;

    if(context && context.length) {
      for(i = 0, l = context.length; i < l; ++i) {
        context[i].__index__ = i;
        ret = ret + fn(context[i]);
      }
    } else {
      ret = inverse(this);
    }
    return ret;
  });

  // 添加 `ifFalse` 判断
  Handlebars.registerHelper('ifFalse', function (context, options) {
    return Handlebars.helpers['if'].call(this, !context, options);
  });

  // 用户信息,包括多身份信息
  var identities_defe = function (data) {
    if (!data) return;
    var user = data.user;
    Store.set('user', data.user);

    $('.user-xstats .attended').html(user.cross_quantity);
    $('#user-name > span').html(user.name);

    var jst_user = $('#jst-user-avatar');
    var s = Handlebars.compile(jst_user.html());
    var h = s({avatar_filename: user.avatar_filename});
    $('.user-avatar').append(h);

    $('.user-name').find('h3').html(user.name);

    Handlebars.registerHelper('avatarFilename', function (context) {
      var s = context;
      if (context === 'default.png') s = '/img/default_portraituserface_20.png';
      return s;
    });

    Handlebars.registerHelper('printName', function (name, external_id) {
      if (!name) {
        name = external_id.match(/([^@]+)@[^@]+/)[1];
      }
      return name;
    });

    Handlebars.registerHelper('ifOauthVerifying', function (provider, status, options) {
      var context = provider === 'twitter' && status === 'VERIFYING';
      return Handlebars.helpers['if'].call(this, context, options, options);
    });

    Handlebars.registerHelper('ifVerifying', function (provider, status, options) {
      var context = provider === 'email' && status === 'VERIFYING';
      return Handlebars.helpers['if'].call(this, context, options);
    });

    Handlebars.registerHelper('atName', function (provder, external_id) {
      var s = '';
      if (provder === 'twitter') s = '@' + external_id;
      else s = external_id;
      return s;
    });

    Handlebars.registerHelper('editable', function (provder, options) {
      var context = provder === 'email';
      return Handlebars.helpers['if'].call(this, context, options);
    });

    var jst_identity_list = $('#jst-identity-list');
    var s = Handlebars.compile(jst_identity_list.html());
    var default_identity = user.default_identity;
    R.each(user.identities, function (e, i) {
      if (e.id === default_identity.id) {
        e.__default__ = true;
      }
    });

    var h = s({identities: user.identities});
    $('.identity-list').append(h);
  };

  // crossList 信息
  var crossList_defe = function (data) {
    if (!data) return;
    data = Store.get('signin');
    if (!data) return;
    var user_id = data.user_id;
    var token = data.token;

    // 返回一个 promise 对象
    return $.ajax({
      url: Util.apiUrl + '/users/' + user_id + '/crosslist?token=' + token,
      type: 'GET',
      dataType: 'JSON',
      xhrFields: { withCredentials: true}
    })
      .done(function (data) {
        if (data.meta.code === 200) {
          var now = +new Date;


          if (data.response.crosses.length) {

            var jst_crosses = $('#jst-crosses-container');

            // 注册帮助函数, 获取 `ACCEPTED` 人数
            Handlebars.registerHelper('confirmed_nums', function (context) {
              return R.filter(context, function (v) {
                if (v.rsvp_status === 'ACCEPTED') return true;
              }).length;
            });

            // 注册帮助函数, 获取总人数
            Handlebars.registerHelper('total', function (context) {
              return context.length;
            });

            // 注册帮助函数，列出 confirmed identities
            Handlebars.registerHelper('confirmed_identities', function (context) {
              return R(context).map(function (v) {
                return v.identity.name;
              }).join(', ');
            });

            // 注册子模版
            Handlebars.registerPartial('jst-cross-box', $('#jst-cross-box').html())
            // 编译模版
            var s = Handlebars.compile(jst_crosses.html());
            // 填充数据
            //var h = s(data.response);
            var h = '';

            var cates = 'upcoming<Upcoming> sometime<Sometime> sevendays<Next 7 days> later<Later> past<Past>';
            var crossList = {};

            R.map(data.response.crosses, function (v, i) {
              crossList[v.sort] || (crossList[v.sort] = {crosses: []});
              crossList[v.sort].crosses.push(v);
            });

            var splitterReg = /<|>/;
            R.map(cates.split(' '), function (v, i) {
              v = v.split(splitterReg)
              var c = crossList[v[0]];
              if (c) {
                c.cate_date = v[1];
                h += s(c);
              }
            });

            $('#profile .crosses').append(h);
          }
        }
    });

  };

  var crosses_defe = function (data) {
    if (!data) return;
    data = Store.get('signin');
    if (!data) return;
    var user_id = data.user_id;
    var token = data.token;
    //var qdate = Store.get('qdate') || '';
    //qdate && (qdate = '&date=' + qdate);
    var now = new Date();
    now.setDate(now.getDate() - 3);
    now = now.getFullYear() + '-' + (now.getMonth() + 1) + '-' + now.getDate();

    return $.ajax({
      url: Util.apiUrl + '/users/' + user_id + '/crosses?token=' + token + '&date=' + now,
      type: 'GET',
      dataType: 'JSON',
      xhrFields: { withCredentials: true}
    })
      .done(function (data) {
        if (data.meta.code === 200) {

          var _date = new Date();
          //Store.set('qdate', _date.getFullYear() + '-' + _date.getMonth() + '-' + _date.getDate());
          var crosses = data.response.crosses;
          var invitations = [];
          var updates = [];
          var updated;
          var conversations = [];
          var identities_KV = {};
          var updatesAjax = [];
          R.each(crosses, function (v, i) {

            // NOTE: 测试数据
            //v.exfee = {"invitations":[{"identity":{"name":"cfddream","nickname":"","bio":"","provider":"email","connected_user_id":173,"external_id":"cfd@demox.io","external_username":"cfd@demox.io","avatar_filename":"http://www.gravatar.com/avatar/012fc0e42d6f94cb035f3842b026139c?d=http%3A%2F%2Fimg.localexfe.me%2Ff%2F3c%2Ff3c81951998320d5825e28295bd66e9c.png","avatar_updated_at":"0000-00-00 00:00:00","created_at":"2012-05-28 22:57:35","updated_at":null,"id":1,"type":"identity"},"by_identity":{"name":"cfddream","nickname":"","bio":"","provider":"email","connected_user_id":173,"external_id":"cfd@demox.io","external_username":"cfd@demox.io","avatar_filename":"http://www.gravatar.com/avatar/012fc0e42d6f94cb035f3842b026139c?d=http%3A%2F%2Fimg.localexfe.me%2Ff%2F3c%2Ff3c81951998320d5825e28295bd66e9c.png","avatar_updated_at":"0000-00-00 00:00:00","created_at":"2012-05-28 22:57:35","updated_at":null,"id":1,"type":"identity"},"rsvp_status":"NORESPONSE","via":"","created_at":"2012-05-29 16:03:26","updated_at":"2012-05-29 16:03:26","token":"","host":true,"with":0,"id":1,"type":"invitation"},{"identity":{"name":"c1","nickname":"","bio":"","provider":"email","connected_user_id":174,"external_id":"c1@demox.io","external_username":"c1@demox.io","avatar_filename":"http://www.gravatar.com/avatar/88a3f56d19c7e1a9bb62c15e2247b463?d=http%3A%2F%2Fimg.localexfe.me%2F7%2Fd2%2F7d271f607ba4f8219fd3315daf7b5708.png","avatar_updated_at":"0000-00-00 00:00:00","created_at":"2012-05-28 23:18:52","updated_at":null,"id":2,"type":"identity"},"by_identity":{"name":"cfddream","nickname":"","bio":"","provider":"email","connected_user_id":173,"external_id":"cfd@demox.io","external_username":"cfd@demox.io","avatar_filename":"http://www.gravatar.com/avatar/012fc0e42d6f94cb035f3842b026139c?d=http%3A%2F%2Fimg.localexfe.me%2Ff%2F3c%2Ff3c81951998320d5825e28295bd66e9c.png","avatar_updated_at":"0000-00-00 00:00:00","created_at":"2012-05-28 22:57:35","updated_at":null,"id":1,"type":"identity"},"rsvp_status":"NORESPONSE","via":"","created_at":"2012-05-29 16:03:26","updated_at":"2012-05-29 16:03:26","token":"","host":false,"with":0,"id":2,"type":"invitation"}],"id":5,"type":"exfee","updated_at":"2012-05-29 17:29:05"};

            // invitations
            //if (user_id !== v.by_identity.connected_user_id) {
              if (v.exfee && v.exfee.invitations && v.exfee.invitations.length) {

                R.each(v.exfee.invitations, function (e, j) {
                  identities_KV[e.identity.id] = [i,j];
                  if (user_id == e.identity.connected_user_id && e.rsvp_status === 'NORESPONSE') {
                    e.__crossIndex = i;
                    invitations.push(e);
                  }
                });

              }
            //

            // NOTE: 测试数据
            //v.updated = {
            //  conversation: {identity_id: 1}
            //};

            // updates
            if ((updated = v.updated)) {

              // exfee
              if (updated.exfee && updated.exfee.length) {
                $.each(updated.exfee, function (e, j) {
                  e.__crossIndex = i;
                  updated.push(e);
                });
              }

              // conversation
              if (updated.conversation) {
                var a = identities_KV[updated.conversation.identity_id];
                var exfee_id = crosses[a[0]].exfee.id;
                updatesAjax.push(
                $.ajax({
                  url: Util.apiUrl + '/conversation/' + exfee_id + '?token=' + token + '&date=' + updated.conversation.updated_at,
                  type: 'GET',
                  dataType: 'JSON',
                  xhrFields: { withCredentials: true}
                })
                  .done(function (data) {
                    if (data.meta.code === 200) {
                      var conversation = data.response.conversation;
                      if (conversation && conversation.length) {
                        conversation[0].__crossIndex = a[0];
                        conversation[0].__conversation_nums = conversation.length;
                        updates.push(conversation[0]);
                      }
                    }
                  })
                );
              }
            }


          });

          Handlebars.registerHelper('crossItem', function (prop) {
            return crosses[this.__crossIndex][prop];
          });

          Handlebars.registerHelper('conversation_nums', function () {
            return this.__conversation_nums;
          });

          Handlebars.registerHelper('humanTime', function (t) {
            return Moment().from(t);
          });

          if (invitations.length) {
            var jst_invitations = $('#jst-invitations');
            var s = Handlebars.compile(jst_invitations.html());
            var h = s({crosses: invitations});
            $('#profile .gr-b').append(h);
          }

          if (updatesAjax.length) {
            var dw = $.when;
            dw = dw.apply(null, updatesAjax);
            dw.then(function (data) {
              var uh = $('#jst-updates').html();
              var s = Handlebars.compile(uh);
              var h = s({updates: updates});
              $('#profile .gr-b').append(h);
            });
          }

        }
      });
  };

  // Defe Queue
  // 可以登陆状态
  var SIGN_IN_OTHERS = 'app:signinothers';
  Bus.on(SIGN_IN_OTHERS, function (d) {
    d.then([crossList_defe, crosses_defe]);
  });
  var SIGN_IN_SUCCESS = 'app:signinsuccess';
  Bus.on(SIGN_IN_SUCCESS, function (data) {
    identities_defe(data);
  });

  var $BODY = $(document.body);
  $(function () {

    // 暂时使用jQuery 简单实现功能
    // 编辑 user/identity name etc.
    $BODY.on('dblclick.profile', '.user-name h3', function (e) {
      var value = $.trim($(this).html());
      var $input = $('<input type="text" value="' + value + '" class="pull-left" />');
      $input.data('oldValue', value);
      $(this).after($input).hide();
      $input.lastfocus();
      $('.xbtn-changepassword').addClass('hide');
    });

    $BODY.on('focusout.profile keydown.profile', '.user-name input', function (e) {
        var t = e.type, kc = e.keyCode;
        if (t === 'focusout' || (kc === 9 || (!e.shiftKey && kc === 13))) {
          var value = $.trim($(this).val());
          var oldValue = $(this).data('oldValue');
          $(this).hide().prev().html(value).show();
          $(this).remove();
          !$('.settings-panel').data('hoverout') && $('.xbtn-changepassword').removeClass('hide');

          if (!value || value === oldValue) return;

          var signin = Store.get('signin');
          var token = signin.token;

          $.ajax({
            url: Util.apiUrl + '/users/update?token=' + token,
            type: 'POST',
            dataType: 'JSON',
            data: {
              name: value
            }
          })
            .done(function (data) {
              if (data.meta.code === 200) {
                Store.set('user', data.response.user);
                Bus.emit('app:changename', value);
              }
            });
        }
    });

    $BODY.on('dblclick.profile', '.identity-list li.editable .username > em', function (e) {
      var value = $.trim($(this).html());
      var $input = $('<input type="text" value="' + value + '" class="username-input" />');
      $input.data('oldValue', value);
      $(this).after($input).hide();
      $input.lastfocus();
    });

    $BODY.on('focusout.profile keydown.profile', '.identity-list .username-input', function (e) {
        var t = e.type, kc = e.keyCode;
        if (t === 'focusout' || (kc === 9 || (!e.shiftKey && kc === 13))) {
          var value = $.trim($(this).val());
          var oldValue = $(this).data('oldValue');
          var identity_id = $(this).parent().parent().data('identity-id');
          $(this).hide().prev().html(value).show();
          $(this).remove();


          if (!value || value === oldValue) return;

          var signin = Store.get('signin');
          var token = signin.token;

          $.ajax({
            url: Util.apiUrl + '/identities/' + identity_id + '/update?token=' + token,
            type: 'POST',
            dataType: 'JSON',
            data: {
              name: value
            }
          })
            .done(function (data) {
              if (data.meta.code === 200) {
                var user = Store.get('user');
                for (var i = 0, l = user.identities.length; i < l; ++i) {
                  if (user.identities[i].id === identity_id) {
                    user.identities[i] = data.response.identity;
                    break;
                  }
                }

                Store.set('user', user);
              }
            });
        }
    });

    // 添加身份
    $BODY.on('click.profile.identity', '.xbtn-addidentity', function (e) {
    });

    $BODY.on('click.profile', '#profile div.cross-type', function (e) {
      e.preventDefault();
      $(this).next().toggleClass('hide');
      $(this).find('span.arrow').toggleClass('lt rb');
    });

    $BODY.on('hover.profile', '.settings-panel', function (e) {
      var t = e.type;
      $(this).data('hoverout', t === 'mouseleave');
      if (t === 'mouseenter') {
        $(this).find('.xbtn-changepassword').removeClass('hide');
        $(this).find('.xlabel').removeClass('hide');
      } else {
        $(this).find('.xbtn-changepassword').addClass('hide');
        $(this).find('.xlabel').addClass('hide');
      }
    });

  });

});
