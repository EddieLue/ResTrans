<?php
return <<<EMAIL
<!DOCTYPE html>
<html lang="zh-cmn-Hans">
<head>
  <meta charset="UTF-8">
  <title>用于重置 ResTrans 登录密码的链接</title>
  <style type="text/css">
   html, body {
    height: 100%;
   }
   body {
    background: #e0e0e0;
   }
    .main-section {
      margin: 0 auto;
      width: 450px;
      text-align: center;
      background: white;
      padding: 15px 0;
      box-shadow: 0 0 1px 1px #9e9e9e;
    }
    .restrans-name {
      font-size: 30px;
      color: #03a9f4;
    }
    .title {
      font-size: 14px;
      margin: 0 10px;
      color: #666;
    }
    .tip {
      font-size: 12px;
      color: #999;
      letter-spacing: 1px;
      margin: 20px 20px;
    }
    .hl {
      font-style: italic;
      font-size: 13px;
    }
    .main-button {
      border: none;
      padding: 10px 15px;
      color: white;
      background: #0091ea;
      display: inline-block;
      text-decoration: none;
      font-size: 14px;
    }
    .copyright {
      color: #aaa;
      display: block;
      text-align: center;
      font-size: 12px;
      margin: 5px;
    }
  </style>
</head>
<body>
  <div class="main-section">
    <div class="top">
      <span class="restrans-name">ResTrans</span>
      <span class="title">找回密码</span>
    </div>
    <p class="tip">
<span class="hl">如果您没有发起过请求，请忽略或删除此邮件。</span><br>
您收到此邮件是因为您曾经在此网站（{$app->config["site_url"]}）上注册过账号但忘记了密码，此邮件的链接能引导您重置网站的登录密码。
    </p>
    <a class="main-button" href="{$app->config["site_url"]}user/password/reset/{$token}">点击重设新密码（1 小时内有效）</a>
  </div>
  <span class="copyright">&copy; ResTrans</span>
</body>
</html>
EMAIL;
?>