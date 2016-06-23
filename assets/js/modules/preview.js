define(["backbone", "notifications"], function (Backbone, Notifications) {

  var FileLine = Backbone.Model.extend({
    idAttribute: "line_number"
  });

  var FileLineView = Backbone.View.extend({
    template: _.template($("#template-preview-line").html()),
    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var FileLines = Backbone.Collection.extend({
    model: FileLine,
    url: function () {
      var url = _s["SYS_CFG.site_uri"] + "task/" + _s["PREVIEW.task_id"];
          url += "/set/" + _s["PREVIEW.set_id"] + "/file/" + _s["PREVIEW.file_id"] + "/line/";
      return url;
    }
  });

  var PreviewFileLinesView = Backbone.View.extend({
    el: ".page",

    events: {
      "click .load-lines": "loadLines"
    },

    initialize: function () {
      this.collection = new FileLines();
      this.listenTo(this.collection, "sync", this.render);
      this._load(1, 100);
    },

    _load: function (start, end) {
      this.collection.fetch({
        data: {
          start: start,
          end: end,
          preview: 1,
          force_array: 1
        },
        context: this,
        merge: false,
        remove: false,
        error: function (c) {
          if (!c.size()) {
            this.$(".no-lines").removeClass("hidden");
            this.$(".row").remove();
          }
        },
        complete: function () {
          this.$(".loading-lines").addClass("hidden");
        }
      });
    },

    loadLines: function (e) {
      e && e.preventDefault();
      this._load(this.collection.size() + 1, this.collection.size() + 100);
    },

    render: function (c, resp) {
      if (resp.length >= 1) {
        this.$(".load-lines").removeClass("hidden");
      }
      if (c.size() === +_s["PREVIEW.lines"]) {
        this.$(".load-lines").addClass("hidden");
      }

      c.each(function (m) {
        if (this.$(".row[data-row-id=" + m.id + "]").length) return;
        this.$(".rows").append((new FileLineView({model: m})).render());
      }, this);
    }
  });

  $(function () {
    new PreviewFileLinesView();
  });
});