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

namespace ResTrans\Core\Event;

use ResTrans\Core;
use ResTrans\Core\Route;
use ResTrans\Core\View;
use ResTrans\Core\Model\ModelConventions;
use ResTrans\Core\SystemNotification;

class Base extends EventConventions {

  public function registrar() {
    $this->once( "appInit", "init" );
    $this->once( "loadOptions", "loadGlobalOptions" );
    $this->once( "appEnd", "end" );
    $this->listen("simplyLoginRedirect");
    $this->listen("loginRedirect");
    $this->listen("loggedInRedirect");
    $this->listen("loggedInReject");
    $this->listen("hasUnreadNotifications");
    $this->listen("hasUnreadMessages");
  }

  public function init() {
    session_start();
    ob_start();
  }

  public function end() {
    ob_end_flush();
  }

  public function loadGlobalOptions() {
    $db = Core\Database::instance( $this->appi )->connect();

    try {
      $loadGlobalOptions = $db->pdoPrepare( $db->select( "options", "*" ) );
      $loadGlobalOptions->execute();
      $optionsData = $loadGlobalOptions->fetchAll();

      if ( ! count( $optionsData ) ) throw new \Exception();

      $options = array();
      array_walk( $optionsData , function( $option ) use ( &$options ) {
        $options[$option->name] = $option->value;
      } );
    } catch ( \PDOException $e ) {
      // 抛出无法读取配置异常
      throw new Core\ResTransCoreException( "global_options_error" );
    }

    return $options;
  }

  /**
   * 简单的登录跳转控制，用于必须登录的某些情况，
   * 如 GET 任务设置、组织设置、个人设置、工作台等
   * @param  string $currentUrl 用于跳转的 URL
   */
  public function simplyLoginRedirect ($currentUrl = null) {
    $currentUrl = $currentUrl ? $currentUrl : $this->appi->getCurrentUrl();
    $mainUrl = $this->appi->config["site_url"] . "login?redirect=";
    $this->appi->route->redirect($mainUrl . $currentUrl);
    $this->trigger("Base:appEnd");
    exit();
  }

  /** 
   * 控制 GET 页面的登录跳转
   * @param  View   $view 视图对象，用于设置跳转URL
   */
  public function loginRedirect ($view = null) {
    $currentUrl = $this->appi->getCurrentUrl();
    $currentUrl = urlencode($currentUrl);
    $view && $view->setValue("HOME.current_url", $currentUrl);

    if ((int)$this->appi->getOption("anonymous_access") === 1) return;

    $this->trigger("Base:simplyLoginRedirect");
  }

  /**
   * 控制 AJAX GET 接口的登录跳转
   * @param  Route  $route 路由对象，用于设置返回提示
   */
  public function needLogin ($route = null) {
    if ((int)$this->appi->getOption("anonymous_access") === 1) return;
    $route && $route->jsonReturn(
      ["status_short" => "not_logged_in", "status_detail" => Core\L("not_logged_in")]
      );
    $this->trigger("Base:appEnd");
    exit();
  }

  public function loggedInRedirect ($route) {
    $route->redirect($this->appi->config["site_url"] . "start/");
    $this->trigger("Base:appEnd");
    exit();
  }

  public function loggedInReject ($route) {
    $route
      ->setResponseCode(400)
      ->jsonReturn(
      ["status_short" => "has_logged_in", "status_detail" => Core\L("has_logged_in")]
    );
    $this->trigger("Base:appEnd");
    exit();
  }

  public function hasUnreadNotifications ($userId, View $view) {
    if (Core\SystemNotification::init($this->appi)->hasUnread($userId)) {
      $view->setValue("NOTICE.point", true);
    }
  }

  public function hasUnreadMessages (ModelConventions $message, $userId, $view) {
    if ($message->hasUnread($userId)) $view->setValue("MESSAGE.point", true);
  }
}