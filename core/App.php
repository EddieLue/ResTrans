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

namespace ResTrans\Core;

/**
 * 启动入口文件
 */
class App {

  public $event;

  public $config;

  public $route;

  public $globalOptions;

  const VERSION = "1.0.0-a";

  /**
   * 异常控制器
   */
  protected function exceptionHandler() {
    set_exception_handler( function ($exception) {
      ( new Controller\InternalError( $this ) )->getIndex( $exception );
    } );
  }

  /**
   * 启动文件入口
   * @param array $config 配置
   */
  public function __construct( $config ) {
    // 载入异常处理器
    $this->exceptionHandler();

    // 载入配置 + 设置时区
    $this->config = $config;
    is_array($this->config) or die( "Config Error!" );
    date_default_timezone_set( $config["time_zone"] );

    // 载入语言切换
    $this->language = Lang::init($this);

    // 载入事件管理器 + 预设定事件
    $this->event = new Event( $this );
    $this->preHook();

    // 触发启动事件
    $this->event->trigger( "Base:appInit" );
    $this->globalOptions = $this->event->trigger( "Base:loadOptions" );

    // 启动路由
    $this->route = new Route( $this );
    $this->route->start();
  }

  /**
   * 预注册事件
   */
  protected function preHook() {
    new Event\Base($this);
    new Event\Verify($this);
    new Event\Proc($this);
  }

  /**
   * 与主程序分离的插件加载器
   * @param  string $pluginName 插件目录名称
   * @param  string $pluginFile 插件文件名
   * @return object             插件实例
   */
  public function plugin( $pluginName, $pluginFile ) {
    $directory = realpath( CORE_PATH . "Plugin/" . $pluginName ) . "/";
    $file = $pluginFile . ".php";

    if ( ! is_dir( $directory ) || ! is_readable( $directory . $file ) ) return false;

    include_once $directory . $file;
    if ( class_exists( $pluginFile ) ) return new $pluginFile();
    return true;
  }

  /**
   * ResTrans 获取选项值（数据库）
   * @param  string $optionName 选项名称
   */
  public function getOption ($optionName) {
    return $this->globalOptions[$optionName];
  }

  /**
   * sha1 随机生成器
   * @return string sha1 值
   */
  public function sha1Gen() {
    return sha1(mt_rand(1,PHP_INT_MAX) . microtime());
  }

  /**
   * 随机字符生成器
   * @param  int $length 长度
   * @return string         随机生成的字符
   */
  public function charsGen($length) {
    if ( count( $length ) > 62 ) return null;
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr( str_shuffle( $chars ), 0, (int)$length );
  }

  /**
   * 全局的时间格式化方法
   * @param  int  $timestamp 时间戳
   * @param  boolean $full      是否返回完整的时间格式
   * @return string             格式化后的时间
   */
  public function fTime( $timestamp, $full = false ) {

    $nowTime = time();
    $thisYear = (int)date("y");
    $timestampYear = (int)date( "y", $timestamp );
    $distance = time() - $timestamp;

    $todayStart = mktime( 0, 0, 0 );
    $todayEnd = mktime( 23, 59, 59 );
    $yesterdayStart = $todayStart - 86400 * 24;

    $year = $full ? "年" : "-";
    $month = $full ? "月" : "-";
    $day = $full ? "日" : "";

    switch ( $distance ) {
      case $distance < 60:
        return "不久之前";
        break;
      case $distance > 60 && $distance < 3600:
        return floor( $distance / 60 ) . "分钟前";
        break;
      case $distance >= $todayStart && $distance <= $todayEnd:
        return "今天" . date( "i:s", $timestamp );
        break;
      case $distance >= $yesterdayStart && $distance <= ( $todayStart - 1 ):
        return "昨天" . date( "i:s", $timestamp );
        break;
      default:
        $output = "";
        if ( $thisYear !== $timestampYear ) $output = $timestampYear . $year;

        $full ? $output .= date( "n{$month}d{$day} H:i", $timestamp ) :
                $output .= date( "n{$month}d{$day}", $timestamp );

        return $output;
        break;
    }
  }

  /**
   * 获得当前页面 url
   * @return string url
   */
  public function getCurrentUrl () {
    $currentUrl = empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] === "off" ? "http://" : "https://";
    $currentUrl .= $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    return $currentUrl;
  }

  /**
   * 获取头像地址
   * @param  string $email 邮箱地址
   * @return string        头像地址
   */
  public function getAvatarLink ($email) {
    return "https://gravatar.moefont.com/avatar/" . md5($email) . "?s=32";
  }
}