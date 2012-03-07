/*
Create all necessary defaults and model groups for the collections
*/
var Formloader;

Formloader = _.defaults(Formloader || {}, {
  Uri: (function() {
    var path;
    path = window.location.pathname;
    path = path.substr(1);
    if (path.substr(-1) === '/') path = path.substr(0, path.length - 1);
    return path = path.split('/');
  })(),
  UrlBase: "" + window.location.protocol + "//" + window.location.host + "/formloader",
  Collections: {},
  Models: {},
  View: {},
  Views: {},
  Vars: {},
  Templates: {},
  Proxy: {},
  Helpers: {},
  Forms: {},
  FieldTypes: {}
});

_.extend(Formloader.Proxy, Backbone.Events);

Formloader.Templates.Alert = _.template("<div class=\"alert alert-<%= type %>\">\n  <a class=\"close\" data-dismiss=\"alert\">×</a>\n  <%= message %>\n</div>");

Formloader.Templates.Modal = _.template("<div class=\"modal\">\n  <div class=\"modal-header\">\n    <a class=\"close\" data-dismiss=\"modal\">×</a>\n    <h3><%= title %></h3>\n  </div>\n  <div class=\"modal-body\">\n    <%= form %>\n  </div>\n</div>");

Formloader.Templates.Validation = _.template("<form>\n  <div class=\"clearfix\" data-formloader-wrapper=\"email\">\n  	<% if(prompt){ %>\n  	<label for=\"subVal\"><%= prompt %></label>\n  	<div class=\"input\">\n  		 <input id=\"subVal\" name=\"subVal\" value=\"<%= subVal %>\" type=\"text\">\n  	</div>\n  	<% }else{ %>\n  	<label for=\"subVal\"><strong>Info:</strong></label>\n  	<div class=\"input\">\n      <p><%= clicktip %></p>\n	  </div>\n	  <% } %>\n  </div>\n  <div class=\"form-actions\">\n    <button id=\"saveModal\" class=\"btn btn-info\" type=\"submit\"><% if(prompt){ %>Submit<% }else{ %>Save<% } %></button>\n  </div>\n</form>");

Formloader.Templates.HoverTrash = _.template("<a href=\"#\" class=\"icon-trash\" style=\"display:none\" data-formloader-hidefield=\"<%= name %>\"></a>");

Formloader.Templates.Options = _.template("<div class=\"optionRow\">\n   <div>\n      <input type=\"text\" class=\"inline span2\" name=\"<%= name %>[key][]\" placeholder=\"key\" value=\"<%= key %>\" />\n      <input type=\"text\" class=\"inline span2\" name=\"<%= name %>[value][]\" placeholder=\"value\" value=\"<%= value %>\" />\n      <a href=\"#\" data-formloader-action=\"<%= type %>\" class=\"btn <%= _class %>\"><%= sign %></a>\n      <span class=\"btn small disabled optionSortHandle\">Sort</span>\n   </div>\n   <br>\n</div>");

Formloader.Helpers.emptyAllInputs = function($domElement) {
  return $domElement.find(':input').each(function() {
    var $empty;
    Formloader.Proxy.trigger("emptied", {
      name: $(this).attr('name')
    });
    $empty = $(this).val('');
    if (this.tagName.toLowerCase() === "select") return $empty.change();
  });
};

Formloader.Helpers.isEmptyInput = function($domElement) {
  var input, inputs, _i, _len;
  inputs = $domElement.find(':input');
  if (inputs.length === 1) {
    if ($(inputs[0]).attr('value') === '') return true;
  } else {
    for (_i = 0, _len = inputs.length; _i < _len; _i++) {
      input = inputs[_i];
      if ($(input).attr('value') !== '') return false;
    }
    return true;
  }
};

