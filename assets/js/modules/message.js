define(["backbone", "notifications"], function (Backbone, Notifications) {

  var Receiver = Backbone.Model.extend({
    urlRoot: _s["SYS_CFG.site_uri"] + "message/receiver",
    idAttribute: "user_id"
  });

  var ReceiverView = Backbone.View.extend({

    initialize: function ( opts ) {
      this.setElement( opts.$msgvEl.find(".write-message") );
      this.$receiverAvatar = this.$(".receiver");
      this.$searchInput = this.$(".search-receiver-keyword");
      this.$showSearchInput = this.$(".show-search-input");
      this.model ? null : this.model = new Receiver();

      this.listenTo(this.model, "change", function (model) {
        this._ensureState();
        if ( ! model.has("user_id") || ! model.has("name") || ! model.has("avatar_link") ) {
          this.$receiverAvatar.hide();
          this.$searchInput.show();
          return;
        }

        this.$receiverAvatar.find(".username").html(model.escape("name"));
        this.$receiverAvatar.find("img").attr("src", model.get("avatar_link"));
        this.$receiverAvatar.css({"display": "inline-block"});
        this.$searchInput.hide();
      });

      this._ensureState();
    },

    _ensureState: function () {
      this.model.id ? this.$showSearchInput.show() : this.$showSearchInput.hide();
    },

    events: {
      "submit .search-area": "searchReceiver",
      "click .show-search-input": "resetState"
    },

    resetState: function (e) {
      e.preventDefault();
      this.model.clear();
    },

    searchReceiver: function (e) {
      e.preventDefault();
      var model = this.model,
          $target = $(e.target),
          keyword = this.$searchInput.val();
      model.clear();

      model.fetch({
        data:{ "keyword": keyword },
        context: this,
        success: function () {
          this.$searchInput.val("");
        }
      });
    }

  });

  var Message = Backbone.Model.extend({

    idAttribute: "message_id",

    validate: function (attrs) {
      if ( ! attrs.receiver ) {
        return "请输入私信接受方的 ID 或 用户名。";
      }
      if ( ! attrs.content || ! attrs.content.length || attrs.content.length > 1000 ) {
        return "私信内容必须介乎 1-1000 字符之间。";
      }
    }
  });

  var DialogueMessages = Backbone.Collection.extend({
    model: Message,
    url: function () {
      return _s["SYS_CFG.site_uri"] +
             "message/" +
             this.conversationModel.get("owner") +
             "/" +
             this.conversationModel.get("otherside");
    },

    initialize: function (models, opts) {
      this.conversationModel = opts.conversationModel;
    }

  });

  var DialogueMessage = Backbone.View.extend({

    events: {
      "click .msg-options .delete": "deleteMessage"
    },

    resetElement: function () {
      this.setElement(this.$diag.find(".message[data-message-id="+this.model.id+"]"));
    },

    initialize: function (opts) {
      this.$diag = opts.$diag;
      this.messageDeleteConfirm = false;
    },

    ensureTemplate: function () {
      this.template = this.model.get("type") ? 
                        _.template($("#template-myself").html()) :
                        _.template($("#template-otherside").html());
    },

    render: function () {
      this.ensureTemplate();
      var type = this.model.get("type"),
          unread = !!(!type && this.model.get("unread"));
      return this.template({
        message_id: this.model.id,
        name: type ? this.model.get("owner_name") : this.model.get("otherside_name"),
        owner: this.model.get("owner"),
        otherside: this.model.get("otherside"),
        owner_avatar_link: this.model.get("owner_avatar_link"),
        otherside_avatar_link: this.model.get("otherside_avatar_link"),
        content: this.model.get("content"),
        friendly_time: this.model.get("friendly_time"),
        unread: unread
      });
    },

    messageDeleteConfirm: false,

    deleteMessage: function (e) {
      e.preventDefault();
      var $target = $(e.target);
      if ( ! this.messageDeleteConfirm ) {
        $target.text("再按一次以删除");
        this.messageDeleteConfirm = true;
        return;
      }
      this.model.destroy({
        success: function () {
          this.remove();
        },
        error: function (model, resp) {
          var resp = resp.responseJSON;
          Notifications.push(resp.status_detail, "warning");
        },
        context: this
      });
    }

  });

  var DialogueMessagesView = Backbone.View.extend({
    events: {
      "click .more-messages a": "moreMessages"
    },

    initialize: function (opts) {
      this.conversationModel = opts.conversationModel;

      this.collection = new DialogueMessages(null, {conversationModel: opts.conversationModel});

      this.on("resetNewMessages", function (collection) {
        this.insertMessages(collection);
        this._scroll();
      }, this);

      this.on("setNewMessages", function (collection){
        this.insertMessages(collection);
      }, this);

      this.on("setMessagesAsRead", function () {
        $.post( _s["SYS_CFG.site_uri"] +
                "message/" +
                this.conversationModel.get("owner") + "/" +
                this.conversationModel.get("otherside") + "/readed/", "", "", "json");
      },this);
    },

    _scroll: function () {
      var $diag = this.$(".diag-messages");
      $diag.scrollTop(this.$(".diag-messages").height());
    },

    insertMessages: function (collection) {
      var $moreMessages = this.$(".more-messages"), $diag = this.$(".diag-messages");
      collection.each(function (model) {
        if ( $diag.find(".message[data-message-id="+model.id+"]").length ) return;
        var view = new DialogueMessage({model: model, $diag: $diag});
        $moreMessages.after(view.render());
        view.resetElement();
      }, this);
    },

    viewMessages: function (isUnread) {
      this._clearMessages();
      if ( this.collection.length ) {
        this.insertMessages(this.collection);
      } else {
        this.resetNewMessages();
      }
      isUnread && this.trigger("setMessagesAsRead");
    },

    _clearMessages: function() {
      var $diag = this.$(".diag-messages");
      $diag.remove(".message");
    },

    resetNewMessages: function () {
      this._switchMoreMessagesActionState("loading");
      this.collection.fetch({
        data: {start: 1, amount: this.reqAmount},
        reset: true,
        context: this,
        success: function (collection, resp) {
          this.trigger("resetNewMessages", collection);
          if ( resp.length < this.reqAmount ) {
            this._switchMoreMessagesActionState("no_messages");
          }
          this._switchMoreMessagesActionState("load");
        },
        error: function () {
          this._switchMoreMessagesActionState("no_messages");
        }
      });
    },

    reqAmount: 20,

    setNewMessages: function (start) {
      this._switchMoreMessagesActionState("loading");
      var start = ( start ? start : this.collection.size() ) + 20;
      this.collection.fetch({
        data: {start: start, amount: this.reqAmount},
        remove: false,
        merge: false,
        context: this,
        success: function (collection, resp) {
          this.trigger("setNewMessages", collection);
          if ( resp.length < this.reqAmount ) {
            this._switchMoreMessagesActionState("no_messages");
          }
          this._switchMoreMessagesActionState("load");
        },
        error: function () {
          this._switchMoreMessagesActionState("no_messages");
        }
      });
    },

    _switchMoreMessagesActionState: function (state) {
      var $moreMessages = this.$(".more-messages");
      if ( state === "load" ) {
        $moreMessages.html("<a href=\"\">加载更早的私信</a>");
      } else if ( state === "loading" ) {
        $moreMessages.html("<span><i class=\"loading\"></i>正在加载</a>");
      } else if ( state === "no_messages" ) {
        $moreMessages.html("<span>没有更多私信</a>");
      } 
    },

    moreMessages: function (e) {
      e.preventDefault();
      this.setNewMessages();
    },

    render: function () {
      var template = _.template($("#template-dialogue").html());
      this.between = this.conversationModel.get("owner") + "-" + this.conversationModel.get("otherside");
      return template({
        "between": this.between
      });
    },

    createDialogue: function () {
      $(".message-dialog .dialog-body").append(this.render());
      this.setElement(".message-dialog .dialog-body .dialogue[data-conversation-between="+this.between+"]");
    },

    replyMessage: function (e) {
      var $input = this.$(".message-writer .content-input"),
      newMessage = new NewMessage({
        receiver: this.conversationModel.get("otherside"),
        content: $input.val(),
        token: _s["APP.token"]
      },
      { validate:true }),
      $target = $(e.target),
      rb = function () {
        $target.html("回复");
      };

      $target.html('<i class="loading"></i>正在回复');
      if ( newMessage.validationError ) {
        rb();
        Notifications.push(newMessage.validationError, "warning");
        return;
      }

      var success = function (model, resp) {
        rb();
        resp.token && ( _s["APP.token"] = resp.token );
        model.unset("token");
        model.unset("receiver");
        model.set({
          message_id: resp.message_id,
          owner: this.conversationModel.get("owner"),
          owner_avatar_link: resp.avatar_link,
          otherside: this.conversationModel.get("otherside"),
          type: 1,
          owner_name: this.conversationModel.get("owner_name"),
          otherside_name: this.conversationModel.get("otherside_name"),
          unread: 1,
          friendly_time: "不久之前"
        },{silent: true});
        this.collection.add(model);
        this.addMessage(model);
        $input.val("");
      };

      var error = function (model, resp) {
        var resp = resp.responseJSON;
        resp.token && ( _s["APP.token"] = resp.token );
        rb();
        Notifications.push(resp.status_detail, "warning");
      };

      newMessage.save(null, {
        success: success,
        error: error,
        context: this
      });
    },

    addMessage: function (model) {
      var $diag = this.$(".diag-messages"), view = new DialogueMessage({model: model, $diag: $diag});
      $diag.append(view.render());
      view.resetElement();
    }

  });

  var ConversationView = Backbone.View.extend( {

    template: _.template($("#template-conversation").html()),

    events: {
      "click .conversation-view-all": "viewMessages",
      "click .conversation-delete": "deleteConversation"
    },

    dialogueMessagesView: false,

    viewMessages: function (e) {
      if ( ! this.dialogueMessagesView ) {
        this.dialogueMessagesView = new DialogueMessagesView({
          conversationModel: this.model
        });
      }
      this.dialogueMessagesView.createDialogue();
      this.dialogueMessagesView.viewMessages(this.model.get("unread"));
      this.messageDialogView.viewMessages(this.model, this.dialogueMessagesView);
      this.model.set({unread: false}, {silent: true});
      this.conversationsView.setMessageReaded(this.model.id);
    },

    deleteConversation: function (e) {
      e.preventDefault();
      this.model.url = _s["SYS_CFG.site_uri"] + "conversation/otherside/" + this.model.get("otherside");
      this.model.destroy({
        success: function () {
        this.remove();
      }, context: this});
    },

    initialize: function (opts) {
      this.messageDialogView = opts.messageDialogView;
      this.conversationsView = opts.conversationsView;
    },

    resetElement: function () {
      this.setElement(".conversation[data-message-id="+this.model.id+"]");
    },

    render: function () {
      var otherside_id = this.model.get("otherside"), 
          otherside_name = this.model.get("otherside_name");
      return this.template({
        message_id: this.model.id,
        otherside_id: otherside_id,
        otherside_name: otherside_name,
        avatar_link: this.model.get("avatar_link"),
        time: this.model.get("friendly_time"),
        content: this.model.get("content"),
        unread: this.model.get("unread")
      });
    }
  } );

  var Conversations = Backbone.Collection.extend({

    model: Message,

    url: _s["SYS_CFG.site_uri"] + "conversation/",

    comparator: function ( model1, model2 ) {
      return model2.id - model1.id;
    }
  });

  var ConversationsView = Backbone.View.extend( {

    el: ".message-dialog .dialog-body .conversations",

    events: {
      "click .more-conversations": "loadConversations"
    },

    initialize: function (opts) {
      // 接收点什么
      this.messageDialogView = opts.messageDialogView;

      this.$loadingConversations = this.$(".loading-conversations");
      this.$moreConversations = this.$(".more-conversations");

      this.on("resetConversations", function (collection) {
        this.$loadingConversations.hide();
        this.insertConversations(collection);
        this._switchMoreConversationsAction("load");
        collection.length >= this.reqAmount ?
          this.$moreConversations.removeClass("hidden") :
          this.$moreConversations.addClass("hidden");
      }, this);

      this.on("setConversations", function (collection) {
        this.insertConversations(collection);
        this._switchMoreConversationsAction("load");
      }, this);
    },

    loadConversations: function (e) {
      e.preventDefault();
      $target = $(e.target);
      this._switchMoreConversationsAction("loading");
      this.fetchNewConversations();
    },

    _switchMoreConversationsAction: function (to) {
      if ( to === "loading" ) {
        this.$moreConversations.find("a").css({display: "none"});
        this.$moreConversations.find("i").css({display: "inline-block"});
      } else if ( to === "load" ) {
        this.$moreConversations.find("a").css({display: "block"});
        this.$moreConversations.find("i").css({display: "none"});
      }
    },

    insertConversations: function (collection) {
      collection.each(function(model) {
        models = collection.where({otherside: model.get("otherside")});
        if ( models.length ) {
          $(".conversation[data-message-id="+models[0].id+"]").remove();
        }
        var view = new ConversationView({
          model: model,
          messageDialogView: this.messageDialogView,
          conversationsView: this
        });
        this.$moreConversations.before(view.render());
        view.resetElement();
      }, this);
    },

    reqAmount: 20,
    fetching: false,

    resetNewConversations: function () {
    this.fetching = true;
    this.$(".conversation").remove();
    this.$loadingConversations.show();
    this.collection.fetch({
        data: { start: 1, amount: this.reqAmount }, reset: true, context: this,
        success: function (collection) {
          this.trigger("resetConversations", collection);
          this.fetching = false;
        },
        error: function () {
          this.fetching = false;
          this.$loadingConversations.hide();
          Notifications.push("没有找到任何会话。");
          this.messageDialogView.displayMessageWriterDialog();
        }
      });
    },

    fetchNewConversations: function (start) {
      this.fetching = true;
      var start = ( start ? start : this.collection.size() ) + 20;
      this.collection.fetch({
        data: { start: start, amount: this.reqAmount }, remove: false, merge: false, context: this,
        success: function(collection, resp) {
          this.trigger("setConversations", collection);
          this.fetching = false;
        },
        error: function () {
          this.fetching = false;
          this.$moreConversations.hide();
        }
      });
    },

    setMessageReaded: function (messageId) {
      this.$(".conversation[data-message-id="+messageId+"] .unread").html("");
    }
  } );

  var NewMessage = Message.extend({
    urlRoot: _s["SYS_CFG.site_uri"] + "message/"
  });

  var NewMessageView = Backbone.View.extend( {

    el: ".message-dialog .dialog-body .write-message",

    initialize: function (opts) {
      // 需要接收点什么
      this.receiverView = opts.receiverView;
      this.messageDialogView = opts.messageDialogView;
    },

    _resetSendMessageButton: function ($button) {
      $button.removeAttr("disabled");
      $button.find("i.loading").remove();
    },

    sendMessage: function (e) {
      var $target = $(e.target);
      $target.attr("disabled", "");
      $target.prepend('<i class="loading"></i>');
      var newMessage = new NewMessage( {
        "receiver": this.receiverView.model.id,
        "content": this.$(".content-input").val(),
        "token": _s["APP.token"]
      },
      {
        validate: true,
      } );

      if ( newMessage.validationError ) {
        this._resetSendMessageButton($target);
        Notifications.push(newMessage.validationError, "warning");
        return;
      }

      var sendSuccess = function (model, resp, opts) {
        resp.token && ( _s["APP.token"] = resp.token );
        this.receiverView.model.clear();
        this.messageDialogView.refreshMessages(e);
        this.messageDialogView.displayConversationsDialog(e);
        this.$(".content .content-input").val("");
        this._resetSendMessageButton($target);
      };

      var sendError = function (model, resp, opts) {
        resp.responseJSON.token && ( _s["APP.token"] = resp.responseJSON.token );
        _s.reqErrorHandle(resp, "发送私信失败：", null, true, "warning", Notifications);
        this._resetSendMessageButton($target);
      };

      newMessage.save(null, {
        success: sendSuccess,
        error: sendError,
        context: this
      });
    }

  } );

  MessageDialogView = Backbone.View.extend({

    el: ".message-dialog",

    events: {
      "click .msg-diag-allcnv": "displayConversationsDialog",
      "click .msg-diag-newmsg": "displayMessageWriterDialog",
      "change .search-area": "searchReceiver",
      "click .send-message": "sendMessage",
      "click .close-message-dialog": "closeMessageDialog",
      "click .refresh-messages": "refreshMessages",
      "click .reply-message": "replyMessage"
    },

    initialize: function () {
      // 私信列表
      this.$conversations = this.$(".conversations");
      this.$headerConversations = this.$(".msg-diag-allcnv");
      // 撰写新私信
      this.$newMessage = this.$(".write-message");
      this.$headerNewMessage = this.$(".msg-diag-newmsg");
      // 与谁对话
      this.$messageDialogue = this.$(".dialogue");
      this.$headerMessageDialogue = this.$(".msg-diag-dialogue");

      // 按钮
      this.$refreshMessages = this.$(".refresh-messages");
      this.$buttonReplyMessage = this.$(".reply-message");
      this.$buttonSendMessage = this.$(".send-message");

      // 私信集合
      this.conversationsCollection = new Conversations();
      this.conversationsView = new ConversationsView( { 
        collection: this.conversationsCollection,
        messageDialogView: this
       } );
    },

    _activeDiagBody: function (body) {
      _.forIn(this.bodyList, function (val, key) {
        ( body === key ) ? this[val].removeClass("hidden") : this[val].addClass("hidden");
      }, this);
    },

    bodyList: {
      "conversations": "$conversations",
      "writer": "$newMessage"
    },

    _deactiveAllDiagBody: function () {
      _.each(this.bodyList, function (body) {
        this[body].addClass("hidden");
      }, this);
    },

    _activeHeader: function (header, title) {
      var l = {
        "all": "$headerConversations",
        "new": "$headerNewMessage",
        "dialogue": "$headerMessageDialogue"
      };

      _.forIn(l, function (val, key) {
        ( header === key ) ? this[val].addClass("active") : this[val].removeClass("active");
      }, this);
      ( header === "dialogue" ) && this[l[header]].find("a").text(title);
      ( header !== "dialogue" ) ?
       this[l["dialogue"]].hide() :
       this[l["dialogue"]].show();
    },

    refreshMessages: function (e) {
      e.preventDefault();
      this.conversationsView.fetching || this.conversationsView.resetNewConversations();
    },

    clearDialogue: function () {
      this.$(".dialogue").remove();
    },

    displayConversationsDialog: function (e) {
      e.preventDefault();
      this.$el.show();
      if (this.newConversationsFetched && !this.conversationsCollection.length) {
        Notifications.push("没有会话。");
      }
      this._activeDiagBody("conversations");
      this._activeHeader("all");
      this.clearDialogue();
      this.$buttonReplyMessage.hide();
      this.$buttonSendMessage.hide();
      this.$refreshMessages.show();

      if ( ! this.newConversationsFetched ) this.conversationsView.resetNewConversations();
      this.newConversationsFetched = true;
    },

    newConversationsFetched: false,

    displayMessageWriterDialog: function (e) {
      e && e.preventDefault();
      this.$el.show();
      this._activeDiagBody("writer");
      this._activeHeader("new");
      this.clearDialogue();
      this.$buttonReplyMessage.hide();
      this.$buttonSendMessage.show();
      this.$refreshMessages.hide();

      this.receiverView ? null : this.receiverView = new ReceiverView( { $msgvEl: this.$el } );
      this.newMessageView || ( this.newMessageView = new NewMessageView( { 
        receiverView: this.receiverView,
        messageDialogView: this
      } ) );
    },

    viewMessages: function (model, dialogueMessagesView) {
      this.$el.show();
      this._deactiveAllDiagBody();
      var between = model.get("owner") + "-" +model.get("otherside");
      this.$(".dialog-body .dialogue[data-conversation-between="+between+"]").show();
      this._activeHeader("dialogue", model.get("otherside_name"));
      this.$buttonReplyMessage.show();
      this.$buttonSendMessage.hide();
      this.$refreshMessages.hide();
      this.dialogueMessagesView = dialogueMessagesView;
    },

    sendMessage: function (e) {
      e.preventDefault();
      this.newMessageView.sendMessage(e);
    },

    closeMessageDialog: function (e) {
      this.$el.hide();
    },

    replyMessage: function (e) {
      e.preventDefault();
      this.dialogueMessagesView.replyMessage(e);
    }
  });

  return MessageDialogView;
} );