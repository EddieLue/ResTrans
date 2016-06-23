<!DOCTYPE html>
<html lang="zh-cmn-Hans">
<head>
  <meta charset="UTF-8">
  <title>ResTrans 发生错误</title>
  <style type="text/css">
    body {
      padding: 10px;
      margin: 0;
      font-size: 12px;
    }
    .center {
      margin: 0 auto;
      width: 640px;
    }
    .header {
      font-size: 18px;
      margin: 0;
      padding: 10px;
      color: #2980b9;
    }
    .cause {
      display: block;
      font-size: 13px;
      background: #ccc;
      padding: 10px;
      color: #2980b9;
      
    }
    .backtrace {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .backtrace .item {
      display: block;
      padding: 8px 10px;
      background: #eee;
      transition: transform .2s ease-out;
    }
    .backtrace .item:hover {
      background: #ddd;
      transform: scale(.98);
      transition: transform .2s ease-in;
    }
    .footer {
      text-align: right;
      color: #aaa;
    }
  </style>
</head>
<body>
  <div class="center">
    <h1 class="header">很抱歉， ResTrans 在运行时出现错误。</h1>
    <span class="cause">
      可能的原因：<strong><?php $v("ERROR.cause") ?> <?php if ($vr("ERROR.show_backtrace")): echo "("; $v("ERROR.exception_class"); echo ")"; endif; ?></strong></span>
<?php if ($vr("ERROR.show_backtrace")): ?>
      <ul class="backtrace">
      <?php foreach( $vr("ERROR.trace") as $trace ): ?>
      <?php $item = '<li class="item">'. @$trace["file"] . "#line:". @$trace["line"] .'</li>'; ?>
          <?php ( isset( $trace["file"] ) && isset( $trace["line"] ) ) AND print( $item ) ?>
      <?php endforeach; ?>
      </ul>
<?php endif; ?>
      <div class="footer">&copy; ResTrans</div>
  </div>
</body>
</html>