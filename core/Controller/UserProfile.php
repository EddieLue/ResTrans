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
use ResTrans\Core\Route;
use ResTrans\Core\User;
use ResTrans\Core\View;
use ResTrans\Core\Event;
use ResTrans\Core\CommonException;
use ResTrans\Core\RouteResolveException;
use ResTrans\Core\Model;
use ResTrans\Core\Model\ModelConventions;

class UserProfile extends ControllerConventions {

  public $relationModel = "UserProfile";

  public function __construct( Core\App $app ) {
    parent::__construct( $app );
  }

  public function getProfile($userId, View $view, ModelConventions $model, App $app,
    Event $event) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $currentUserId = $currentUser["user_id"];
      $event->trigger("Base:hasUnreadNotifications", $currentUserId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $currentUserId, $view);
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $view->setValue("USER.is_login", true);
      $view->setArray("USER", $currentUser);
    } catch (CommonException $e) {
      $currentUserId = 0;
      $currentUser = [];
            /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $userId = $event->trigger("Proc:decodeUserIds", $userId);

    // 获得对方的信息
    // 获得对方的组织
    $user = User::instance($app, $model->db());
    try {
      $otherside = $user->findUserInfo($userId, Core\User::BY_ID);
    } catch (CommonException $e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode(), $e);
    }

    $otherside->last_login_time_friendly = $app->fTime($otherside->last_login, true);
    $otherside->signup_time_friendly = $app->fTime($otherside->signup, true);
    $third = $currentUserId !== $otherside->user_id;
    if (!$otherside->public_email && $third && !$currentUser["admin"]) {
      $view->setValue("OTHERSIDE.public_email", true);
    }

    $additionOuputOptions = [];
    if (count($currentUser) && $currentUser["admin"] && $otherside->user_id !== $currentUserId) {
      $view->setValue("OTHERSIDE.display_account_control", true);
      $additionOuputOptions = ["OTHERSIDE.blocked", "OTHERSIDE.send_message"];
    }

    if ($third) {
      $organizations = $model->getOrganizations($otherside->user_id, true);
    } else {
      $organizations = $model->getMyOrganizations($userId, true);
    }

    $otherside->user_id = $event->trigger("Proc:encodeUserIds", $otherside->user_id);

    $organizations = $organizations ? $event->trigger("Proc:encodeOrganizationIds", $organizations) : [];
    $organizationTotal = count($organizations);
    $organizations = array_slice($organizations, 0, 20);
    $organizations = $event->trigger("Proc:profileOrganizations", $organizations);

    $view->feModules("profile,navbar")
         ->title( [ $otherside->name, ($third ? "" : "我的") . "个人主页" ] )
         ->setArray("OTHERSIDE", (array)$otherside)
         ->setValue("OTHERSIDE.organizations", $organizations)
         ->setValue("OTHERSIDE.organization_total", $organizationTotal)
         ->setValue("OTHERSIDE.third", $third)
         ->setAppToken()
         ->feData( array_merge([ "APP.token", "OTHERSIDE.user_id", "OTHERSIDE.organizations",
          "OTHERSIDE.organization_total" ], $additionOuputOptions) )
         ->init( "UserProfile" );
  }

  public function jsonGETOrganization ($userId, Model\ModelConventions $model, Core\Event $event,
    Core\Route $route, Core\App $app) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $myUserId = $currentUser["user_id"];
    } catch (CommonException $e) {
      $event->trigger("Base:needLogin", $route);
    }

    $userId = $event->trigger("Proc:decodeUserIds", $userId);
    $user = User::instance($app, $model->db())->findUserInfo($userId, Core\User::BY_ID);

    // 是否已经登录
    $start  = (int)$route->queryString("start", Core\Route::HTTP_GET);
    $amount = (int)$route->queryString("amount", Core\Route::HTTP_GET);

    if ($myUserId !== $user->user_id) {
      $organizations = $model->getOrganizations($user->user_id, false, $start, $amount, true);
    } else {
      $organizations = $model->getMyOrganizations($myUserId, false, $start, $amount, true);
    }
    
    if ($organizations) {
      $organizations = $event->trigger("Proc:profileOrganizations", $organizations);
      $organizations = $event->trigger("Proc:encodeOrganizationIds", $organizations);
    }

    $route->jsonReturn($organizations);
  }

  public function jsonPOSTSetting ($userId, ModelConventions $model, Event $event, Route $route, App $app) {
    $currentUser = $event->trigger("Verify:isLogin");
    $currentUserId = $currentUser["user_id"];
    // 验证 token
    $event->trigger("Verify:checkToken", Route::HTTP_POST);

    $userId = $event->trigger("Proc:decodeUserIds", $userId);
    $thatUser = User::instance($app, $model->db())->findUserInfo($userId, USER::BY_ID);

    if (!$currentUser["admin"] || $thatUser->admin) {
      throw new Core\CommonException("permission_denied", 403);
    }

    $data = [
      "user_id" => $userId,
      "blocked" => $route->queryString("blocked", Core\Route::HTTP_POST, ["intval"]),
      "send_message" => $route->queryString("send_message", Core\Route::HTTP_POST, ["intval"]),
    ];

    $db = $model->db();
    $db->TSBegin();
    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $model->set( "UserSettings", $data );
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException($invalid);

    $model->save();
    $db->TSCommit();
    $route->jsonReturn($this->status("user_settings_saved", true));
  }
}