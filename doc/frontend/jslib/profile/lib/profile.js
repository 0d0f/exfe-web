define(function (require, exports, module) {
  // TODO: 临时解决 Router 问题
  //if (! /^\/profile(_iframe)?\..*/.test(window.location.pathname)) return;

  var $ = require('jquery');
  var Store = require('store');
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

  Handlebars.registerHelper('printName', function (name, external_id) {
    if (!name) {
      name = external_id.match(/([^@]+)@[^@]+/)[1];
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
    var tz = /(^[\+\-]\d\d:\d\d)/.exec(b.timezone)[1];
    // 创建一个 moment date-object
    var d = Moment.utc(b.date + ' ' + b.time + ' ' + tz, 'YYYY-MM-DD HH:mm:ss Z');

    var s = '', f;

    // 比对时区
    var czEtz = cz === tz;

    // 直接输出 origin 时间
    if (time.outputformat) {
      s = time.origin;
      if (!czEtz) {
        s += ' ' + tz;
      }
      return s;
    } else {

      if (b.time_word) {
        s += b.time_word + ' (at) ';
      }

      s += b.time;

      if (!czEtz) {
        s += ' (' + tz + ') ';
      }

      if (b.date_word) {
        s += b.date_word + ' (on) ';
      }

      s += ' ' + b.date;

      if (d.year() !== c.year()) {
        s += ' ' + y;
      }
    }

    return d.calendar();
    //return s;
  });

  // Invitations print time
  Handlebars.registerHelper('printTime2', function (time, options) {
    time  = Handlebars.helpers['crossItem'].call(this, time, options);
    return Handlebars.helpers['printTime4'].call(this, time);
  });

  Handlebars.registerHelper('printTime4', function (time) {
    // 终端时区
    var c = Moment();
    var cz = c.format('Z');
    var b = time.begin_at;

    // Cross 时区
    var tz = /(^[\+\-]\d\d:\d\d)/.exec(b.timezone)[1];
    // 创建一个 moment date-object
    var d = Moment.utc(b.date + ' ' + b.time + ' ' + tz, 'YYYY-MM-DD HH:mm:ss Z');

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
      if (b.time !== '00:00:00') {
        f += 'hA ';
      }
      if (b.date) {
        f += 'ddd MMM D';
      }

      return d.format(f);
    }
  });

  // Updates print time
  Handlebars.registerHelper('printTime3', function (time, options) {
    //time  = Handlebars.helpers['crossItem'].call(this, time, options);
    var b = time.begin_at;
    // Cross 时区
    var tz = /(^[\+\-][\d]{2}:[\d]{2})/.exec(b.timezone)[1];
    // 创建一个 moment date-object
    var d = Moment.utc(b.date + ' ' + b.time + ' ' + tz, 'YYYY-MM-DD HH:mm:ss Z');
    return d.fromNow();
  });

  Handlebars.registerHelper('rsvpAction', function (identities, identity_id) {
    var i = R.filter(identities, function (v) {
      if (v.identity.id === identity_id) return true;
    })[0];
    var html = '';
    if (i.rsvp_status !== 'INTERESTED') {
      var html = '<div><i class="';
      html += 'icon-rsvp-' + i.rsvp_status.toLowerCase() + '"></i> ';
      html += i.rsvp_status.charAt(0) + i.rsvp_status.substr(1).toLowerCase() + ': ' + i.identity.name + '</div>';
    }

    var is = R.map(identities, function (v) {
      if (v.by_identity.id === identity_id && v.identity.id !== identity_id) return v.identity.name;
    }).filter(function (v) {
      if (v) return true;
    }).join(' ,');

    html += '<div><i class="icon-invite"></i> ';
    html += 'Invited: ' + is;
    html += '</div>';
    return html;
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

  Handlebars.registerHelper('atName', function (provder, external_id) {
    //var s = '';
    //if (provder === 'twitter') s = '@' + external_id;
    //else s = external_id;
    return external_id;
  });

  Handlebars.registerHelper('editable', function (provder, status, options) {
    var context = provder === 'email' && status === 'CONNECTED';
    return Handlebars.helpers['if'].call(this, context, options);
  });

  // 用户信息,包括多身份信息
  var identities_defe = function (data) {
    if (!data) return;

    var user;

    if (data.response) user = data.response.user;
    else if (data instanceof Array) user = data[0].response.user;

    Store.set('user', user);

    $('.user-xstats .attended').html(user.cross_quantity);
    $('#user-name > span').html(user.name);

    var jst_user = $('#jst-user-avatar');
    var s = Handlebars.compile(jst_user.html());
    var h = s({avatar_filename: user.avatar_filename});
    $('.user-avatar').append(h);

    $('.user-name').find('h3').html(user.name);


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

    // 返回一个 promise 对象
    return Api.request('crosslist'
      , {
        resources: {
          user_id: user_id
        }
      }
      , function (data) {
          var now = +new Date;

          if (data.crosses.length) {

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
            //var h = s(data.response);
            var h = '';

            var cates = 'upcoming<Upcoming> sometime<Sometime> sevendays<Next 7 days> later<Later> past<Past>';
            var crossList = {};

            R.map(data.crosses, function (v, i) {
              crossList[v.sort] || (crossList[v.sort] = {crosses: []});
              crossList[v.sort].crosses.push(v);
            });

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
      }
    );
  };

  var crosses_inversation_defe = function (data) {
    if (!data) return;
    data = Store.get('signin');
    if (!data) return;
    var user_id = data.user_id;
    //var qdate = Store.get('qdate') || '';
    //qdate && (qdate = '&date=' + qdate);
    var now = new Date();
    //now.setDate(now.getDate() - 3);
    now = now.getFullYear() + '-' + (now.getMonth() + 1) + '-' + now.getDate();

    return Api.request('crosses'
      , {
        resources: {
          user_id: user_id
        }
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

  };

  var crosses_update_defe = function (data) {
    if (!data) return;
    data = Store.get('signin');
    if (!data) return;
    var user_id = data.user_id;
    var now = new Date();
    now.setDate(now.getDate() - 3);
    now = now.getFullYear() + '-' + (now.getMonth() + 1) + '-' + now.getDate();

    return Api.request('crosses'
      , {
        resources: {
          user_id: user_id
        },
        params: {
          updated_at: now
        }
      }
      , function (data) {
        var crosses = data.crosses;
        var updatesAjax = [];
        var updates = [];
        if (crosses.length) {
          R.each(crosses, function (v, i) {
            if (v.updated) {
                v.updated.conversation && updatesAjax.push(
                  Api.request('conversation'
                    , {
                      resources: {exfee_id: v.exfee.id},
                      params: {
                        date: now
                      }
                    }
                    , function (data) {
                      var conversation = data.conversation;
                      if (conversation && conversation.length) {
                        var c = conversation[conversation.length - 1];
                        if (c.by_identity.connected_user_id === user_id) return;
                        c.__conversation_nums = conversation.length;
                        v.updated.conversation.item = c;
                      }
                    }
                  )
                );

            }

          });
        }

          if (updatesAjax.length) {
            var dw = $.when;
            dw = dw.apply(null, updatesAjax);
            dw.then(function (data) {
              var uh = $('#jst-updates').html();
              var s = Handlebars.compile(uh);
              var h = s({updates: crosses});
              $('#profile .ios-app').before(h);
            });
          }

      }
    );
  };

  // 加载新手引导
  var newbieGuide = function (data) {
    var cross_nums = +$('.user-xstats > .attended').text();

    // test
    //Store.set('newbie_guide', 0);
    var newbie_status = Store.get('newbie_guide');

    if (!newbie_status && cross_nums <= 3) {
      var s = document.createElement('script');
      s.type = 'text/javascript';
      s.async = true;
      s.src = '/static2/js/newbieguide/0.0.1/newbieguide.min.js'
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
  // 可以登陆状态
  var SIGN_IN_OTHERS = 'app:signinothers';
  Bus.on(SIGN_IN_OTHERS, function (d) {
    d.then([crossList_defe, crosses_inversation_defe, crosses_update_defe, iosapp, newbieGuide]);
  });
  var SIGN_IN_SUCCESS = 'app:signinsuccess';
  Bus.on(SIGN_IN_SUCCESS, function (data) {
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
  $(function () {

    $BODY.on('hover.profile', '.identity-list > li', function (e) {
      //$(this).find('i.icon-minus-sign').toggleClass('hide');
      //if (e.type === 'mouseenter') $(this).addClass('over');
      //else $(this).removeClass('over');
      $(this).find('.xbtn-reverify, .xbtn-reauthorize').toggleClass('hide');
    });

    // removed identity
    $BODY.on('click.profile', 'i.icon-minus-sign', function (e) {
      var identity_id = $(this).parent().data('identity-id');
      var signinData = Store.get('signin');
      var token = signinData.token;

      //
      return;
      if (password) {

        Api.request('deleteIdentity'
          , {
            type: 'POST',
            data: {
              identity_id: identity_id,
              password: password
            }
          }
          , function (data) {
          }
          , function (data) {
            if (data.meta.code === 403) {
              alert('Please input password.');
            }
          }
        );

      }
    });

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

          Api.request('updateUser'
            , {
              type: 'POST',
              data: {
                name: value
              }
            }
            , function (data) {
              Store.set('user', data.user);
              Bus.emit('app:changename', value);
            }
          );

        }
    });

    $BODY.on('dblclick.profile', '.identity-list li.editable .username > em', function (e) {
      var value = $.trim($(this).html());
      var $input = $('<input type="text" value="' + value + '" class="username-input" />');
      $input.data('oldValue', value);
      $(this).after($input).hide();
      $input.focusend();
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

          Api.request('updateIdentity'
            , {
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

      Api.request('rsvp'
        , {
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

    // more
    $BODY.on('click.profile', '.more > a', function (e) {
      e.preventDefault();
      var $e = $(this);
      var p = $e.parent();
      var cate = p.data('cate');
      var data = Store.get('signin');
      var token = data.token;
      var user_id = data.user_id;
      var more_position = p.prev().find(' .cross-box').length;
      var more_category = cate;

      Api.request('crosslist'
        , {
          resources: {
            user_id: user_id
          },
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

  });

  // iso-app dismiss link
  $BODY.on('click.profile.iosapp', '.ios-app > .exfe-dismiss', function (e) {
    e.preventDefault();
    Store.set('iosapp_dismiss', true);
    $BODY.off('click.profile.iosapp');
    $(this).parent().fadeOut();
  });

  /*
  $('.identity-list').dndsortable({
    delay: 300,
    wrap: true,
    sort: function (dragging, dropzone) {
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
    },
    items: 'li',
    setData: function (e) {
      return $(e).data('identity-id');
    },
    start: function () {
      $(this).addClass('dragme');
      $('.xbtn-addidentity').addClass('hide');
      $('.identities-trash').removeClass('hide over');
    },
    end: function () {
      $(this).removeClass('dragme');
    },
    change: function (data) {
      //console.log(data);
    }
  });
  */

  /*
  // identity remote
  $BODY.on('dragstart.profile', '.identity-list > li', function (e) {
    e.stopPropagation();
    $(this).addClass('dragme');
    $('.xbtn-addidentity').addClass('hide');
    $('.identities-trash').removeClass('hide over');
    e.originalEvent.dataTransfer.effectAllowed = 'move';
    e.originalEvent.dataTransfer.setData("text/plain", $(this).data('identity-id'));
    return true;
  });

  $BODY.on('dragend.profile', '.identity-list > li', function (e) {
    $(this).removeClass('dragme');
    e.stopPropagation();
    return false;
  });

  $BODY.on('dragenter.profile', '.trash-overlay', function (e) {
    $(this).parent().addClass('over');
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
    return false;
  });

  $BODY.on('drop.profile', '.trash-overlay', function (e) {
    e.stopPropagation();
    e.preventDefault();
    var dt = e.originalEvent.dataTransfer;
    var data = dt.getData('text/plain');

    return false;
  });
  */

});
