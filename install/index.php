<?php
  $protocol = empty($_SERVER["HTTPS"]) ? "http://" : "https://";
  $host = $_SERVER["HTTP_HOST"];
  $siteUri = preg_replace("/install\/(index.php)?(.*)/", "", $_SERVER["PHP_SELF"]);
?>
<!DOCTYPE html>
<html lang="zh-cmn-hans">
<head>
  <meta charset="UTF-8">
  <title>安装 / ResTrans</title>
  <link rel=stylesheet href="../assets/css/style.css">
  <link rel=stylesheet href="./style.css">
  <script src="../assets/js/third-party/jquery.js"></script>
  <script>
    $(function () {
      $dsn = $("input[name=dsn]");
      $dbUser = $("input[name=db-user]");
      $dbPass = $("input[name=db-pass]");
      $dbPrefix = $("input[name=db-prefix]");
      $adminEmail = $("input[name=admin-email]");
      $adminUserName = $("input[name=admin-user]");
      $adminPassword = $("input[name=admin-pass]");
      $adminPasswordAgain = $("input[name=admin-pass-again]");
      $siteUrl = $("input[name=site-url]");

      var notice = function (text, type) {
        $("p.notice").text(text);
        type === "" ?
          ($("p.notice").removeClass("warning") && $("p.notice").removeClass("success")) :
          $("p.notice").addClass(type);
      };

      $(".form").on("submit", function(e) {
        e && e.preventDefault();
        if (
          !$dsn.val().length ||
          !$dbUser.val().length ||
          !$dbPass.val().length ||
          !$dbPrefix.val().length) return notice("数据库信息有误。", "warning");
        if (
          !$adminEmail.val().length ||
          !$adminUserName.val().length ||
          ($adminUserName.val().length < 2 || $adminUserName.val().length > 12) ||
          !$adminPassword.val().length ||
          !$adminPasswordAgain.val() ||
          ($adminPassword.val() !== $adminPasswordAgain.val())) {
          return notice("管理员信息有误。", "warning");
        }
        if (!$siteUrl.val().length) return notice("URL 地址有误。", "warning");
        $.ajax({
          url: "install.php",
          method: "POST",
          dataType: "json",
          data: {
            "dsn": $dsn.val(),
            "db_user": $dbUser.val(),
            "db_pass": $dbPass.val(),
            "db_prefix": $dbPrefix.val(),
            "admin_email": $adminEmail.val(),
            "admin_username": $adminUserName.val(),
            "admin_password": $adminPassword.val(),
            "site_url": $siteUrl.val()
          },
          beforeSend: function () {
            $(".install-now").text("正在安装···").attr("disabled", true);
          },
          success: function (data) {
            if (data.error_code !== 0) {
            $(".install-now").html("现在安装<i class=\"right\"></i>").removeAttr("disabled");
              return notice("安装似乎并未完成。", "warning");
            }
            $(".install-now").html("安装完成");
            return notice("安装已完成，请删除 install 目录。", "success");
          },
          error: function (data) {
            $(".install-now").html("现在安装<i class=\"right\"></i>").removeAttr("disabled");
            return notice(data.responseJSON.error_message, "warning");
          }
        });
      });
    });
  </script>
</head>
<body>
  <div class="install">
    <div class="top">
      <div class="logo"></div>
    </div>
    <form class="form" method="post">
      <div class="hr">
        <span class="text">
          <span>数据库连接</span>
        </span>
      </div>
      <input type="text" class="re-input" name="dsn" placeholder="MySQL DSN" value="mysql:host=; port=3306; dbname=; charset=utf8mb4;">
      <input type="text" name="db-user" class="re-input input-split input-split-first" placeholder="数据库连接用户">
      <input type="password" name="db-pass" class="re-input input-split" placeholder="数据库连接密码">
      <input type="text" name="db-prefix" class="re-input input-split" value="restrans_" placeholder="数据表前缀">
      <div class="hr">
        <span class="text">
          <span>管理员信息</span>
        </span>
      </div>
      <input type="email" name="admin-email" class="re-input input-split input-split-first" placeholder="邮箱地址">
      <input type="text" name="admin-user" class="re-input input-split" placeholder="用户名">
      <input type="password" name="admin-pass" class="re-input input-split input-split-first" placeholder="密码">
      <input type="password" name="admin-pass-again" class="re-input input-split" placeholder="密码（再次）">
      <div class="hr">
        <span class="text">
          <span>可能无需修改的···</span>
        </span>
      </div>
      <input type="text" name="site-url" class="re-input" placeholder="访问 ResTrans 的 URL" value="<?php echo $protocol. $host . $siteUri ?>">
      <p class="notice">稍后您可在 config/config.php 文件中配置 SMTP 和翻译 API 等信息。</p>
      <button type="submit" class="re-button re-button-primary install-now">现在安装<i class="right"></i></button>
    </form>
    <span class="footer">&copy; ResTrans 2016</span>
  </div>
</body>
</html>