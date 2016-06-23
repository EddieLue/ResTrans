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
use ResTrans\Core\Route;
use ResTrans\Core\Event;
use ResTrans\Core\Model;
use ResTrans\Core\CommonException;
use ResTrans\Core\Model\ModelConventions;
use ResTrans\Core\Parser\ParserProvider;

class WorkTable extends ControllerConventions {

  public $relationModel = "WorkTable";

  public function getMain ($hash, Model\ModelConventions $model, Core\Event $event,
    Core\View $view, Core\Route $route, Core\App $app) {

    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
      $event->trigger("Base:hasUnreadNotifications", $userId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $userId, $view);
      $view->setValue("USER.is_login", true);
    } catch (CommonException $e) {
      $event->trigger("Base:simplyLoginRedirect");
    }

    $title = ["工作台"];
    $lastFile = $lastSet = null;
    $allSets = [];
    $files = [];

    $working = $event->trigger("Verify:worktableRecord", $model, $hash, $userId);
    if (!$working) throw new Core\RouteResolveException("worktable_unavailable");

    $view->setValue("USER.user_id", $event->trigger("Proc:encodeUserIds", $userId));
    $view->setValue("USER.user_name", $currentUser["name"]);
    array_unshift($title, $working->task_name, $working->organization_name);
    $view->setValue("WORKTABLE.current", $working);
    $view->setValue("WORKTABLE.hash", $working->hash);

    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($working->organization_id, true);
    $orgId = $organization->organization_id;

    // 查找集
    $taskModel = $model("Task");
    $task = $taskModel->getTask($working->task_id, true);

    if ($task->organization_id !== $orgId) {
      throw new CommonException("params_error", 403);
    }

    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    try {
      $event->trigger(
        "Verify:manageSetsAndFiles",
        $model("Organization"),
        $organization,
        $task,
        $userId
      );
      $view->setValue("WORKTABLE.allow_manage_files", true);
    } catch (CommonException $e) {
      $view->setValue("WORKTABLE.allow_manage_files", false);
    }

    try {
      $event->trigger("Verify:isTaskFrozen", $task);
      $view->setValue("TASK.frozen", false);
    } catch (CommonException $e) {
      $view->setValue("TASK.frozen", true);
    }

    $view->setValue("WORKTABLE.default_title", implode(" / ", $title));
    $view->setValue("WORKTABLE.default_original_language", $task->original_language);
    $view->setValue("WORKTABLE.default_target_language", $task->target_language);

    if ( $task->set_total ) {
      $allSets = $taskModel->getSets($working->task_id, false);

      list($allSets, $lastSet) = $event->trigger(
        "Proc:allSetsForWorkTable",
        $allSets,
        $working->last_set_id
      );

      if ( !$lastSet ) {
        $lastSet = $allSets[0];
        $view->setValue("WORKTABLE.has_reset", true);
      }

      array_unshift($title, $lastSet->name);
      $view->setValue("WORKTABLE.last_set", $lastSet);
    }
    // 查找文件
    if ( $lastSet && $lastSet->file_total ) {
      $files = $taskModel->getFiles($lastSet->set_id);
      $sLastFile = clone $files[0];

      list(
        $files,
        $lastFile,
        $sLastFile
      ) = $event->trigger(
        "Proc:filesForWorkTable",
        $files,
        $sLastFile,
        $working->last_file_id
      );

      if (!$lastFile) {
        $lastFile = $files[0];
        $view->setValue("WORKTABLE.has_reset", true);
      }
      array_unshift($title, $lastFile->name);

      $view->setValue("WORKTABLE.last_file", $lastFile);
      $view->setValue("WORKTABLE.last_set_files", $files);
    } else {
      $lastSet && $view->setValue("WORKTABLE.no_files", !(bool)$lastFile);
    }

    if ($lastFile && $lastFile->line) {
      try {
        $parser = ParserProvider::init($app)->getParser($sLastFile->path, $lastFile->ext);

        $lastLine = $working->last_line;
        $totalLine = $lastFile->line;
        $lineStart = ($lastLine > $totalLine || $lastLine < 1) ? 1 : $lastLine;
        $lineEnd = $lineStart + 100;
        if ($lineEnd > $totalLine) $lineEnd = $totalLine;

        $translations = $taskModel->getTranslations($lastFile->file_id, $lineStart, $lineEnd);
        array_walk($translations, function (&$translation) use (&$app) {
          $translation->friendly_time = $app->fTime($translation->contributed);
        });

        if ($translations) {
          $event->trigger("Proc:encodeFileIds", $translations);
          $event->trigger("Proc:encodeUserIds", $translations, "contributor");
          $event->trigger("Proc:encodeUserIds", $translations, "proofreader");
        }

        $lines = $parser->combine($parser->read($lineStart, $lineEnd), $translations);

        $view->setValue("WORKTABLE.last_file_lines", $lines);

        $model->updateRecord(
          $hash,
          $lastSet->set_id,
          $sLastFile->file_id,
          $lineStart,
          false
        );
      } catch (\Exception $e) {
        $view->setValue("WORKTABLE.parse_error", true);
      }
    }

