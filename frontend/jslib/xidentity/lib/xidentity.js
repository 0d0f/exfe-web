define(function (require) {

  var $ = require('jquery');
  var Util = require('util');
  var Bus = require('bus');
  var Api = require('api');
  var $BODY = $(document.body);
  var Store = require('store');
  var Typeahead = require('typeahead');
  var Handlebars = require('handlebars');

  var IdentityPop = Typeahead.extend({

    itemRender: function (item) {
      var template = Handlebars.compile(this.options.viewData.item);
      return $(template(item));
    },

    matcher: function (item) {
      var eid = item.external_id;
      return ~eid.toLowerCase().indexOf(this.query.toLowerCase());
    },

    focus: function () {
      var v = Util.trim(this.target.val());
      if (v) {
        //this.emit('search', v);
      } else {
        this.emit('nothing', v);
      }
    },

    select: function () {
      var active = this.$('.active'),
          val = active.data('value');

      if (!val) return false;

      this.target
        .val(this.updater(val))
        .change();

      this.emit('select', val);
      this.selecting = false;
    },

    tab: function () {
      var val = this.target.val();
      //this.emit('select', val);
      return this.hide();
    },

    keypress: function (e, keyCode) {
      if (!this.isShown) return;

      keyCode = e.keyCode;
      switch(keyCode) {
        case 9: // tab
          this.tab();
          //e.preventDefault();
          break;
        //case 13: // enter
        //case 27: // escape
          //e.preventDefault();
          //break;

        case 38: // up arrow
          if (e.type !== 'keydown') break;
          e.preventDefault();
          this.selecting = true;
          this.prev();
          this.select();
          break;

        case 40: //down break
          if (e.type !== 'keydown') break;
          e.preventDefault();
          this.selecting = true;
          this.next();
          this.select();
          break;
      }

      e.stopPropagation();
    },

    options: {

      // ajax settings
      url: null,
      useCache: false,
      delay: 200,
      extraParams: {},
      autoClearResults: false,
      dataType: "JSON",
      minLength: 1,

      viewData: {

        item: ''
          + '<li data-value="{{external_id}}">'
            + '<i class="pull-right icon16-identity-{{provider}}"></i>'
            + '<span>{{external_id}}</span>'
          + '</li>'
      },

      onSelect: function (val) {
        this.emit('search', val);
      },

      // 清楚数据缓存
      onClearCache: function () {
        delete this.cache;
      },

      onSearch: function (q) {
        var that = this
          , options = that.options
          , res
          , items;

        that.cache || (that.cache = {});

        if (!that.selecting && that.source && that.source.length) {
          items = $.grep(that.source, function (item) {
            return that.matcher(item);
          });

          if (!items.length) {
            that.isShown ? that.hide() : that;
          } else {
            that.render(items.slice(0)).show();
          }
        }

        if (that.timer) {
          clearTimeout(that.timer);
          // ajax loading
          that.target.next().addClass('hide');;
        }

        if ((res = Util.parseId(q)).provider) {
          var external_username = res.external_identity;
          if (res.provider === 'twitter') {
            external_username = res.external_username;
          }
          var identity = {
            provider: res.provider,
            external_username: external_username
          };

          that.timer = setTimeout(function () {
            clearTimeout(that.timer);
            search(q);
          }, options.delay);

          // falg: SIGN_IN SIGIN_UP VERIFY SET_PASSWORD
          function ajax(e) {
            that.ajaxDefer && that.ajaxDefer.readyState < 4 && that.ajaxDefer.abort();
            that.emit('autocomplete:beforesend');
            if (options.useCache && that.cache[e]) that.emit('autocomplete:finish', that.cache[e]);
            else {
              that.ajaxDefer = Api.request('getRegistrationFlag'
                , {
                  data: identity,
                  beforesend: function (xhr) {
                    // ajax loading
                    that.target.next().removeClass('hide');;
                  },
                  complete: function (xhr) {
                    // ajax loading
                    that.target.next().addClass('hide');;
                  }
                }
                , function (data) {
                  if (e === that.target.val()) {
                    options.useCache && (that.cache[e] = data);
                    that.emit('autocomplete:finish', data);
                  }
                }
              );
            }
          }

          function search(a) {
            if (a.length >= that.options.minLength) {
              ajax(a);
              that.searchValue = a;
            } else {
              that.emit('autocomplete:clear');
            }
          }

        } else {
          that.emit('autocomplete:finish', null);
        }
      }

    }

  });

  $(function () {

    var user = Store.get('user'), identities;
    user && (identities = user.identities);

    $BODY.on('focus.typeahead.data-api', '[data-typeahead-type="identity"]', function (e) {
      var $this = $(this);

      if ($this.data('typeahead')) return;
      e.preventDefault();
      $this.data('typeahead', new IdentityPop({

        options: {
          source: identities,
          useCache: true,
          target: $this,
          // 当输入框没有值时，触发
          onNothing: function () {
            this.target.parent().removeClass('identity-avatar');
            Bus.emit('widget-dialog-identification-nothing');
          },

          'onAutocomplete:finish': function (data) {
            var identity;
            if (data && (identity = data.identity)) {
              if (identity['avatar_filename'] === 'default.png') {
                identity['avatar_filename'] = '/img/default_portraituserface_20.png';
              }
              this.target
                .prev()
                .attr('src', identity['avatar_filename'])
                .parent()
                .addClass('identity-avatar');
            } else {
              this.target.parent().removeClass('identity-avatar');
            }
            Bus.emit('widget-dialog-identification-auto', data);
          }
        }
      }));

    });

  });

});
