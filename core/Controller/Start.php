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
use ResTrans\Core\App;
use ResTrans\Core\CommonException;
use ResTrans\Core\Event;
use ResTrans\Core\View;
use ResTrans\Core\Route;
use ResTrans\Core\Model\ModelConventions;

class Start extends ControllerConventions {

  public $relationModel = "Start";

  public function getIndex(Event $event, View $view, ModelConventions $model) {
    $currentUserId = $currentUser = null;

    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $currentUserId = $currentUser["user_id"];
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $view->setValue("USER.is_login", true);
      $event->trigger("Base:hasUnreadNotifications", $currentUserId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $currentUserId, $view);
    } catch (CommonException $e) {
      /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $tasks = $model->getAllTasks($currentUserId, 1);
    if ($tasks) {
      $tasks = $event->trigger("Proc:tasksForRestful", $tasks);
      $tasks = $event->trigger("Proc:encodeOrganizationIds", $tasks);
      $tasks = $event->trigger("Proc:encodeTaskIds", $tasks);
      $tasks = $event->trigger("Proc:encodeUserIds", $tasks);
    }

    $view->setValue( "NAV.highlight", "start" )
      ->setArray("USER", (array)$currentUser)
      ->title( [ "首页" ] )
      ->setValue("HOME.tasks", $tasks)
      ->setAppToken()
      ->feData( [ "APP.token", "USER.is_login", "HOME.tasks" ] )
      ->feModules( "navbar,start" )
      ->init( "Start" );
  }

  public function jsonGETTask (Event $event, ModelConventions $model, Route $route) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
    } catch (CommonException $e) {
      $event->trigger("Base:needLogin", $route);
    }

    $tasks = $model->getAllTasks(
      $currentUser["user_id"],
      $route->queryString("start", Route::HTTP_GET, ["intval"])
    );

    if ($tasks) {
      $tasks = $event->trigger("Proc:tasksForRestful", $tasks);
      $tasks = $event->trigger("Proc:encodeOrganizationIds", $tasks);
      $tasks = $event->trigger("Proc:encodeTaskIds", $tasks);
      $tasks = $event->trigger("Proc:encodeUserIds", $tasks);
    }

    $route->jsonReturn((array)$tasks);
  }

}