Formloader.Helpers.Popup = function(path, opts, data) {
  var callback, windowName, windowOptions,
    _this = this;
  windowName = (opts != null ? opts.windowName : void 0) || '_blank';
  windowOptions = (opts != null ? opts.windowOptions : void 0) || 'location=0,status=0,scrollbars=yes,width=940,height=800';
  callback = (opts != null ? opts.callback : void 0) || function() {
    return window.location.reload();
  };
  this._oauthWindow = window.open(path, windowName, windowOptions);
  this._oauthInterval = window.setInterval(function() {
    if (_this._oauthWindow.closed) {
      window.clearInterval(_this._oauthInterval);
      return callback();
    }
  }, 1000);
  if ((data != null)) return this._oauthWindow.document.write(data);
};

Formloader.Model = Backbone.Model.extend({
  idAttribute: '_id',
  defaults: {
    group: '',
    name: '',
    label: '',
    subVal: '',
    bound: false,
    selected: false,
    prompt: false,
    clicktip: false
  },
  initialize: function() {
    if (this.collection.type === "validations") {
      return this.on("change:selected", function(m) {
        if (this.get('selected') === false) return this.set('subVal', '');
      }, this);
    }
  }
});

Formloader.Collection = Backbone.Collection.extend({
  url: function() {
    return "/formloader/js/" + this.type;
  },
  model: Formloader.Model,
  initialize: function(m, opts) {
    this.type = opts.type;
    this.url = this.url();
    if (opts.type === "fields") {
      Formloader.Proxy.on('fieldset:switc', function(group, label, switc) {
        var _this = this;
        return this.find(function(item) {
          if (item.get('group') === group && item.get('label') === label) {
            return item.set('bound', switc);
          }
        });
      }, this);
    }
    return null;
  },
  parse: function(resp) {
    var _this = this;
    return _.filter(resp, function(item) {
      return !_this.get(item._id);
    });
  }
});

