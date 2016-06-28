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
use ResTrans\Core\View;
use ResTrans\Core\Event;
use ResTrans\Core\Route;
use ResTrans\Core\Model;
use ResTrans\Core\User;
use ResTrans\Core\Model\ModelConventions;
use ResTrans\Core\CommonException;

class Setting extends ControllerConventions {

  public $relationModel = "Setting";

  public function getPersonal (View $view, ModelConventions $model, Event $event, App $app) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $event->trigger("Base:hasUnreadNotifications", $currentUser["user_id"], $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $currentUser["user_id"], $view);
    } catch (CommonException $e) {
      $event->trigger("Base:simplyLoginRedirect");
    }

    $cookies = User::instance($app, $model->db())->getLoginCookies();
    $sessions = $model->getMySessions($currentUser["user_id"], true);
    if (!$sessions) $sessions = [];
    $sessions = $event->trigger("Proc:personalSessions", $sessions, $cookies["session_id"]);
    $settings = [
      "page_type" => 1,
      "gender" => $currentUser["gender"],
      "public_email" => $currentUser["public_email"]
    ];

    if ($currentUser["admin"]) {
      $view->setValue("SETTING.show_global_setting_page_link", true);
    } else {
      $view->setValue("SETTING.show_global_setting_page_link", false);
    }

    $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);

    $view->feModules("setting,navbar")
         ->title( [ "个人设置" ] )
         ->setValue("USER.is_login", (bool)$currentUser )
         ->setArray("USER", (array)$currentUser )
         ->setValue("USER.sessions", $sessions)
         ->setArray("SETTING", $settings)
         ->setAppToken()
         ->feData(["APP.token", "SETTING.page_type", "SETTING.gender", "SETTING.public_email",
          "USER.sessions"]);
         $view->init("PersonalSetting");
  }

  public function jsonPOSTPersonalProfile (Route $route, Event $event, ModelConventions $model) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

    $data = [
      "gender" => $route->queryString("gender", Route::HTTP_POST, ["intval"]),
      "public_email" => $route->queryString("public_email", Route::HTTP_POST, ["intval"]),
      "user_id" => $userId
    ];

    $db = $model->db();
    $db->TSBegin();
    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $model->set( "Profile", $data );
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException($invalid);

    $model->save();
    $db->TSCommit();
    $route->jsonReturn($this->status("profile_settings_saved", true));
  }

  public function jsonPOSTPersonalCommon (Route $route, Event $event, ModelConventions $model) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

    $data = [
      "receive_message" => $route->queryString("receive_message", Route::HTTP_POST, ["intval"]),
      "user_id" => $userId
    ];

    $db = $model->db();
    $db->TSBegin();
    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $model->set( "Common", $data );
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException($invalid);

    $model->save();
    $db->TSCommit();
    $route->jsonReturn($this->status("common_settings_saved", true));
  }

  public function jsonPOSTPersonalSecurity (Route $route, Event $event, ModelConventions $model) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

    $common->user()->setPassword(
      $userId,
      $route->queryString("new_password", Route::HTTP_POST),
      $route->queryString("new_password", Route::HTTP_POST)
    );

    $route->jsonReturn($this->status("security_settings_saved", true));
  }

  public function jsonDELETEPersonalSession ($sessionId, $expire, Route $route, Event $event,
    ModelConventions $model, App $app) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $cookies = User::instance($app, $model->db())->getLoginCookies();
    if ($sessionId === substr($cookies["session_id"], 0, 16)) {
      throw new Core\CommonException("cannot_remove_current_session_id");
    }

    $model->deleteSessionId($sessionId, $expire, $userId, true);

    $route->jsonReturn($this->status("session_deleted", true));
  }

  public function getGlobal (Core\View $view, ModelConventions $model, Core\App $app, Event $event, Route $route) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $event->trigger("Base:hasUnreadNotifications", $currentUser["user_id"], $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $currentUser["user_id"], $view);
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
    } catch (CommonException $e) {
      $event->trigger("Base:simplyLoginRedirect");
    }

    if (!$currentUser["admin"]) {
      $route->redirect($app->config["site_url"] . "setting/personal/");
      $event->trigger("Base:appEnd");
      exit();
    }

    array_walk($app->globalOptions, function (&$option) {
      $option = (int)$option;
    });

    $view->feModules("setting,navbar")
         ->title( [ "全局设置" ] )
         ->setValue("USER.is_login", (bool)$currentUser )
         ->setArray("USER", (array)$currentUser )
         ->setArray("OPTIONS", $app->globalOptions)
         ->setValue("SETTING.page_type", 2)
         ->setAppToken()
         ->feData(["APP.token", "SETTING.page_type", "OPTIONS.anonymous_access", "OPTIONS.register",
          "OPTIONS.login_captcha", "OPTIONS.member_create_organization", "OPTIONS.send_email"])
         ->init("GlobalSetting");
  }

  public function jsonPOSTGlobalCommon (ModelConventions $model, Event $event, Route $route) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

    if (!$currentUser["admin"]) {
      throw new Core\CommonException("permission_denied");
    }

    $data = [
      "login_captcha" => $route->queryString("login_captcha", Route::HTTP_POST, ["intval"]),
      "anonymous_access" => $route->queryString("anonymous_access", Route::HTTP_POST, ["intval"]),
      "member_create_organization" => $route->queryString(
        "member_create_organization",
        Route::HTTP_POST,
        ["intval"]
      )
    ];

    $db = $model->db();
    $db->TSBegin();
    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $model->set( "GlobalCommon", $data );
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException($invalid);

    $model->save();
    $db->TSCommit();
    $route->jsonReturn($this->status("global_common_settings_saved", true));
  }

  public function jsonPOSTGlobalRegister (ModelConventions $model, Event $event, Route $route) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

    if (!$currentUser["admin"]) {
      throw new Core\CommonException("permission_denied");
    }

    $data = [
      "register" => $route->queryString("register", Route::HTTP_POST, ["intval"]),
      "send_email" => $route->queryString("send_email", Route::HTTP_POST, ["intval"]),
    ];

    $db = $model->db();
    $db->TSBegin();
    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $model->set( "GlobalRegister", $data );
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException($invalid);

    $model->save();
    $db->TSCommit();
    $route->jsonReturn($this->status("global_register_settings_saved", true));
  }

  public function jsonGETGlobalUsers (ModelConventions $model, Route $route, Event $event) {
    $currentUser = $event->trigger("Verify:isLogin");

    if (!$currentUser["admin"]) {
      throw new Core\CommonException("permission_denied");
    }

    $users = $model->getUsers(
      $route->queryString("start", Route::HTTP_GET, ["intval"]),
      $route->queryString("amount", Route::HTTP_GET, ["intval"]),
      $route->queryString("blocked", Route::HTTP_GET, ["intval"]),
      true
    );

    $route->jsonReturn(
      $event->trigger(
        "Proc:globalUsers",
        $users ? $event->trigger("Proc:encodeUserIds", $users) : []
        )
    );
  }

  public function jsonGETGlobalOrganizations (ModelConventions $model, Route $route, Event $event) {
    $currentUser = $event->trigger("Verify:isLogin");

    if (!$currentUser["admin"]) {
      throw new Core\CommonException("permission_denied");
    }

    $organizations = $model->getOrganizations(
      $route->queryString("start", Route::HTTP_GET, ["intval"]),
      $route->queryString("amount", Route::HTTP_GET, ["intval"]),
      true
    );

    $route->jsonReturn(
      $organizations ? $event->trigger("Proc:encodeOrganizationIds", $organizations) : []
    );
  }

  public function jsonGETGlobalTasks (ModelConventions $model, Route $route, Event $event) {
    $currentUser = $event->trigger("Verify:isLogin");

    if (!$currentUser["admin"]) {
      throw new Core\CommonException("permission_denied");
    }

    $tasks = $model->getTasks(
      $route->queryString("start", Route::HTTP_GET, ["intval"]),
      $route->queryString("amount", Route::HTTP_GET, ["intval"]),
      true
    );

    if ($tasks) {
      $event->trigger("Proc:encodeTaskIds", $tasks);
      $event->trigger("Proc:encodeOrganizationIds", $tasks);
    }
    $route->jsonReturn($tasks);
  }
}