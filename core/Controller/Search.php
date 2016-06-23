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

namespace ResTrans\Core\Controller;

use ResTrans\Core;
use ResTrans\Core\Route;
use ResTrans\Core\View;
use ResTrans\Core\Event;
use ResTrans\Core\Model\ModelConventions;
use ResTrans\Core\CommonException;

class Search extends ControllerConventions {

  public $relationModel = "Search";

  public function getIndex (Route $route, View $view, ModelConventions $model, Event $event) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $event->trigger("Base:hasUnreadNotifications", $currentUser["user_id"], $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $currentUser["user_id"], $view);
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $view->setValue("USER.is_login", true)
        ->setArray("USER", $currentUser);
    } catch (CommonException $e) {
      /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $kw = $route->queryString("keyword", Route::HTTP_GET, ["trim"]);

    $tasks = $kw ? $model->searchTasks($kw, 1) : [];
    $tasks = $tasks ? $event->trigger("Proc:encodeTaskIds", $tasks) : [];

    $organizations = $kw ? $model->searchOrganizations($kw, 1) : [];
    $organizations = $organizations ? $event->trigger("Proc:encodeOrganizationIds", $organizations) : [];
    $results = ["tasks" => $tasks, "organizations" => $organizations, "kw" => $kw];

    $view->title( [ "搜索任务或组织" ] )
      ->setArray("SEARCH", $results)
      ->setAppToken()
      ->feData( [ "APP.token", "USER.is_login", "SEARCH.tasks", "SEARCH.organizations", "SEARCH.kw" ] )
      ->feModules( "navbar,start" )
      ->init( "Search" );
  }

  public function jsonGETIndex (Route $route, ModelConventions $model) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
    } catch (CommonException $e) {
      $event->trigger("Base:needLogin", $route);
    }

    $kw = $route->queryString("s_kw", Route::HTTP_GET, ["trim"]);
    $type = $route->queryString("s_t", Route::HTTP_GET, ["trim"]);
    $start = $route->queryString("start", Route::HTTP_GET, ["intval"]);

    if ($type === "t") {
      $result = $kw && $start ? $model->searchTasks($kw, $start, true) : [];
      $result = $result ?  $event->trigger("Proc:encodeTaskIds", $result) : [];
    } else if ($type === "o") {
      $result = $kw && $start ? $model->searchOrganizations($kw, $start, true) : [];
      $result = $result ? $event->trigger("Proc:encodeOrganizationIds", $result) : [];
    }

    $route->jsonReturn($result);
  }
}