Formloader.View.AutoComplete = Backbone.View.extend({
  events: {
    'click [data-formloader-add]': function(e) {
      var _this = this;
      e.preventDefault();
      if (this.collection.type !== "validations") {
        return Formloader.Helpers.Popup("" + Formloader.UrlBase + "/" + ($(e.currentTarget).attr('data-formloader-add')) + "/create?ref=popup", {
          callback: function() {
            return _this.collection.fetch({
              add: true
            });
          }
        });
      }
    }
  },
  initialize: function() {
    var _this = this;
    this.$tagit = this.$el.find('ul');
    if (this.collection.type !== "validations") {
      this.$('[data-formloader-add]').show();
    }
    _.bind(this.tagToggle, this);
    _.bind(this.validatePrompt, this);
    _.bind(this.addStartTags, this);
    if (this.options.subCall) _.bind(this.options.subCall, this);
    this.initStatus = false;
    this.$tagit.tagit({
      requireAutocomplete: (function() {
        return _this.collection.type !== "validations";
      })(),
      removeConfirmation: true,
      itemName: 'tagit',
      fieldName: this.$tagit.attr('data-tagit-name'),
      tagSource: function(e, show) {
        return show(_this.collection.chain().filter(function(item) {
          return item.get("selected") !== true && item.get("bound") !== true && (item.get("label").toLowerCase().indexOf(e.term) === 0 || item.get("name").toLowerCase().indexOf(e.term) === 0);
        }).map(function(model) {
          if (model.get("group") === Formloader.Vars.GroupName) {
            return model.get("name");
          } else {
            return "" + (model.get('name')) + "." + (model.get('group'));
          }
        }).value());
      },
      onTagAdded: function(e, tag) {
        _this.tagToggle(tag, true);
        if (_this.collection.type === "validations" && _this.initStatus === true) {
          _this.validatePrompt(tag, true);
        }
        return null;
      },
      onTagRemoved: function(e, tag) {
        _this.tagToggle(tag, false);
        return null;
      },
      onTagClicked: function(e, tag) {
        var type, _id;
        if (_this.collection.type !== "validations") {
          type = _this.collection.type;
          _id = tag.attr('data-formloader_id');
          return Formloader.Helpers.Popup("" + Formloader.UrlBase + "/" + type + "/" + _id + "?ref=popup", {
            callback: function() {
              return _this.collection.fetch({
                add: true
              });
            }
          });
        } else {
          _this.validatePrompt(tag);
          return e.preventDefault();
        }
      }
    }).sortable();
    return this;
  },
  addStartTags: function(tags) {
    var item, tag, _i, _len;
    for (_i = 0, _len = tags.length; _i < _len; _i++) {
      tag = tags[_i];
      if (tag.indexOf('.') === -1 && Formloader.Vars.GroupName !== '') {
        item = tag + '.' + Formloader.Vars.GroupName;
      } else {
        item = tag.split('.');
        item = "" + item[1] + "." + item[0];
      }
      this.$tagit.tagit("createTag", "" + item);
    }
    return this.initStatus = true;
  },
  validatePrompt: function(tag, added, callback) {
    var ValidationView, model, tagId;
    tagId = tag.attr('data-formloader_id').split('[');
    model = this.collection.get(tagId[0]) || false;
    if (model && (added !== true || model.get('prompt') !== false)) {
      ValidationView = Backbone.View.extend({
        events: {
          "click #saveModal": function(e) {
            e.preventDefault();
            return this.$el.modal('hide');
          }
        },
        render: function(form) {
          var _this = this;
          this.$el.html(Formloader.Templates.Modal({
            form: form,
            title: "Set Validation"
          }));
          this.$el.modal('show');
          this.$el.on('hide', function() {
            var subVal;
            if (model.get('prompt') !== false) {
              subVal = _this.$el.find('#subVal').attr('value');
              if ((subVal != null) && subVal !== '') {
                model.set('subVal', subVal);
                tag.find('.tagit-label').text("" + (model.get('name')) + "[" + subVal + "]");
                tag.attr('data-formloader_id', "" + (model.get('_id')) + "[" + subVal + "]");
                return null;
              } else {
                model.set('selected', false);
                tag.remove();
                return false;
              }
            }
          });
          return this.$el.on('hidden', function() {
            return _this.remove();
          });
        }
      });
      return new ValidationView().render(Formloader.Templates.Validation(model.toJSON()));
    }
  },
  tagToggle: function(tag, set) {
    var model, subVal, tagVal;
    subVal = -1;
    tagVal = tag.find('input').attr('value');
    if (tagVal.indexOf(".") === -1) {
      tagVal = "" + Formloader.Vars.GroupName + "." + tagVal;
    } else {
      tagVal = tagVal.split('.');
      subVal = tagVal[0].search(/\[(.*?)\]/);
      if (subVal !== -1) subVal = tagVal[0].match(/\[(.*?)\]/)[1];
      tagVal = "" + tagVal[1] + "." + (tagVal[0].replace(/\[(.*?)\]/, ''));
    }
    model = this.collection.find(function(item) {
      return item.get('label') === tagVal;
    });
    if (model) {
      this.model = model;
      if (subVal !== -1) {
        model.set('subVal', subVal);
        tag.find('.tagit-label').text("" + (model.get('name')) + "[" + subVal + "]");
        tag.attr('data-formloader_id', "" + (model.get('_id')) + "[" + subVal + "]");
      } else {
        if (set && this.model.get('group') === Formloader.Vars.GroupName || this.model.get('group') === "fuel") {
          tag.find('.tagit-label').text(this.model.get('name'));
        }
        tag.attr('data-formloader_id', this.model.get('_id'));
      }
      this.model.set('selected', set);
      if (this.options.subCall) this.options.subCall(tag, this.model);
    } else {
      if (this.collection.type === "validations") {
        tag.removeClass('ui-state-default');
        tag.addClass('ui-button-primary');
      } else {
        tag.addClass('ui-state-error');
      }
    }
    return null;
  }
});

