/** 组织 */
define( [ "backbone", "notifications" ], function ( Backbone, Notifications ) {

  var Settings = Backbone.Model.extend({
    url: _s["SYS_CFG.site_uri"] + "organization/" + _s["ORG.organization_id"] + "/setting/",
    parse: function (){return {};}
  });

  var User = Backbone.Model.extend({
    idAttribute: "user_id",
    defaults: {is_admin: false}
  });

  var UserView  = Backbone.View.extend({
    events: {
      "click .delete": "delete",
      "click .member-detail .common": "switchTranslatePrivilege",
      "click .member-detail .proofread": "switchProofreadPrivilege",
      "click .member-detail .upload": "switchUploadPrivilege",
      "click .member-detail .manage": "switchManagePrivilege"
    },

    initialize: function (opts) {
      this.organizationUsersView = opts.ouv;
      this.listenTo(this.model, "destroy", function () {
        this.$el.remove();
      });
    },

    delete: function (e) {
      e && e.preventDefault();
      this.organizationUsersView.trigger("showRemoveDialog", this);
    },

    switchTranslatePrivilege: function (e) {
      e && e.preventDefault();
      this._switchPrivilege("translate", {translate: +!this.model.get("translate")}, $(e.target));
    },

    switchProofreadPrivilege: function (e) {
      e && e.preventDefault();
      this._switchPrivilege("proofread", {proofread: +!this.model.get("proofread")}, $(e.target));
    },

    switchUploadPrivilege: function (e) {
      e && e.preventDefault();
      this._switchPrivilege("upload", {upload: +!this.model.get("upload")}, $(e.target));
    },

    switchManagePrivilege: function (e) {
      e && e.preventDefault();
      this._switchPrivilege("manage", {manage: +!this.model.get("manage")}, $(e.target));
    },

    switching: false,
    _switchPrivilege: function (type, data, $target) {
      if (this.switching) return;
      this.switching = true;
      $target.removeClass("on").addClass("focus");
      var upState = function () {
        $target.removeClass("focus");
        this.model.get(type) ? $target.addClass("on") : $target.removeClass("focus");
      };
      _.extend(data, {"change_prop": type});
      this.model.save(data, {
        context: this,
        success: function (model, resp) {
          this.switching = false;
          Notifications.push("已更新成员权限。", "success");
          upState.call(this);
        }, 
        error: function (model, resp) {
          this.switching = false;
          _s.reqErrorHandle(resp, "设置失败：", null, true, "warning", Notifications);
          var newData = !data[type];
          data[type] = newData;
          this.model.set(data);
          upState.call(this);
        }
      });
    },

    template: _.template($("#template-user").html()),

    /** 渲染单个用户 */
    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var Users = Backbone.Collection.extend({
    model: User,
    url: _s["SYS_CFG.site_uri"] + "organization/" + _s["ORG.organization_id"] + "/user/"
  });

  var Task = Backbone.Model.extend({
      idAttribute: "task_id"
  });

  var Tasks = Backbone.Collection.extend({
    model: Task,
    url: _s["SYS_CFG.site_uri"] + "organization/" + _s["ORG.organization_id"] + "/task/"
  });

  // 单个回复的模型
  var Comment = Backbone.Model.extend( {
    idAttribute: "comment_id"
  } );
  // 单个回复的视图
  var CommentView = Backbone.View.extend( {

    initialize: function (options) {
      this.organizationId = options.organizationId;
      this.discussionId = options.discussionId;
    },

    resetElement: function () {
      this.setElement();
    },

    template: _.template($("#template-discussion-comment").html()),

    render: function () {
      return this.template(_.extend(this.model.attributes, {"parent_user_name": ""}));
    }

  } );
  // 回复列表集合
  var Comments = Backbone.Collection.extend( {

    model: Comment,

    url: function () {
      url = _s["SYS_CFG.site_uri"] + "organization/" + this.organizationId;
      url += "/discussion/" + this.discussionId;
      url += "/comment/";
      return url;
    },

    initialize: function (models, options) {
      this.discussionId = options.discussionId;
      this.organizationId = options.organizationId;
    },

    parse: function (resp) {
      return resp.data;
    }

  } );
  // 回复列表和回复输入器视图
  var CommentsView = Backbone.View.extend( {

    events: {
      "keyup .comment-writer": "refreshWriterState",
      "keydown .comment-writer": "clearReplyState",
      "submit .send-comment": "sendComment",
      "click .more-comments": "loadComments",
      "click .comments .comment .reply-comment": "replyComment",
      "click .comments .comment .reply a": "scrollToComment",
      "click .comments .comment .delete-comment": "deleteComment"
    },

    resetElement: function () {
      this.setElement("li.discussion[data-discussion-id="+this.discussionId+"] .comment-outside");
      this.$loadCommentsButton = this.$(".more-comments").addClass("hidden");
      this.$commentsList = this.$(".comments");
      if ($(".discussions").data("discussion-only")) {
        // 隐藏加载更多按钮
        this.displayComments("end");
      }
    },

    initialize: function (options) {
      this.discussionId = options.discussionId;
      this.organizationId = options.organizationId;
      this.commentTotal = options.commentTotal;
      this.discussionView = options.dv;
      this.parent = 0;
      this.listenTo(this.collection, "update", function (collection) {
        collection.each(function (model) {

          if ( this.$("li.comment[data-comment-id="+model.id+"]").length ) return;

          // 寻找被回复者的用户名
          var parent = collection.get(model.get("parent_id"));

          ( parent !== undefined ) && model.set({ parent_user_name: parent.get("user_name") });

          this.render( new CommentView({
            model: model,
            organizationId: this.organizationId,
            discussionId: this.discussionId
          }) );
        }, this);
      });
    },

    displayComments: function (step) {
      this.$commentsList.show();
      this.ensureLoadingButton();
      if ( this.$commentsList.has("li.comment").length ) return;
      this._loadComments(step);
    },

    loadComments: function (e) {
      this._loadComments(false);
      e.preventDefault();
    },

    _loadComments: function (step) {
      var step = (!step) ? 20 : step;
      this.collection.fetch({
        data: {
          start: this.collection.size() + 1,
          amount: step
        },
        remove: false,
        merge: false,
        context: this,
        success: function (collection, resp, opts) {
          this.commentTotal = resp.total;
          this.ensureLoadingButton();
        }
      });
    },

    hideComments: function () {
      this.$commentsList.hide();
      this.$loadCommentsButton.addClass("hidden");
    },

    ensureLoadingButton: function () {
      if ( this.commentTotal > this.collection.size() ) {
        return this.$loadCommentsButton.removeClass("hidden");
      }
      this.$loadCommentsButton.addClass("hidden");
    },

    replyComment: function (e) {
      var $target = $(e.target);
      var commentId = $target.parents(".comment").data("comment-id");
      var comment = this.collection.get(commentId);
      var $commentWriter = $target.parents(".comment-outside").find(".comment-writer");

      if (this.parent === 0 || this.parent !== comment.id) {
        this._setReply(comment, $commentWriter);
        return;
      }
      this._unsetReply($commentWriter);
    },

    _setReply: function (comment, $commentWriter) {
      $commentWriter.attr("placeholder", "回复 "+comment.get("user_name")+":");
      this.parent = comment.id;
      $("body").scrollTop($commentWriter.offset().top - 200);
    },

    _unsetReply: function ($commentWriter) {
      $commentWriter.attr("placeholder", "回复此讨论");
      this.parent = 0;
    },

    scrollToComment: function (e) {
      e.preventDefault();
      var $target = $(e.target);
      var $ele = this.$(".comments .comment[data-comment-id="+$target.data("parent-id")+"]");
      $ele.length && $("body").scrollTop($ele.offset().top - 45);
    },

    render: function (commentView) {
      this.$("ul.comments").append(commentView.render());
      commentView.resetElement();
    },

    refreshWriterState: function ( e ) {
      var $target = $(e.target);

      if ($target.val().length ) {
        $target.height(50);
        return $target.siblings(".comment-send").css("display", "inline-block");
      }

      $target.height(20);
      $target.siblings(".comment-send").css("display", "none");
    },

    clearReplyState: function (e) {
      $target = $(e.target);
      if ( e.keyCode === 8 && $target.val() === "" ) this._unsetReply($target);
    },

    sendComment: function ( e ) {
      var $target = $(e.target);
      var $commentWriter = $target.find(".comment-writer");
      var $commentSend = $target.find(".comment-send");
      $commentSend.attr("disabled", "");

      var newComment = new Comment(null, {collection: this.collection});

      newComment.set( { "content": $commentWriter.val(),
                        "parent_id": this.parent,
                        "token": _s["APP.token"] } );

      var postSuccess = function ( model, response, options ) {

        var data = response;
        newComment.unset("token");
        newComment.set({
          comment_id: data.comment_id,
          user_name: _s["USER.name"],
          friendly_time: "不久之前"
        });
        this.discussionView.commentsExpanded || this.discussionView.expandComments(e);
        var ct = this.discussionView.model.get("comment_total");
        this.discussionView.model.set({
          comment_total: ++ct
        });
        this._loadComments("end");
        $commentWriter.val("");
        $commentSend.removeAttr("disabled");

        if ( data.token ) _s["APP.token"] = data.token;
      };

      var postError = function ( model, response, options ) {

        var data = response.responseJSON;
        Notifications.push( data.status_detail, "warning" );

        $commentSend.removeAttr("disabled");

        if ( data.token ) _s["APP.token"] = data.token;
      };

      newComment.save(null, { success: postSuccess, error: postError, context: this });
      e.preventDefault();
    },

    deleteComment: function (e) {
      e && e.preventDefault();
      var $target = $(e.target);
      var commentId = $target.parents(".comment").data("comment-id");
      var comment = this.collection.get(commentId);
      $target.attr("disabled", true).text("正在删除···");
      comment.destroy({
        context: this,
        success: function (m, resp) {
          if (!resp || !resp.status_short === "comment_deleted") return;
          $target.parents(".comment").remove();
          this.discussionView.model.set({
            "comment_total": this.discussionView.model.get("comment_total") - 1
          });
        },
        error: function (m, resp) {
          _s.reqErrorHandle(resp, "删除失败：", null, true, "warning", Notifications);
        }
      });
    }

  } );

  var Discussion = Backbone.Model.extend( {
    idAttribute: "discussion_id"
  } );

  var DiscussionView = Backbone.View.extend({
    events: {
      "click .delete-this": "removeDiscussion",
      "click .expand-comments": "expandComments"
    },

    template: _.template($("#template-discussion").html()),

    resetElement: function () {
      this.setElement('li.discussion[data-discussion-id='+ this.model.id +']');
      this.$expandCommentsButton = this.$(".expand-comments");
      this.commentsExpanded = false;
      this._refreshExpandCommentsButtonState(this.commentsView.collection);
      this.commentsView.resetElement();
    },

    expandComments: function (e) {
      if (this.commentsExpanded) {
        this.commentsView.hideComments();
        this.$expandCommentsButton.text(this.model.get("comment_total")+"条回复");
        this.commentsExpanded = false;
        return;
      }

      this.commentsView.displayComments();
      this.$expandCommentsButton.css("display", "inline-block").text("收起所有");
      this.commentsExpanded = true;
    },

    removeDiscussion: function (e) {
      e && e.preventDefault();
      this.discussionView.deleteDiscussionDialog.setView(this).$el.removeClass("hidden");
    },

    initialize: function (options) {
      var def = {
          discussionId: this.model.id,
          organizationId: options.organizationId,
          commentTotal: this.model.get("comment_total"),
          dv: this
        };
      this.discussionView = options.dv;
      this.commentsView = new CommentsView(_.extend({collection: new Comments(null, def)}, def));
      this.listenTo(this.commentsView.collection, "update", this._refreshExpandCommentsButtonState);
    },

    _refreshExpandCommentsButtonState: function () {
      if (this.model.get("comment_total") > 0) {
        this.$expandCommentsButton.css("display", "inline-block");
        return;
      }
    },

    render: function () {
      return this.template( {
        "discussion_id": this.model.id,
        "organization_id": this.model.get("organization_id"),
        "avatar_link": this.model.get("avatar_link"),
        "user_id": this.model.get("user_id"),
        "user_name": this.model.get("user_name"),
        "content": _.escape(this.model.get("content")).replace(/\n/g, "<br>"),
        "friendly_time": this.model.get("friendly_time"),
        "can_delete": this.model.get("can_delete"),
        "comment_total": this.model.get("comment_total")
      } );
    },
  });

  var Discussions = Backbone.Collection.extend({
    model: Discussion,

    url: function () {
      return _s["SYS_CFG.site_uri"] + "organization/" + this.organizationId + "/discussion/";
    },

    initialize: function (models, options) {
      this.organizationId = options.organizationId;
      this.discussionTotal = options.discussionTotal;
    },

    parse: function (resp, options) {
      return resp.data;
    }
  });

  var DeleteDiscussionDialog = Backbone.View.extend({
    el: ".dialog-delete-discussion",

    events: {
      "click .delete": "delete",
      "click .cancel": "cancel"
    },

    setView: function (view) {
      this.view = view;
      return this;
    },

    delete: function (e) {
      e && e.preventDefault();
      var view = this.view;
      $(e.target).attr("disabled", true).text("正在删除···");
      this.view.model.destroy({
        context: this,
        success: function (m, resp) {
          if (resp && resp.status_short === "discussion_deleted") {
            $(e.target).text("删除完成");
            location.href = _s["SYS_CFG.site_url"] + "organization/" + _s["ORG.organization_id"];
          }
        },
        error: function (m, resp) {
          _s.reqErrorHandle(resp, "删除失败：", null, true, "warning", Notifications);
          $(e.target).removeAttr("disabled").text("删除");
        },
      });
    },

    cancel: function (e) {
      e && e.preventDefault();
      this.$el.addClass("hidden");
    }
  });

  var DiscussionsView = Backbone.View.extend( {
    el: ".organization-discussion",
    events: {
      "submit .send-discuss": "newDiscussion",
      "keyup .discuss-input": "refreshInputCharLength",
      "click .more-discussions": "moreDiscussions"
    },

    initialize: function (options) {
      // 新讨论
      this.$newDiscussion = this.$(".send-discuss");
      this.$disInput = this.$(".discuss-input");
      this.$disCharCount = this.$(".char-count .current");
      this.$disSend = this.$(".action .discuss-send");
      this.$disSend.attr("disabled", "");
      // 讨论列表
      this.$discussions = this.$("ul.discussions");
      this.organizationId = options.organizationId;
      this.discussionTotal = options.discussionTotal;

      this.deleteDiscussionDialog = new DeleteDiscussionDialog();

      this.collection = new Discussions(null, {organizationId: this.organizationId,
                                                discussionTotal: this.discussionTotal});
      // 抓取讨论集合的 reset 事件
      this.listenTo( this.collection, "reset", function (collection) {
        collection.each( function (model) {
          (new DiscussionView({
            model: model,
            organizationId: this.organizationId,
            dv: this
          })).resetElement();
        }, this );

        this._moreDiscussionsButtonRefresh(this.discussionTotal);
      } );

      this.listenTo( this.collection, "sync", function (collection) {
        collection.each( function (model) {
          if ( ! this.$discussions.find('li.discussion[data-discussion-id='+model.id+']').length ) {
            this.render(new DiscussionView({
              model: model,
              organizationId: this.organizationId,
              dv: this
            }));
          }
        }, this );

        this._moreDiscussionsButtonRefresh(this.discussionTotal);
      }, this );

      this.collection.reset(_s["ORG.discussions"]);
      this._moreDiscussionsButtonRefresh(this.discussionTotal);
    },

    _moreDiscussionsButtonRefresh: function ( size ) {
      var $moreDiscussionsButton = $(".more-discussions");
      if ( this.collection.size() >= size ) {
        $moreDiscussionsButton.hide();
        return ;
      }
      $moreDiscussionsButton.show();
    },

    render: function (discussionView) {
      this.$discussions.append(discussionView.render());
      discussionView.resetElement();
    },

    refreshInputCharLength: function ( e ) {

      var charLength = $(e.target).val().length;
      if ( charLength > 500 ) {
        this.$disCharCount.addClass( "exceed" );
        this.$disSend.attr( "disabled", "" );
      } else if ( charLength >= 1 && charLength <= 500 ) {
         this.$disCharCount.removeClass( "exceed" );
         this.$disSend.removeAttr( "disabled" );
      } else {
        this.$disSend.attr( "disabled", "" );
      }
      $( ".char-count .current" ).text( charLength );
      e.preventDefault();
    },

    newDiscussion: function ( e ) {

      this.$disSend.attr( "disabled", "" );
      this.$disSend.text( "正在发表···" );
      var newDiscussion = new Discussion(null, {collection: this.collection});
      newDiscussion.set( { "content": this.$disInput.val(),
                           "token": _s["APP.token"] } );

      var that = this;
      var postSucc = function ( model, response, options ) {
        window.location.reload();
      };

      var postError = function ( model, response, options ) {

        var data = response.responseJSON;

        ( "" !== data.status_detail ) ? Notifications.push( data.status_detail, "warning" ) : "";

        data.token && ( _s["APP.token"] = data.token );

        that.$disSend.removeAttr( "disabled" );
        that.$disSend.text( "发表" );
      };

      newDiscussion.save( null, { success: postSucc, error: postError } );
      e.preventDefault();
    },

    moreDiscussions: function ( e ) {
      var step = 20;
      var a = this.collection.fetch( { data: {
        start: this.collection.size() + 1,
        amount: step
      },
      remove: false,
      merge: false,
      success: function (collection, resp, options) {
        this.discussionTotal = resp.total;
      },
      context: this } );
      e.preventDefault();
    }

  } );

  var ExitOrganizationDialog = Backbone.View.extend({
    el: ".dialog-exit-organization",

    events: {
      "click .exit": "exit",
      "click .cancel": "cancel"
    },

    exit: function (e) {
      e && e.preventDefault();
      var $target = $(e.target);
      $target.attr("disabled", true).text("正在退出···");
      $.ajax(_s["SYS_CFG.site_uri"] + "organization/" + _s["ORG.organization_id"] + "/user/exit/", {
        method: "DELETE",
        dataType: "json",
        context: this,
        success: function (resp) {
          if (resp && resp.status_short === "user_exited") {
            $target.text("已退出");
            location.href = _s["SYS_CFG.site_url"] + "start";
          }
        },
        error: function (resp) {
          _s.reqErrorHandle(resp, "请求退出失败：", null, true, "warning", Notifications);
            $target.text("退出").removeAttr("disabled");
        }
      });
    },

    cancel: function (e) {
      e && e.preventDefault();
      this.$el.addClass("hidden");
    }
  });

  var OrganizationView = Backbone.View.extend( {
    el: "body",
    events: {
      "click .join-organization": "joinOrganization",
      "click .exit-organization": "showExitDialog"
    },

    initialize: function () {
      // 组织详情
      this.$orgDetail = this.$( ".organization-left" );
      this.$sendDiscussionArea = this.$(".send-discuss");
      this.$sendDiscussTextarea = this.$sendDiscussionArea.find(".discuss-input");
      this.$commentWriter = this.$(".comment-writer");
      this.$joinButton = this.$(".join-organization");
      this.organizationId = _s["ORG.organization_id"];
      this.discussionTotal = _s["ORG.discussion_total"];

      this.exitOrganizationDialog = new ExitOrganizationDialog();

      // 其他用户控制
      if ( ! _s["USER.is_login"] ) {
        this.$sendDiscussionArea.css("display", "none");
        this.$joinButton.attr("disabled","").text("登录以加入此组织");
      }

      if ( ! _s["USER.is_member_of_org"] && !_s["USER.is_admin"] ) {
        this.$sendDiscussTextarea.attr( "disabled", "" );
        this.$sendDiscussTextarea.attr( "placeholder", "加入组织以参与讨论" );
        //所有找到的回复输入框均置隐藏
        $(".send-comment .comment-writer").css("display", "none");
      }

      if (_s["ORG.page_type"] === 1) {
        this.discussionsView = new DiscussionsView({
          organizationId: this.organizationId,
          discussionTotal: this.discussionTotal });

        if (this.$(".discussions").data("discussion-only")) {
          // 隐藏加载更多按钮
          this.$(".more-discussions").hide();
        }
      }
    },

    joinOrganization: function (e) {
      e && e.preventDefault();
      $target = $(e.target);
      $.ajax(_s["SYS_CFG.site_uri"] + "organization/" + _s["ORG.organization_id"] + "/user/join/", {
        method: "POST",
        dataType: "json",
        data: {"token": _s["APP.token"]},
        context: this,
        beforeSend: function () {
          $target.text("正在请求···").attr("disabled", true);
        },
        success: function (resp) {
          if (resp && resp.status_short === "join_organization_request_sended" && resp.token) {
            $target.text("已发送加入请求");
            _s["APP.token"] = resp.token;
          } else if (resp && resp.status_short === "join_organization_succeed") {
            location.href = _s["SYS_CFG.site_url"] + "organization/" + _s["ORG.organization_id"];
          }
        },
        error: function (resp) {
          _s.reqErrorHandle(resp, "请求失败：", null, true, "warning", Notifications);
          $target.text("加入").removeAttr("disabled");
          _s["APP.token"] = resp.token;
        }
      });
    },

    showExitDialog: function (e) {
      e && e.preventDefault();
      this.exitOrganizationDialog.$el.removeClass("hidden");
    }
  } );

  var OrganizationTasksView = Backbone.View.extend({
    el: ".organization-tasks",
    events: {
      "click .page-forward": "loadTasks"
    },

    initialize: function () {
      this.collection = new Tasks(_s["ORG.tasks"], {otv: this});
    },

    loadTasks: function (e) {
      e && e.preventDefault();
      if (this.collection.size() >= _s["ORG.member"]) return;
      this.collection.fetch({
        context: this,
        data: {start: this.collection.size() + 1, amount: 20},
        remove: false,
        merge: false,
        success: function (c, resp) {
          this.render();
          this.toggleButtonState();
        },
        error: function (c, resp) {
          var resp = resp.responseJSON;
          if (resp.status_short === "tasks_not_found") {
            _s["ORG.task_total"] = this.collection.size();
            this.toggleButtonState();
          }
        }
      });
    },

    template: _.template($("#template-task").html()),

    render: function () {
      this.collection.each(function (task) {
        if (this.$(".task-summary[data-task-id=" + task.id + "]").length) return;
        this.$("tbody").append(this.template(task.attributes));
      }, this);
    },

    toggleButtonState: function () {
      if (this.collection.size() >= _s["ORG.task_total"]) {
        return this.$("tfoot").addClass("hidden");
      }
      return this.$("tfoot").removeClass("hidden");
    }
  });

  var DeleteUser = Backbone.View.extend({
    el: ".delete-user",

    events: {
      "click .delete": "delete",
      "click .close": "hide"
    },

    user: undefined,

    initialize: function (opts) {
      this.organizationUsersView = opts.ouv;
    },

    delete: function (e) {
      e && e.preventDefault();
      var $target = $(e.target);
      $target.attr("disabled", true);
      this.user.destroy({
        context: this,
        success: function (m ,resp) {
          this.hide(undefined);
          Notifications.push("已移除该成员。", "success");
        },
        error: function (m, resp) {
          _s.reqErrorHandle(resp, "删除失败：", null, true, "warning", Notifications);
          this.hide(undefined);
          this.user = undefined;
        }
      });
    },

    show: function (e, view) {
      e && e.preventDefault();
      this.userView = view;
      this.user = view.model;
      this.$(".name").text(this.user.get("user_name"));
      this.$(".delete").attr("disabled", false);
      this.$el.removeClass("hidden");
    },

    hide: function (e) {
      e && e.preventDefault();
      this.$el.addClass("hidden");
    }
  });

  var OrganizationUsersView = Backbone.View.extend({
    el: ".organization-members",

    events: {
      "click .page-forward": "loadUsers"
    },

    initialize: function () {
      this.collection = new Users(_s["ORG.users"], {ouv: this});
      this.deleteUserDialog = new DeleteUser({ouv: this});
      this.on("showRemoveDialog", function (userView) {
        this.deleteUserDialog.show(undefined, userView);
      }, this);
      this.collection.each(function (user) {
        (new UserView({model: user, ouv: this})).setElement(this.$("tbody tr[data-user-id=" + user.id + "]"));
      }, this);
    },

    loadUsers: function (e) {
      e && e.preventDefault();
      if (this.collection.size() >= _s["ORG.member_total"]) return;
      this.collection.fetch({
        context: this,
        data: {start: this.collection.size() + 1, amount: 20},
        remove: false,
        merge: false,
        success: function (c, resp) {
          this.render();
          this.toggleButtonState();
        },
        error: function (c, resp) {
          var resp = resp.responseJSON;
          if (resp.status_short === "users_not_found") {
            _s["ORG.member_total"] = this.collection.size();
            this.toggleButtonState();
          }
        }
      });
    },

    render: function () {
      this.collection.each(function (user) {
      if (this.$("tbody [data-user-id=" + user.id + "]").length) return;
      var v = new UserView({ouv: this, model: user});
      this.$("tbody").append(v.render());
      v.setElement(this.$("tbody [data-user-id=" + user.id + "]"));
      }, this);
    },

    toggleButtonState: function () {
      if (this.collection.size() >= _s["ORG.member_total"]) {
        return this.$("tfoot").addClass("hidden");
      }
      return this.$("tfoot").removeClass("hidden");
    }
  });

  var OrganizationSettingsView = Backbone.View.extend({
    el: ".organization-right",

    events: {
      "submit .organization-settings": "saveSettings"
    },

    initialize: function () {
      this.settings = new Settings(
      {
        "name": _s["ORG.name"],
        "description": _s["ORG.description"],
        "maximum": _s["ORG.maximum"],
        "join_mode": _s["ORG.join_mode"],
        "accessibility": _s["ORG.accessibility"],
        "default_privileges": _s["ORG.default_privileges"],
        "member_create_task": _s["ORG.member_create_task"],
      }, {osv: this});
    },

    saveSettings: function (e) {
      e.preventDefault();
      var $target = $(e.target);
      $target.find("button[type=submit]").attr("disabled", true);

      var defaultPrivileges = 0;
      if (this.$("input[name=organization-default-privileges-translate]").is(":checked")) {
        defaultPrivileges = 1;
      }
      if (this.$("input[name=organization-default-privileges-proofread]").is(":checked")) {
        defaultPrivileges = 2;
      }
      if (this.$("input[name=organization-default-privileges-translate]").is(":checked") &&
                 this.$("input[name=organization-default-privileges-proofread]").is(":checked")) {
        defaultPrivileges = 3;
      }

      this.settings.set({
        "token": _s["APP.token"],
        "name": this.$("#organization-name").val(),
        "description": this.$("#organization-desc").val(),
        "maximum": +this.$("input[name=organization-member-limit]").filter(":checked").val(),
        "join_mode": this.$("input[name=organization-join-mode]").filter(":checked").val(),
        "accessibility": this.$("input[name=organization-accessibility]").is(":checked"),
        "default_privileges": defaultPrivileges,
        "member_create_task": this.$("input[name=organization-create-task]").is(":checked"),
      });
      this.settings.save(null, {
        success: function (m, resp) {
          $target.find("button[type=submit]").removeAttr("disabled");
          if (resp.status_short === "settings_saved") {
            Notifications.push("已保存。", "success");
            _s["APP.token"] = resp.token;
            $("title").text(m.get("name") + " / 组织成员 / ResTrans");
            $(".organization-left a.organization-name").text(m.get("name"));
            $(".organization-left p.organization-description").html(_s.xescape(m.get("description")));
          }
        }, 
        error: function (m, resp) {
          $target.find("button[type=submit]").removeAttr("disabled");
          _s.reqErrorHandle(resp, "保存失败：", null, true, "warning", Notifications);
        }
      });
    }
  });

  new OrganizationView();
  (_s["ORG.page_type"] === 2) && new OrganizationTasksView();
  (_s["ORG.page_type"] === 3) && new OrganizationUsersView();
  (_s["ORG.page_type"] === 4) && new OrganizationSettingsView();
} );