/** 任务 */
define( [ "backbone", "notifications" ], function ( Backbone, Notifications ) {
  var ApiKeySetting = Backbone.Model.extend({
    idAttribute: "task_id",
    url: function () {
      return _s["SYS_CFG.site_uri"] + "task/" + this.id + "/setting/api/key/";
    }
  });

  var Setting = Backbone.Model.extend({
    urlRoot: _s["SYS_CFG.site_uri"] + "task/" + _s["TASK.task_id"] + "/setting/",
  });

  var WorkTable = Backbone.Model.extend({
    urlRoot: _s["SYS_CFG.site_uri"] + "worktable/",
  });

  var Set = Backbone.Model.extend({
    idAttribute: "set_id",
    urlRoot: _s["SYS_CFG.site_uri"] + "task/" + _s["TASK.task_id"] + "/set/",
    validate: function (attrs) {
      return !$.trim(attrs.name);
    }
  });

  var Sets = Backbone.Collection.extend({
    model: Set
  });

  var CreateSetDialog = Backbone.View.extend({
    el: ".create-set-dialog",

    events: {
      "click .close-dialog": "hideDialog",
      "submit .create-set": "createSet"
    },

    showDialog: function (e) {
      e.preventDefault();
      this.$el.show();
    },

    hideDialog: function (e) {
      this.$el.hide();
    },

    createSet: function (e) {
      e.preventDefault();
      var $target = $(e.target),
          $setName = $target.find(".set-name"),
          $submitButton = $target.find("button[type=submit]"),
          resetButton = function () {
            $submitButton.removeAttr("disabled");
            $submitButton.text("创建集");
          };

      $submitButton.attr("disabled", "");
      $submitButton.text("创建集…");
      this.model.set({ name: $setName.val(), token: _s["APP.token"] }, { validate: true });
      if (this.model.validationError) {
        Notifications.push("请输入文件集的名称。", "warning");
      }

      var success = function (m, data) {
        $submitButton.text("正在完成···");
        if ( "create_set_succeed" === data.status_short && _.has( data, "url" ) ) {
          window.location.href = data.url;
        }
      };

      var error = function (m, resp) {
        var data = resp.responseJSON;
        this.model.set( { token: (_s["APP.token"] = data.token) } );
        if ( _.has( data, "status_detail" ) ) Notifications.push( data.status_detail, "warning" );
        resetButton.call(this);
      };

      this.model.save(null, {
        success: success,
        error: error,
        context: this
      });
    }
  });

  var DeleteSet = Backbone.View.extend({
    el: ".delete-set-dialog",

    events: {
      "click .delete": "delete",
      "click .close": "hide"
    },

    set: undefined,

    delete: function (e) {
      e && e.preventDefault();
      var $target = $(e.target);
      $target.attr("disabled", true);
      if (!this.set) {
        return $target.removeAttr("disabled");
      }

      this.set.destroy({
        context: this,
        success: function (m, resp) {
          if (resp && resp.status_short === "set_deleted" && resp.redirect_url) {
            location.href = resp.redirect_url;
            return $target.text("正在完成···");
          }
          $target.removeAttr("disabled");
        },
        error: function (m, resp) {
          _s.reqErrorHandle(resp, "删除文件集失败：", null, true, "warning", Notifications);
          $target.removeAttr("disabled");
        }
      });
    },

    show: function (e) {
      e && e.preventDefault();
      this.$el.removeClass("hidden");
    },

    hide: function (e) {
      e && e.preventDefault();
      this.$el.addClass("hidden");
    }
  });

  var SetView = Backbone.View.extend({
    el: ".task-right",

    events: {
      "click .secondary-nav .create-set a": "showCreateSetDialog",
      "click .files .open": "open",
      "click .files .delete-set": "deleteSet"
    },

    initialize: function (opts) {
      this.createSetDialog = new CreateSetDialog({model: new Set()});
      this.deleteSetDialog = new DeleteSet();
      this.setCollection = new Sets(_s["TASK.sets"]);
      _s["TASK.current_set"] && 
      (this.currentSet = this.setCollection.get(_s["TASK.current_set"].set_id));
      this.$openingWorktable = $(".open-worktable");
    },

    deleteSet: function (e) {
      this.deleteSetDialog.set = this.currentSet;
      this.deleteSetDialog.show();
    },

    showCreateSetDialog: function (e) {
      this.createSetDialog.showDialog(e);
    },

    open: function (e) {
      e && e.preventDefault();
      this.$openingWorktable.removeClass("hidden");
      var model = new WorkTable();
      model.fetch({
        data: {
          "organization_id": _s["ORG.organization_id"],
          "task_id": _s["TASK.task_id"],
          "set_id": _s["TASK.current_set"].set_id
        },
        context: this,
        success: function (m, resp) {
          console.log(resp);
          if (resp.link) location.href = resp.link;
          else {
            this.$openingWorktable.addClass("hidden");
            Notifications.push("打开工作台失败。");
          }
        }, 
        error: function (m, resp) {
          this.$openingWorktable.addClass("hidden");
          _s.reqErrorHandle(resp, "打开工作台失败：", null, true, "warning", Notifications);
        }
      });
    }
  });

  var DeleteTaskDialog = Backbone.View.extend({
    el: ".delete-task-dialog",

    events: {
      "click .delete": "delete",
      "click .cancel": "hide"
    },

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
      this.task = new (Backbone.Model.extend({
        "idAttribute": "task_id",
        defaults: {
          "task_id": _s["TASK.task_id"]
        },
        urlRoot: _s["SYS_CFG.site_url"] + "task/"
      }));
    },

    delete: function (e) {
      e && e.preventDefault();
      $target = $(e.target);
      $target.attr("disabled", true);
      this.task.destroy({
        context: this,
        success: function (m, resp) {
          $target.removeAttr("disabled");
          if (resp && resp.status_short === "task_deleted" && resp.redirect_url) {
            location.href = resp.redirect_url;
          }
        },
        error: function (m, resp) {
          $target.removeAttr("disabled");
          _s.reqErrorHandle(resp, "删除失败：", null, true, "warning", Notifications);
        }
      });
    },

    hide: function (e) {
      e.preventDefault();
      this.$el.addClass("hidden");
    }
  });

  var SettingView = Backbone.View.extend({
    el: ".task-right",

    events: {
      "submit .general-setting": "saveSetting",
      "click .delete-task .open-delete-dialog": "openDeleteDialog",
      "click .freeze": "freezeTask",
      "click .api-key .regenerate": "regenerateNewKey"
    },

    initialize: function () {
      this.deleteTaskDialog = new DeleteTaskDialog({sv: this});
      this.setting = new Setting({
        "name": _s["TASK.name"],
        "description": _s["TASK.description"],
        "original_language": _s["TASK.original_language"],
        "target_language": _s["TASK.target_language"]
      });
      this.apiKeySetting = new ApiKeySetting({
        "task_id": _s["TASK.task_id"]
      });
      this.listenTo(this.apiKeySetting, "sync", this.renderKey);
      this.freeze = new (Backbone.Model.extend({
        idAttribute: "task_id",
        defaults: {
          "task_id": _s["TASK.task_id"],
          "frozen": _s["TASK.frozen"]
        },
        urlRoot: _s["SYS_CFG.site_uri"] + "task/"
      }));
    },

    saveSetting: function (e) {
      e && e.preventDefault();
      var $target = $(e.target).find("input[type=submit]");
      $target.attr("disabled", true);
      this.setting.save({
        "token": _s["APP.token"],
        "name": this.$("#task-name").val(),
        "description": this.$("#task-desc").val(),
        "original_language": this.$("input[name=task-original-language]").filter(":checked").val(),
        "target_language": this.$("input[name=task-target-language]").filter(":checked").val()
      },
      {
        success: function (m ,resp) {
          $target.removeAttr("disabled");
          Notifications.push("已保存。", "success");
          _s["APP.token"] = resp.token;
          $("title").text(m.get("name") + " / 任务选项 / ResTrans");
          $(".task-left a.task-name").text(m.get("name"));
          $(".task-left p.task-description").text(m.get("description"));
        },
        error: function (m, resp) {
          $target.removeAttr("disabled");
          _s.reqErrorHandle(resp, "保存失败：", null, true, "warning", Notifications);
          _s["APP.token"] = resp.responseJSON.token;
        }
      });
    },

    regenerateNewKey: function (e) {
      e && e.preventDefault();
      $(e.target).attr("disabled", true).text("重新生成···");
      this.apiKeySetting.save(null, {
        context: this,
        remove: false,
        add: false,
        merge: false,
        error: function (m, resp) {
          _s.reqErrorHandle(resp, "冻结失败：", null, true, "warning", Notifications);
        },
        complete: function () {
          $(e.target).removeAttr("disabled").text("重新生成");
        }
      });
    },

    renderKey: function (m, resp) {
      if (resp && resp.status_short === "api_key_updated" && resp.api_key) {
        this.$(".api-key .key").val(resp.api_key);
      }
    },

    openDeleteDialog: function (e) {
      e && e.preventDefault();
      this.deleteTaskDialog.$el.removeClass("hidden");
    },

    freezeTask: function (e) {
      e && e.preventDefault();
      $target = $(e.target), cs = this.freeze.get("frozen");
      $target.attr("disabled", true);
      cs ? $target.text("正在解冻···") : $target.text("正在冻结···");
      this.freeze.save({
        "frozen": !cs
      }, {
        context: this,
        success: function (m, resp) {
          if (resp.hasOwnProperty("frozen")) {
            if (resp.frozen) {
              $target.removeClass("re-button-primary").text("解除冻结");
            } else {
              $target.addClass("re-button-primary").text("冻结此任务");
            }
          }
          $target.removeAttr("disabled");
        },
        error: function (m, resp) {
          $target.removeAttr("disabled");
          _s.reqErrorHandle(resp, "冻结失败：", null, true, "warning", Notifications);
        }
      });
    }
  });

  _s["TASK.page_type"] === 1 && new SetView();
  _s["TASK.page_type"] === 2 && new SettingView();
} );