Formloader.FieldTypes.CollapsibleFieldset = Backbone.View.extend({
  events: {
    "click [data-formloader-showfieldtrigger]": function(e) {
      var $e;
      e.preventDefault();
      $e = $(e.target);
      if ($e.attr('data-formloader-showfieldtrigger') === "show") {
        $e.attr('data-formloader-showfieldtrigger', "hide");
        $e.text('Hide Empty Fields');
        return this.collection.each(function(m) {
          return m.set('visible', true);
        });
      } else {
        $e.text('Show All Fields');
        $e.attr('data-formloader-showfieldtrigger', "show");
        return this.collection.each(function(m) {
          if (Formloader.Helpers.isEmptyInput(m.get('target')) === true) {
            return m.set('visible', false);
          }
        });
      }
    },
    "click [data-formloader-showfield]": function(e) {
      var m;
      e.preventDefault();
      m = this.collection.get($(e.target).attr('data-formloader-showfield'));
      if (m != null) {
        m.set('visible', true);
        if (!(this.collection.find(function(item) {
          return item.get('visible') === false;
        }) != null)) {
          this.$('[data-formloader-showfieldtrigger]').click();
        }
        return this.delegateEvents();
      }
    },
    "click a[data-formloader-hidefield]": function(e) {
      var m;
      e.preventDefault();
      m = this.collection.get($(e.currentTarget).attr('data-formloader-hidefield'));
      if (m != null) {
        m.set('visible', false);
        Formloader.Helpers.emptyAllInputs(m.get('target'));
        return this.delegateEvents();
      }
    },
    "hover label": function(e) {
      if (e.type === "mouseenter") {
        return $(e.target).find('a').show();
      } else {
        return $(e.target).find('a').hide();
      }
    }
  },
  initialize: function() {
    var _this = this;
    this.collection = new this.collection();
    return this.$("[data-formloader-wrapper]").each(function(k, i) {
      var $i, id;
      $i = $(i);
      id = $i.attr('data-formloader-wrapper');
      _this.collection.add({
        id: id,
        link: _this.$("[data-formloader-showfield=\"" + id + "\"]"),
        target: $i,
        visible: _.indexOf(Formloader.unhide, id) !== -1
      });
      return $i.find('label').prepend(Formloader.Templates.HoverTrash({
        name: id
      }));
    });
  }
});

Formloader.FieldTypes.OptionRow = Backbone.View.extend({
  events: {
    "click [data-formloader-action]": function(e) {
      e.preventDefault();
      switch ($(e.target).attr('data-formloader-action')) {
        case "add_option_row":
          $(e.target).removeClass("btn-primary").addClass("btn-danger").attr('data-formloader-action', 'remove_option_row').text('-');
          this.addOptionRow();
          break;
        case "remove_option_row":
          $(e.target).closest('div.optionRow').remove();
      }
      return this.$el.sortable({
        handle: this.$('span')
      });
    }
  },
  initialize: function() {
    var key, options, val;
    _.bind(this.addOptionRow, this);
    options = Formloader.Forms[this.$el.attr('data-option-sgroup')] || false;
    if (options) {
      for (key in options) {
        val = options[key];
        this.addOptionRow(key, val);
      }
    }
    return this.$el.sortable({
      handle: this.$('span')
    });
  },
  addOptionRow: function(key, val) {
    var $last, tmpl,
      _this = this;
    tmpl = Formloader.Templates.Options({
      name: this.$el.attr('data-option-sgroup'),
      key: key,
      value: val,
      type: (key != null ? 'remove_option_row' : 'add_option_row'),
      sign: (key != null ? '-' : '+'),
      _class: (key != null ? 'btn-danger' : 'btn-primary')
    });
    $last = this.$('div.optionRow').last();
    if (key != null) {
      $last.before(tmpl);
    } else {
      $last.after(tmpl);
    }
    return Formloader.Proxy.on("emptied", function(e) {
      if (e.name.indexOf("" + (_this.$el.attr("data-option-sgroup"))) !== -1) {
        return _this.$('div.optionRow:not(:last-child)').remove();
      }
    });
  }
});

