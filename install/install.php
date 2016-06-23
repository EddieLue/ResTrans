<?php
  header("Content-Type: application/json");
  require "../vendor/autoload.php";

  $send400 = function () {
    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
  };

  $random = function () {
    return mt_rand(1, 9999);
  };

  $randomKey = function () {
    return sha1(time() . mt_rand(1, PHP_INT_MAX));
  };

  /**
   * 接收参数
   */
  $dsn = $_POST["dsn"];
  $dbUser = $_POST["db_user"];
  $dbPass = $_POST["db_pass"];
  $dbPrefix = $_POST["db_prefix"];
  $adminEmail = $_POST["admin_email"];
  $adminUserName = $_POST["admin_username"];
  $adminPassword = $_POST["admin_password"];
  $siteUrl = $_POST["site_url"];
  /**
   * 连接并写入数据库
   */
  try {
    $pdo = new PDO($dsn, $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
    $send400();
    echo json_encode(["error_message" => "数据库连接失败。", "error_code" => 1]);
    exit;
  }

  try {
    $createOptionsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}options` (
        `name` VARCHAR(100) NOT NULL,
        `value` MEDIUMTEXT NOT NULL,
        PRIMARY KEY (`name`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB"
    );

    $createMessagesTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}messages` (
        `message_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `owner` INT(10) UNSIGNED NOT NULL,
        `otherside` INT(10) UNSIGNED NOT NULL,
        `type` TINYINT(3) UNSIGNED NOT NULL,
        `content` VARCHAR(1000) NOT NULL,
        `unread` TINYINT(3) UNSIGNED NOT NULL,
        `created` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`message_id`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $createNotificationsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}notifications` (
        `notification_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `type` TINYINT(3) UNSIGNED NOT NULL,
        `target_id` INT(10) UNSIGNED NOT NULL,
        `sender` INT(10) UNSIGNED NOT NULL,
        `receiver` INT(10) UNSIGNED NOT NULL,
        `view_status` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
        `status` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
        `created` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`notification_id`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $createRetrieveTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}retrieve` (
        `token` CHAR(40) NOT NULL,
        `expire` INT(10) UNSIGNED NOT NULL,
        `user_id` INT(10) UNSIGNED NOT NULL,
        UNIQUE INDEX `retrieve_token` (`token`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB"
    );

    $createVerificationsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}verifications` (
        `code` CHAR(40) NOT NULL,
        `expire` INT(10) UNSIGNED NOT NULL,
        `user` INT(10) UNSIGNED NOT NULL,
        `email` VARCHAR(128) NOT NULL,
        UNIQUE INDEX `verification_code` (`code`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB"
    );

    $createWorkingsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}working_sets` (
        `hash` CHAR(40) NOT NULL,
        `user_id` INT(10) UNSIGNED NOT NULL,
        `organization_id` INT(10) UNSIGNED NOT NULL,
        `task_id` INT(10) UNSIGNED NOT NULL,
        `last_set_id` INT(10) UNSIGNED NOT NULL,
        `last_file_id` INT(10) UNSIGNED NOT NULL,
        `last_line` INT(10) UNSIGNED NOT NULL,
        `created` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`hash`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB"
    );

    $createSessionsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}sessions` (
        `user_id` INT(11) NOT NULL,
        `session_id` CHAR(40) NOT NULL,
        `expire` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`session_id`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB"
    );

    $createUsersTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}users` (
        `user_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(12) NOT NULL,
        `email` VARCHAR(100) NULL DEFAULT NULL,
        `password` CHAR(60) NOT NULL,
        `signup` INT(10) UNSIGNED NOT NULL,
        `last_login` INT(10) UNSIGNED NOT NULL,
        `blocked` TINYINT(4) UNSIGNED NOT NULL DEFAULT '0',
        `gender` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
        `public_email` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
        `receive_message` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1',
        `send_message` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1',
        `admin` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
        PRIMARY KEY (`user_id`),
        UNIQUE INDEX `user` (`name`, `email`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $createOrganizationsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}organizations` (
        `organization_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT(10) UNSIGNED NOT NULL,
        `name` VARCHAR(20) NOT NULL,
        `description` VARCHAR(500) NULL DEFAULT NULL,
        `maximum` INT(10) UNSIGNED NOT NULL,
        `created` INT(10) UNSIGNED NOT NULL,
        `join_mode` TINYINT(3) UNSIGNED NOT NULL,
        `accessibility` TINYINT(3) UNSIGNED NOT NULL,
        `default_privileges` TINYINT(3) UNSIGNED NOT NULL,
        `member_create_task` TINYINT(3) UNSIGNED NOT NULL,
        `member_total` INT(10) UNSIGNED NOT NULL DEFAULT '0',
        `task_total` INT(10) UNSIGNED NOT NULL DEFAULT '0',
        `discussion_total` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`organization_id`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $createOrganizationUsersTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}user_organization` (
        `organization_id` INT(10) UNSIGNED NOT NULL,
        `user_id` INT(10) UNSIGNED NOT NULL,
        `joined` INT(10) UNSIGNED NOT NULL DEFAULT '0',
        `translate` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
        `proofread` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
        `manage` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
        `upload` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0'
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB"
    );

    $createTasksTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}tasks` (
        `task_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `organization_id` INT(10) UNSIGNED NOT NULL,
        `user_id` INT(10) UNSIGNED NOT NULL,
        `name` VARCHAR(20) NOT NULL,
        `description` VARCHAR(1000) NOT NULL,
        `created` INT(10) UNSIGNED NOT NULL,
        `percentage` FLOAT UNSIGNED NOT NULL,
        `original_language` CHAR(5) NOT NULL,
        `target_language` CHAR(5) NOT NULL,
        `set_total` INT(10) UNSIGNED NOT NULL,
        `frozen` TINYINT(3) UNSIGNED NOT NULL,
        `api_key` CHAR(40) NOT NULL,
        `api_request` INT(10) UNSIGNED NOT NULL DEFAULT '0',
        PRIMARY KEY (`task_id`),
        UNIQUE INDEX `api_key` (`api_key`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $createSetsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}sets` (
        `set_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(50) NOT NULL,
        `task_id` INT(10) UNSIGNED NOT NULL,
        `user_id` INT(10) UNSIGNED NOT NULL,
        `file_total` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`set_id`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $createFilesTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}files` (
        `file_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `set_id` INT(10) UNSIGNED NOT NULL,
        `name` VARCHAR(256) NOT NULL,
        `extension` VARCHAR(12) NOT NULL,
        `path` CHAR(80) NOT NULL,
        `uploader` INT(10) UNSIGNED NOT NULL,
        `size` INT(10) UNSIGNED NOT NULL,
        `line` INT(10) UNSIGNED NOT NULL,
        `translatable` INT(10) UNSIGNED NOT NULL,
        `percentage` FLOAT UNSIGNED NOT NULL,
        `original_language` CHAR(5) NOT NULL,
        `target_language` CHAR(5) NOT NULL,
        `created` INT(10) UNSIGNED NOT NULL,
        `last_contributor` INT(10) UNSIGNED NOT NULL,
        `last_contributed` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`file_id`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $createTranslationsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}translations` (
        `translation_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `file_id` INT(10) UNSIGNED NOT NULL,
        `line` INT(10) UNSIGNED NOT NULL,
        `text` TEXT NOT NULL,
        `best_translation` TINYINT(3) UNSIGNED NOT NULL,
        `contributor` INT(10) UNSIGNED NOT NULL,
        `contributed` INT(10) UNSIGNED NOT NULL,
        `proofreader` INT(10) UNSIGNED NOT NULL DEFAULT '0',
        PRIMARY KEY (`translation_id`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $createDiscussionsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}discussions` (
        `discussion_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `organization_id` INT(10) UNSIGNED NOT NULL,
        `user_id` INT(10) UNSIGNED NOT NULL,
        `content` VARCHAR(500) NOT NULL,
        `comment_total` INT(10) UNSIGNED NOT NULL,
        `created` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`discussion_id`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $createDiscussionCommentsTable = $pdo->query(
      "CREATE TABLE IF NOT EXISTS `{$dbPrefix}discussion_comments` (
        `comment_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `organization_id` INT(10) UNSIGNED NOT NULL,
        `discussion_id` INT(10) UNSIGNED NOT NULL,
        `user_id` INT(10) UNSIGNED NOT NULL,
        `parent_id` INT(10) UNSIGNED NOT NULL,
        `content` VARCHAR(500) NOT NULL,
        `created` INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (`comment_id`)
      )
      COLLATE='utf8mb4_general_ci'
      ENGINE=InnoDB
      AUTO_INCREMENT={$random()}"
    );

    $pdo->beginTransaction();
    $password = password_hash($adminPassword, PASSWORD_BCRYPT);
    $addAdminUser = $pdo->prepare(
      "INSERT INTO `{$dbPrefix}users` (`name`, `email`, `password`, `signup`, `admin`) VALUES (?, ?, ?, ?, 1)");
    $addAdminUser->bindValue(1, $adminUserName, PDO::PARAM_STR);
    $addAdminUser->bindValue(2, $adminEmail, PDO::PARAM_STR);
    $addAdminUser->bindValue(3, $password, PDO::PARAM_STR);
    $addAdminUser->bindValue(4, time(), PDO::PARAM_INT);
    $addAdminUser->execute();

    $addOptionData = $pdo->query(
      "INSERT INTO `{$dbPrefix}options` (`name`, `value`) VALUES
        ('anonymous_access', '1'),
        ('login_captcha', '1'),
        ('member_create_organization', '1'),
        ('register', '1'),
        ('send_email', '0')"
    );

    if (!$addAdminUser ||!$addOptionData) throw new \Exception();

    $pdo->commit();
  } catch (\Exception $e) {var_dump($e);
    $pdo->rollBack();
    $send400();
    echo json_encode(["error_message" => "创建表时出现错误。", "error_code" => 2]);
  }

  /**
   * 写入配置文件
   */
  $url = parse_url($siteUrl);
  $configText = <<<CONFIG
<?php
  return [
    /**
     * 语言
     */
    "language"            => "zh-cn",
    /**
     * 时区
     */
    "time_zone"           => "Asia/Shanghai",
    /**
     * URL & URI
     */
    "site_url"            => "$siteUrl",
    "site_uri"            => "$url[path]",
    /**
     * 出错时显示回调信息
     */
    "exception_backtrace" => false,
    /**
     * 定时任务触发密钥
     */
    "cron_key"            => "{$randomKey()}",
    /**
     * URL 计算密钥
     */
    "url_encrypt_key"     => "{$randomKey()}",
    /**
     * 数据库信息
     */
    "database"            => [
      "type"         => "Mysql",
      "dsn"          => "$dsn",
      "user"         => "$dbUser",
      "pass"         => "$dbPass",
      "table_prefix" => "$dbPrefix"
    ],
    /**
     * 注册（找回密码）邮件发送
     */
    "mail"                => [
      "smtp_host"     => "",
      "smtp_user"     => "",
      "smtp_password" => "",
      "smtp_secure"   => "ssl",
      "smtp_port"     => 465
    ],
    /**
     * 机械翻译配置
     * 目前仅支持 Microsoft Translator
     * ResTrans 会按顺序轮询每个 API ，直至用尽为止。
     */
    "api"                 => [ 
      [
        "api_name"    => "MicrosoftTranslator",
        "secret"      => "",
        "client_id"   => ""
      ],
    ]
  ];
?>
CONFIG;
  if (file_put_contents("../config/config.php", $configText)) {
    echo json_encode(["error_message" => "", "error_code" => 0]);
  } else {
    $send400();
    echo json_encode(["error_message" => "写入配置文件时失败。", "error_code" => 3]);
  }
?>