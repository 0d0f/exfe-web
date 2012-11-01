define(function (require, exports, module) {
  var $ = require('jquery');
  var Store = require('store');
  var Config = require('config');
  var Dialog = require('dialog');
  var Dialogs = require('xdialog').dialogs;
  var Handlebars = require('handlebars');
  var R = require('rex');
  var Util = require('util');
  var Bus = require('bus');
  var Api = require('api');
  var Moment = require('moment');

  Moment.calendar = {
    sameDay : 'ha, dddd MMMM D',
    nextDay : 'h:ssA [Tomorrow], dddd MMMM D',
    nextWeek : 'dddd [at] LT',
    lastDay : 'hA, dddd MMMM D',
    lastWeek : 'hA, dddd MMMM D',
    sameElse : 'L'
  };

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

  Handlebars.registerHelper('avatarFilename', function (context, options) {
    return context;
  });

  Handlebars.registerHelper('printName', function (name, external_username) {
    if (!name) {
      name = external_username.match(/([^@]+)@[^@]+/)[1];
    }
    return name;
  });

  // 日期输出
  Handlebars.registerHelper('printTime', function (time) {
    // 终端时区
    var c = Moment();
    var cz = c.format('Z');
    var b = time.begin_at;

    // Cross 时区
    var tz = (b.timezone && /(^[\+\-]\d\d:\d\d)/.exec(b.timezone)[1]) || '';
    // 创建一个 moment date-object
    var s = '', sf = '';
    if (b.date) {
      s += b.date;
      sf += 'YYYY-MM-DD';
    }

    if (b.time) {
      s += ' ' + b.time;
      sf += ' HH:mm:ss';
    }

    if ((b.date || b.time) && tz) {
      s += ' ' + tz;
      sf += ' ZZ'
    }

    var d = Moment(s, sf);
    //var d = Moment.utc(b.date + ' ' + b.time + ' ' + tz, 'YYYY-MM-DD HH:mm:ss Z');

    var s = '', f, tt;

    // 比对时区
    var czEtz = cz === tz;

    // 直接输出 origin 时间
    if (time.outputformat) {
      s = time.origin;
      if (!czEtz) {
        s += ' ' + tz;
      }
      return s || 'Sometime';
    } else {

      if (b.time_word) {
        s += b.time_word + ' (at) ';
      }

      if (b.time) {
        s += b.time;
      }

      if (!czEtz && tz) {
        s += ' (' + tz + ') ';
      }

      if (b.date_word) {
        s += b.date_word + ' (on) ';
      }

      if (b.date) {
        s += ' ' + b.date;
      }

      if (d && d.year() !== 1900 && d.year() !== c.year()) {
        s += ' ' + d.year();
      }
    }

    return s || (sf ? d.format(sf) : 'Sometime');
    //return d.calendar();
    //return s;
  });

  // Invitations print time
  Handlebars.registerHelper('printTime2', function (time, options) {
    time  = Handlebars.helpers['crossItem'].call(this, time, options);
    return Handlebars.helpers['printTime4'].call(this, time);
  });

  Handlebars.registerHelper('printTime5', function (time) {
    var s = Handlebars.helpers['printTime4'].call(this, time);
    return s || 'To be decided';
  });

  Handlebars.registerHelper('printPlace', function (place) {
    return place || 'To be decided';
  });

  Handlebars.registerHelper('printTime4', function (time) {
    // 终端时区
    var c = Moment();
    var cz = c.format('Z');
    var b = time.begin_at;

    var tz = (b.timezone && /(^[\+\-]\d\d:\d\d)/.exec(b.timezone)[1]) || '';
    var s = '', f = '';

    // 比对时区
    var czEtz = cz === tz;

    if (time.outputformat) {
      s = time.origin;
      if (!czEtz) {
        s += ' ' + tz;
      }
      return s;
    } else {
      // Cross 时区
      // 创建一个 moment date-object
      var s = '', sf = '';
      if (b.date) {
        s += b.date;
        sf += 'YYYY-MM-DD';
      }

      if (b.time) {
        s += ' ' + b.time;
        sf += ' HH:mm:ss';
      }

      if (tz) {
        s += ' ' + tz;
        sf += ' ZZ'
      }

      var d = Moment(s, sf);

      if (b.time) {
        f += 'hA ';
      }
      if (b.date) {
        f += 'ddd MMM D';
      }

      return f ? d.format(f) : 'Sometime';
    }
  });

  // Updates print time
  Handlebars.registerHelper('printTime3', function (time, options) {
    //time  = Handlebars.helpers['crossItem'].call(this, time, options);
    var b = time.begin_at;

    // 没有 date & 没有 date_word 显示空
    if (!b.date) {
      return b.date_word || '';
    }

    // Cross 时区
    var tz = (b.timezone && /(^[\+\-]\d\d:\d\d)/.exec(b.timezone)[1]) || '';
    // 创建一个 moment date-object
    var s = '', sf = '';
    if (b.date) {
      s += b.date;
      sf += 'YYYY-MM-DD';
    }

    if (b.time) {
      s += ' ' + b.time;
      sf += ' HH:mm:ss';
    }

    if (tz) {
      s += ' ' + tz;
      sf += ' ZZ'
    }

    var d = Moment(s, sf);
    return sf ? d.fromNow() : 'Sometime';
  });

  Handlebars.registerHelper('rsvpAction', function (identities, identity_id) {
    var rsvp = {
      'ACCEPTED': 'Accepted',
      'INTERESTED': 'Interested',
      'DECLINED': 'Unavailable',
      'NORESPONSE': 'Pending'
    };
    var i = R.filter(identities, function (v) {
      if (v.identity.id === identity_id) return true;
    })[0];
    var html = '';
    if (i && i.rsvp_status !== 'INTERESTED') {
      var html = '<div><i class="';
      html += 'icon-rsvp-' + i.rsvp_status.toLowerCase() + '"></i> ';
      html += rsvp[i.rsvp_status] + ': ' + i.identity.name + '</div>';
    }

    var is = R.map(identities, function (v) {
      if (v.by_identity.id === identity_id && v.identity.id !== identity_id) return v.identity.name;
    }).filter(function (v) {
      if (v) return true;
    }).join(', ');

    html += '<div><i class="icon-invite"></i> ';
    html += 'Invited: ' + is;
    html += '</div>';
    return is ? html : '';
  });

  Handlebars.registerHelper('ifPlace', function (options) {
    var title = Handlebars.helpers['crossItem'].call(this, 'place');
    return Handlebars.helpers['if'].call(this, title.length, options);
  });

  Handlebars.registerHelper('ifOauthVerifying', function (provider, status, options) {
    var context = provider === 'twitter' && status === 'VERIFYING';
    return Handlebars.helpers['if'].call(this, context, options, options);
  });

  Handlebars.registerHelper('makeDefault', function (def, status, options) {
    var context = !def && status === 'CONNECTED';
    return Handlebars.helpers['if'].call(this, context, options, options);
  });

  Handlebars.registerHelper('ifVerifying', function (provider, status, options) {
    var context = provider === 'email' && status === 'VERIFYING';
    return Handlebars.helpers['if'].call(this, context, options);
  });

  Handlebars.registerHelper('atName', function (provder, external_username) {
    var s = '';
    if (provder === 'twitter') s = '@' + external_username;
    else s = external_username;
    return s;
  });

  Handlebars.registerHelper('editable', function (provder, status, options) {
    var context = provder === 'email' && status === 'CONNECTED';
    return Handlebars.helpers['if'].call(this, context, options);
  });

  // 用户信息,包括多身份信息
  var identities_defe = function (user) {
    $('.user-xstats .attended').html(user.cross_quantity);

    var jst_user = $('#jst-user-avatar');

    var s = Handlebars.compile(jst_user.html());
    var h = s({avatar_filename: user.avatar_filename});

    $('.user-avatar').append(h);

    $('#profile .user-name').find('h3').html(user.name || user.nickname);

    $('#profile .user-name')
      .find('.changepassword')
      .attr('data-dialog-type', user.password ? 'changepassword' : 'setpassword')
      .find('span')
      .text( user.password ? 'Change Password...' : 'Set Password...');

    Handlebars.registerPartial('jst-identity-item', $('#jst-identity-item').html())

    var jst_identity_list = $('#jst-identity-list');
    var s = Handlebars.compile(jst_identity_list.html());
    var default_identity = user.identities[0];
    default_identity.__default__ = true;

    var total_connected = 0;
    var identities = R.filter(user.identities, function (v) {
      if (v.status === "CONNECTED") {
        ++total_connected;
      }

      if (v.provider === 'email' || v.provider === 'twitter') {
        return true;
      }
    });

    // 必须至少一个 `CONNECTED` identity 才能添加身份
    if (total_connected) {
      $('#app-profile-addidentity').removeClass('hide');
    }

    var h = s({identities: identities});
    $('.identity-list').append(h);
    if (event = $('#app-main').data('event')) {
      var action = event.action;
      if (action === 'add_identity') {
        var data = event.data;
        function addIdentity(external_username, provider, that) {
          var authorization = Store.get('authorization')
            , token = authorization.token;
          var defe = Api.request('addIdentity',
            {
              type: 'POST',
              params: { token: token },
              data: {
                external_username: external_username,
                provider: provider
              }
            },
            function (data) {
              var identity = data.identity
                , user = Store.get('user')
                , identities = user.identities;
              identities.push(identity);
              Store.set('user', user);
              var s = Handlebars.compile($('#jst-identity-item').html());
              var h = s(data.identity);
              $('.identity-list').append(h);
              that && that.destory();
            },
            function (data) {
              var meta = data && data.meta;
              if (meta
                  && meta.code === 401
                  && meta.errorType === 'authenticate_timeout') {

                that && that.destory();
                var $d = $('<div data-widget="dialog" data-dialog-type="authentication" data-destory="true" class="hide"></div>');
                $('#app-tmp').append($d);
                var e = $.Event('click.dialog.data-api');
                e._data = {callback: function () { addIdentity(external_username, provider)}};
                $d.trigger(e);
              }
            }
          );
        }
        addIdentity(data.identity.external_username, data.identity.provider);
        $('#app-main').removeData('event');
      }
    }
  };

  // crossList 信息
  var crossList_defe = function (data) {
    if (!data) return;
    var user_id = data.user_id
      , token = data.token;

    // 返回一个 promise 对象
    return Api.request('crosslist'
      , {
        params: { token: token },
        resources: { user_id: user_id }
      }
      , function (data) {
          var now = +new Date;

          var jst_crosses = $('#jst-crosses-container');

          // 注册帮助函数, 获取 `ACCEPTED` 人数
          Handlebars.registerHelper('confirmed_nums', function (context) {
            var n = 0;
            var m = R.filter(context, function (v) {
              if (v.rsvp_status === 'ACCEPTED') {
                n += v.mates;
                return true;
              }
            }).length || 0;
            return m + n;
          });

          // 注册帮助函数, 获取总人数
          Handlebars.registerHelper('total', function (context) {
            var n = 0;
            R.filter(context, function (v) {
              if (v.rsvp_status === 'ACCEPTED') {
                n += v.mates;
                return true;
              }
            });
            return context.length + n;
          });

          // 注册帮助函数，列出 confirmed identities
          Handlebars.registerHelper('confirmed_identities', function (context) {
            var d = R(context).filter(function (v) {
              if (v.rsvp_status === 'ACCEPTED') return true;
            });
            // limit 7
            return R(d.slice(0, 7)).map(function (v, i) {
              return v.identity.name;
            }).join(', ');
          });

          // 注册子模版
          Handlebars.registerPartial('jst-cross-box', $('#jst-cross-box').html())
          // 编译模版
          var s = Handlebars.compile(jst_crosses.html());
          // 填充数据
          var h = '';

          var cates = 'upcoming<Upcoming> sometime<Sometime> sevendays<Next 7 days> later<Later> past<Past>';
          var crossList = {};

          R.map(data.crosses, function (v, i) {
            crossList[v.sort] || (crossList[v.sort] = {crosses: []});
            crossList[v.sort].crosses.push(v);
          });

          if (!crossList['upcoming']) {
            crossList['upcoming'] = {};
          }

          crossList['upcoming'].hasGatherAX = true;
          crossList['upcoming'].totalCrosses = data.crosses.length;

          var more = data.more.join(' ');

          var splitterReg = /<|>/;
          R.map(cates.split(' '), function (v, i) {
            v = v.split(splitterReg);
            var c = crossList[v[0]];
            if (c) {
              c.cate = v[0];
              c.cate_date = v[1];
              c.hasMore = more.search(v[0]) > -1;
              h += s(c);
            }
          });

          $('#profile .crosses').append(h);
      }
    );
  };

  var crosses_inversation_defe = function (data) {
    if (!data) return;
    var user_id = data.user_id
      , token = data.token;
    //var qdate = Store.get('qdate') || '';
    //qdate && (qdate = '&date=' + qdate);
    var now = new Date();
    //now.setDate(now.getDate() - 3);
    now = now.getFullYear() + '-' + (now.getMonth() + 1) + '-' + now.getDate();

    var dfd = Api.request('crosses'
      , {
        params: { token: token },
        resources: { user_id: user_id }
      }
      , function (data) {
          var _date = new Date();
          //Store.set('qdate', _date.getFullYear() + '-' + _date.getMonth() + '-' + _date.getDate());
          var crosses = data.crosses;
          var invitations = [];
          var updates = [];
          var updated;
          var conversations = [];
          var identities_KV = {};
          var updatesAjax = [];
          R.each(crosses, function (v, i) {

            // invitations
            //if (user_id !== v.by_identity.connected_user_id) {
              if (v.exfee && v.exfee.invitations && v.exfee.invitations.length) {

                R.each(v.exfee.invitations, function (e, j) {
                  identities_KV[e.id] = [i,j];
                  if (user_id === e.identity.connected_user_id && e.rsvp_status === 'NORESPONSE') {
                    e.__crossIndex = i;
                    e.__identityIndex = j;
                    invitations.push(e);
                  }
                });


              }
          });

          Handlebars.registerHelper('crossItem', function (prop) {
            if (prop === 'place') {
              return crosses[this.__crossIndex][prop].title;
            } else if (prop === 'invitationid') {
              return crosses[this.__crossIndex]['exfee'].invitations[this.__identityIndex].id;
            } else if (prop === 'exfeeid') {
              return crosses[this.__crossIndex]['exfee'].id;
            }
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
            $('#profile .gr-b .invitations').removeClass('hide').append(h);
          }

      }
    );

    return dfd.done(newbieGuide);
  };

  var crosses_update_defe = function (data) {
    if (!data) return;
    var user_id = data.user_id
      , token = data.token
      , now = new Date()
      , mt = 0;

    now.setDate(now.getDate() - 3);
    mt = +now;
    now = now.getFullYear() + '-' + (now.getMonth() + 1) + '-' + now.getDate();

    return Api.request('crosses'
      , {
        resources: { user_id: user_id },
        params: {
          updated_at: now,
          token: token
        }
      }
      , function (data) {
          var crosses = data.crosses;
          var updates;

          if (0 === crosses.length) { return; }

          updates = R.filter(crosses, function (v, i) {
            var up = v.updated, b = false;
            //if (0 === v.conversation_count) {
              //v.conversation_count = b;
            //}
            if (up) {
              var k, dv, t;
              for (k in up) {
                if (k === 'background') {
                  continue;
                }
                dv = up[k];
                t = +new Date(dv.updated_at.replace(/\-/g, '/'));
                if (t > mt) {
                  if (k === 'exfee') {
                    if (dv.identity_id === v.by_identity.id || checkUserIdByIdentityId(dv.identity_id, user_id, v.exfee.invitations)) {
                      b |= false;
                    }
                    else {
                      b |= true;
                    }
                  }
                  else {
                    b |= true;
                  }
                }
                else {
                  b |= false;
                  up[k] = false;
                }

              }
            }

            if (b) {
              return true;
            }
          });

          if (0 === updates.length) {
            return;
          }

          var uh = $('#jst-updates').html();
          var s = Handlebars.compile(uh);
          var h = s({updates: updates});
          $('.siderbar.updates').append(h).removeClass('hide');

        }
    );
  };

  function checkUserIdByIdentityId(identity_id, user_id, invitations) {
    var rs = R.filter(invitations, function (v) {
      if (v.identity.id === identity_id) {
        return true;
      }
    })[0];
    if (rs && rs.identity.connected_user_id === user_id) {
      return true;
    }
    return false;
  }

  // 加载新手引导
  var newbieGuide = function (data) {
    if (!data) return;
    data = Store.get('authorization');
    if (!data) return;
    var user_id = data.user_id;
    var cross_nums = +$('.user-xstats > .attended').text();

    // test
    //Store.set('newbie_guide', 0);
    var newbie_status = Store.get('newbie_guide:' + user_id);

    if (!newbie_status && cross_nums <= 3 && !$('#app-browsing-identity').size()) {
      var s = document.createElement('script');
      var $ss = $('script#js-newbieguide');
      s.id = 'js-newbieguide';
      s.type = 'text/javascript';
      s.async = true;
      s.src = '/static/js/newbieguide/0.0.2/newbieguide.min.js?t=' + Config.timestamp;
      $(s).attr('data-exists', $ss.attr('data-exists'));
      $ss.remove();
      var body = document.body;
      body.appendChild(s);
    }
  }

  // ios-app
  var iosapp = function (data) {
    var dismiss = Store.get('iosapp_dismiss');
    if (!dismiss) {
      $('.ios-app').removeClass('hide');
    }
  };

  // Defer Queue
  Bus.on('app:profile:show', function (d) {
    d.done([crossList_defe, crosses_inversation_defe, crosses_update_defe, iosapp]);
  });
  Bus.on('app:profile:identities', function (data) {
    identities_defe(data);
  });
  // 添加身份
  Bus.on('app:addidentity', function (data) {
    var jst_identity_list = $('#jst-identity-list');
    var s = Handlebars.compile(jst_identity_list.html());
    var h = s({identities: [data.identity]});
    $('.identity-list').append(h);
  });

  var $BODY = $(document.body);

  // 暂时使用jQuery 简单实现功能
  // 编辑 user/identity name etc.
  $BODY.on('dblclick.profile', '.user-name h3', function (e) {
    var value = $.trim($(this).html());
    var $input = $('<input type="text" value="' + value + '" class="pull-left" />');
    $input.data('oldValue', value);
    $(this).after($input).hide();
    $input.focusend();
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
        var authorization = Store.get('authorization')
          , token = authorization.token;

        Api.request('updateUser'
          , {
            type: 'POST',
            params: { token: token },
            data: {
              name: value
            }
          }
          , function (data) {
            Store.set('user', data.user);
            Bus.emit('app:page:changeusername', value);
          }
        );

      }
  });

  $BODY.on('dblclick.profile', '.identity-list li .identity > span.identityname em', function (e) {
    var that = $(this)
      , $li = that.parents('li')
      , provider = $li.data('provider')
      , status = $li.data('status')
      , editable = $li.data('editable');

    if ('twitter facebook google'.indexOf(provider) !== -1) {
      $li.find('.isOAuth').removeClass('hide');
    } else if (editable) {
      var value = $.trim(that.text());
      var $input = $('<input type="text" value="' + value + '" class="username-input" />');
      $input.data('oldValue', value);
      that.after($input).hide();
      $input.focusend();
    }
  });

  $BODY.on('focusout.profile keydown.profile', '.identity-list .username-input', function (e) {
      var t = e.type, kc = e.keyCode;
      if (t === 'focusout' || (kc === 9 || (!e.shiftKey && kc === 13))) {
        var value = $.trim($(this).val());
        var oldValue = $(this).data('oldValue');
        var identity_id = $(this).parents('li').data('identity-id');
        $(this).hide().prev().text(value).show();
        $(this).remove();


        if (!value || value === oldValue) return;
        var authorization = Store.get('authorization')
          , token = authorization.token;

        Api.request('updateIdentity'
          , {
            params: { token: token },
            resources: {identity_id: identity_id},
            type: 'POST',
            data: {
              name: value
            }
          }
          , function (data) {
            var user = Store.get('user');
            for (var i = 0, l = user.identities.length; i < l; ++i) {
              if (user.identities[i].id === data.identity_id) {
                user.identities[i] = identity;
                break;
              }
            }

            Store.set('user', user);
          }
        );
      }
  });

  // RSVP Accpet
  $BODY.on('click.profile', '.xbtn-accept', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var identity = Store.get('lastIdentity');
    var p = $(this).parent();
    var crossid = p.data('id');
    var invitationid = p.data('invitationid');
    var cross_box = $('.gr-a [data-id="' + crossid + '"]');
    var exfee_id = p.data('exfeeid');
    var authorization = Store.get('authorization')
      , token = authorization.token;

    Api.request('rsvp'
      , {
        params: { token: token },
        resources: {exfee_id: exfee_id},
        type: 'POST',
        data: {
          rsvp: '[{"identity_id":' + identity.id + ', "rsvp_status": "ACCEPTED", "by_identity_id": ' + identity.id + '}]',
          by_identity_id: identity.id
        }
      }
      , function (data) {
        var fs = cross_box.find('>div :first-child');
        var i = +fs.text();
        fs.text(i + 1);
        var ls = cross_box.find('>div :last-child');
        var s = ls.text();
        var identity = Store.get('lastIdentity');
        ls.text(s + (s ? ', ' : '') + identity.name);
        var inv;
        if (!p.parent().prev().length && !p.parent().next().length) {
          inv = p.parents('.invitations');
        }
        p.parent().remove();
        inv && inv.remove();
      }
    );

  });

  // 身份验证
  /*
  $BODY.on('click.profile.identity', '.xbtn-reverify', function (e) {
    var identity_id = $(this).parents('li').data('identity-id');
    var user = Store.get('user');
    var identity = R.filter(user.identities, function (v, i) {
      if (v.id === identity_id) return true;
    })[0];
    $(this).data('source', identity);
  });
  */

  // 添加身份
  $BODY.on('click.profile.identity', '.xbtn-addidentity', function (e) {
  });

  $BODY.on('click.profile', '#profile div.cross-type', function (e) {
    e.preventDefault();
    $(this).next().toggleClass('hide').next().toggleClass('hide');
    $(this).find('span.arrow').toggleClass('lt rb');
  });

  $BODY.on('hover.profile', '.changepassword', function (e) {
    var t = e.type;
    $(this).data('hoverout', t === 'mouseleave');
    if (t === 'mouseenter') {
      $(this).addClass('xbtn-changepassword');
    } else {
      $(this).removeClass('xbtn-changepassword');
    }
  });

  /*
  $BODY.on('hover.profile', '.settings-panel', function (e) {
    var t = e.type;
    $(this).data('hoverout', t === 'mouseleave');
    if (t === 'mouseenter') {
      $(this).find('.xbtn-changepassword').removeClass('hide');
      //$(this).find('.xlabel').removeClass('hide');
    } else {
      $(this).find('.xbtn-changepassword').addClass('hide');
      //$(this).find('.xlabel').addClass('hide');
    }
  });
  */

  // more
  $BODY.on('click.profile', '.more > a', function (e) {
    e.preventDefault();
    var $e = $(this);
    var p = $e.parent();
    var cate = p.data('cate');
    var data = Store.get('authorization');
    var token = data.token;
    var user_id = data.user_id;
    var more_position = p.prev().find(' .cross-box').length;
    var more_category = cate;

    Api.request('crosslist'
      , {
        params: { token: token },
        resources: { user_id: user_id },
        data: {
          more_category: more_category,
          more_position: more_position
        }
      }
      , function (data) {
        if (data.crosses.length) {
          var h = '{{#crosses}}'
              + '{{> jst-cross-box}}'
            + '{{/crosses}}';
          var s = Handlebars.compile(h);
          p.prev().append(s(data))
          var l = R.filter(data.more, function (v) {
            if (v === cate) return true;
          });
          if (!l.length) {
            $e.remove();
          }
        }
      }
    );

  });

  // iso-app dismiss link
  $BODY.on('click.profile.iosapp', '.ios-app > .exfe-dismiss', function (e) {
    e.preventDefault();
    Store.set('iosapp_dismiss', true);
    $BODY.off('click.profile.iosapp');
    $(this).parent().fadeOut();
  });

  var Uploader = null;
  var uploadSettings;
  // 头像上传控件
  var uploader = null;
  // uploader
  $BODY.on('click.data-link', '.user-avatar .avatar, .identity-list > li > .avatar', function (e) {
    var $e = $(this),
        $img = $e.find('img');

    if (!$e.parent().data('editable')) { return false; }

    var identity_id = $e.parent().data('identity-id');

    var data = {};

    if (identity_id) {
      data.identity_id = identity_id;
    }

    data['80_80'] = $img[0].src;

    data['80_80'] = decodeURIComponent(data['80_80']);

    if (!data['80_80'].match(/\/80_80_/)) {
      data['80_80'] = '';
    }

    data['original'] = data['80_80'].replace(/80_80_/, 'original_');

    if (!Uploader) {
      Uploader = require('uploader').Uploader;
      uploadSettings = $.extend(true, {}, require('uploader').uploadSettings, {
        options: {
          onHideBefore: function (e) {
            uploaderTarget = null;
            uploader = null;
          }
        }
      });
    }

    if (uploader) {
      uploader.hide();
      uploader = null;
    }

    uploader = new Uploader(uploadSettings).render();
    uploader.show(data);
  });

  $BODY.dndsortable({
    delay: 300,
    wrap: true,
    list: '.identity-list',
    items: ' > li',
    sort: function (dragging, dropzone) {
      /*
      var c = this.data('timer');
      if (c) {
        clearTimeout(c);
        c = null;
      }
      c = setTimeout(function () {
        $(dropzone)[$(dragging).index() < $(dropzone).index() ? 'after' : 'before'](dragging);
        console.log('dropzone', 1);
      }, 300);
      this.data('timer', c);
      */
    },
    setData: function (e) {
      return $(e).data('identity-id');
    },
    start: function (e) {
      if (1 === $('.settings-panel .identity-list > li').size()) {
        return false;
      }
      $(this).addClass('dragme');
      $('.xbtn-addidentity').addClass('hide');
      $('.identities-trash').removeClass('hide over');
    },
    end: function () {
      $(this).removeClass('dragme');
      $('.xbtn-addidentity').removeClass('hide');
      $('.identities-trash').addClass('hide over');
    },
    change: function (data) {
      //console.log(data);
    }
  });

  $BODY.on('dragenter.profile', '.trash-overlay', function (e) {
    $(this).parent().addClass('over');
    $('.icon24-trash').addClass('icon24-trash-red');
    return false;
  });

  $BODY.on('dragover.profile', '.trash-overlay', function (e) {
    e.stopPropagation();
    e.preventDefault();
    //e.originalEvent.dataTransfer.dropEffect = 'move';
    return false;
  });

  $BODY.on('dragleave.profile', '.trash-overlay', function (e) {
    $(this).parent().removeClass('over');
    $('.icon24-trash').removeClass('icon24-trash-red');
    return false;
  });

  $BODY.on('drop.profile', '.trash-overlay', function (e) {
    e.stopPropagation();
    e.preventDefault();
    var dt = e.originalEvent.dataTransfer;
    var identity_id = +dt.getData('text/plain');
    $(this).parent().removeClass('over');
    $('.icon24-trash').removeClass('icon24-trash-red');

    // delete identity
    function _deleteIdentity(identity_id) {
      var authorization = Store.get('authorization')
        , token = authorization.token;
      Api.request('deleteIdentity'
        , {
          type: 'POST',
          params: { token: token },
          data: {
            identity_id: identity_id
          }
        },
        function (data) {
          var user = Store.get('user')
            , identities = user.identities;
          R.some(identities, function (v, i) {
            if (v.id === identity_id) {
              identities.splice(i, 1);
              return true;
            }
          });
          Store.set('user', user);
          $('.identity-list > li[data-identity-id="' + identity_id + '"]').remove();

          // 没有 `CONNECTED` 身份时，隐藏 `addidentity` 按钮
          var total_connected = 0;
          R.each(identities, function (v) {
            if (v.status === 'CONNECTED') {
              ++total_connected;
            }
          });
          if (!total_connected) {
            $('#app-profile-addidentity').addClass('hide');
          }
        },
        function (data) {
          var meta = data && data.meta;
          if (meta
              && meta.code === 401
              && meta.errorType === 'authenticate_timeout') {

            var $d = $('<div data-widget="dialog" data-dialog-type="authentication" data-destory="true" class="hide"></div>');
            $('#app-tmp').append($d);
            var e = $.Event('click.dialog.data-api');
            e._data = {callback: function () { _deleteIdentity(identity_id); }};
            $d.trigger(e);
          }
        }
      );
    }

    _deleteIdentity(identity_id);

    return false;
  });

});