$(function() {
  var checkValue, toggleAppend;
  Formloader.Vars.Group = $("#group");
  Formloader.Vars.GroupName = Formloader.Vars.Group.attr('value');
  Formloader.Vars.Group.on('keyup', function(e) {
    return Formloader.Vars.GroupName = $(this).attr('value');
  });
  
    <?= isset($use) ? 'Formloader.Vars.Use = '.$use : '' ?>
    <?= isset($init) ? $init : '' ?>
  ;
  toggleAppend = function() {
    return $("li.tagit-choice").each(function() {
      var $input, id, inputVal;
      $input = $(this).find('input');
      id = $(this).attr('data-formloader_id');
      inputVal = $input.attr('value');
      $input.attr('value', id);
      $(this).attr('data-formloader_id', inputVal);
      return null;
    });
  };
  $("[data-option-sgroup]").each(function() {
    return new Formloader.FieldTypes.OptionRow({
      el: this
    });
  });
  $("[data-collapsible-fieldset]").each(function() {
    return new Formloader.FieldTypes.CollapsibleFieldset({
      el: this,
      collection: Backbone.Collection.extend({
        model: Backbone.Model.extend({
          defaults: {
            target: '',
            link: '',
            visible: false
          },
          initialize: function() {
            if (this.get('visible') === true) this.showItem(this);
            return this.on('change:visible', function(m) {
              if (m.get('visible') === true) {
                return this.showItem(m);
              } else {
                return this.hideItem(m);
              }
            }, this);
          },
          hideItem: function(m, init) {
            m.get('link').show();
            return m.get('target').attr('value', '').hide();
          },
          showItem: function(m, init) {
            m.get('target').show();
            m.get('link').hide();
            return m.get('target').find('a[data-formloader-hidefield]').hide();
          }
        })
      })
    });
  });
  checkValue = function($item) {
    if (_.indexOf(['dropdown', 'checkboxes', 'radios'], $item != null ? $item.attr('value') : void 0) !== -1) {
      return $("[data-formloader-optsettings]").show();
    } else {
      return $("[data-formloader-optsettings]").hide();
    }
  };
  $("[data-formloader-fieldtype]").on("change", function(e) {
    return checkValue($(this));
  });
  checkValue($("[data-formloader-fieldtype]"));
  $("[data-formloader-actions]").on("click", "button", function(e) {
    var $form, formloader_alerts, type,
      _this = this;
    type = $(e.target).attr('value');
    $form = $(this).closest('form');
    $form.append('<div id="formloader_appended_output"/>');
    $("#formloader_appended_output").append("<input id='formloader_appended_submit' type='hidden' name='submit' value='" + type + "' />");
    toggleAppend();
    formloader_alerts = function(data) {
      data = $.parseJSON(data);
      $("#formloader_appended_output").remove();
      toggleAppend();
      $form.prepend(Formloader.Templates.Alert({
        type: data.type,
        message: data.message
      }));
      window.scrollTo(0, 50);
      return setTimeout(function() {
        return $(".alert").fadeOut();
      }, 5000);
    };
    _.each(Formloader.Views, function(view) {
      if (_.indexOf(Formloader.Vars.Use, view.collection.type) !== -1) {
        view.collection.each(function(item) {
          var stringSave;
          if (item.get('selected') === true && item.get('bound') === false) {
            stringSave = ("" + (JSON.stringify(item))).replace(/\'/, "&#39;");
            return $("#formloader_appended_output").append("<input type='hidden' name='tagit_" + view.collection.type + "[]' value='" + stringSave + "' />");
          }
        });
      }
      return null;
    });
    if (type === "Preview") {
      return $.post("" + Formloader.UrlBase + "/api/preview/" + Formloader.Uri[1], $form.serialize()).success(function(data) {
        var PreviewView;
        PreviewView = Backbone.View.extend({
          events: {
            "click": function(e) {
              if ($(e.target).is('button') || $(e.target).is('submit')) {
                return e.preventDefault();
              }
            }
          },
          render: function(form) {
            var _this = this;
            this.$el.html(Formloader.Templates.Modal({
              form: form,
              title: "Preview"
            }));
            this.$el.modal('show');
            this.$el.on('hidden', function() {
              return _this.remove();
            });
            return toggleAppend();
          }
        });
        new PreviewView().render(data);
        return $("#formloader_appended_output").remove();
      }).error(formloader_alerts);
    }
  });
  return null;
});
