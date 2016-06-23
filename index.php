<?php
/**
 * --------------------------------------------------
 * (c) ResTrans 2016
 * --------------------------------------------------
 * Apache License 2.0
 * --------------------------------------------------
 * get.restrans.com
 * --------------------------------------------------
*/

error_reporting(-1);
$dirSep = DIRECTORY_SEPARATOR;
define( "CORE_PATH", __DIR__ . $dirSep . "core" . $dirSep );
define( "DATA_PATH", __DIR__ . $dirSep . "data" . $dirSep );
/** 获得配置数据 */
$config = require "config/config.php";

/** 引入应用文件 */
require "vendor/autoload.php";
require CORE_PATH . "App.php";

/** 创建应用实例 */
new ResTrans\Core\App($config);