    if ($files) {
      $files = $event->trigger("Proc:encodeFileIds", $files);
      $files = $event->trigger("Proc:encodeUserIds", $files, "last_contributor");
    }

    $allSets = $event->trigger("Proc:encodeSetIds", $allSets);
    $allSets = $event->trigger("Proc:encodeUserIds", $allSets);
    $working->task_id = $event->trigger("Proc:encodeTaskIds", $working->task_id);
    $working->organization_id = $event->trigger("Proc:encodeOrganizationIds", $working->organization_id);

    $view->setAppToken()
         ->title($title)
         ->setValue("WORKTABLE.sets", $allSets)
         ->setValue("WORKTABLE.no_sets", !(bool)$lastSet)
         ->setValue("WORKTABLE.task_id", $working->task_id)
         ->setValue("WORKTABLE.max_upload_file_size", ini_get("upload_max_filesize"))
         ->feData( [ "APP.token", "WORKTABLE.default_title", "WORKTABLE.hash", "WORKTABLE.no_files",
           "WORKTABLE.no_sets", "WORKTABLE.has_reset", "WORKTABLE.max_upload_file_size",
           "WORKTABLE.task_id", "WORKTABLE.sets", "USER.user_id", "USER.user_name",
           "WORKTABLE.last_set", "WORKTABLE.last_file", "WORKTABLE.last_set_files",
           "WORKTABLE.default_target_language", "WORKTABLE.default_original_language",
           "WORKTABLE.last_file_lines", "WORKTABLE.parse_error"] )
         ->feModules( "worktable" )
         ->init( "WorkTable" );
  }

  public function jsonPOSTDownloadLink ($hash, Model\ModelConventions $model, Core\Event $event,
    Core\Route $route, Core\App $app) {
    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $working = $event->trigger("Verify:worktableRecord", $model, $hash, $userId);
    if (!$working) throw new Core\RouteResolveException("worktable_unavailable");

    $taskId = $working->task_id;
    $task = $model("Task")->getTask($taskId, true);
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

    if (!$memberOfOrganization && !$currentUser["admin"]) {
      throw new Core\CommonException("permission_denied");
    }

    $fileConfiguration = $route->phpInput("json");
    if (!count($fileConfiguration)) throw new Core\CommonException("no_file_selected");

    $configuration = [];
    foreach ($fileConfiguration as $conf) {
      if (
        !is_array($conf) ||
        !isset($conf["best_translation"]) ||
        !isset($conf["newest_translation"]) ||
        !isset($conf["machine_translation"]) ||
        !isset($conf["source"])
      ) {
        throw new CommonException("params_error");
      }

      $conf["file_id"] = $event->trigger("Proc:decodeFileIds", $conf["file_id"]);
      $file = $model("Task")->getFile($conf["file_id"], true);
      $model("Task")->setBelongsTo($file->set_id. $taskId, true);
      $conf["best_translation"] = $model->isBool($conf["best_translation"], true, true);
      $conf["newest_translation"] = $model->isBool($conf["newest_translation"], true, true);
      $conf["machine_translation"] = $model->isBool($conf["machine_translation"], true, true);
      $conf["source"] = $model->isBool($conf["source"], true, true);
      $configuration[] = $conf;
    }

    $data = [
      "download_id" => $app->charsGen(40),
      "configuration" => $configuration,
      "expire" => time() + 60
    ];

    $_SESSION["download_session"] = $data;
    $downloadLink = $app->config['site_url'] . "worktable/{$working->hash}";
    $downloadLink .= "/download/{$data["download_id"]}";
    $route->jsonReturn($this->status(
      "download_link_created",
      false,
      ["download_link" => $downloadLink]
    ));
  }

  public function getDownload ($hash, $downloadId, Model\ModelConventions $model,
    Core\App $app, Core\Event $event) {
    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $throwError = function () {
      throw new RouteResolveException("download_unavailable");
    };

    $working = $event->trigger("Verify:worktableRecord", $model, $hash, $userId);
    $working || $throwError();

    $task = $model("Task")->getTask($working->task_id, true);
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

    if (!$memberOfOrganization && !$currentUser["admin"]) {
      throw new Core\CommonException("permission_denied");
    }

    (!isset($_SESSION["download_session"])) && $throwError();

    $downloadSession = $_SESSION["download_session"];
    $sessionExpire = $downloadSession["expire"];
    $sessionConfiguration = $downloadSession["configuration"];
    unset($_SESSION["download_session"]);
    ($sessionExpire < time()) && $throwError();

    $conf = array_shift($sessionConfiguration);
    $file = $model("Task")->getFile($conf["file_id"], true);

    $parser = ParserProvider::init($app)->getParser($file->path, $file->extension);

    $conf["line"] = $file->line;
    $bestTranslations = [];
    $newestTranslations = [];

    if ($conf["best_translation"]) {
      $bestTranslations = $model("Task")->getBestTranslations($file->file_id);
      $bestTranslations = ($bestTranslations && count($bestTranslations) > 0) ?
        $event->trigger("Proc:sortReIndexTranslations", $bestTranslations) : [];
    }

    if ($conf["newest_translation"]) {
      $newestTranslations = $model("Task")->getNewestTranslations($file->file_id);
      $newestTranslations = ($newestTranslations && count($newestTranslations) > 0) ?
        $event->trigger("Proc:sortReIndexTranslations", $newestTranslations) : [];
    }

    $build = $parser->build($conf, $bestTranslations, $newestTranslations);

    $rawFileName = $file->name . "." . $file->extension;
    $fileName = rawurlencode($rawFileName);
    $ua = $_SERVER["HTTP_USER_AGENT"];
    header("Content-type: application/octet-stream");
    if (preg_match("/MSIE/", $ua)) {
     header('Content-Disposition: attachment; filename="' . $fileName . '"');
    } else if (preg_match("/Firefox/", $ua)) {
     header("Content-Disposition: attachment; filename*=\"utf8''" . $rawFileName . '"');
    } else {
     header('Content-Disposition: attachment; filename="' . $rawFileName . '"');
    }
    $parser->output($build);
  }

  public function jsonGETId (ModelConventions $model, Route $route, View $view, App $app,
    Event $event) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $method = Route::HTTP_GET;
    $organizationId = $route->queryString("organization_id", $method, ["trim"]);
    $taskId = $route->queryString("task_id", $method, ["trim"]);
    $setId = $route->queryString("set_id", $method, ["trim"]);

    $organizationId = $event->trigger("Proc:decodeOrganizationIds", $organizationId);
    $taskId = $event->trigger("Proc:decodeTaskIds", $taskId);
    $setId = $event->trigger("Proc:decodeSetIds", $setId);

    // 是否存在此组织
    $organization = $model("Organization")->getOrganization($organizationId, true);
    $organizationId = $organization->organization_id;
    // 是否存在任务
    $task = $model("Task")->getTask($taskId, true);
    if ($task->organization_id !== $organizationId) {
      throw new CommonException("params_error", 403);
    }
    // 是否存在文件集
    $set = $model("Task")->getSet($setId);
    if ($set->task_id !== $task->task_id) {
      throw new CommonException("params_error", 403);
    }

    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model("Organization"),
      $organization,
      $organization->organization_id,
      $userId
    );

    if (!$memberOfOrganization && !$currentUser["admin"]) {
      throw new Core\CommonException("permission_denied");
    }

    $record = $model->searchRecord($organizationId, $taskId, $userId);

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use ($db) {
      $db->TSRollBack();
    });

    if (!$record) {
      $data = [
        "hash"            => $app->sha1Gen(),
        "user_id"         => $userId,
        "organization_id" => $organizationId,
        "task_id"         => $taskId,
        "created"         => time()
      ];

      // 创建新的任务
      $model->set("NewRecord", $data);
      $invalid = $model->firstInvalid();
      if ($invalid) throw new Core\CommonException($invalid);

      $model->save();
      $db->TSCommit();
      $url = $app->config["site_url"] . "worktable/" . $data["hash"];
      return $route->jsonReturn($this->status("worktable_link_created", false, ["link" => $url]));
    } elseif ($record->last_set_id !== $setId) {
      $model->updateRecord($record->hash, $setId, 0, 1, true);
      $db->TSCommit();
      $url = $app->config["site_url"] . "worktable/" . $record->hash;
      return $route->jsonReturn($this->status("worktable_link_created", false, ["link" => $url]));
    }

    $url = $app->config["site_url"] . "worktable/" . $record->hash;
    $route->jsonReturn($this->status("worktable_link_created", false, ["link" => $url]));
  }

  public function jsonGETDraft (ModelConventions $model, Route $route, Event $event) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];
    $records = $model->getRecords($userId, true);
    $records = $event->trigger("Proc:encodeUserIds", $records);
    $records = $event->trigger("Proc:encodeTaskIds", $records);
    $records = $event->trigger("Proc:encodeOrganizationIds", $records);
    $records = $event->trigger("Proc:encodeSetIds", $records, "last_set_id");
    $records = $event->trigger("Proc:encodeFileIds", $records, "last_file_id");
    $route->jsonReturn($records);
  }

  public function jsonDELETEDraft ($hash, ModelConventions $model, Route $route, Event $event) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $record = $model->getWorkingSetRecord($hash, $userId, true);
    $model->deleteRecord($hash, true);
    $route->jsonReturn($this->status("record_deleted", false));
  }
}