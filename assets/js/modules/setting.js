define( [ "backbone", "notifications" ], function ( Backbone, Notifications ) {
  var empt = function () {
    return {};
  };

  var GlobalCommon = Backbone.Model.extend({
    url: _s["SYS_CFG.site_uri"] + "setting/global/common/"
  });

  var GlobalRegister = Backbone.Model.extend({
    url: _s["SYS_CFG.site_uri"] + "setting/global/register/"
  });

  var Task = Backbone.Model.extend({
    idAttribute: "task_id"
  });

  var TaskView = Backbone.View.extend({
    template: _.template($("#template-task").html()),
    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var Tasks = Backbone.Collection.extend({
    model: Task,
    url: _s["SYS_CFG.site_uri"] + "setting/global/task/"
  });

  var Organization = Backbone.Model.extend({
    idAttribute: "organization_id"
  });

  var OrganizationView = Backbone.View.extend({
    template: _.template($("#template-organization").html()),
    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var Organizations = Backbone.Collection.extend({
    model: Organization,
    url: _s["SYS_CFG.site_uri"] + "setting/global/organization/"
  });

  var User = Backbone.Model.extend({
    idAttribute: "user_id"
  });

  var UserView = Backbone.View.extend({
    template: _.template($("#template-user").html()),
    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var Users = Backbone.Collection.extend({
    model: User,
    url: _s["SYS_CFG.site_uri"] + "setting/global/user/"
  });

  var Session = Backbone.Model.extend({
    url: _s["SYS_CFG.site_uri"] + "setting/personal/session/",
    initialize: function () {
      this.url += this.id + "/" + this.get("expire");
    },
    idAttribute: "session_id"
  });

  var Sessions = Backbone.Collection.extend({
    model: Session
  });

  var ProfileSetting = Backbone.Model.extend({
    url: _s["SYS_CFG.site_uri"] + "setting/personal/profile/",
    parse: empt
  });

  var CommonSetting = Backbone.Model.extend({
    url: _s["SYS_CFG.site_uri"] + "setting/personal/common/",
    parse: empt
  });

  var SecuritySetting = Backbone.Model.extend({
    url: _s["SYS_CFG.site_uri"] + "setting/personal/security/"
  });

  var PersonalProfileSetting = Backbone.View.extend({
    el: ".setting-personal-profile",
    events: {
      "submit .re-form": "saveSetting"
    },

    saveSetting: function (e) {
      e && e.preventDefault();
      $(e.target).find("button[type=submit]").attr("disabled", true);
      this.model.save({
        "gender": +this.$("input[name=user-gender]").filter(":checked").val(),
        "public_email": +this.$("input[name=public-email]").is(":checked"),
        "token": _s["APP.token"]
      }, {
        context: this,
        success: function (m, resp) {
          if (resp && resp.status_short === "profile_settings_saved" && resp.token) {
            _s["APP.token"] = resp.token;
            Notifications.push("资料设置已保存。", "success");
          }
        },
        error: function (m ,resp) {
          _s.reqErrorHandle(resp, "保存设置失败：", null, true, "warning", Notifications);
          _s["APP.token"] = resp.responseJSON.token;
        },
        complete: function () {
          $(e.target).find("button[type=submit]").removeAttr("disabled");
        }
      });
    }
  });

  var PersonalCommonSetting = Backbone.View.extend({
    el: ".setting-personal-common",
    events: {
      "submit .re-form": "saveSetting"
    },

    saveSetting: function (e) {
      e && e.preventDefault();
      $(e.target).find("button[type=submit]").attr("disabled", true);
      this.model.save({
        "receive_message": +this.$("input[name=receive-message]").filter(":checked").val(),
        "token": _s["APP.token"]
      }, {
        context: this,
        success: function (m, resp) {
          if (resp && resp.status_short === "common_settings_saved" && resp.token) {
            _s["APP.token"] = resp.token;
            Notifications.push("通用设置已保存。", "success");
          }
        },
        error: function (m ,resp) {
          _s.reqErrorHandle(resp, "保存设置失败：", null, true, "warning", Notifications);
          _s["APP.token"] = resp.responseJSON.token;
        },
        complete: function () {
          $(e.target).find("button[type=submit]").removeAttr("disabled");
        }
      });
    }
  });

  var PersonalSecuritySetting = Backbone.View.extend({
    el: ".setting-personal-security",
    events: {
      "submit .re-form": "saveSetting",
      "click .show-new-password": "toggleNewPassword",
      "click .show-old-password": "toggleOldPassword"
    },

    togglePasswordInput: function ($button, $target) {
      if ($target.attr("type") === "password") {
        $target.attr("type", "text");
        $button.text("隐藏密码");
      } else if ($target.attr("type") === "text") {
        $target.attr("type", "password");
        $button.text("显示密码");
      }
    },

    toggleNewPassword: function (e) {
      e && e.preventDefault();
      this.togglePasswordInput($(e.target), this.$("input[name=new-password]"));
    },

    toggleOldPassword: function (e) {
      e && e.preventDefault();
      this.togglePasswordInput($(e.target), this.$("input[name=old-password]"));
    },

    saveSetting: function (e) {
      e && e.preventDefault();
      $(e.target).find("button[type=submit]").attr("disabled", true);
      this.model.save({
        "old_password": this.$("input[name=old-password]").val(),
        "new_password": this.$("input[name=new-password]").val(),
        "token": _s["APP.token"]
      }, {
        context: this,
        success: function (m, resp) {
          if (resp && resp.status_short === "security_settings_saved" && resp.token) {
            _s["APP.token"] = resp.token;
            Notifications.push("通用设置已保存。", "success");
          }
        },
        error: function (m ,resp) {
          _s.reqErrorHandle(resp, "保存设置失败：", null, true, "warning", Notifications);
          _s["APP.token"] = resp.responseJSON.token;
        },
        complete: function () {
          $(e.target).find("button[type=submit]").removeAttr("disabled");
        }
      });
    }
  });

  var PersonalSessionSetting = Backbone.View.extend({
    el: ".setting-personal-session",
    events: {
      "click .remove": "removeSession"
    },

    removeSession: function (e) {
      e && e.preventDefault();
      var $target = $(e.target);
      $target.attr("disabled", true);
      var sessionId = $target.parents("tr").data("session-id"),
          model = this.collection.get(sessionId);
      model.destroy({
        context: this,
        success: function (m, resp) {
          if (resp && resp.status_short === "session_deleted") {
            $target.parents("tr").remove();
          }
        },
        error: function (m, resp) {
          _s.reqErrorHandle(resp, "删除会话失败：", null, true, "warning", Notifications);
        },
        complete: function () {
          $target.removeAttr("disabled");
        }
      });
    }
  });

  var GlobalUserSetting = Backbone.View.extend({
    el: ".setting-global-user",
    events: {
      "click .load-user": "loadUser",
      "click .load-more": "loadUser"
    },

    initialize: function () {
      this.listenTo(this.collection, "sync", this.render);
    },

    fetchBlocked: 0,

    loadUser: function (e) {
      $(e.target).attr("disabled", true);
      this.collection.fetch({
        data: {
          "start": this.collection.size() + 1,
          "amount": 20,
          "blocked": this.fetchBlocked
        },
        remove: false,
        context: this,
        error: function (m, resp) {
          if (resp && resp.responseJSON.status_short === "no_users") {
            return this.$("tfoot").addClass("hidden");
          }
          _s.reqErrorHandle(resp, "加载失败：", null, true, "warning", Notifications);
        },
        complete: function () {
          if (!this.collection.size()) return $(e.target).text("没有找到用户");
          $(e.target).removeAttr("disabled");
        }
      });
    },

    render: function () {
      if (this.collection.size()) {
        this.$(".re-table").removeClass("hidden");
        this.$(".load-user").addClass("hidden");
      }
      this.collection.each(function (model) {
        if (this.$("tr[data-user-id=" + model.id + "]").length) return;
        this.$("tbody").append((new UserView({model: model})).render());
      }, this);
    }
  });

  var GlobalBlockedUserSetting = GlobalUserSetting.extend({
    el: ".setting-global-blocked-user",
    fetchBlocked: 1
  });

  var GlobalOrganizationSetting = Backbone.View.extend({
    el: ".setting-global-organization",
    events: {
      "click .load-organization": "loadOrganization",
      "click .load-more": "loadOrganization"
    },

    initialize: function () {
      this.listenTo(this.collection, "sync", this.render);
    },

    loadOrganization: function (e) {
      $(e.target).attr("disabled", true);
      this.collection.fetch({
        data: {
          "start": this.collection.size() + 1,
          "amount": 20,
        },
        remove: false,
        context: this,
        error: function (m, resp) {
          if (resp && resp.responseJSON.status_short === "no_organizations") {
            return this.$("tfoot").addClass("hidden");
          }
          _s.reqErrorHandle(resp, "加载失败：", null, true, "warning", Notifications);
        },
        complete: function () {
          if (!this.collection.size()) return $(e.target).text("没有找到组织");
          $(e.target).removeAttr("disabled");
        }
      });
    },

    render: function () {
      if (this.collection.size()) {
        this.$(".re-table").removeClass("hidden");
        this.$(".load-organization").addClass("hidden");
      }
      this.collection.each(function (model) {
        if (this.$("tr[data-organization-id=" + model.id + "]").length) return;
        this.$("tbody").append((new OrganizationView({model: model})).render());
      }, this);
    }
  });

  var GlobalTaskSetting = Backbone.View.extend({
    el: ".setting-global-task",
    events: {
      "click .load-task": "loadTask",
      "click .load-more": "loadTask"
    },

    initialize: function () {
      console.log(this);
      this.listenTo(this.collection, "sync", this.render);
    },

    loadTask: function (e) {
      $(e.target).attr("disabled", true);
      this.collection.fetch({
        data: {
          "start": this.collection.size() + 1,
          "amount": 20,
        },
        remove: false,
        context: this,
        error: function (m, resp) {
          if (resp && resp.responseJSON.status_short === "no_tasks") {
            return this.$("tfoot").addClass("hidden");
          }
          _s.reqErrorHandle(resp, "加载失败：", null, true, "warning", Notifications);
        },
        complete: function () {
          if (!this.collection.size()) return $(e.target).text("没有找到任务");
          $(e.target).removeAttr("disabled");
        }
      });
    },

    render: function () {
      if (this.collection.size()) {
        this.$(".re-table").removeClass("hidden");
        this.$(".load-task").addClass("hidden");
      }
      this.collection.each(function (model) {
        if (this.$("tr[data-task-id=" + model.id + "]").length) return;
        this.$("tbody").append((new TaskView({model: model})).render());
      }, this);
    }
  });

  var GlobalCommonSetting = Backbone.View.extend({
    el: ".setting-global-common",
    events: {
      "submit form": "saveCommon"
    },

    saveCommon: function (e) {
      e && e.preventDefault();
      $(e.target).find("button[type=submit]").attr("disabled", true);
      this.model.save({
        "login_captcha": +this.$("input[name=login-captcha]").is(":checked"),
        "anonymous_access": +this.$("input[name=anonymous-access]").is(":checked"),
        "member_create_organization": +this.$("input[name=member-create-organization]").is(":checked"),
        "token": _s["APP.token"]
      }, {
        success: function (m, resp) {
          if (resp && resp.status_short === "global_common_settings_saved" && resp.token) {
            _s["APP.token"] = resp.token;
            Notifications.push("通用设置已保存。", "success");
          }
        },
        error: function (m ,resp) {
          _s.reqErrorHandle(resp, "保存设置失败：", null, true, "warning", Notifications);
          _s["APP.token"] = resp.responseJSON.token;
        },
        complete: function () {
          $(e.target).find("button[type=submit]").removeAttr("disabled");
        }
      });
    }
  });

  var GlobalRegisterSetting = Backbone.View.extend({
    el: ".setting-global-register",
    events: {
      "submit form": "saveRegister"
    },

    saveRegister: function (e) {
      e && e.preventDefault();
      $(e.target).find("button[type=submit]").attr("disabled", true);
      this.model.save({
        "register": +this.$("input[name=register]").is(":checked"),
        "send_email": +this.$("input[name=send-email]").is(":checked"),
        "token": _s["APP.token"]
      }, {
        success: function (m, resp) {
          if (resp && resp.status_short === "global_register_settings_saved" && resp.token) {
            _s["APP.token"] = resp.token;
            Notifications.push("注册设置已保存。", "success");
          }
        },
        error: function (m ,resp) {
          _s.reqErrorHandle(resp, "保存设置失败：", null, true, "warning", Notifications);
          _s["APP.token"] = resp.responseJSON.token;
        },
        complete: function () {
          $(e.target).find("button[type=submit]").removeAttr("disabled");
        }
      });
    }
  });

  if (_s["SETTING.page_type"] === 1) {
    new PersonalProfileSetting({
      model: new ProfileSetting({
          "gender": _s["SETTING.gender"],
          "public_email": _s["SETTING.public_email"]
        })
    });
    new PersonalCommonSetting({
      model: new CommonSetting({
          "receive_message": _s["SETTING.receive_message"]
        })
    });
    new PersonalSecuritySetting({
      model: new SecuritySetting()
    });
    new PersonalSessionSetting({
      collection: new Sessions(_s["USER.sessions"])
    });
  } else if (_s["SETTING.page_type"] === 2) {
    new GlobalUserSetting({
      collection: new Users()
    });
    new GlobalBlockedUserSetting({
      collection: new Users()
    });
    new GlobalOrganizationSetting({
      collection: new Organizations()
    });
    new GlobalTaskSetting({
      collection: new Tasks()
    });
    new GlobalCommonSetting({
      model: new GlobalCommon({
        "login_captcha": _s["OPTIONS.login_captcha"],
        "anonymous_access": _s["OPTIONS.anonymous_access"],
        "member_create_organization": _s["OPTIONS.member_create_organization"]
      })
    });
    new GlobalRegisterSetting({
      model: new GlobalRegister({
        "register": _s["OPTIONS.register"],
        "send_email": _s["OPTIONS.send_email"],
      })
    });
  }
});