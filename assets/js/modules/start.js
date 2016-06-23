define(["backbone", "notifications"], function(Backbone, Notifications){

  var Organization = Backbone.Model.extend({
    idAttribute: "organization_id"
  });

  var Organizations  = Backbone.Collection.extend({
    model: Organization,
    url: _s["SYS_CFG.site_uri"] + "organization/"
  });

  var OrganizationView = Backbone.View.extend({
    template: _.template($("#template-organization").html()),

    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var Task = Backbone.Model.extend({
    idAttribute: "task_id"
  });

  var Tasks  = Backbone.Collection.extend({
    model: Task,
    url: _s["SYS_CFG.site_uri"] + "start/task/"
  });

  var TaskView = Backbone.View.extend({
    template: _.template($("#template-start-task").html()),

    render: function () {
      return this.template(this.model.attributes);
    }
  });

  var OrganizationsView = Backbone.View.extend({
    el: ".organizations",

    events: {
      "click .page-forward": "fetchOrganizations"
    },

    initialize: function () {
      this.collection = new Organizations(_s["HOME.organizations"]);
      this.listenTo(this.collection, "sync", this.render);
    },

    fetchOrganizations: function (e) {
      e && e.preventDefault();
      $(e.target).attr("disabled", true);
      this.collection.fetch({
        data: {
          start: this.collection.size() + 1
        },
        merge: false,
        remove: false,
        context: this,
        error: function (c, resp) {
          _s.reqErrorHandle(resp, "拉取更多组织失败", null, true, "", Notifications);
        },
        complete: function () {
          $(e.target).removeAttr("disabled");
        }
      });
    },

    render: function (collection, resp) {
      if (resp.length < 1) this.$(".page-forward").remove();
      collection.each(function (model) {
        if (this.$("tr[data-organization-id=" + model.id + "]").length) return;
        var view = new OrganizationView({model: model});
        this.$("tbody").append(view.render());
        view.setElement(this.$("tr[data-organization-id=" + model.id + "]"));
      }, this);
    }
  });

  var StartView = Backbone.View.extend({
    el: ".tasks",

    events: {
      "click .page-forward": "fetchTasks"
    },

    initialize: function () {
      this.collection = new Tasks(_s["HOME.tasks"]);
      this.listenTo(this.collection, "sync", this.render);
    },

    fetchTasks: function (e) {
      e && e.preventDefault();
      $(e.target).attr("disabled", true);
      this.collection.fetch({
        data: {
          start: this.collection.size() + 1
        },
        merge: false,
        remove: false,
        context: this,
        error: function (c, resp) {
          _s.reqErrorHandle(resp, "拉取更多任务失败", null, true, "", Notifications);

        },
        complete: function () {
          $(e.target).removeAttr("disabled");
        }
      });
    },

    render: function (collection, resp) {
      if (resp.length < 1) this.$("tfoot").remove();
      collection.each(function (model) {
        if (this.$("tr[data-task-id=" + model.id + "]").length) return;
        var view = new TaskView({model: model});
        this.$("tbody").append(view.render());
        view.setElement(this.$("tr[data-task-id=" + model.id + "]"));
      }, this);
    }
  });

  var SearchItem = Backbone.Model.extend();

  var SIView = Backbone.View.extend({
    taskTemplate: _.template($("#template-search-item-t").html()),
    organizationTemplate: _.template($("#template-search-item-o").html()),
    render: function () {
      return this.model.idAttribute === "task_id" ?
        this.taskTemplate(this.model.attributes) : 
        this.organizationTemplate(this.model.attributes);
    }
  });

  var SearchItems = Backbone.Collection.extend({
    url: _s["SYS_CFG.site_uri"] + "search/",

    initialize: function (m, opts) {
      opts.type === 0 && (this.model = SearchItem.extend({idAttribute: "task_id"}));
      opts.type === 1 && (this.model = SearchItem.extend({idAttribute: "organization_id"}));
    }
  });

  var SearchView = Backbone.View.extend({
    el: ".result",

    events: {
      "click .load-tasks": "loadTasks",
      "click .load-organizations": "loadOrganizations"
    },

    initialize: function () {
      this.tasks = new SearchItems(_s["SEARCH.tasks"], {type: 0});
      this.organizations = new SearchItems(_s["SEARCH.organizations"], {type: 1});
      this.listenTo(this.organizations, "sync", this.renderO);
      this.listenTo(this.tasks, "sync", this.renderT);
    },

    loadTasks: function (e) {
      e && e.preventDefault();
      this.tasks.fetch({
        data: {
          "s_kw": _s["SEARCH.kw"],
          "s_t": "t",
          "start": this.tasks.size() + 1
        },
        remove: false,
        merge: false,
        context: this,
        error: function () {
          this.$(".load-tasks").remove();
        }
      });
    },

    loadOrganizations: function (e) {
      e && e.preventDefault();
      this.organizations.fetch({
        data: {
          "s_kw": _s["SEARCH.kw"],
          "s_t": "o",
          "start": this.organizations.size() + 1
        },
        remove: false,
        merge: false,
        context: this,
        error: function () {
          this.$(".load-organizations").remove();
        }
      });
    },

    renderT: function (c, resp) {
      return this.render.call(this, this.tasks, resp);
    },

    renderO: function (c, resp) {
      return this.render.call(this, this.organizations, resp);
    },

    render: function (collection, resp) {
      var type = collection === this.tasks ? 0 : 1;

      if (resp && resp.length < 1) {
        type ? this.$(".load-organizations").remove() : this.$(".load-tasks").remove();
      }

      collection.each(function (model) {
        if ( (!type && this.$(".task[data-task-id=" + model.id + "]").length) ||
             (type && this.$(".organization[data-organization-id=" + model.id + "]").length) ) {
          return;
        }

        var view = new SIView({model: model});
        type ? this.$(".organization-result").append(view.render()) :
          this.$(".task-result").append(view.render());
      }, this);
    }
  });

  new OrganizationsView();
  new StartView();
  new SearchView();
});