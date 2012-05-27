define(function (require, exports, module) {
  // TODO: 临时解决 Router 问题
  if (! /^\/profile\..*/.test(window.location.pathname)) return;

  var $ = require('jquery');
  var Store = require('store');
  var Handlebars = require('handlebars');
  var R = require('rex');
  var Util = require('util');
  var Bus = require('bus');

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

    Handlebars.registerHelper('atName', function (provder, external_id) {
      var s = '';
      if (provder === 'twitter') s = '@' + external_id;
      else s = external_id;
      return s;
    });

    var jst_identity_list = $('#jst-identity-list');
    var s = Handlebars.compile(jst_identity_list.html());
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

            var cates = 'upcoming<Upcoming> anytime<Anytime> sevendays<Next 7 days> later<Later> past<Past>';
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
    var qdate = Store.get('qdate') || '';
    qdate && (qdate = '&date=' + qdate);

    return $.ajax({
      url: Util.apiUrl + '/users/' + user_id + '/crosses?token=' + token + qdate,
      type: 'GET',
      dataType: 'JSON',
      xhrFields: { withCredentials: true}
    })
      .done(function (data) {
        if (data.meta.code === 200) {

          var _date = new Date();
          Store.set('qdate', _date.getFullYear() + '-' + _date.getMonth() + '-' + _date.getDate());
          var crosses = data.response.crosses;
          var invitations = [];
          var updates = [];
          var updated;
          var conversations = [];
          var identities_KV = {};
          R.each(crosses, function (v, i) {

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
            //}

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
              if (updated.conversation && updated.conversation.length) {

              }
            }


          });

          Handlebars.registerHelper('crossItem', function (prop) {
            return crosses[this.__crossIndex][prop];
          });

          if (invitations.length) {
            var jst_invitations = $('#jst-invitations');
            var s = Handlebars.compile(jst_invitations.html());
            var h = s({crosses: invitations});
            $('#profile .gr-b').append(h);
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

    // 添加身份
    $BODY.on('click.profile.identity', '.xbtn-addidentity', function (e) {
    });

    $BODY.on('click.profile', '#profile div.cross-type', function (e) {
      e.preventDefault();
      $(this).next().toggleClass('hide');
      $(this).find('span.arrow').toggleClass('lt rb');
    });

  });

});
