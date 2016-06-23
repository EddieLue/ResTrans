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
use ResTrans\Core\Event;
use ResTrans\Core\Parser\ParserProvider;
use ResTrans\Core\Model\ModelConventions;

class Api extends ControllerConventions {

  public $relationModel = "Task";

  public function jsonGETTranslation (ModelConventions $model, Route $route, App $app, Event $event) {
    $this->addTokenOnError(false);

    $apiKey = $route->queryString("api_key", Route::HTTP_GET, ["trim"]);
    $lineStart = $route->queryString("line_start", Route::HTTP_GET, ["intval"]);
    $lineEnd = $route->queryString("line_end", Route::HTTP_GET, ["intval"]);
    $setId = $route->queryString("set_id", Route::HTTP_GET, ["trim"]);
    $setId = $event->trigger("Proc:decodeSetIds", $setId);
    $fileId = $route->queryString("file_id", Route::HTTP_GET, ["trim"]);
    $fileId = $event->trigger("Proc:decodeFileIds", $fileId);

    if ($lineStart < 0 || $lineEnd < $lineStart) {
      throw new Core\CommonException("params_error", 403);
    }

    $task = $model->getTaskByApiKey($apiKey, true);

    $set = $model->getSet($setId, true);
    if ($set->task_id !== $task->task_id) throw new Core\CommonException("set_not_found", 404);

    $file = $model->getFile($fileId, true);
    if ($file->set_id !== $set->set_id) throw new Core\CommonException("file_not_found", 404);
    if ($lineEnd > $file->line) throw new Core\CommonException("params_error", 403);

    $result = [
      "task" => [
        "id"          => $event->trigger("Proc:encodeTaskIds", $task->task_id),
        "name"        => $task->name,
        "description" => $task->description
        ],
      "set"  => [
        "id" => $event->trigger("Proc:encodeSetIds", $setId),
        "name" => $set->name
      ],
      "file"  => [
        "id" => $event->trigger("Proc:encodeFileIds", $fileId),
        "name" => $file->name
      ]
    ];

    $parser = ParserProvider::init($app)->getParser($file->path, $file->extension);

    $line = $parser->read($lineStart, $lineEnd);
    $translations = $model->getBestTranslationsForApi($file->file_id, $lineStart, $lineEnd);
    if (!$translations) $translations = [];
    $event->trigger("Proc:encodeUserIds", $translations, "contributor_id");

    $reindexingTranslations = [];
    foreach ($translations as $translation) {
      $reindexingTranslations[$translation->line] = $translation;
      unset($reindexingTranslations[$translation->line]->line);
    }

    $result["data"] = [];
    array_walk($line, function (&$l) use (&$result, &$reindexingTranslations) {
      $current["source"]["text"] = property_exists($l, "text") ? $l->text : "";
      $current["source"]["machine_translation"] = 
        property_exists($l, "machine_translation") ? $l->machine_translation : "";

      if (isset($reindexingTranslations[(int)$l->line_number])) {
        $current["translations"]["best"] = $reindexingTranslations[(int)$l->line_number];
      }

      $result["data"][] = $current;
    });

    $model->increaseApiRequestCount($apiKey, 1, true);
    $route->jsonReturn($result);
  }
}