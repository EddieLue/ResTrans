/** 开始 */
define(["backbone", "notifications"], function (Backbone, Notifications) {
  var UserControlSetting = Backbone.Model.extend({
    url: _s["SYS_CFG.site_uri"] + "profile/" + _s["OTHERSIDE.user_id"] + "/setting/"
  });

  var Organization = Backbone.Model.extend({
    idAttribute: "organization_id",
  });

  var OrganizationView = Backbone.View.extend({
    template: _.template($("#template-profile-organization").html()),

    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var Organizations = Backbone.Collection.extend({
    model: Organization,
    url: _s["SYS_CFG.site_uri"] + "profile/" + _s["OTHERSIDE.user_id"] + "/organization/"
  });

  var UserControlDialog = Backbone.View.extend({
    el: ".user-control",
    events: {
      "click .close": "close",
      "submit .user-control-options": "save"
    },

    show: function () {
      this.$el.removeClass("hidden");
    },

    save: function (e) {
      e && e.preventDefault();
      $(e.target).find(".save").attr("disabled", true);
      this.model.save({
        "blocked": +this.$("input[name=user-status]").is(":checked"),
        "send_message": +this.$("input[name=send-message]").is(":checked"),
        "token": _s["APP.token"]
      }, {
        success: function (m, resp) {
          if (resp && resp.status_short === "user_settings_saved" && resp.token) {
            _s["APP.token"] = resp.token;
            Notifications.push("用户设置已保存。", "success");
          }
        },
        error: function (m, resp) {
          _s.reqErrorHandle(resp, "保存设置失败：", null, true, "warning", Notifications);
          _s["APP.token"] = resp.responseJSON.token;
        },
        complete: function () {
          $(e.target).find(".save").removeAttr("disabled");
        }
      });
    },

    close: function (e) {
      e && e.preventDefault();
      this.$el.addClass("hidden");
    }
  });

  var Profile = Backbone.View.extend({
    el: ".profile-top",
    events: {
      "click .options .open-user-control": "openUserControlDialog"
    },

    initialize: function () {
      this.userControlDialog = new UserControlDialog({
        p: this,
        model: new UserControlSetting({
          "blocked": _s["OTHERSIDE.blocked"],
          "send_message": _s["OTHERSIDE.send_message"]
        })
      });
    },

    openUserControlDialog: function (e) {
      this.userControlDialog.show();
    }
  });

  var ProfileOrganization = Backbone.View.extend({
    el: ".profile-organizations",

    events: {
      "click .load-more button": "loadOrganizations"
    },

    initialize: function () {
      this.organizations = new Organizations(_s["OTHERSIDE.organizations"]);
      this.listenTo(this.organizations, "sync", this.render);
      this.listenTo(this.organizations, "sync", this.refreshLoadingButtonState);
      this.listenTo(this.organizations, "error", this.refreshLoadingButtonState);
    },

    loadOrganizations: function (e) {
      e && e.preventDefault();
      $(e.target).attr("disabled", true);
      this.organizations.fetch({
        data: {
          "start": this.organizations.size() + 1,
          "amount": 20
        },
        context: this,
        error: function (c, resp) {
          _s.reqErrorHandle(resp, "设置失败：", null, true, "warning", Notifications);
        },
        remove: false,
        merge: false
      });
    },

    render: function () {
      this.organizations.each(function (organization) {
        if (this.$("tr[data-organization-id=" + organization.id + "]").length) return;
        this.$("tbody").append((new OrganizationView({model: organization})).render());
      }, this);
    },

    refreshLoadingButtonState: function () {
      this.$(".load-more button").removeAttr("disabled");
      if (this.organizations.size() >= _s["OTHERSIDE.organization_total"]) {
        this.$("tfoot").addClass("hidden");
      }
    }
  });

  new Profile();
  new ProfileOrganization();
});