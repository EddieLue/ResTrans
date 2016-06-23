define( ["backbone", "notifications", "autosize", "message"], function ( Backbone, Notifications, autosize, MessageDialogView ) {

  var Download = Backbone.Model.extend({
    idAttribute: "file_id",
    validate: function (attrs) {
      if (
        !attrs.best_translation &&
        !attrs.newest_translation &&
        !attrs.machine_translation &&
        !attrs.source
      ) {
        Notifications.push("你必须至少选择一个选项。");
        return "must_select";
      }
    }
  });

  var Downloads = Backbone.Collection.extend({
    model: Download,
    url: _s["SYS_CFG.site_uri"] + "worktable/" + _s["WORKTABLE.hash"] + "/download/link/"
  });

  var Translation = Backbone.Model.extend({
    idAttribute: "translation_id",
    setUrlRoot: function (setId, fileId) {
      setId = setId ? setId : this.collection.fileModel.collection.fileSet.id;
      fileId = fileId ? fileId : this.collection.fileModel.id;
      this.urlRoot = _s["SYS_CFG.site_uri"] + "worktable/" + _s["WORKTABLE.hash"];
      this.urlRoot += "/set/" + setId + "/file/" + fileId + "/translation/";
    }
  });

  var Changes = Backbone.Collection.extend({
    model: Translation,
    setUrl: function (setId, fileId) {
      this.url =  _s["SYS_CFG.site_uri"] + "worktable/" + _s["WORKTABLE.hash"];
      this.url += "/set/" + setId + "/file/" + fileId + "/translation/";
    }
  });

  var TranslationView = Backbone.View.extend({
    templateTranslation: _.template($("#template-translation").html()),
    render: function () {
      return this.templateTranslation({
        translation_id: this.model.id ? this.model.id : this.model.cid,
        content: this.model.get("text"),
        is_best: this.model.get("best_translation"),
        has_best: this.model.get("best_translation") || !!this.model.collection.getBestTranslation(),
        not_save: this.model.isNew(),
        contribute_time: this.model.get("friendly_time"),
        contributor_name: this.model.get("contributor_name"),
        contributor: this.model.get("contributor")
      });
    }
  });

  var Translations = Backbone.Collection.extend({
    model: Translation,
    initialize: function (m, opts) {
      this.fileModel = opts.fileModel;
      this.url = _s["SYS_CFG.site_uri"] + "task/" + _s["WORKTABLE.task_id"];
      this.url += "/set/" + opts.fileModel.collection.fileSet.id;
      this.url += "/file/" + opts.fileModel.id + "/translation/";
    },

    getBestTranslation: function () {
      return this.bestTranslation = this.bestTranslation ?
        this.bestTranslation : this.findWhere({best_translation: 1});
    },

    myTranslation: function () {
      return this.findWhere({contributor: _s["USER.user_id"]});
    }
  });

  var Line = Backbone.Model.extend({
    idAttribute: "line_number",

    initialize: function () {
      this.fileModel = this.collection.fileModel;
      this.translations = new Translations(this.get("translations"), {fileModel: this.fileModel});
      this.unset("translations");
    }
  });

  var LineView = Backbone.View.extend({
    templateLineOnly: _.template($("#template-line-only").html()),
    templateTranslatedText: _.template($("#template-translated-text").html()),
    templateBestTranslation: _.template($("#template-best-translation").html()),

    renderLineOnly: function (addition, showCandidate) {
      var candidates = "",
          translations = this.model.translations;
      translations.size() && translations.each(function (translation) {
        var view = new TranslationView({model: translation});
        candidates += view.render();
      });
      return this.templateLineOnly({
        line: this.model.get("line_number"),
        need: !!this.model.get("translate") ? "" : " no-need",
        content: this.model.get("text"),
        addition: addition ? addition : "",
        display_candidates: showCandidate ? "" : " hidden",
        candidate_translations: candidates
      });
    },

    renderTranslatedText: function () {
      var machineTranslation = _.escape(this.model.get("machine_translation")),
          placeholderText = machineTranslation ? machineTranslation : "输入译文",
          myTranslation = this.model.translations.myTranslation(),
          myTranslationText = myTranslation ? myTranslation.get("text") : "";

      return this.templateTranslatedText({
        machine_translation: placeholderText,
        text: myTranslationText,
        line: this.model.get("line_number")
      });
    },

    renderBestTranslation: function () {
      if (!this.model.translations.getBestTranslation()) {
        return this.templateBestTranslation({
          translation_id: 0,
          show_best_translation: " hidden",
          best_translation_text: "",
          contributor: "",
          proofreader: ""
        });
      }

      var bt = this.model.translations.bestTranslation;

      return this.templateBestTranslation({
        translation_id: this.model.translations.bestTranslation.id,
        show_best_translation: "",
        best_translation_text: this.model.translations.bestTranslation.get("text"),
        contributor: "<a target=\"_blank\" href=\"" + _s["SYS_CFG.site_url"] + "profile/" + bt.escape("contributor") + "\" title=\"翻译者\">" + bt.escape("contributor_name") + "</a>",
        proofreader: "<a target=\"_blank\" href=\"" + _s["SYS_CFG.site_url"] + "profile/" + bt.escape("proofreader") + "\" title=\"校对者\">" + bt.escape("proofreader_name") + "</a>"
      });
    },

    render: function () {
      if ( !this.model.get("translate") ) return this.renderLineOnly();
      var renderTranslatedText = this.renderTranslatedText(),
          renderBestTranslation = this.renderBestTranslation(),
          showCandidate = !this.model.translations.getBestTranslation();
      return this.renderLineOnly(renderTranslatedText + renderBestTranslation, showCandidate);
    }
  });

  var LineCollection = Backbone.Collection.extend({
    model: Line,

    initialize: function (models, opts) {
      this.fileModel = opts.fileModel;
      this.setUrl();
    },

    setUrl: function () {
      this.url =  _s["SYS_CFG.site_uri"] + "task/" + _s["WORKTABLE.task_id"] + "/set/";
      this.url += this.fileModel.collection.fileSet.id + "/file/" + this.fileModel.id + "/line/";
    },

    comparator: "line_number"
  });

  var FileManagerFile = Backbone.Model.extend({
    idAttribute: "file_id",
    alreadyFetched: false,
    initialize: function () {
      this.lines = new LineCollection(null, {fileModel: this});
    },

    setUrlRoot: function () {
      this.url = _s["SYS_CFG.site_uri"] + "worktable/" + _s["WORKTABLE.hash"];
      this.url += "/set/" + this.collection.fileSet.id;
      this.url += "/file/" + this.id;
    }
  });

  var FileManagerFileCollection = Backbone.Collection.extend({
    model: FileManagerFile,
    alreadyFetched: false,
    initialize: function (m, opts) {
      this.url =  _s["SYS_CFG.site_uri"] + "task/" + _s["WORKTABLE.task_id"];
      this.url += "/set/" + opts.set.id + "/file/";
      this.fileSet = opts.set;
    }
  });

  var FileManagerFileView = Backbone.View.extend({
    events: {
      "click .file-meta .name": "openFile",
      "click .file-options .download": "downloadFile",
      "click .file-options .delete": "deleteFile"
    },

    openFile: function (e) {
      e.preventDefault();
      this.workTableTop.openFile(this.model);
    },

    downloadFile: function (e) {
      e.preventDefault();
      this.workTableTop.downloadDialog.trigger("showDownloadDialog", this.model);
    },

    deleteFile: function (e) {
      e.preventDefault();
      this.workTableTop.deleteFileDialog.trigger("showDeleteDialog", this.model, function () {
        this.remove();
        var fm = this.workTableTop.fm,
            sets = fm.setCollection;
        fm.trigger("refreshFileTips");
      }, this);
    },

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
    },

    uploadFileTemplate: _.template($("#template-file-manager-upload").html()),

    fileTemplate: _.template($("#template-file-manager-file").html()),

    renderUpload: function () {
      return this.uploadFileTemplate({
        cid: this.model.cid,
        name: this.model.get("name"),
        status: "0%"
      });
    },

    renderFile: function () {
      return this.fileTemplate({
        id: this.model.id,
        name: this.model.get("name"),
        ext: "." + this.model.get("ext"),
        line: this.model.get("line"),
        percentage: this.model.get("percentage"),
        last_contributor: this.model.get("last_contributor"),
        last_update: this.model.get("last_update"),
        last_update_by: this.model.get("last_update_by")
      });
    },

    render: function (type) {
      return type === "upload" ? this.renderUpload() : this.renderFile();
    }
  });

  var FileManagerSet = Backbone.Model.extend({
    urlRoot: _s["SYS_CFG.site_uri"] + "task/" + _s["WORKTABLE.task_id"] + "/set/",
    idAttribute: "set_id",

    initialize: function () {
      this.files = new FileManagerFileCollection(null, {set: this});
    },

    validate: function (attrs) {
      return !$.trim(attrs.name);
    },
  });

  var FileManagerSetCollection = Backbone.Collection.extend({
    model: FileManagerSet,
    url: _s["SYS_CFG.site_uri"] + "task/" + _s["WORKTABLE.task_id"] + "/set/"
  });

  var FileManagerSetView = Backbone.View.extend({
    events: {
      "click a": "switch"
    },

    initialize: function (opts) {
      this.fm = opts.fm;
      this.listenTo(this.fm.setCollection, "remove", function (model) {
        (this.model.id === model.id) && this.remove();
      });
    },

    switch: function (e) {
      this.fm.switchSet(e);
    },

    template: _.template('<li class="set" data-set-id="<%= id %>"><a href=""><%- name %></a></li>'),

    render: function () {
      return this.template({
        id: this.model.id,
        name: this.model.get("name")
      });
    }
  });

  var CreateSetDialog = Backbone.View.extend({
    el: ".create-set-dialog",

    events: {
      "submit .create-set": "create",
      "click .close-dialog": "close"
    },

    noSets: function () {
      this.$(".close-dialog").hide();
    },

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
    },

    close: function (e) {
      e.preventDefault();
      this.$el.addClass("hidden");
    },

    create: function (e) {
      e.preventDefault();
      var $target = $(e.target),
          $setName = $target.find(".set-name"),
          $submitButton = $target.find("button[type=submit]"),
          model = new FileManagerSet({token: _s["APP.token"]}),
          resetButton = function () {
            $submitButton.removeAttr("disabled");
            $submitButton.text("创建集");
          };

      $submitButton.attr("disabled", "");
      $submitButton.text("创建集···");
      model.set({ name: $setName.val() }, { validate: true });
      if (model.validationError) {
        Notifications.push("请输入文件集的名称。", "warning");
        resetButton.call(this);
      }

      var success = function (m, data) {
        $submitButton.text("正在完成…");
        if ( "create_set_succeed" === data.status_short ) {
          _s["APP.token"] = data.token;
          m.unset("token");
          this.workTableTop.trigger("createSet", m);
          this.$el.addClass("hidden");
          Notifications.push("文件集已创建。", "success");
        }
      };

      var error = function (m, resp) {
        var data = resp.responseJSON;
        _s["APP.token"] = data.token;
        model.set( { token: (_s["APP.token"] = data.token) } );
        if ( _.has( data, "status_detail" ) ) Notifications.push( data.status_detail, "warning" );
      };

      model.save(null, {
        success: success,
        error: error,
        context: this,
        complete: function () {
          resetButton.call(this);
        }
      });
    }
  });

  var FileLanguageSettingsDialog = Backbone.View.extend({

    el: ".choose-file-language",

    events: {
      "click .cancel": "closeDialog",
      "click .continue": "continueUpload"
    },

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
    },

    showDialog: function (e) {
      var $el = this.$el,
          settingTemplate = _.template($("#template-file-language-settings").html());
      // 插入
      $el.find(".language-selector .selector").remove();
      _.each(e.target.files, function (file) {
        $el.find(".language-selector").append(settingTemplate({file_name: file.name}));
      }, this);
      // 更改默认语言
      var sdol = _s["WORKTABLE.default_original_language"],
          sdtl = _s["WORKTABLE.default_target_language"];
      $el.find(".select-original-language option[value="+sdol+"]").attr("selected", true);
      $el.find(".select-target-language option[value="+sdtl+"]").attr("selected", true);
      this.files = e.target.files;
      $el.show();
    },

    closeDialog: function (e) {
      e.preventDefault();
      this.$el.hide();
    },

    continueUpload: function (e) {
      this.files || Notifications.push("无法继续，你所选择的文件列表已丢失。");
      var files = [], filesObj = this.files;
      this.$("li.selector").each(function (i) {
        var $el = $(this),
            ol = $el.find(".select-original-language .re-select option").filter(":selected").val(),
            tl = $el.find(".select-target-language .re-select option").filter(":selected").val();

        files.push({"fileObj": filesObj[i], "original_language": ol, "target_language": tl});
      });
      this.workTableTop.trigger("uploadFiles", files);
      this.closeDialog(e);
    }

  });

  var BeforeSwitchFileDialog = Backbone.View.extend({
    el: ".before-switch-file",

    events: {
      "click .save-now": "save",
      "click .discard": "discard",
      "click .cancel": "hide"
    },

    file: undefined,

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
    },

    save: function (e) {
      this.trigger("save", e);
    },

    discard: function (e) {
      this.trigger("discard", e);
    },

    show: function (e) {
      e && e.preventDefault();
      this.$el.show();
    },

    hide: function (e) {
      e.preventDefault();
      this.$el.hide();
    }
  });

  var GotoLine = Backbone.View.extend({
    el: ".goto-line-dialog",

    events: {
      "submit .go": "go",
      "click .close": "close",
    },

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
      this.on("close", this.close);
    },

    go: function (e) {
      e.preventDefault();
      this.trigger("gotoLine", this.$(".ln").val());
    },

    show: function (e) {
      e && e.preventDefault();
      this.$el.show();
      this.$(".ln").focus();
    },

    close: function (e) {
      e && e.preventDefault();
      this.$el.hide();
    }
  });

  var DeleteFileDialog = Backbone.View.extend({
    el: ".delete-file-dialog",

    events: {
      "click .delete": "delete",
      "click .close": "close"
    },

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
      this.on("showDeleteDialog", this.show, this);
    },

    delete: function (e) {
      e && e.preventDefault();
      var $target = $(e.target);
      $target.attr("disabled", true);
      this.file.setUrlRoot();
      this.file.destroy({
        success: function () {
          Notifications.push("文件已删除。", "success");
          var main = this.workTableTop.main;
          main.fm.lf(undefined);
          /** clearLines 必须在 lf 之后调用的 */
          main.clearLines();
          main.trigger("resetTitle", "", "");
          main.trigger("changeTitle", "");
          main.trigger("changeLanguage", "未知原始语言", "未知目标语言");
          this.callback && this.callbackContext && this.callback.call(this.callbackContext);
          this.close();
        },
        error: function () {
          Notifications.push("文件删除失败。", "warning");
          this.close();
        },
        context: this
      });
    },

    show: function (file, callback, context) {
      this.$el.removeClass("hidden");
      this.$(".delete").removeAttr("disabled");
      this.$(".fn").text(file.escape("name"));
      this.file = file;
      this.callback = callback;
      this.callbackContext = context;
    },

    close: function (e) {
      e && e.preventDefault();
      this.$el.addClass("hidden");
    }
  });

  var DownloadDialog = Backbone.View.extend({
    el: ".download-fallback-setting-dialog",

    events: {
      "click .start-download": "startDownload",
      "click .cancel": "cancel",
    },

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
      this.on("showDownloadDialog", this.show, this);
    },

    show: function (files) {
      var proc = function (file) {
        return {file_id: file.id};
      };
      if (files instanceof Backbone.Model) {
        files = [proc.call(this, files)];
      } else if (files instanceof Backbone.Collection) {
        files = files.map(proc, this);
      }

      this.downloads = new Downloads(files);
      if (!this.downloads.size()) return;
      this.$(".start-download")
        .text("下载 " + this.downloads.size() + " 个文件")
        .removeAttr('disabled');
      this.$el.show();
    },

    startDownload: function (e) {
      this.$(".start-download").text("即将开始···").attr("disabled", true);
      var reset = function () {
        this.$(".start-download")
          .text("下载 " + this.downloads.size() + " 个文件")
          .removeAttr("disabled");
      }, validationError;
      this.downloads.each(function (m) {
        m.set({
          "best_translation": !!(+this.$("input[name=best-translation]").filter(":checked").val()),
          "newest_translation": !!(+this.$("input[name=newest-translation]").filter(":checked").val()),
          "machine_translation": !!(+this.$("input[name=machine-translation]").filter(":checked").val()),
          "source": !!(+this.$("input[name=source]").filter(":checked").val()),
        }, {validate: true});
        validationError = m.validationError ? m.validationError : null;
      }, this);
      if (validationError) return reset.call(this);
      this.downloads.sync("create", this.downloads, {
        success: function (resp) {
          if (resp.status_short === "download_link_created") location.href = resp.download_link;
          reset.call(this);
          this.cancel();
          Notifications.push("下载已开始。", "success");
        },
        error: function (resp) {
          resp = resp.responseJSON;
          if (resp.status_short !== "download_link_created") {
            Notifications.push(resp.status_detail, "error");
          }
          reset.call(this);
          Notifications.push("下载失败。", "warning");
        },
        context: this
      });
    },

    cancel: function (e) {
      e && e.preventDefault();
      this.$el.hide();
    }
  });

  var Sidebar = Backbone.View.extend({
    events: {
      "click .toggle-sidebar": "toggleSidebar"
    },

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
    },

    toggleSidebar: function (e) {
      e.preventDefault();
      this.workTableTop.toggleSidebar(this.$el);
    }
  });

  var ChangeManager = Sidebar.extend({
    el: ".worktable .top .nav .cm-main"
  });

  var GlossaryManager = Sidebar.extend({
    el: ".worktable .top .nav .glossary-main"
  });

  var FileManager = Sidebar.extend({
    el: ".worktable .top .nav .fm-main",

    events: {
      "click .toggle-sidebar": "toggleSidebar",
      "click .select-set .current-set": "toggleSetList",
      "change .upload-files .select-button": "showLanguageSettings",
      "click .files-options .create-set": "showCreateSetDialog",
      "click .files-options .refresh-set-list": "requestSetCollection",
      "click .files-options .refresh-file-list": "requestFileCollection"
    },

    initialize: function (opts) {
      var max      = _s["WORKTABLE.max_upload_file_size"],
          maxUnit  = max.substring(max.length-1),
          lastSet  = _s["WORKTABLE.last_set"]  ? _s["WORKTABLE.last_set"] : undefined,
          lastFile = _s["WORKTABLE.last_file"] ? _s["WORKTABLE.last_file"] : undefined;

      switch(maxUnit) {
        case "K": case "k": this.uploadFileMax = max.substring(0, max.length-1) * 1024; break;
        case "M": case "m": this.uploadFileMax = max.substring(0, max.length-1) * 1048576; break;
        case "G": case "g": this.uploadFileMax = max.substring(0, max.length-1) * 1073741824; break;
        default: this.uploadFileMax = max;
      }

      this.$setList = this.$(".select-set ul.set-list");
      this.workTableTop = opts.wtt;
      this.setCollection = new FileManagerSetCollection(_s["WORKTABLE.sets"]);
      this.setCollection.each(function (model) {
        var view = new FileManagerSetView({model: model, fm: this});
        view.setElement(this.$setList.find(".set[data-set-id=" + model.id + "]"));
      }, this);

      // 设置最后打开的文件集和文件
      if (lastSet) {
        this.ls(this.setCollection.get(lastSet.set_id));
        (_s["WORKTABLE.last_set_files"] &&
        this.ls().files.reset(_s["WORKTABLE.last_set_files"]) &&
        (this.ls().files.alreadyFetched = true));

        this.insertFiles(this.ls().files, false);
      }

      this.ls() && lastFile && this.lf(this.ls().files.get(lastFile.file_id));

      this.on("refreshFileTips", function() {
        var $noFiles = this.$(".file-list .files li.no-files");
        this.$(".file-list .files li.file").length ? $noFiles.addClass("hidden") :
          $noFiles.removeClass("hidden");
      }, this);

      this.listenTo(this.setCollection, "remove", function (m, collection) {
        if ( !collection.size() ) {
          this.$(".select-set .current-set").text("无文件集");
          this.showCreateSetDialog();
          this.workTableTop.noSets();
        }
      });

      this.on("reRenderFile", function (file) {
        var $file = this.$(".file-list .files li.file[data-file-id=" + file.id + "]");
        if (!$file.length) return;
        $file.remove();
        this.insertFiles(file, true);
      }, this);

      this.trigger("refreshFileTips");
    },

    ls: function (set) {
      // 设置 & 获取最后打开的文件集
      if (Object.prototype.hasOwnProperty.call(arguments, 0)) {
        return this.lastSet = set;
      }

      return this.lastSet;
    },

    lf: function (file) {
      // 设置 & 获取最后打开的文件
      if (Object.prototype.hasOwnProperty.call(arguments, 0)) {
        return this.lastFile = file;
      }

      return this.lastFile;
    },

    listResize: function (height) {
      this.$(".file-list").height(height - 110);
    },

    displaySetList: false,
    toggleSetList: function (e) {
      e && e.preventDefault();
      this.displaySetList ?
        this.$setList.css("display", "none") :
        this.$setList.css("display", "block");

      this.displaySetList = !this.displaySetList;
    },

    _insert: function (model, render) {
      var view = new FileManagerFileView({ model: model, wtt: this.workTableTop });
      render && this.renderInsertFiles(view.render("file"));
      view.setElement(this.$(".file-list .files li.file[data-file-id=" + model.id + "]"));
    },

    insertFiles: function (file, render) {
      if (file instanceof Backbone.Model) return this._insert(file, render);
      file.each((function (render) {
        var that = this;
        return function (file) {that._insert(file, render)};
      }).call(this, render), this);
      this.trigger("refreshFileTips");
    },

    switchSet: function (e) {
      e.preventDefault();
      this.toggleSetList();
      var $target = $(e.target),
          setId = $target.parent().data("set-id"),
          set = this.setCollection.get(setId);
      this._switchSet(set);
    },

    _switchSet: function (set) {
      this.workTableTop.main.loading("打开集");
      var reset = function () {
        this.workTableTop.main.hideLoading();
        this.requestingFileCollection = false;
      };

      if (!set) {
        returnreset.call(this);
      }

      var fileCollection = set.files;
      if (fileCollection.alreadyFetched) {
        reset.call(this);
        this.$(".file-list .files li.file").remove();
        this.insertFiles(fileCollection, true);
        this.currentSet(fileCollection.fileSet);
        return;
      }

      this.fetchFile(fileCollection);
    },

    requestFileCollection: function (e) {
      e.preventDefault();
      this.workTableTop.main.loading("打开集");
      this.requestingFileCollection || this.fetchFile(this.ls().files);
    },

    requestingFileCollection: false,
    fetchFile: function (fileCollection) {
      if (this.requestingFileCollection) return;
      this.requestingFileCollection = true;

      var reset = function () {
        this.workTableTop.main.hideLoading();
        this.requestingFileCollection = false;
      },
         clear = function () {
        this.$(".file-list .files li.file").remove();
      },
         success = function (collection, resp) {
        reset.call(this);
        clear.call(this);
        if (!resp.length) return;
        this.insertFiles(collection, true);
        collection.alreadyFetched = true;
        this.currentSet(collection.fileSet);
      },
          error = function (collection, resp) {
        reset.call(this);
        clear.call(this);
        var resp  = resp.responseJSON, detail = resp.status_detail ? resp.status_detail : "";
        ((resp.status_short === "file_not_found") ||
        Notifications.push("打开集的时候发生错误。" + detail, "warning"));
        if (resp.status_short === "set_not_found") {
          this.setCollection.remove(collection.fileSet);
        } else {
          this.insertFiles(collection, true);
          this.currentSet(collection.fileSet);
        }
      };

      fileCollection.fetch({ reset: true, success: success, error: error, context: this });
    },

    currentSet: function (set) {
      this.$(".select-set .current-set").html(set.get("name") + "<i class=\"down-arrow\"></i>");
      this.ls(set);
    },

    showLanguageSettings: function (e) {
      if ( !this.ls() ) {
        Notifications.push("出现了问题，请刷新文件集或文件列表然后再试一次。", "warning");
        return;
      }
      e.target.files.length && this.workTableTop.trigger("showLanguageSettingsDialog", e);
    },

    uploadFiles: function (files) {
      _.each(files, function (file) {
        if (file.fileObj.size > this.uploadFileMax) {
          Notifications.push("「" + file.fileObj.name + "」文件大小不符合上传标准。", "warning");
          return;
        }
        var model = this.ls().files.add({
          name: file.fileObj.name,
          size: file.fileObj.size,
          target_language: file.target_language,
          original_language: file.original_language
        }, {collection: this.ls().files}),
            view = new FileManagerFileView({ model: model, wtt: this.workTableTop}),
            formData = new FormData();
            uploadURL = _s["SYS_CFG.site_uri"] + "worktable/" + _s["WORKTABLE.hash"];
            uploadURL += "/set/" + this.ls().id + "/file/";
        var xhr = new XMLHttpRequest(),
            upEvent = function (evn, m, v) {
              var that = this;
              return function (e) {
                that[evn](e, m, v);
              };
            };

        this.renderInsertFiles(view.render("upload"));
        view.setElement(this.$(".file-list .files .temporary-file[data-file-cid="+model.cid+"]"));
        xhr.addEventListener("progress", upEvent.call(this, "uploadProgress", model, view), false);
        xhr.addEventListener("load", upEvent.call(this, "uploadCompleted", model, view), false);
        xhr.addEventListener("error", upEvent.call(this, "uploadFailed", model, view), false);
        xhr.addEventListener("loadend", upEvent.call(this, "uploadEnd", model, view), false);
        xhr.open("POST", uploadURL, true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.setRequestHeader("Accept", "application/json");
        formData.append("file", file.fileObj);
        formData.append("original_language", file.original_language);
        formData.append("target_language", file.target_language);
        xhr.send(formData);
      }, this);
    },

    uploadProgress: function (e, model) {
      var percentage = e.lengthComputable ? Math.round(e.loaded * 100 / e.total) : 0;
          $tempFile = this.$(".file-list .files .temporary-file[data-file-cid="+model.cid+"]");
      $tempFile.find(".status span").text(percentage + "%");
      if ( percentage === 100 ) {
        $tempFile.find(".status span").text("正在处理");
        $tempFile.find(".status button").hide();
      }
    },

    uploadCompleted: function (e, model, view) {
      var xhr = e.target;
      view.remove();
      try {
        var data = $.parseJSON(e.target.responseText);
      } catch (e) {
        var data = {};
      }
      if (xhr.status !== 200 || !data.status_short) {
        this.ls().files.remove(model);
        var detail = data.status_detail ? data.status_detail : "未知错误";
        Notifications.push("上传时发生问题：" + detail + "。", "warning");
        return;
      }
      model.set(data);
      this.renderInsertFiles(view.render("file"));
      view.setElement(this.$(".file-list .files li.file[data-file-id=" + model.id + "]"));
      Notifications.push("文件上传成功。", "success");
    },

    uploadFailed: function (e, model, view) {
      view.remove();
      this.ls().files.remove(model);
      Notifications.push("文件上传失败。", "warning");
    },

    uploadEnd: function () {
      this.$(".upload-files .select-button").val(null);
    },

    renderInsertFiles: function (html) {
      this.$(".file-list .files").prepend(html);
      this.trigger("refreshFileTips");
    },

    showCreateSetDialog: function (e) {
      e && e.preventDefault();
      this.workTableTop.trigger("showCreateSetDialog");
    },

    requestingSetCollection: false,
    requestSetCollection: function (e) {
      e.preventDefault();
      this.requestingSetCollection || this.refreshSetCollection(true);
    },

    refreshSetCollection: function (request) {
      this.requestingSetCollection = true;
      this.workTableTop.main.loading("加载集");

      var refresh = function (collection) {
        this.$setList.empty();

        collection.each(function (model) {
          var view = new FileManagerSetView({model: model, fm: this});
          this.renderSetList(view.render());
          view.setElement(this.$setList.find(".set[data-set-id="+model.id+"]"));
        }, this);

        this.workTableTop.main.hideLoading();
        this.requestingSetCollection = false;
      }

      if (!request) {
        refresh.call(this, this.setCollection);
        return;
      }

      this.setCollection.fetch({
        success: function (collection) {
          refresh.call(this, collection);
        },
        error: function () {
          Notifications.push("加载文件集列表出现错误，请重试。", "warning");
          this.workTableTop.main.hideLoading();
          this.requestingSetCollection = false;
        },
        context: this
      });
    },

    renderSetList: function (insert) {
      this.$setList.append(insert);
    }
  });

  var WorkTableTop = Backbone.View.extend({

    el: ".worktable .top",

    events: {
      "click .tools .toolkit .tool-global .subtools .message": "attachMessageDialogView",
      "click .tools .toolkit .tool-global .subtools .subtool .logout": "logout",
      "click .tools .toolkit .tool-translate .subtools .refresh-line": "refreshLine",
      "click .tools .toolkit .tool-translate .subtools .goto-line": "gotoLine",
      "blur .current-file": "changeFileName",
      "click .tools .toolkit .tool .toggle": "showDropdown"
    },

    initialize: function () {
      this.messageDialogView = new MessageDialogView();
      this.$noSetsDialog = $(".no-sets-dialog");
      this.sb = new Sidebar({wtt: this});
      this.fm = new FileManager({wtt: this});
      this.gm = new GlossaryManager({wtt: this});
      this.cm = new ChangeManager({wtt: this});

      $(window).on("beforeunload", function () {
        return "关闭工作台？";
      });

      this.on("createSet", this.createSet);
      this.on("uploadFiles", this.fm.uploadFiles, this.fm);
      this.createSetDialog = new CreateSetDialog({wtt: this});
      this.fileLanguageSettingsDialog = new FileLanguageSettingsDialog({wtt: this});
      this.beforeSwitchFileDialog = new BeforeSwitchFileDialog({wtt: this});
      this.gotoLineDialog = new GotoLine({wtt: this});
      this.downloadDialog = new DownloadDialog({wtt: this});
      this.deleteFileDialog = new DeleteFileDialog({wtt: this});

      this.on("showCreateSetDialog", function () {
        this.createSetDialog.$el.removeClass("hidden");
      }, this);

      this.on("showLanguageSettingsDialog", function (e) {
        this.fileLanguageSettingsDialog.showDialog(e);
      });

      _s["WORKTABLE.no_sets"]   && this.noSets();
      _s["WORKTABLE.has_reset"] && this.hasReset();
      _s["WORKTABLE.no_files"]  && this.noFiles();

      $(window).on("resize", (function () {
        var that = this;
        return function () { that.windowResize() };
      }).call(this));
    },

    windowResize: function () {
      this.fm.listResize($(window).innerHeight());
    },

    noSets: function () {
      this.createSetDialog.noSets();
      Notifications.push("你需要创建至少一个文件集来存放待翻译文件。");
    },

    hasReset: function () {
      Notifications.push("由于文件（集）的变动，这可能不是你上次工作的位置。");
    },

    noFiles: function () {
      Notifications.push("你当前打开的工作集中没有文件，点击文件管理器的上传按钮上传文件。");
    },

    createSet: function (m) {
      this.fm.setCollection.add(m);
      this.fm.refreshSetCollection(false);
    },

    attachMessageDialogView: function (e) {
      $(e.target).find("i.red-point").addClass("hidden");
      this.messageDialogView.displayConversationsDialog(e);
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
            location.href = _s["SYS_CFG.site_url"] + "start/";
          }
        }
      });
    },

    refreshLine: function (e) {
      this.main.refreshLineOfCurrentFile(e);
    },

    gotoLine: function (e) {
      this.gotoLineDialog.show(e);
    },

    displaySidebar: false,
    toggleSidebar: function ($sidebar) {
      var $sb = $sidebar,
          hide = "hide-sidebar-main",
          show = "show-sidebar-main";
      this.displaySidebar ?
        ($sb.removeClass(show) && $sb.addClass(hide)) :
        ($sb.removeClass(hide) && $sb.addClass(show));
      this.displaySidebar = !this.displaySidebar;
    },

    setMain: function (main) {
      this.main = main;
      this.listenTo(main, "changeTitle", function (title) {
        this.$(".nav .current-file").val(title);
      });
      this.listenTo(main, "changeLanguage", function (originalLanguage, targetLanguage) {
        var $fl = this.$(".file-language");
        $fl.find(".original-language").text(originalLanguage);
        $fl.find(".target-language").text(targetLanguage);
      });
    },

    openFile: function (file) {
      this.main.open(file);
    },

    changeFileName: function (e) {
      var $target = $(e.target),
          name = $target.val(),
          file = this.fm.lf();
      if (!file) return;
      if (name.match(/[\\\/:?<>"|]/)) {
        return Notifications.push("文件名不能包含某些特殊字符。", "warning");
      }

      if (file.get("name") === name) return;
      file.set({name: name}).save(null, {
        success: function (model, resp, opts) {
          this.main.trigger("resetTitle", file.get("name"), this.fm.ls().get("name"));
          Notifications.push("文件名修改成功。", "success");
          this.fm.trigger("reRenderFile", file);
        },
        error: function () {
          Notifications.push("文件名修改失败。", "warning");
        },
        context: this
      });
    },

    _closeAllDropdownList: function () {
      this.$(".subtools").hide();
      this.$(".tool .toggle .down-arrow").removeClass("down-arrow-on");
    },

    showDropdown: function (e) {
      e && e.preventDefault();
      e.stopPropagation();
      var _c = this._closeAllDropdownList;
      _c();
      $target = $(e.target).parents("a.toggle").length === 1 ?
        $(e.target).parents("a.toggle") : $(e.target);
      $target.find(".down-arrow").addClass("down-arrow-on");
      $target.find("i.red-point").addClass("hidden");
      $target.siblings(".subtools").show();
      $("body").one("click", (function () {
        var that = this;
        return function (e) {
          _c.call(that);
          return true;
        };
      }).call(this));
    }
  });

  var WorkTableMain = Backbone.View.extend({
    el: ".worktable .wt-main",

    events: {
      "keydown .page .row .translated-text .re-worktable-textarea": "textareaKeyDown",
      "click .page .row .para-tools .insert-machine-translation": "clickToInsertMachineTranslation",
      "blur .page .row .translated-text .re-worktable-textarea": "autoSave",
      "click .save": "saveNow",
      "click .page .load-after": "loadAfterRows",
      "click .page .load-before": "loadBeforeRows",
      "click .para-tools .pre-row": "prevRow",
      "click .para-tools .next-row": "nextRow",
      "click .best-translation .show-candidates": "toggleCandidates",
      "click .use-original-text": "useOriginalText",
      "click .best-translation .revoke": "revokeBestTranslation",
      "click .candidate-translations .translation .best": "setAsBestTranslation",
      "click .candidate-translations .translation .delete": "deleteTranslation"
    },

    initialize: function (opts) {
      this.workTableTop = opts.wtt;
      this.fm = this.workTableTop.fm;
      autosize(this.$(".page .row .translated-text .re-worktable-textarea"));
      this.$loading = this.$(".wt-nav-loading");
      this.$noRow = this.$(".page .no-row");
      this.unsavedChanges = new Changes({wtm: this});
      this.$loadBefore = this.$(".page .load-before");
      this.$loadAfter = this.$(".page .load-after");
      this.savingChanges = new Changes();
      this.changesBuffer = new Changes();

      this.on("checkPreviousLine", function () {
        (!this.fm.lf() || (this.fm.lf().lines.get(1) && this.$(".page .row[data-line=1]").length)) ?
          this.$loadBefore.addClass("hidden") : this.$loadBefore.removeClass("hidden");
      }, this);

      this.on("checkNextLine", function () {
        if (!this.fm.lf()) return this.$loadAfter.addClass("hidden");
        var lines = this.fm.lf().lines, total = this.fm.lf().get("line");
        (lines.get(total) && this.$(".page .row[data-line="+ total +"]").length) ?
          this.$loadAfter.addClass("hidden") : this.$loadAfter.removeClass("hidden");
      }, this);

      this.on("checkLine", function () {
        if (!this.$(".page .row").length) {
          this.$(".no-row").removeClass("hidden").text("文件可能加载失败或没有行。");
        } else this.$(".no-row").addClass("hidden").text("正在加载行···");
      }, this);

      this.on("textareaUpdate", function () {
        autosize(this.$(".page .row .translated-text .re-worktable-textarea"));
      }, this);

      this.on("resetTitle", function (fileName, setName) {
        var $title = $("html head title");
            defaultTitle = _s["WORKTABLE.default_title"] + " / ResTrans";
            setName && (defaultTitle = setName + " / " + defaultTitle);
            fileName && (defaultTitle = fileName + " / " + defaultTitle);
            $title.text(defaultTitle);
      }, this);

      this.listenTo(this.changesBuffer, "remove", function (m, c) {
        if (!this.saving && this.countdown && !c.size()) {
          window.clearTimeout(this.countdown);
          this.countdown = false;
          this.$(".save").addClass("hidden");
        }
      });

      this.listenTo(opts.wtt.beforeSwitchFileDialog, "save", function (e) {
        this.saveChanges(function () {
          var dialog = opts.wtt.beforeSwitchFileDialog;
          dialog.hide(e);
          this.open(dialog.file);
          dialog.file = undefined;
        }, this);
      });

      this.listenTo(opts.wtt.beforeSwitchFileDialog, "discard", function (e) {
        this.savingChanges.reset();
        this.changesBuffer.reset();
        this.$(".save").addClass("hidden");
        this.saving = false;
        window.clearTimeout(this.countdown);
        this.countdown = undefined;
        var dialog = opts.wtt.beforeSwitchFileDialog;
        console.log(dialog);
        dialog.hide(e);
        this.open(dialog.file);
        dialog.file = undefined;
        Notifications.push("变更已全部抛弃。");
      });

      this.listenTo(opts.wtt.gotoLineDialog, "gotoLine", this.gotoLine);

      _s["WORKTABLE.no_files"] && this.noFiles();
      _s["WORKTABLE.last_file_lines"] && this.initLines(_s["WORKTABLE.last_file_lines"]);
      /** 无论如何也要尝试切换提示的状态 */
      this.trigger("checkLine");
      opts.wtt.windowResize();
      this.hideLoading();
    },

    prevRow: function (e) {
      var $lineTextArea = $(e.target).parents(".row").find(".re-worktable-textarea");
      this.selectPrevTextArea({target: $lineTextArea});
      e.preventDefault();
    },

    nextRow: function (e) {
      var $lineTextArea = $(e.target).parents(".row").find(".re-worktable-textarea");
      this.selectNextTextArea({target: $lineTextArea});
      e.preventDefault();
    },

    toggleCandidates: function (e) {
      var $target = $(e.target);
          $candidates = $target.parents(".row").find("ul.candidate-translations");
      if ($candidates.hasClass("hidden")) {
        $target.text("收起候选译文");
        $candidates.removeClass("hidden");
      } else {
        $target.text("展开候选译文");
        $candidates.addClass("hidden");
      }
      e.preventDefault();
    },

    useOriginalText: function (e) {
      var $target = $(e.target),
          $lineTextArea = $target.parents(".row").find(".translated-text .re-worktable-textarea"),
          line = this.fm.lf().lines.get($target.parents(".row").data("line"));
      $lineTextArea.val(line.get("text"));
      e.preventDefault();
    },

    textareaKeyDown: function (e) {
      if (!e.ctrlKey && !e.shiftKey) return;
      (e.keyCode === 39) && this.insertMachineTranslation(e);
      (e.keyCode === 40 || e.keyCode === 9) && this.selectNextTextArea(e);
      (e.keyCode === 38) && this.selectPrevTextArea(e);
    },

    clickToInsertMachineTranslation: function (e) {
      e && e.preventDefault();
      console.log($(e.target).
        parents(".para-tools").
        siblings(".translated-text").
        find("textarea"));
      return this.insertMachineTranslation($(e.target).
        parents(".para-tools").
        siblings(".translated-text").
        find("textarea"));
    },

    selectNextTextArea: function (e) {
      $(e.target).parents(".row").nextAll(".row").has(".re-worktable-textarea").first().
      find(".re-worktable-textarea").focus();
    },

    selectPrevTextArea: function (e) {
      $(e.target).parents(".row").prevAll(".row").has(".re-worktable-textarea").first().
      find(".re-worktable-textarea").focus();
    },

    insertMachineTranslation: function (e) {
      var $target = e[0] ? e : $(e.target),
          line = this.fm.lf().lines.get($target.parents(".row").data("line"));
      if ($target.val()) return;
      $target.val(line.get("machine_translation"));
    },

    initLines: function (lines) {
      var lastFileLines = this.fm.lf().lines;
      lastFileLines.reset(lines);
      this.clearLines().renderInsertLines(lastFileLines);
    },

    noFiles: function () {
      this.$noRow.removeClass("hidden").text("没有文件，在文件管理器中打开或上传文件。");
    },

    interval: null,
    loading: function (text) {
      this.$loading.find(".loading-text").text(text);
      this.interval = window.setInterval((function () {
        var that = this, i = 1, d = {1: "·  ", 2: " · ", 3: "  ·"};
        return function () {
          i++ && (i === 4) && (i = 1);
          that.$loading.find(".ing").text(" " + d[i]);
        };
      }).call(this), 1000);
      this.$loading.show();
    },

    hideLoading: function () {
      this.$loading.hide();
      window.clearInterval(this.interval);
      this.$loading.find(".loading-text").val("正在加载");
    },

    clearLines: function () {
      this.$(".page .row").remove();
      this.trigger("checkPreviousLine");
      this.trigger("checkNextLine");
      this.trigger("checkLine");
      return this;
    },

    open: function (file) {
      var set = file.collection.fileSet,
          lines = file.lines,
          _open = function () {
        this.fm.ls(set);
        this.fm.lf(file);
        this.clearLines().renderInsertLines(lines);
        this.hideLoading();
        this.trigger("resetTitle", file.get("name"), set.get("name"));
        this.trigger("changeTitle", file.get("name"));
        this.trigger(
          "changeLanguage",
          file.get("original_language_name"),
          file.get("target_language_name")
        );
      };
      if (this.lock()) return;
      if (this.changesBuffer.size() || this.saving) {
        var dialog = this.workTableTop.beforeSwitchFileDialog;
        dialog.file = file;
        return dialog.show();
      }
      this.loading("正在打开文件");
      if (lines.size()) {
        return _open.call(this);
      }
      lines.setUrl();
      this.loadRows(lines, 1, 100, _open, true);
    },

    renderInsertLines: function (lineCollection) {
      /** 不让每行独自捕捉事件 */
      lineCollection.each(function (line) {
        if (this.$(".page .row[data-line="+line.id+"]").length) return;
        var view = new LineView({model: line});
        if (!this.$(".page .row").length) return this.$(".page .load-before").after(view.render());
        var currentPosition = _.indexOf(lineCollection.models, line),
            prevModel = lineCollection.models[currentPosition-1],
            nextModel = lineCollection.models[currentPosition+1],
            $prevLine = prevModel ? this.$(".page .row[data-line="+prevModel.id+"]") : {},
            $nextLine = nextModel ? this.$(".page .row[data-line="+nextModel.id+"]") : {};
        if ($prevLine.length) {
          $prevLine.after(view.render());
        } else if ($nextLine.length) {
          $nextLine.before(view.render());
        } else if (line.id < this.$(".page .row:first").data("line")) {
          this.$(".page .row:first").before(view.render());
        } else {
          this.$(".page .row").each(function () {
            if (line.id < $(this).data("line")) $(this).before(view.render());
          });
        }
      }, this);
      this.trigger("textareaUpdate");
      this.trigger("checkPreviousLine");
      this.trigger("checkNextLine");
      this.trigger("checkLine");
    },

    autoSave: function (e) {
      var $target = $(e.target),
          val = $target.val(),
          line = this.fm.lf().lines.get($target.parents(".row").data("line")),
          translations = line.translations,
          translation;

      if (!(translation = translations.myTranslation())) {
        if (!val) return;
        translation = translations.add({
          file_id: this.fm.lf().id,
          line: line.id,
          text: val,
          best_translation: 0,
          contributor: _s["USER.user_id"],
          contributor_name: _s["USER.user_name"],
          friendly_time: "不久之前"
        });
        translation.set({_cid: translation.cid});
      } else {
        if (translation.get("text") === val) return;
        translation.set({text: val, friendly_time: "不久之前"});
      }

      this.autoSaveChanges(translation);
    },

    saveNow: function (e) {
      e.preventDefault();
      this.saveChanges();
    },

    countdown: undefined,
    saving: false,
    autoSaveChanges: function (translation) {
      this.updateTranslation(translation);
      this.changesBuffer.add(translation, {merge: true});
      if (this.saving || this.countdown) return;
      this.$(".save").removeClass("hidden");
      this.startCountdown();
    },

    startCountdown: function () {
      this.countdown = window.setTimeout((function () {
        var that = this;
        return function () { that.saveChanges() }
      }).call(this), 120000);
    },

    saveChanges: function (callback, context) {
      if (this.saving || !this.changesBuffer.size()) return;
      this.$(".save").addClass("hidden");
      this.saving = true;
      window.clearTimeout(this.countdown);
      this.countdown = undefined;
      this.savingChanges.reset(this.changesBuffer.models);
      this.changesBuffer.reset();
      this.loading("正在保存");
      this.savingChanges.setUrl(this.fm.lf().collection.fileSet.id, this.fm.lf().id);
      var reset = function () {
        this.saving = false;
        this.hideLoading();
      };
      this.savingChanges.sync("patch", this.savingChanges, {
        context: this,
        success: function (resp, status, xhr) {
          reset.call(this);
          if (resp.status_short === "part_patched" || resp.status_short === "all_patched") {
            resp.status_short === "part_patched" && Notifications.push("修改已提交，但只有部分被保存，将在稍后重试。", "success");
            resp.status_short === "all_patched" && Notifications.push("您的修改已保存。", "success");
            return this.afterSaved(resp.patched, this.savingChanges, callback, context);
          }
          Notifications.push("保存失败，将在稍后重试。", "warning");
        },
        error: function (resp, status, xhr) {
          reset.call(this);
          Notifications.push("保存失败：" + resp.responseJSON.status_detail, "warning");
          this.savingChanges.each(function (translation) {
            this.autoSaveChanges(translation);
          }, this);
        }
      });
    },

    afterSaved: function (patched, savingChanges, callback, context) {
      _.each(patched, function (p) {
        var change = savingChanges.get(p.translation_id) ? 
          savingChanges.get(p.translation_id) : savingChanges.findWhere({_cid: p._cid});

        if (!change) return;
        var id = change.id ? change.id : p._cid,
            translations = this.fm.lf().lines.get(p.line).translations,
            $t = this.$(".row[data-line=" + p.line + "]").
            find(".candidate-translations").find(".translation[data-translation-id='" + id + "']");
          $t.attr("data-translation-id", p.translation_id);
          translations.bestTranslation || $t.find(".panel .options .best").removeClass("hidden");
          p._cid = undefined;

          if (p.text) {
            translations.get(id).set(p);
          } else {
            translations.remove(change);
          }
          savingChanges.remove(change);
      }, this);
      savingChanges.each(this.autoSaveChanges, this);
      (this.changesBuffer.size() === 0) && callback && context && callback.call(context);
      savingChanges.reset();
    },

    updateTranslation: function (translation) {
      var $line = this.$(".page .row[data-line="+ translation.get("line") +"]"),
          id = translation.id ? translation.id : translation.cid,
          $translation = $line.find("li.translation[data-translation-id="+ id +"]"),
          $bestTranslation = $line.find(".best-translation[data-best-translation-id="+ id +"]"),
          $candidates = $line.find(".candidate-translations");
      if (!translation.get("text")){
        $bestTranslation.length && $bestTranslation.remove() && $candidates.removeClass("hidden");
        return $translation.length && $translation.remove();
      }
      if (!$translation.length) {
        var view = new TranslationView({model: translation});
        return $line.find(".candidate-translations").prepend(view.render());
      }
      $translation.find("p").text(translation.get("text"));
      $bestTranslation && $bestTranslation.find("p").text(translation.get("text"));
      $translation.find(".panel .meta .date").text(translation.get("friendly_time"));
    },

    revokeBestTranslation: function (e) {
      var $target = $(e.target),
          line = this.fm.lf().lines.get($target.parents(".row").data("line")),
          $bestTranslation = $target.parents(".best-translation"),
          translation = line.translations.get($bestTranslation.data("best-translation-id"));

      if ($target.data("disabled") === false) {
        this.loading("正在撤销最佳译文");
        $target.data("disabled", true);
      } else return false;

      var reset = function () {
        $target.data("disabled", false);
        this.hideLoading();
      };
      translation.set({best_translation: false});
      translation.setUrlRoot();
      translation.save(null, {
        context: this,
        success: function (m, resp) {
          reset.call(this);
          if (resp.status_short !== "translation_update_succeed") {
            return Notifications.push("撤销时发生错误。", "warning");
          }
          $parents = $target.parents(".best-translation");
          $parents.addClass("hidden");
          $parents.siblings(".candidate-translations").removeClass("hidden").
          find(".translation .best").removeClass("hidden").
          siblings(".translation .delete").removeClass("hidden");
        },
        error: function (m, resp) {
          reset.call(this);
          if (status_detail = resp.responseJSON.status_detail) {
            return Notifications.push("撤销时发生错误。" + status_detail, "warning");
          }
        }
      });
      e.preventDefault();
    },

    setAsBestTranslation: function (e) {
      var $target = $(e.target),
          line = this.fm.lf().lines.get($target.parents(".row").data("line")),
          $translation = $target.parents(".translation"),
          translation = line.translations.get($translation.data("translation-id"));

      if ($target.data("disabled") === false) {
        this.loading("正在设为最佳译文");
        $target.data("disabled", true);
      } else return false;

      var reset = function () {
        $target.data("disabled", false);
        this.hideLoading();
      };

      translation.set({
        best_translation: true,
        "proofreader": _s["USER.user_id"],
        "proofreader_name": _s["USER.user_name"]
      });
      translation.setUrlRoot();
      translation.save(null, {
        context: this,
        success: function (m, resp) {
          reset.call(this);
          if (resp.status_short !== "translation_update_succeed") {
            return Notifications.push("设为最佳时发生错误。", "warning");
          }
          $parents = $target.parents(".candidate-translations");
          $parents.find(".translation .best").addClass("hidden");
          $parents.find(".translation[data-translation-id="+m.id+"] .delete").addClass("hidden");
          line.translations.bestTranslation = translation;
          var view = new LineView({model: line});
          $parents.siblings(".best-translation").remove();
          $parents.before(view.renderBestTranslation());
          $parents.addClass("hidden");
        },
        error: function (m, resp) {
          reset.call(this);
          if (status_detail = resp.responseJSON.status_detail) {
            return Notifications.push("设为最佳时发生错误：" + status_detail, "warning");
          }
        }
      });
      e.preventDefault();
    },

    deleteTranslation: function (e) {
      e.preventDefault();
      var $target = $(e.target),
          line = this.fm.lf().lines.get($target.parents(".row").data("line")),
          $translation = $target.parents(".translation"),
          translationId = $translation.data("translation-id"),
          translation = line.translations.get(translationId),
          translationInBuffer = this.changesBuffer.get(translationId);

      this.changesBuffer.remove(translationInBuffer);
      if (translationInBuffer && translationInBuffer.isNew()) {
        $translation.remove();
      }

      if (translation && translation.isNew()) {
        return line.translations.remove(translation);
      }

      translation.setUrlRoot();
      translation.destroy({
        success: function () {
          $translation.remove();
        },
        error: function () {
          Notifications.push("删除一个候选译文时失败。", "warning");
        },
        context: this
      });
    },

    isLocked: false,
    lock: function (locked) {
      if (locked === undefined) return this.isLocked;
      this.isLocked = Boolean(locked);
    },

    loadBeforeRows: function (e) {
      e.preventDefault();
      if (this.lock()) return;
      var $target = $(e.target),
          file = this.fm.lf(),
          lines = function (that) {
            return that.fm.lf().lines;
      },
          $firstRow = this.$(".page .row:first"),
          _load = function () {
        this.lock(false);
        if (this.fm.lf() !== lines(this).fileModel) return;
        this.renderInsertLines(lines(this));
        this.hideLoading();
        $target.find("a").text("加载之前的行");
      };
      this.loading("正在加载行");
      this.lock(true);
      $target.find("a").text("加载之前的行···");
      if (lines(this).first().get("line_number") !== $firstRow.data("line")) {
        this.renderInsertLines(lines(this));
      }
      var start = (($firstRow.data("line") - 100) <= 1) ? 1 : $firstRow.data("line") - 100,
          end = $firstRow.data("line");
      this.loadRows(lines(this), start, end, _load, false, false);
    },

    loadAfterRows: function (e) {
      e.preventDefault();
      if (this.lock()) return;
      var $target = $(e.target),
          file = this.fm.lf(),
          lines = function (that) {
            return that.fm.lf().lines;
      },
          $lastRow = this.$(".page .row:last"),
          _load = function () {
        this.lock(false);
        if (this.fm.lf() !== lines(this).fileModel) return;
        this.renderInsertLines(lines(this));
        this.hideLoading();
        $target.find("a").text("加载之后的行");
      };
      this.loading("正在加载行");
      this.lock(true);
      $target.find("a").text("加载之后的行···");
      if (lines(this).first().get("line_number") !== $lastRow.data("line")) {
        this.renderInsertLines(lines(this));
      }
      var start = $lastRow.data("line") + 1,
          end = (($lastRow.data("line") + 100) > file.get("line")) ? file.get("line") :
            $lastRow.data("line") + 100;
      this.loadRows(lines(this), start, end, _load, false, false);
    },

    refreshLineOfCurrentFile: function (e) {
      e.preventDefault();
      if (this.lock()) return;
      var file = this.fm.lf(),
          lines = function (that) {
            return that.fm.lf().lines;
          },
          _load = function () {
        this.lock(false);
        if (this.fm.lf() !== lines(this).fileModel) return;
        this.clearLines().renderInsertLines(lines(this));
        this.hideLoading();
      };

      if (!file) return;

      this.loading("正在加载行");
      this.lock(true);
      this.loadRows(
        lines(this),
        lines(this).first().get("line_number"),
        lines(this).last().get("line_number"),
        _load,
        true
      );
    },

    gotoLine: function (ln) {
      var $row = this.$(".row[data-line=" + ln + "]"),
          close = function (that) {
        that.workTableTop.gotoLineDialog.trigger("close");
      };
      if ($row.length) {
        $("body").animate({scrollTop: $row.offset().top - 35}, 500);
        return close(this);
      }
      if (this.lock()) return;
      this.lock(true);
      var file = this.fm.lf(),
          lines = function (that) {
            return that.fm.lf().lines;
          },
          _load = function () {
        this.lock(false);
        if (this.fm.lf() !== lines(this).fileModel) return;
        this.clearLines().renderInsertLines(lines(this));
        this.hideLoading();
      };
      if (!file) return;
      this.loading("正在加载行");
      this.loadRows(lines(this), ln, 100, _load, true);
      return close(this);
    },

    loadRows: function (lines, start, end, callback, reset, remove) {
      lines.fetch({
        data: { start: start, end: end },
        context: this,
        remove: remove,
        reset: reset,
        success: function (collection, resp, opts) {
          if (resp.status_detail) return Notifications.push("加载行时发生错误。", "warning");
          callback && callback.call(this);
        },
        error: function (collection, resp, opts) {
          this.hideLoading();
          if (resp.responseJSON.status_detail) {
            return Notifications.push("加载文件发生错误。", "warning");
          }
        }
      });
    }
  });

  var workTableTop = new WorkTableTop();
  var workTableMain = new WorkTableMain({wtt: workTableTop});
  workTableTop.setMain(workTableMain);
} );