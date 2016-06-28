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
use ResTrans\Core\Lang;
use ResTrans\Core\App;
use ResTrans\Core\Model;
use ResTrans\Core\Route;
use ResTrans\Core\Event;
use ResTrans\Core\View;
use ResTrans\Core\Model\ModelConventions;
use ResTrans\Core\CommonException;
use ResTrans\Core\RouteResolveException;
use ResTrans\Core\Parser\ParserProvider;

class Task extends ControllerConventions {

  public $relationModel = "Task";

  public $acceptLanguage = ["en-US", "en-UK", "ja-JP", "de-DE", "ru-RU", "fr-FR", "ko-KR", "zh-CN",
    "zh-HK", "zh-TW"];

  public function getSingleTask ( $taskId, Core\View $view, Model\ModelConventions $model,
    Core\App $app, Core\Event $event) {
    /** 登录状态校验 */
    $currentUser = [];
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $event->trigger("Base:hasUnreadNotifications", $userId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $userId, $view);
      $view->setValue("USER.is_login", true)
            ->setArray("USER", (array)$currentUser);
    } catch (CommonException $e) {
      $userId = 0;
      $view->setValue("USER.is_login", false);
      /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);

    try {
      $task = $model->getTask($taskId);
    } catch (CommonException $e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode());
    }

    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    try {
      $memberOfOrganization = $event->trigger(
        "Verify:accessOrganization",
        $model("Organization"),
        $organization,
        $organization->organization_id,
        $userId
      );
    } catch (CommonException $e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode());
    }

    $view->setValue("TASK.open_wt", $memberOfOrganization || ($currentUser && $currentUser["admin"]));

    try {
      $event->trigger("Verify:accessTaskSetting", $task, $organization, $userId);
      $view->setValue("ORG.access_setting_pages", true);
    } catch (CommonException $e) {
      $view->setValue("ORG.access_setting_pages", false);
    }

    $task->friendly_time = $app->fTime($task->created);
    $task->friendly_original_language = Lang::get($task->original_language);
    $task->friendly_target_language = Lang::get($task->target_language);
    $sets = $model->getSets($task->task_id, false);
    $sets = $sets ? $sets : [];

    if ( isset($sets[0]) ) {
      $currentSet = $sets[0];
      if ($currentSet->file_total && $files = $model->getFiles($currentSet->set_id)) {
        $files = $event->trigger("Proc:filesForRestful", $files);
        $files = $event->trigger("Proc:encodeFileIds", $files);
        $files = $event->trigger("Proc:encodeSetIds", $files);
        $view->setValue("Set.files", $files);
      }
      $view->setValue("TASK.current_set", $currentSet);
    } else {
      $view->setValue("TASK.current_set", false);
    }

    $task->organization_id = $event->trigger("Proc:encodeOrganizationIds", $task->organization_id);
    $task->user_id = $event->trigger("Proc:encodeUserIds", $task->user_id);
    $task->task_id = $event->trigger("Proc:encodeTaskIds", $task->task_id);
    $sets = $event->trigger("Proc:encodeSetIds", $sets);
    $sets = $event->trigger("Proc:encodeUserIds", $sets);

    $view->feModules("task,navbar" )
         ->title( [ $task->name, "任务" ] )
         ->setValue("USER.is_login", (bool)$currentUser )
         ->setValue("TASK.sets", (array)$sets )
         ->setValue( "TASK.page_type", 1 )
         ->setValue("TASK.create_set", $memberOfOrganization || ($currentUser && $currentUser["admin"]))
         ->setValue("ORG.organization_id", $task->organization_id)
         ->setValue("ORG.name", $task->organization_name)
         ->setArray("TASK", (array)$task )
         ->setAppToken()
         ->feData( [ "APP.token", "TASK.task_id", "TASK.sets", "TASK.page_type",
            "ORG.organization_id", "TASK.current_set" ] )
         ->init( "TaskFiles" );
  }

  public function getSingleSet ($taskId, $setId, View $view, ModelConventions $model,
    App $app, Event $event) {
    $currentUser = [];

    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $event->trigger("Base:hasUnreadNotifications", $userId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $userId, $view);
      $view->setValue("USER.is_login", true)
        ->setArray("USER", (array)$currentUser);
    } catch (CommonException $e) {
      $userId = 0;
      $view->setValue("USER.is_login", true);
      /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);

    try {
      $task = $model->getTask($taskId);
    } catch (CommonException $e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode());
    }

    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    try {
      $memberOfOrganization = $event->trigger(
        "Verify:accessOrganization",
        $model("Organization"),
        $organization,
        $organization->organization_id,
        $userId
      );
    } catch (CommonException $e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode());
    }

    $view->setValue("TASK.open_wt", $memberOfOrganization || ($currentUser && $currentUser["admin"]));


    try {
      $event->trigger("Verify:accessTaskSetting", $task, $organization, $userId);
      $view->setValue("ORG.access_setting_pages", true);
    } catch (CommonException $e) {
      $view->setValue("ORG.access_setting_pages", false);
    }

    $task->friendly_time = $app->fTime($task->created);
    $task->friendly_original_language = Lang::get($task->original_language);
    $task->friendly_target_language = Lang::get($task->target_language);

    $sets = $model->getSets($task->task_id);
    $sets = $sets ? $sets : [];
    $model->setBelongsTo($setId. $taskId, true);

    $currentSet = null;
    foreach ( $sets as $set ) {
      if ( $setId !== $set->set_id ) continue;
      $currentSet = $set;
      break;
    }
    if (!$currentSet) throw new Core\RouteResolveException("set_not_found");
    if ($currentSet->file_total && $files = $model->getFiles($currentSet->set_id)) {
      $files = $event->trigger("Proc:filesForRestful", $files);
      $files = $event->trigger("Proc:encodeFileIds", $files);
      $files = $event->trigger("Proc:encodeSetIds", $files);
      $view->setValue("Set.files", $files);
    }

    $task->task_id = $event->trigger("Proc:encodeTaskIds", $task->task_id);
    $task->organization_id = $event->trigger("Proc:encodeOrganizationIds", $task->organization_id);
    $task->user_id = $event->trigger("Proc:encodeUserIds", $task->user_id);
    $sets = $event->trigger("Proc:encodeSetIds", $sets);
    $sets = $event->trigger("Proc:encodeUserIds", $sets);

    $view->feModules( "task,navbar" )
         ->title( [ $task->name . "({$currentSet->name})", "任务" ] )
         ->setValue( "USER.is_login", (bool)$currentUser )
         ->setValue( "TASK.sets", $sets )
         ->setValue( "TASK.current_set", $currentSet )
         ->setValue("TASK.create_set", $memberOfOrganization || ($currentUser && $currentUser["admin"]))
         ->setValue( "TASK.page_type", 1 )
         ->setValue("ORG.organization_id", $task->organization_id)
         ->setValue("ORG.name", $task->organization_name)
         ->setArray( "USER", (array)$currentUser )
         ->setArray( "TASK", (array)$task )
         ->setAppToken()
         ->feData( [ "APP.token", "TASK.task_id", "TASK.sets", "TASK.page_type",
            "ORG.organization_id", "TASK.current_set" ] )
         ->init( "TaskFiles" );
  }

  public function jsonDELETESingleSet ($taskId, $setId, ModelConventions $model, Core\Event $event,
    Core\App $app, Core\Route $route) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);

    $task = $model->getTask($taskId, true);

    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $model->setExists($setId, true);
    $model->setBelongsTo($setId, $task->task_id, true);

    $event->trigger(
      "Verify:manageSetsAndFiles",
      $model("Organization"),
      $organization,
      $task,
      $userId
    );

    $event->trigger("Verify:isTaskFrozen", $task);

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $files = $model->getFiles($setId, false);
    $model->deleteSet($setId, true);
    $model->updateSetTotal($taskId);
    $model->deleteSetFiles($setId, true);
    $model->updateTaskPercentage($taskId, true);
    if ($files) {
      foreach ($files as $file) {
        $model->deleteAllTranslationsOfFile($file->file_id, true);
        @unlink(DATA_PATH . $file->path[0] . "/" . $file->path . ".json");
      }
    }

    $db->TSCommit();
    $taskId = $event->trigger("Proc:encodeTaskIds", $taskId);
    $url = $app->config["site_url"] . "task/" . $taskId;
    $route->jsonReturn($this->status("set_deleted", false, ["redirect_url" => $url]));
  }

  public function getTaskSetting ($taskId, Core\View $view, ModelConventions $model, Core\App $app,
    Event $event, Route $route) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $event->trigger("Base:hasUnreadNotifications", $userId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $userId, $view);
      $view->setValue("USER.is_login", true)
        ->setArray("USER", (array)$currentUser);
    } catch (CommonException $e) {
      $userId = 0;
      $view->setValue("USER.is_login", true);
      /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);

    try {
      $task = $model->getTask($taskId);
    } catch (CommonException $e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode());
    }

    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    try {
      $memberOfOrganization = $event->trigger(
        "Verify:accessOrganization",
        $model("Organization"),
        $organization,
        $organization->organization_id,
        $userId
      );
      $event->trigger("Verify:accessTaskSetting", $task, $organization, $userId);
    } catch (CommonException $e) {
      $route->redirect($app->config["site_url"] . "task/" . $task->task_id);
      $event->trigger("Base:appEnd");
      exit();
    }

    $task->friendly_time = $app->fTime($task->created);
    $task->friendly_original_language = Lang::get($task->original_language);
    $task->friendly_target_language = Lang::get($task->target_language);
    $setting["name"] = $task->name;
    $setting["description"] = $task->description;
    $setting["original_language"] = $task->original_language;
    $setting["target_language"] = $task->target_language;

    $task->task_id = $event->trigger("Proc:encodeTaskIds", $task->task_id);
    $task->organization_id = $event->trigger("Proc:encodeOrganizationIds", $task->organization_id);
    $task->user_id = $event->trigger("Proc:encodeUserIds", $task->user_id);

    $view->feModules("task,navbar" )
         ->title([$task->name, "任务选项"])
         ->setValue("TASK.page_type", 2)
         ->setValue("ORG.organization_id", $task->organization_id)
         ->setValue("ORG.name", $task->organization_name)
         ->setArray("USER", (array)$currentUser )
         ->setArray("TASK", (array)$task )
         ->setAppToken()
         ->feData( ["APP.token", "TASK.task_id", "TASK.page_type", "ORG.organization_id",
         "TASK.name", "TASK.description", "TASK.original_language", "TASK.target_language",
         "TASK.frozen", "TASK.api_key"] )
         ->init( "TaskSettings" );
  }

  public function jsonPOSTTask ( Core\Route $route, Model\ModelConventions $model,
    Core\Event $event, Core\App $app ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    // 验证 token
    $event->trigger("Verify:checkToken",Route::HTTP_POST);

    $method = Core\Route::HTTP_POST;
    $orgId = $route->queryString("organization_id", $method, ["trim"]);
    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    try {
      $event->trigger("Verify:isAdmin", $userId);
      $isAdmin = true;
    } catch (CommonException $e) {
      $isAdmin = false;
    }

    if (!$memberOfOrganization && !$isAdmin) {
      throw new CommonException("permission_denied");
    }

    $event->trigger("Verify:createTask", $organization, $userId);

    $data = [
      "organization_id"   => $orgId,
      "user_id"           => $userId,
      "name"              => $route->queryString("name", $method, ["trim"]),
      "description"       => $route->queryString("description", $method),
      "original_language" => "en-US",
      "target_language"   => "zh-CN",
      "created"           => time(),
      "key"               => $app->charsGen(40)
    ];

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use ($db) {
      $db->TSRollBack();
    });

    // 创建新的任务
    $model->set( "Task", $data );
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException($invalid);

    $model->save();
    $taskId = $model->lastInsertId;
    $model("Organization")->updateTaskTotal($data["organization_id"]);

    $db->TSCommit();

    $taskId = $event->trigger("Proc:encodeTaskIds", $taskId);
    $attrs = [ "url" => "{$app->config['site_url']}task/{$taskId}" ];
    $route->jsonReturn( $this->status( "create_task_succeed", false, $attrs ) );
  }

  public function jsonPOSTSet ( $taskId, Model\ModelConventions $model, Core\Route $route,
     Core\Event $event, Core\App $app ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $method = Core\Route::HTTP_POST;
    $name = $route->queryString("name", $method, ["trim"]);
    // 验证 token
    $event->trigger("Verify:checkToken", $method);

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);
    $task = $model->getTask($taskId);

    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger(
      "Verify:manageSetsAndFiles",
      $model("Organization"),
      $organization,
      $task,
      $userId
    );

    $event->trigger("Verify:isTaskFrozen", $task);

    $data = [
      "task_id"  => $taskId,
      "user_id"  => $userId,
      "set_name" => $name
    ];

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use ($db) {
      $db->TSRollBack();
    });

    // 创建集合
    $model->set( "NewSet", $data );
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException( $invalid );

    $model->save();
    $setId = $model->lastInsertId;

    $model->clearInvalid()->updateSetTotal( $taskId );

    $db->TSCommit();

    $taskId = $event->trigger("Proc:encodeTaskIds", $taskId);
    $setId = $event->trigger("Proc:encodeSetIds", $setId);
    $attrs = [ "url" => "{$app->config['site_url']}task/{$taskId}/set/{$setId}", "set_id" => $setId ];
    $route->jsonReturn( $this->status( "create_set_succeed", true, $attrs ) );
  }

  public function jsonGETSet($taskId, Model\ModelConventions $model, Core\Route $route, Event $event) {
    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);;
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
    } catch (CommonException $e) {
      $event->trigger("Base:needLogin", $route);
    }

    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $sets = $model->getSets($taskId);
    if ( ! $sets ) throw new Core\CommonException("no_sets", 404);
    $sets = $event->trigger("Proc:encodeSetIds", $sets);
    $sets = $event->trigger("Proc:encodeUserIds", $sets);
    $route->jsonReturn($sets);
  }

  public function jsonPOSTWFile($hash, $setId, Model\ModelConventions $model, Core\Route $route,
    Core\App $app, Core\Event $event) {
    $user = $event->trigger("Verify:isLogin");
    $userId = (int)$user["user_id"];

    $this->addTokenOnError(false);
    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $method = Core\Route::HTTP_POST;

    $originalLanguage = (string)$route->queryString("original_language", $method, ["trim"]);
    $targetLanguage = (string)$route->queryString("target_language", $method, ["trim"]);

    $working = $event->trigger("Verify:worktableRecord", $model("WorkTable"), $hash, $userId);
    if (!$working) throw new Core\RouteResolveException("worktable_unavailable");

    $task = $model->getTask($working->task_id);
    $taskId = $task->task_id;
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger(
      "Verify:manageSetsAndFiles",
      $model("Organization"),
      $organization,
      $task,
      $userId
    );

    $event->trigger("Verify:isTaskFrozen", $task);

    if ( !$task->set_total ) {
      throw new Core\CommonException("set_not_found");
    }

    if ( !in_array($originalLanguage, $this->acceptLanguage) ){
      $originalLanguage = $task->original_language;
    }
    if ( !in_array($targetLanguage, $this->acceptLanguage) ){
      $targetLanguage = $task->target_language;
    }
    $set = $model->getSet($setId);
    $model->setBelongsTo($setId. $taskId, true);

    if ( !isset($_FILES["file"]["error"]) || is_array($_FILES["file"]["error"]) ) {
      throw new Core\CommonException("upload_failed");
    }

    $file = $_FILES["file"];
    if ( $file["error"] === UPLOAD_ERR_INI_SIZE || $file["error"] === UPLOAD_ERR_FORM_SIZE ) {
      throw new Core\CommonException("file_too_large");
    } else if ( $file["error"] !== UPLOAD_ERR_OK ) {
      throw new Core\CommonException("upload_failed");
    }

    $name = substr($file["name"], 0, strrpos($file["name"], "."));
    $extension = strtolower(substr(strrchr($file["name"], "."), 1));
    if (!$name) {
      throw new Core\CommonException("file_name_error");
    }

    $parser = ParserProvider::init($app)->getParserByCustomize($file["tmp_name"], $extension);

    $db = $model->db();
    $db->TSBegin();
    $event->on("controllerError", function () use ($parser, $db) {
      $parser->unsave();
      $db->TSRollBack();
    });

    $parser
      ->check($file)
      ->setLanguage($originalLanguage, $targetLanguage)
      ->parse()
      ->save();

    $data = ["set_id" => $setId, "name" => mb_substr($name, 0, 250, "UTF-8"),
      "extension" => $extension, "path" => substr($parser->lastSavedFileName, 0, 80),
      "uploader" => $userId, "size" => filesize($file["tmp_name"]), "line" => $parser->lines,
      "translatable" => $parser->translatable, "original_language" => $originalLanguage,
      "target_language" => $targetLanguage, "created" => time(), "last_contributor" => $userId,
      "last_contributed" => time()];

    $model->set("NewFile", $data);
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException($invalid);

    $model->save();
    $fileId = $model->lastInsertId;

    $model->clearInvalid()->updateFileTotal( $setId );
    $model->updateTaskPercentage($task->task_id, true);
    $db->TSCommit();

    $respData = ["file_id" => $fileId, "name" => $data["name"], "ext" => $data["extension"],
      "line" => $data["line"], "percentage" => "0", "last_update_by" => $user["name"],
      "last_update" => $app->fTime($data["last_contributed"]),
      "last_contributor" => $event->trigger("Proc:encodeUserIds", $userId),
      "original_language_name" => Lang::get($data["original_language"]),
      "target_language_name" => Lang::get($data["target_language"])];

    $respData["file_id"] = $event->trigger("Proc:encodeFileIds", $respData["file_id"]);
    $route->jsonReturn($this->status("file_saved", false, $respData));
  }

  public function jsonDELETEWFile ($hash, $setId, $fileId, Model\ModelConventions $model,
    Core\Route $route, Core\App $app, Core\Event $event) {
    $this->addTokenOnError(false);
    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $fileId = $event->trigger("Proc:decodeFileIds", $fileId);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $working = $event->trigger("Verify:worktableRecord", $model("WorkTable"), $hash, $userId);
    if (!$working) throw new Core\RouteResolveException("worktable_unavailable");

    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($working->organization_id, true);
    $orgId = $organization->organization_id;

    $task = $model("Task")->getTask($working->task_id, true);
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger(
      "Verify:manageSetsAndFiles",
      $model("Organization"),
      $organization,
      $task,
      $userId
    );

    $event->trigger("Verify:isTaskFrozen", $task);

    $model->setExists($setId, true);
    $model->setBelongsTo($setId. $working->task_id, true);
    $model->fileExists($fileId, true);
    $file = $model->getFile($fileId, true);
    if ($file->set_id !== $setId) {
      throw new CommonException("params_error", 403);
    }

    $db = $model->db();
    try {
      $db->TSBegin();
      $model->deleteFile($fileId, true);
      $model->updateFileTotal($setId);
      $model->deleteAllTranslationsOfFile($fileId, true);
      $model->updateTaskPercentage($working->task_id, true);
      $db->TSCommit();
      @unlink(DATA_PATH . $file->path[0] . "/" . $file->path . ".json");
    } catch (Core\CommonException $e) {
      $db->TSRollBack();
    }

    $route->jsonReturn($this->status("delete_file_succeed", false));
  }

  public function jsonGETFile (
    $taskId,
    $setId,
    Core\Route $route,
    Model\ModelConventions $model,
    Core\App $app,
    Core\Event $event
  ) {
    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);
    $setId = $event->trigger("Proc:decodeSetIds", $setId);

    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $model->setExists($setId, true);
    $model->setBelongsTo($setId. $taskId, true);

    $files = $model->getFiles($setId, true);
    $files = $event->trigger("Proc:filesForRestful", $files);
    $files = $event->trigger("Proc:encodeFileIds", $files);
    $files = $event->trigger("Proc:encodeSetIds", $files);
    $files = $event->trigger("Proc:encodeUserIds", $files, "last_contributor");
    $route->jsonReturn($files);
  }

  public function jsonPUTFile (
    $taskId,
    $setId,
    $fileId,
    Core\Route $route,
    Model\ModelConventions $model,
    Core\App $app,
    Event $event
  ) {
    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);
    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $fileId = $event->trigger("Proc:decodeFileIds", $fileId);

    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger(
      "Verify:manageSetsAndFiles",
      $model("Organization"),
      $organization,
      $task,
      $userId
    );

    $event->trigger("Verify:isTaskFrozen", $task);

    $fileName = $route->queryString("name", Core\Route::HTTP_PUT, ["trim"]);

    $model->setExists($setId, true);
    $model->setBelongsTo($setId. $taskId, true);
    $model->fileExists($fileId, true);

    $db = $model->db();
    try {
      $db->TSBegin();
      $model->updateFileName($fileId, $fileName, true);
      $db->TSCommit();
    } catch (CommonException $e) {
      $db->TSRollBack();
      throw $e;
    }

    $route->jsonReturn($route->phpInput("json"));
  }

  public function jsonGETLine ($taskId, $setId, $fileId, Model\ModelConventions $model,
    Core\Route $route, Core\App $app, Event $event) {

    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);
    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $fileId = $event->trigger("Proc:decodeFileIds", $fileId);

    $lineStart = $route->queryString("start", Core\Route::HTTP_GET, ["intval"]);
    $lineEnd = $route->queryString("end", Core\Route::HTTP_GET, ["intval"]);
    $previewMode = $route->queryString("preview", Core\Route::HTTP_GET, ["intval"]);
    $forceArray = $route->queryString("force_array", Core\Route::HTTP_GET, ["intval"]);

    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $model->setExists($setId, true);
    $model->setBelongsTo($setId. $taskId, true);

    $file = $model->getFile($fileId, true);
    if ($file->set_id !== (int)$setId) {
      throw new CommonException("params_error", 403);
    }

    $lineStart = ( $lineStart < 1 || $file->line < $lineStart ) ? 1 : $lineStart;
    if ( $lineEnd > $file->line || $lineEnd < $lineStart || $lineEnd < 1 ) {
      $lineEnd = ($lineStart + 100 > $file->line) ? $file->line : $lineStart + 100;
    }

    $parser = ParserProvider::init($app)->getParser($file->path, $file->extension);

    $translations = $model->getTranslations(
      $file->file_id,
      $lineStart,
      $lineEnd,
      (bool)$previewMode,
      false
    );

    array_walk($translations, function (&$translation) use (&$app) {
      $translation->friendly_time = $app->fTime($translation->contributed);
    });

    if ($translations) $event->trigger("Proc:encodeFileIds", $translations);
    if ($translations) $event->trigger("Proc:encodeUserIds", $translations, "contributor");
    if ($translations) $event->trigger("Proc:encodeUserIds", $translations, "proofreader");
    $lines = $parser->combine($parser->read($lineStart, $lineEnd), $translations);
    $route->jsonReturn(count($lines) === 1 && !$forceArray ? array_shift($lines) : $lines);
  }

  public function jsonPATCHTranslation ($hash, $setId, $fileId, Core\Route $route,
    Model\ModelConventions $model, Core\Event $event) {
    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $fileId = $event->trigger("Proc:decodeFileIds", $fileId);

    $working = $event->trigger("Verify:worktableRecord", $model("WorkTable"), $hash, $userId);
    if (!$working) throw new Core\RouteResolveException("worktable_unavailable");

    $task = $model->getTask($working->task_id, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger(
      "Verify:manageSetsAndFiles",
      $model("Organization"),
      $organization,
      $task,
      $userId,
      "translate"
    );

    $event->trigger("Verify:isTaskFrozen", $task);

    $model->setExists($setId, true);
    $model->setBelongsTo($setId. $working->task_id, true);
    $file = $model->getFile($fileId, true);
    if ($file->set_id !== $setId) throw new Core\CommonException("params_error", 403);

    $patched = [];
    $lastLine = 0;
    $updatePercentage = false;
    $translations = $route->phpInput("json");
    $db = $model->db();
    if (!$translations || empty($translations)) {
      throw new Core\CommonException("no_patch", 200);
    }

    $translations = $event->trigger("Proc:decodeFileIds", $translations);

    foreach ($translations as $translation) {
      if (!key_exists("file_id", $translation) || !key_exists("line", $translation) ||
          !key_exists("text", $translation) ||
          !key_exists("best_translation", $translation)) continue;
      if ($file->file_id !== $translation["file_id"] ||
          $translation["line"] > $file->line) continue;
      $myTranslation = $model->getMyTranslation($file->file_id, $translation["line"], $userId);
      if (!$myTranslation && !empty($translation["text"])) {
        //create
        $db->TSBegin();
        $data = ["file_id" => $file->file_id, "line" => $translation["line"],
          "text" => $translation["text"], "best_translation" => 0, "contributor" => $userId,
          "contributed" => time()];

        $model->set("NewTranslation", $data);
        $invalid = $model->firstInvalid();
        try {
          if ($invalid) throw new Core\CommonException($invalid);
          $model->save();
          $lastInsertId = $model->lastInsertId;
          $data["translation_id"] = $lastInsertId;
          $data["_cid"] = $translation["_cid"];
          $db->TSCommit();
          $patched[] = $data;
          $lastLine = $translation["line"];
          $updatePercentage = true;
        } catch (Core\CommonException $e) {
          $db->TSRollBack();
          continue;
        }
      } elseif ($myTranslation && key_exists("translation_id", $translation) &&
                $translation["translation_id"] === $myTranslation->translation_id &&
                !empty($translation["text"])) {
        // update
        $data = [
          "text" => $translation["text"],
          "translation_id" => $myTranslation->translation_id,
          "best_translation" => (bool)$myTranslation->best_translation,
          "contributed" => time(),
          "proofreader" => (bool)$myTranslation->best_translation ? $myTranslation->proofreader : 0
        ];

        $updated = function () use (
          &$patched,
          &$lastLine,
          &$updatePercentage,
          $data,
          &$file,
          &$myTranslation) {
          $patched[] = array_merge($data, [
            "file_id" => $file->file_id, 
            "line" => $myTranslation->line,
            "contributor" => $myTranslation->contributor
          ]);
          $lastLine = $myTranslation->line;
          $updatePercentage = true;
        };

        if ($myTranslation->text === $translation["text"]) {
          $updated();
          continue;
        }

        $db->TSBegin();
        $model->set("UpdateTranslation", $data);
        $invalid = $model->firstInvalid();
        try {
          if ($invalid) throw new Core\CommonException($invalid);
          $model->save();
          $db->TSCommit();
          $updated();
        } catch (Core\CommonException $e) {
          $db->TSRollBack();
          continue;
        }
      } elseif ($myTranslation && key_exists("translation_id", $translation) &&
                $translation["translation_id"] === $myTranslation->translation_id &&
                empty($translation["text"])) {
        // delete
        $db->TSBegin();
        try {
          $model->deleteTranslation($myTranslation->translation_id, true);
          $db->TSCommit();
          $patched[] = $translation;
          $lastLine = $translation["line"] ? $translation["line"] : 1;
          $updatePercentage = true;
        } catch (Core\CommonException $e) {
          $db->TSRollBack();
        }
      }
    }

    if ($lastLine) {
      $worktable = $model("WorkTable");
      $db->TSBegin();
      try {
        $worktable->updateRecord($working->hash, $setId, $fileId, $lastLine, true);
        $db->TSCommit();
      } catch (Core\CommonException $e) {
        $db->TSRollBack();
      }
    }

    if ($updatePercentage) {
      $db->TSBegin();
      try {
        $model->updateFilePercentage($file->file_id, $file->translatable, true);
        $model->updateTaskPercentage($working->task_id, true);
        $db->TSCommit();
      } catch (Core\CommonException $e) {
        $db->TSRollBack();
      }
    }

    $detail = count($patched) !== count($translations) ? "part_patched" : "all_patched";
    $patched = $event->trigger("Proc:encodeFileIds", $patched);
    $patched = $event->trigger("Proc:encodeUserIds", $patched, "contributor");
    $route->jsonReturn($this->status($detail, false, ["patched" => $patched]));
  }

  public function jsonPUTSingleTranslation ($hash, $setId, $fileId, Core\Route $route,
    Model\ModelConventions $model, Core\Event $event) {
    $best = $thisTranslation = false;
    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $fileId = $event->trigger("Proc:decodeFileIds", $fileId);

    $working = $event->trigger("Verify:worktableRecord", $model("WorkTable"), $hash, $userId);
    if (!$working) throw new Core\RouteResolveException("worktable_unavailable");

    $taskId = $working->task_id;
    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger(
      "Verify:manageSetsAndFiles",
      $model("Organization"),
      $organization,
      $task,
      $userId,
      "proofread"
    );

    $event->trigger("Verify:isTaskFrozen", $task);

    $model->setExists($setId, true);
    $model->setBelongsTo($setId. $taskId, true);
    $file = $model->getFile($fileId, true);
    if ($file->set_id !== (int)$setId) throw new Core\CommonException("params_error", 403);

    $m = Core\Route::HTTP_PUT;
    $bestTranslationState = (boolean)$route->queryString("best_translation", $m, ["intval"]);
    $translationId = (int)$route->queryString("translation_id", $m, ["intval"]);
    $text = (string)$route->queryString("text", $m);
    $line = (string)$route->queryString("line", $m, ["intval"]);

    $data = [
      "best_translation" => $bestTranslationState,
      "translation_id" => $translationId,
      "text" => $text,
      "contributed" => time(),
      "proofreader" => $bestTranslationState ? $userId : 0
    ];

    // 如果已经有最佳，则拒绝
    $lineTranslations = $model->getTranslationByLine($fileId, $line, true);
    foreach ($lineTranslations as $pos => $translation) {

      /** 
       * 找到最佳译文
       */
      if ($translation->best_translation) $best = $translation;

      /** 
       * 找到当前译文
       *
       */
      if ($translation->translation_id === $translationId) $thisTranslation = $translation;
    }

    if (
      !$thisTranslation ||
      $thisTranslation->translation_id !== $translationId
    ) throw new Core\CommonException("params_error", 403);

    /**
     * 如果已经有最佳译文
     */
    if (
      ($best &&
      $best->translation_id !== $translationId &&
      $bestTranslationState === $best->best_translation)
    ) {
      throw new Core\CommonException("params_error", 403);
    }

    $db = $model->db();
    $db->TSBegin();
    $event->on("controllerError", function () use ($db) {
      $db->TSRollBack();
    });
    $model->set("UpdateTranslation", $data);
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException($invalid);

    $model->save();
    $db->TSCommit();

    $worktable = $model("WorkTable");
    $db->TSBegin();
    try {
      $worktable->updateRecord($working->hash, $setId, $fileId, $line, true);
      $db->TSCommit();
    } catch (Core\CommonException $e) {
      $db->TSRollBack();
    }

    $route->jsonReturn($this->status("translation_update_succeed", false));
  }

  public function jsonDELETESingleTranslation ($hash, $setId, $fileId, $translationId,
    Core\Route $route, Model\ModelConventions $model, Event $event) {
    $this->addTokenOnError(false);

    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $fileId = $event->trigger("Proc:decodeFileIds", $fileId);

    $working = $event->trigger("Verify:worktableRecord", $model("WorkTable"), $hash, $userId);
    if (!$working) throw new Core\RouteResolveException("worktable_unavailable");

    $taskId = $working->task_id;
    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger(
      "Verify:manageSetsAndFiles",
      $model("Organization"),
      $organization,
      $task,
      $userId,
      "proofread"
    );

    $event->trigger("Verify:isTaskFrozen", $task);

    $model->setExists($setId, true);
    $model->setBelongsTo($setId. $taskId, true);
    $file = $model->getFile($fileId, true);
    if ($file->set_id !== (int)$setId) throw new CommonException("params_error", 403);

    $translation = $model->getTranslation($translationId, true);
    if ($translation->file_id !== (int)$file->file_id) throw new CommonException("params_error", 403);

    $deleteTranslation = $model->deleteTranslation($translationId, true);
    if ($deleteTranslation)$route->jsonReturn($this->status("translation_deleted", false));
  }

  public function jsonPOSTTaskSetting ($taskId, Core\Route $route, Model\ModelConventions $model,
    Core\Event $event, Core\App $app) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);

    $method = Core\Route::HTTP_POST;
    // 验证 token
    $event->trigger("Verify:checkToken", $method);

    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger("Verify:accessTaskSetting", $task, $organization, $userId);

    // 验证输入
    $data = [
      "task_id"           => $taskId,
      "name"              => $route->queryString("name", $method, ["trim"]),
      "description"       => $route->queryString("description", $method),
      "original_language" => $route->queryString("original_language", $method),
      "target_language"   => $route->queryString("target_language", $method),
    ];
    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use ($db) {
      $db->TSRollBack();
    });

    // 创建新的组织
    $model->set( "UpdateTask", $data );
    // 把 invalid 的信息交给 catch 处理
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException( $invalid );

    $model->save();
    $db->TSCommit();
    $route->jsonReturn($this->status("settings_saved", true));
  }

  public function jsonDELETESingleTask ($taskId, ModelConventions $model, Core\Event $event,
    Core\App $app, Core\Route $route) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);

    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger("Verify:accessTaskSetting", $task, $organization, $userId);
    $event->trigger("Verify:isTaskFrozen", $task);

    $sets = $model->getSets($taskId);
    $sets || ($sets = []);

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $files = [];
    foreach ($sets as $set) {
      $setFiles = $model->getFiles($set->set_id);
      $setFiles && ($files = array_merge($files, $setFiles));
    }

    $model->deleteTask($task->task_id, true);
    $model("Organization")->updateTaskTotal($task->organization_id);
    $model->deleteSets($task->task_id, true);
    foreach ($sets as $set) {
      $model->deleteSetFiles($set->set_id, true);
    }

    if ($files) {
      foreach ($files as $file) {
        $model->deleteAllTranslationsOfFile($file->file_id, true);
        @unlink(DATA_PATH . $file->path[0] . "/" . $file->path . ".json");
      }
    }

    $db->TSCommit();
    $model("WorkTable")->deleteWorkingByTaskId($taskId);
    $task->organization_id = $event->trigger("Proc:encodeOrganizationIds", $task->organization_id);
    $url = $app->config["site_url"] . "organization/" . $task->organization_id . "/task/";
    $route->jsonReturn($this->status("task_deleted", false, ["redirect_url" => $url]));
  }

  public function jsonPUTSingleTask ($taskId, Core\Route $route, ModelConventions $model, Event $event) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);

    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger("Verify:accessTaskSetting", $task, $organization, $userId);

    $status = (bool)$route->queryString("frozen", Core\Route::HTTP_PUT);
    $model->updateFrozenState($taskId, (int)$status, true);
    $route->jsonReturn(["frozen" => $status]);
  }

  public function getPreview ($taskId, $setId, $fileId, View $view, ModelConventions $model, 
    Event $event) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
    } catch (CommonException $e) {
            /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $event->on("controllerError", function ($e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode(), $e);
    });

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);
    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $fileId = $event->trigger("Proc:decodeFileIds", $fileId);

    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $model->setExists($setId, true);
    $model->setBelongsTo($setId. $taskId, true);
    $file = $model->getFile($fileId, true);
    $preview = [
      "task_id" => (int)$taskId,
      "set_id"  => (int)$setId,
      "file_id" => (int)$fileId,
      "lines"   => $file->line
    ];

    $preview["task_id"] = $event->trigger("Proc:encodeTaskIds", $preview["task_id"]);
    $preview["set_id"] = $event->trigger("Proc:encodeSetIds", $preview["set_id"]);
    $preview["file_id"] = $event->trigger("Proc:encodeFileIds", $preview["file_id"]);

    $view->feModules("preview")
         ->title([$file->name . "." . $file->extension, "工作预览"])
         ->setAppToken()
         ->setValue("PREVIEW.file_name", $file->name . "." . $file->extension)
         ->setArray("PREVIEW", $preview)
         ->feData(["APP.token", "PREVIEW.task_id", "PREVIEW.set_id", "PREVIEW.file_id", "PREVIEW.lines"])
         ->init( "Preview" );
  }

  public function jsonPUTApiKey ($taskId, ModelConventions $model, Event $event, App $app, Route $route) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);

    $task = $model->getTask($taskId, true);
    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($task->organization_id, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    $event->trigger("Verify:accessTaskSetting", $task, $organization, $userId);

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $newApiKey = $app->charsGen(40);
    $model->updateApiKey($taskId, $newApiKey, true);
    $model->clearApiRequestCount($taskId, true);

    $db->TSCommit();
    $route->jsonReturn($this->status("api_key_updated", false, ["api_key" => $newApiKey]));
  }
}
