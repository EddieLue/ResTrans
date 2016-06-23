/** 导航 */
define(["backbone", "notifications", "message"],
       function (Backbone, Notifications, MessageDialogView) {

  var Draft = Backbone.Model.extend({
    idAttribute: "hash",
  });

  var DraftView = Backbone.View.extend({
    events: {
      "click .open": "open",
      "click .delete": "delete"
    },

    open: function (e) {
      e && e.preventDefault();
      this.$("button").attr("disabled", true);
      $(e.target).text("准备工作台···");
      location.href = _s["SYS_CFG.site_url"] + "worktable/" + this.model.get("hash");
    },

    delete: function (e) {
      e && e.preventDefault();
      this.$("button").attr("disabled", true);
      $(e.target).text("删除记录···");
      this.model.destroy({
        success: function (m, resp) {
          if (resp && resp.status_short === "record_deleted") {
            $(e.target).text("记录已删除");
            this.$el.css("opacity", ".5");
          }
        },
        error: function (m, resp) {
          this.$("button").removeAttr("disabled");
          $(e.target).text("删除");
          _s.reqErrorHandle(resp, "删除工作台记录失败：", null, true, "warning", Notifications);
        },
        context: this
      });
    },

    template: _.template($("#template-draft").html()),

    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var Drafts = Backbone.Collection.extend({
    model: Draft,
    url: _s["SYS_CFG.site_uri"] + "draft/",
    fetched: false,
    fetching: false
  });

  var WorkingSetsView = Backbone.View.extend({
    el: ".dropdown-workingset",

    initialize: function () {
      this.collection = new Drafts();
      this.listenTo(this.collection, "sync", this.render);
    },

    show: function (e) {
      e && e.preventDefault();
      if (this.collection.fetched || this.collection.fetching) return;
      this.$(".load-drafts").removeClass("hidden");
      this.collection.fetch({
        error: function (c) {
          this.collection.fetching = false;
          this.collection.fetched = true;
          this.$(".load-drafts").addClass("hidden");
          this.$(".no-drafts").removeClass("hidden");
        },
        context: this
      });
    },

    render: function (collection) {
      collection.fetching = false;
      collection.fetched = true;
      this.$(".load-drafts").addClass("hidden");
      if (!collection.size()) {
        this.$(".load-drafts").addClass("hidden");
        this.$(".no-drafts").removeClass("hidden");
      }
      var $draftList = this.$(".draft-list");
      collection.each(function (model) {
        if ($draftList.find("li.draft[data-draft-hash=" + model.id + "]").length) return;
        var view = new DraftView({model: model});
        $draftList.append(view.render());
        view.setElement($draftList.find("li.draft[data-draft-hash=" + model.id + "]"));
      }, this);
    }
  });

  var SystemOrganizationNotificationCtl = Backbone.Model.extend({
    idAttribute: "notification_id",
    url: _s["SYS_CFG.site_uri"] + "notification/organization/"
  });

  var SystemNotification = Backbone.Model.extend({
    idAttribute: "notification_id"
  });

  var SystemNotificationView = Backbone.View.extend({
    events: {
      "click .operation-organization-accept": "orgAccept",
      "click .operation-ignore": "orgIgnore",
      "click .operation-reject": "orgReject",
      "click .operation-delete": "destroyNotification"
    },

    initialize: function () {
      this.orgCtl = new SystemOrganizationNotificationCtl({
        notification_id: this.model.id,
        new_status: this.model.get("status")
      });
    },

    orgAccept: function (e) {
      e && e.preventDefault();
      this._snCtl(this.orgCtl, 2, this._organizationNotificationUpdateSucceed);
    },

    orgReject: function (e) {
      e && e.preventDefault();
      this._snCtl(this.orgCtl, 3, this._organizationNotificationUpdateSucceed);
    },

    orgIgnore: function (e) {
      e && e.preventDefault();
      this._snCtl(this.orgCtl, 4, this._organizationNotificationUpdateSucceed);
    },

    _organizationNotificationUpdateSucceed: function (m, resp) {
        if (resp && resp.status_short === "notification_updated") {
          this.$("button").removeAttr("disabled");
          this.$("button").remove();
          var status = m.get("new_status"), statusText = "已同意";
          statusText = (status === 3) ? "已拒绝" : statusText;
          statusText = (status === 4) ? "已忽略" : statusText;
          var append = '<span class="operation-status">' + statusText + '</span> ';
              append += '<button class="re-button re-button-xs operation-delete">清除此条</button>';
          this.$(".actions").append(append);
        }
    },

    _snCtl: function (ctl, newStatus, successCallback) {
      this.$("button").attr("disabled", true);
      ctl.save({
        new_status: newStatus
      }, {
        success: successCallback,
        merge: true,
        context: this
      });
    },

    destroyNotification: function (e) {
      e && e.preventDefault();
      this.$("button").attr("disabled", true);
      var url = this.orgCtl.url;
      this.orgCtl.url = url + this.orgCtl.id;
      this.orgCtl.destroy({
        success: function () {
          this.$("button").text("提醒已清除");
          this.$el.css("opacity", ".5");
        },
        error: function (m, resp) {
          _s.reqErrorHandle(resp, "清除提醒失败：", null, true, "warning", Notifications);
        },
        context: this
      });
      this.orgCtl.url = url;
    },

    template: _.template($("#template-sys-notification").html()),

    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var SystemOrganizationNotifications = Backbone.Collection.extend({
    model: SystemNotification,
    url: _s["SYS_CFG.site_uri"] + "notification/organization/",
    fetched: false
  });

  var SystemOrganizationNotificationsView = Backbone.View.extend({
    el: ".dropdown-notifications",

    events: {
      "click .load-notifications": "loadNotifications"
    },

    initialize: function () {
      this.collection = new SystemOrganizationNotifications();
      this.listenTo(this.collection, "sync", this.render);
    },

    fetching: false,

    show: function (e) {
      e && e.preventDefault();
      if (this.collection.fetched || this.fetching) return;
      this.fetching = true;
      this.collection.fetch({
        data: {
          "start": 1
        },
        error: function () {
          this.fetching = false;
        }
      });
    },

    loadNotifications: function (e) {
      e && e.preventDefault();
      if (this.fetching) return;
      this.$(".load-notifications").text("正在加载提醒···");
      this.fetching = true;
      this.collection.fetch({
        data: {"start": this.sum + 20},
        error: function () {
          this.fetching = false;
        }
      });
    },

    sum: 0,

    render: function (c, resp) {
      this.collection.fetched = true;
      this.fetching = false;
      if (this.collection.size() < 20 || resp.length < 20) {
        this.$(".load-notifications").addClass("hidden");
        this.$(".no-notifications").removeClass("hidden");
      } else {
        this.$(".load-notifications").text("加载更多提醒");
      }
      this.collection.each(function (model) {
        var selector = ".notification[data-sys-notification-id=" + model.id + "]";
        if (this.$(selector).length) return;
        var view = new SystemNotificationView({model: model});
        this.$(".system-notifications").append(view.render());
        view.setElement(this.$(selector));
        this.sum++;
      }, this);
    }
  });

  var NavBar = Backbone.View.extend( {
    el: ".nav-bar",

    events: {
      "click a#create-task": "showCreateTaskDialog",
      "click a#create-organization": "showCreateOrganizationDialog",
      "click #show-message": "attachMessageDialogView",
      "click #logout": "logout",
      "click .dropdown>a": "showDropdown",
      "click .dropdown-container a": "showDropdownContainer"
    },

    initialize: function (opts) {
      // 实例化私信视图
      this.messageDialogView = new MessageDialogView();
      // 获得来自外部的创建任务/组织窗口视图
      this.createDialogView = opts.createDialogView;
      this.WorkingSetsView = new WorkingSetsView();
      this.systemOrganizationNotifications = new SystemOrganizationNotificationsView();
    },

    attachMessageDialogView: function (e) {
      $(e.target).find("i.red-point").addClass("hidden");
      this.messageDialogView.displayConversationsDialog(e);
    },

    showCreateTaskDialog: function ( e ) {
      e.preventDefault();
      this.createDialogView.displayCreateDialog("Task");
    },

    showCreateOrganizationDialog: function( e ) {
      e.preventDefault();
      this.createDialogView.displayCreateDialog("Organization");
    },

    hideCreateDialog: function ( e ) {
      e.preventDefault();
      this._resetCreateDiagStyle();
    },

    _closeAllDropdownContainer: function () {
      this.$(".container-inner").hide();
      this.$(".dropdown-container a i.down-arrow").removeClass("down-arrow-on");
    },

    _closeAllDropdownList: function () {
      this.$(".dropdown-list").hide();
      this.$(".dropdown a i.down-arrow").removeClass("down-arrow-on");
    },

    showDropdown: function (e) {
      e && e.preventDefault();
      e.stopPropagation();
      var _c = this._closeAllDropdownList;
      _c();
      this._closeAllDropdownContainer();
      $target = $(e.target);
      $parents = $target.parents(".dropdown");
      $parents.find("i.down-arrow").addClass("down-arrow-on");
      $parents.children("a").find(".red-point").addClass("hidden");
      $parents.find(".dropdown-list").show();
      $("body").one("click", (function () {
        var that = this;
        return function () {
          _c.call(that);
        };
      }).call(this));
    },

    _show: function ($container) {
      if ($container[0] === this.$(".dropdown-notifications")[0]) {
        this.systemOrganizationNotifications.show(undefined);
      } else if ($container[0] === this.$(".dropdown-workingset")[0]) {
        this.WorkingSetsView.show(undefined);
      }
    },

    showDropdownContainer: function (e) {
      var _c = this._closeAllDropdownContainer;
      _c();
      this._closeAllDropdownList();
      e.stopPropagation();
      $target = $(e.target);
      $container = $target.parents(".dropdown-container");
      $container.find("i.down-arrow").addClass("down-arrow-on");
      $containerInner = $container.find(".container-inner");
      $containerInner.show();
      this._show($container);
      $("body").on("click", function (e) {
        if ($.contains($containerInner[0], e.target)) return false;
        _c();
      });
    },

    logout: function (e) {
      e && e.preventDefault();
      $.ajax( _s["SYS_CFG.site_url"] + "user/logout/", {
        method: "POST",
        data: {
          "token": _s["APP.token"]
        },
        dataType: "json",
        success: function (data) {
          if (data && data.status_short === "logout_successful") {
            location.reload();
          }
        }
      });
    }
  } );

  var Organization = Backbone.Model.extend( {
    urlRoot: _s["SYS_CFG.site_uri"] + "organization",

    validate: function ( attrs, options ) {
      if ( $.trim( attrs.name ) === "") return "data_error";
    }
  } );

  var Task = Backbone.Model.extend({
    urlRoot: _s["SYS_CFG.site_uri"] + "task",

    validate: function (attrs, options) {
      if ( attrs.organization_id === "" ||
             $.trim( attrs.name ) === "" ) {
        return "data_error";
      }
    }
  });

  var CreateDialogView = Backbone.View.extend( {

    el: "#create-diag",

    events: {
      "click #create-organization-action": "createOrganization",
      "click #create-task-action": "createTask",
      "click .list-item a": "setCurrentOrganization",
      "click #close-diag": "closeCreateDialog",
      "click #create-diag-navtsk>a": "displayCreateTaskDialog",
      "click #create-diag-navorg>a": "displayCreateOrganizationDialog",
      "click .current-organization .undo": "undoCurrentOrganization"
    },

    initialize: function (opts) {
      this.organizationModel = opts.organizationModel;
      this.taskModel = opts.taskModel;
      this.$organizationName =  this.$("#organization-name"); // 组织名称
      this.$organizationDescription =  this.$("#organization-description"); // 组织描述

      this.$createOrganizationAction = $("#create-organization-action");
      this.$createTaskAction = $("#create-task-action");

      this.$taskBelong = this.$("#task-organization");
      this.$taskName = this.$("#task-name");
      this.$taskDescription = this.$("#task-description");

      this.$searchOrganizationDropdownList = this.$(".dropdown-select-list");
    },

    _titleSwitch: function (switchTo) {
      var l = {task: "#create-diag-navtsk", organization: "#create-diag-navorg"};
      _.forIn(l, function (val, key) {
        switchTo === key ? $(val).addClass("active") : $(val).removeClass("active");
      });
    },

    _formSwitch: function (form) {
      var formList = {"task": "#create-task-form", "organization": "#create-organization-form"};
      var buttonList = {"task": "#create-task-action", "organization": "#create-organization-action"};
      _.forIn(formList, function (val, key) {
        form === key ?
        (function() {
          $(val).css("display", "block");
          $(buttonList[key]).css("display", "inline-block");
        })() :
        (function() {
          $(val).css("display", "none");
          $(buttonList[key]).css("display", "none");
        })() ;
      });
    },

    displayCreateTaskDialog: function (e) {
      e.preventDefault();
      this.displayCreateDialog("Task");
    },

    displayCreateOrganizationDialog: function (e) {
      e.preventDefault();
      this.displayCreateDialog("Organization");
    },

    displayCreateDialog: function (dialog) {
      return this["_displayCreate"+dialog+"Dialog"]();
    },

    _displayCreateTaskDialog: function () {
      this.$el.css("display", "table");
      this._formSwitch("task");
      this._titleSwitch("task");
    },

    _displayCreateOrganizationDialog: function () {
      this.$el.css("display", "table");
      this._formSwitch("organization");
      this._titleSwitch("organization");
    },

    closeCreateDialog: function (e) {
      this.$el.css("display", "none");
    },

    createOrganization: function (e) {
      this.$createOrganizationAction.attr("disabled", "");
      this.$createOrganizationAction.text( "正在创建…" );
      this.organizationModel.clear();
      this.organizationModel.set({
        name: this.$organizationName.val(),
        description: this.$organizationDescription.val(),
        token: _s["APP.token"]
      });

      this.listenToOnce( this.organizationModel, "invalid", function () {
        if ( this.organizationModel.validationError === "data_error" ) {
          Notifications.push( "创建组织的必要字段必须填写完整。", "warning" );
          this.$createOrganizationAction.removeAttr("disabled");
          this.$createOrganizationAction.text( "创建组织" );
        }
      } );

      var saveSuccess = function ( model, data ) {
        if ( "create_organization_succeed" === data.status_short && _.has( data, "url" ) ) {
          window.location.href = data.url;
        }
      };

      var saveError = function ( model, resp ) {
        var data = resp.responseJSON;
        if (_.has( data, "status_detail" ) ) Notifications.push( data.status_detail, "warning" );
        if (_.has( data, "token" ) && "" !== data.token ) {

          this.organizationModel.set( { "token": data.token } );
          _s["APP.token"] = data.token;
        }

        this.$createOrganizationAction.removeAttr( "disabled" );
        this.$createOrganizationAction.text( "创建组织" );
      };

      this.organizationModel.save( null, {
        success: saveSuccess,
        error: saveError,
        context: this
      } );
      e.preventDefault();
    },

    createTask: function (e) {
      var resetAction = function () {
        this.$createTaskAction.removeAttr("disabled");
        this.$createTaskAction.text("创建任务");
      };
      this.$createTaskAction.attr("disabled", "");
      this.$createTaskAction.text("正在创建···");
      this.taskModel.clear();
      this.taskModel.set({
        organization_id: this.$taskBelong.val(),
        name: this.$taskName.val(),
        description: this.$taskDescription.val(),
        token: _s["APP.token"]
      }, {validate: true});

      if ( this.taskModel.validationError === "data_error" ) {
        Notifications.push("创建任务的必要字段必须填写完整。", "warning");
        resetAction.call(this);
      }

      var success = function (m, data) {
        if ( "create_task_succeed" === data.status_short && _.has( data, "url" ) ) {
          window.location.href = data.url;
        }
      };

      var error = function (m, resp) {
        var data = resp.responseJSON;
        if ( _.has( data, "status_detail" ) ) Notifications.push( data.status_detail, "warning" );

        if ( _.has( data, "token" ) && "" !== data.token ) {
          this.taskModel.set( { "token": data.token } );
          _s["APP.token"] = data.token;
        }
        resetAction.call(this);
      };

      this.taskModel.save(null, {
        success: success,
        error: error,
        context: this
      });
    }
  } );

  var createDialogView = new CreateDialogView( {
        organizationModel: new Organization(),
        taskModel: new Task()
      } ),
      navbar = new NavBar({createDialogView: createDialogView});
} );