/** Powered by ResTrans */
(function (){
  // requireJS 配置
  require.config({
    // waitSeconds: ,
    baseUrl: _s["SYS_CFG.assets_url"] + "js/",
    paths: {
      jquery: "./third-party/jquery",
      backbone: "./third-party/backbone",
      lodash: "./third-party/lodash",
      autosize: "./third-party/autosize",
      home: "./modules/home",
      message: "./modules/message",
      navbar: "./modules/navbar",
      notifications: "./modules/notifications",
      org: "./modules/org",
      preview: "./modules/preview",
      profile: "./modules/profile",
      setting: "./modules/setting",
      start: "./modules/start",
      task: "./modules/task",
      worktable: "./modules/worktable"
    },
    shim: {
      backbone: {
        deps: ["jquery", "lodash"]
      }
    }
  });
  // 加载模块
  var modulesString = document.getElementById("require").getAttribute("data-modules");
  _s.reqErrorHandle = function (
    resp,
    baseErrorMessage,
    unknownErrorMessage,
    popupNotification,
    notificationType,
    Notifications
  ) {
    var q = function () {
      popupNotification && Notifications.push(unknownErrorMessage, notificationType);
      return unknownErrorMessage;
    };
    unknownErrorMessage = !unknownErrorMessage ? "未知错误。" : unknownErrorMessage;
    if (!resp) return q();
    resp.hasOwnProperty("responseJSON") && (resp = resp.responseJSON);
    if (!resp.hasOwnProperty("status_short") || !resp.hasOwnProperty("status_detail")) return q();

    resp.status_detail === null && (resp.status_detail = "未知错误 / 失败的原因。");
    var errorMessage = baseErrorMessage + resp.status_detail;
    popupNotification && Notifications.push(errorMessage, notificationType);
    return errorMessage;
  };

  _s.xescape = function (string) {
    return _.escape(string).replace(/\n/g, "<br>");
  };
  require(modulesString.split(","));
})();
