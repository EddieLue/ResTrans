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

namespace ResTrans\Core\Model;

use ResTrans\Core;
use ResTrans\Core\Query;

class Task extends ModelConventions {

  public $supportLanguage = ["zh-CN", "zh-TW", "zh-HK", "en-US", "en-UK", "ja-JP", "de-DE", "ru-RU",
    "fr-FR", "ko-KR"];

  public $translationFields = ["translation_id", "file_id", "line", "text",
    "best_translation", "contributor", "contributed", "proofreader"];

  public function setTask (array $data) {
    $organizationModel = $this->getOtherModel("Organization");
    $organization = $organizationModel->getOrganization( $data["organization_id"] );
    $organization || $this->putInvalid( "organization_not_found" );

    $data["name"] = $this->isEmpty( $data["name"], null, "task_name_is_empty" );
    ( mb_strlen($data["name"], "UTF-8") > 20 ) && $this->putInvalid("name_too_long");
    ( mb_strlen($data["description"], "UTF-8") > 1000 ) && $this->putInvalid("description_too_long");
    $data["original_language"] = $this->isValid($data["original_language"], $this->supportLanguage, 
      "en-US");
    $data["target_language"] = $this->isValid($data["target_language"], $this->supportLanguage, 
      "zh-CN");
    return $data;
  }

  public function saveTask ($db, array $data) {
    $fields = ["organization_id", "user_id", "name", "description", "created", "percentage",
      "original_language", "target_language", "set_total", "frozen", "api_key"];
    $prepareSQL = $db->insert("tasks", 11, $db->fieldList($fields));

    try {
      $newTask = $db->pdoPrepare($prepareSQL, ["1#int" => $data["organization_id"],
        "2#int" => $data["user_id"], "3#str" => $data["name"], "4#str" => $data["description"],
        "5#int" => $data["created"], "6#int" => 0, "7#str" => $data["original_language"],
        "8#str" => $data["target_language"], "9#int" => 0, "10#int" => 0, "11#str" => $data["key"] ]);

      $newTask->execute();
      if ( !$newTask->rowCount() ) throw new \Exception();
      $this->setLastInsertId();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException( "create_task_failed" );
    }
  }

  public function setUpdateTask (array $data) {
    $data["name"] = $this->isEmpty( $data["name"], null, "task_name_is_empty" );
    ( mb_strlen($data["name"], "UTF-8") > 20 ) && $this->putInvalid("name_too_long");
    ( mb_strlen($data["description"], "UTF-8") > 1000 ) && $this->putInvalid("description_too_long");
    $data["original_language"] = $this->isValid($data["original_language"], $this->supportLanguage, 
      "en-US");
    $data["target_language"] = $this->isValid($data["target_language"], $this->supportLanguage, 
      "zh-CN");
    return $data;
  }

  public function saveUpdateTask ($db, array $data) {
    $fields = ["name", "description", "original_language", "target_language"];
    $set = "{$db->condition("name")}, {$db->condition("description")}, {$db->condition("original_language")}, {$db->condition("target_language")}";
    $prepareSQL = $db->update("tasks", $set, $db->condition("task_id"));

    try {
      $updateTask = $db->pdoPrepare($prepareSQL, ["1#str" => $data["name"],
        "2#str" => $data["description"], "3#str" => $data["original_language"], "4#str" => $data["target_language"], "5#int" => $data["task_id"] ]);

      $updateTask->execute();
      if ( !$updateTask->rowCount() ) throw new \Exception();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException("save_task_failed");
    }
  }

  public function getTask ( $taskId ) {
    $db = $this->db();
    $prepareSQL = "SELECT `tasks`.`task_id`, `tasks`.`organization_id`, `tasks`.`user_id`, 
`tasks`.`name`, `tasks`.`description`, `tasks`.`created`, `tasks`.`percentage`, `tasks`.`original_language`, `tasks`.`target_language`, `tasks`.`set_total`, `tasks`.`frozen`, 
`tasks`.`api_key`, `tasks`.`api_request`, `users`.`name` as `user_name`, `organizations`.`name` as `organization_name` 
    FROM `{$db->prefix}tasks` as `tasks`, `{$db->prefix}users` as `users`, `{$db->prefix}organizations` as `organizations` 
    WHERE `tasks`.`task_id` = ? AND `tasks`.`user_id` = `users`.`user_id` AND `organizations`.`organization_id` = `tasks`.`organization_id`";

    try {
      $getTask = $db->pdoPrepare($prepareSQL, [ "1#int" => $taskId ]);
      $getTask->execute();
      $fetch = $getTask->fetch();
      if ( ! $fetch ) throw new \Exception();
      return $fetch;
    } catch (\Exception $e) {
      throw new Core\CommonException("task_not_found");
    }
  }

  public function getTasks ($organizationId, $start = 1, $amount = 20, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT `tasks`.`task_id`, `tasks`.`organization_id`, `tasks`.`user_id`, `tasks`.`name`, `tasks`.`description`, `tasks`.`created`, `tasks`.`percentage`, `tasks`.`original_language`, `tasks`.`target_language`, `tasks`.`set_total`, `tasks`.`frozen`, `users`.`name` as `user_name`
    FROM `{$db->prefix}tasks` as `tasks`, `{$db->prefix}users` as `users`
    WHERE `organization_id` = ? AND `users`.`user_id` = `tasks`.`user_id`
    ORDER BY `organization_id` ASC LIMIT ?, ?";

    try {
      $getTasks = $db->pdoPrepare(
        $prepareSQL,
        [ "1#int" => $organizationId, "2#int" => --$start, "3#int" => $amount ]
      );
      $result = $getTasks->execute();
      $fetchAll = $getTasks->fetchAll();
      if (!$result || !$fetchAll) throw new \Exception();
      return $fetchAll;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("tasks_not_found");
      return false;
    }
  }

  public function setExists ($setId, $throw = false) {
    return $throw ? $this->exists("sets", "set_id", $setId, true, "set_not_found"):
                    $this->exists("sets", "set_id", $setId);
  }

  public function fileExists ($fileId, $throw = false) {
    return $throw ? $this->exists("files", "file_id", $fileId, true, "file_not_found"):
                    $this->exists("files", "file_id", $fileId);
  }

  public function translationExists ($translationId, $throw = false) {
    return $throw ? $this->exists("translations", "translation_id", $translationId, true,
      "translation_not_found"):
                    $this->exists("translations", "translation_id", $translationId);
  }

  public function setBelongsTo ($setId, $taskId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->select("sets", $db->fieldList(["set_id", "task_id"],
      "`set_id` = ? AND `task_id` = ?"));
    try {
      $setBelongsTo = $db->pdoPrepare($prepareSQL, [ "1#int" => $setId, "2#int" => $taskId ]);
      $setBelongsTo->execute();
      $fetch = $setBelongsTo->fetch();
      if ( !$fetch || !$setBelongsTo->rowCount() ) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("set_does_not_belong_to_task");
      return false;
    }
  }

  public function setNewSet (array $data) {
     ( !$data["set_name"] || mb_strlen($data["set_name"]) > 50 ) && $this->putInvalid("name_error");
     return $data;
  }

  public function saveNewSet ($db, array $data) {
    $fields = ["name", "task_id", "user_id", "file_total"];
    $prepareSQL = $db->insert("sets", 4, $db->fieldList($fields));
    try {
      $newSet = $db->pdoPrepare($prepareSQL, ["1#str" => $data["set_name"],
        "2#int" => $data["task_id"], "3#int" => $data["user_id"], "4#int" => 0]);
      $newSet->execute();
      $this->setLastInsertId();
      if ( !$newSet->rowCount() ) throw new \Exception();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException( "create_set_failed" );
    }
  }

  public function updateSetTotal( $taskId ) {

    try {
      $task = $this->getTask($taskId);
      $db = $this->db();
      $prepareSQL = $db->select("sets", "count(*)", $db->condition("task_id"));
      $countTotal = $db->pdoPrepare($prepareSQL, [ "1#int" => $taskId ]);
      $countTotal->execute();

      $setTotal      = (int)$task->set_total; // 任务表中
      $countSetTotal = (int)$countTotal->fetchColumn(); // 集合表中
      if ( $countSetTotal === $setTotal ) return true;

      // 若不一致，执行更新
      $prepareSQL = $db->update("tasks", $db->condition("set_total"), $db->condition("task_id"));
      $params = ["1#int" => $countSetTotal, "2#int" => $taskId];
      $updateSetTotal = $db->pdoPrepare($prepareSQL, $params);
      $updateSetTotal->execute();
      if ( !$updateSetTotal->rowCount() ) throw new \Exception();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException("update_set_total_failed");
    }
  }

  public function getSet ( $setId ) {
    $db = $this->db();
    $prepareSQL = $db->select("sets", "*", $db->condition("set_id"));
    try {
      $getSet = $db->pdoPrepare($prepareSQL, ["1#int" => $setId]);
      $getSet->execute();
      $fetch = $getSet->fetch();
      if ( !$fetch ) throw new \Exception();
      return $fetch;
    } catch ( \Exception $e ) {
      throw new Core\CommonException("set_not_found");
    }
  }

  public function getSets ($taskId, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT `sets`.`set_id`, `sets`.`name`, `sets`.`user_id`, `sets`.`file_total`, `users`.`name` as `user_name` 
    FROM `{$db->prefix}sets` as `sets`, `{$db->prefix}users` as `users` 
    WHERE `sets`.`task_id` = ? AND `users`.`user_id` = `sets`.`user_id` 
    ORDER BY `sets`.`set_id`";
    try {
      $getSets = $db->pdoPrepare($prepareSQL, ["1#int" => $taskId]);
      $getSets->execute();
      $fetchAll = $getSets->fetchAll();
      if ( !$fetchAll ) throw new \Exception();
      return $fetchAll;
    } catch ( \Exception $e ) {
      if ($throw) throw new Core\CommonException("set_not_found");
      return false;
    }
  }

  public function getFiles ($setId, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT `files`.`file_id`, `files`.`set_id`, `files`.`name`, `files`.`extension`, `files`.`path`, `files`.`uploader`, `files`.`size`, `files`.`translatable`, `files`.`line`, `files`.`percentage`, `files`.`original_language`, `files`.`target_language`, `files`.`created`, `files`.`last_contributor`, `files`.`last_contributed`, `users`.`name` as `last_contributor_name` 
    FROM `{$db->prefix}files` as `files`, `{$db->prefix}users` as `users` 
    WHERE `files`.`set_id` = ? AND `files`.`last_contributor` = `users`.`user_id` 
    ORDER BY `files`.`created` DESC";
    try {
      $getFiles = $db->pdoPrepare($prepareSQL, ["1#int" => $setId]);
      $getFiles->execute();
      $fetchAll = $getFiles->fetchAll();
      if ( !$fetchAll ) throw new \Exception();
      return $fetchAll;
    } catch ( \Exception $e ) {
      if ($throw) throw new Core\CommonException("file_not_found");
      return false;
    }
  }

  public function setNewFile (array $data) {
    $this->setExists($data["set_id"]) || $this->putInvalid("set_not_found");
    (!$data["translatable"]) && $this->putInvalid("file_not_to_be_translated");
    return $data;
  }

  public function saveNewFile ($db, array $data) {
    $prepareSQL = $db->insert("files", 14, $db->fieldList(["set_id", "name", "extension", "path",
      "uploader", "size", "line", "translatable", "percentage", "original_language",
      "target_language", "created", "last_contributor", "last_contributed" ]));
    try {
      $saveNewFile = $db->pdoPrepare($prepareSQL, [
        "1#int" => $data["set_id"],
        "2#str" => $data["name"],
        "3#str" => $data["extension"],
        "4#str" => $data["path"],
        "5#int" => $data["uploader"],
        "6#int" => $data["size"],
        "7#int" => $data["line"],
        "8#int" => $data["translatable"],
        "9#str" => 0,
        "10#str" => $data["original_language"],
        "11#str" => $data["target_language"],
        "12#int" => $data["created"],
        "13#int" => $data["last_contributor"],
        "14#int" => $data["last_contributed"] ]);
      $saveNewFile->execute();
      if ( !$saveNewFile->rowCount() )throw new \Exception();
      $this->setLastInsertId();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException("file_save_failed");
    }
  }

  public function updateFileTotal ($setId) {
    try {
      $set = $this->getSet($setId);

      // 尝试对比两表中的人数
      // 如果一致即直接返回
      $db = $this->db();
      $prepareSQL = $db->select("files", "count(1)", $db->condition("set_id"));
      $countTotal = $db->pdoPrepare($prepareSQL, [ "1#int" => $setId ]);
      $countTotal->execute();

      $fileTotal      = (int)$set->file_total; // 集合表中
      $countFileTotal = (int)$countTotal->fetchColumn(); // 文件表中
      if ( $countFileTotal === $fileTotal ) return true;

      // 若不一致，执行更新
      $prepareSQL = $db->update("sets", $db->condition("file_total"), $db->condition("set_id"));
      $params = ["1#int" => $countFileTotal, "2#int" => $setId];
      $updateFileTotal = $db->pdoPrepare($prepareSQL, $params);
      $updateFileTotal->execute();
      if ( !$updateFileTotal->rowCount() ) throw new \Exception();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException("update_file_total_failed");
    }
  }

  public function getFile ($fileId, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT `file`.`file_id`, `file`.`set_id`, `file`.`name`, `file`.`extension`, `file`.`path`, `file`.`uploader`, `file`.`size`, `file`.`line`, `file`.`translatable`, `file`.`percentage`, `file`.`original_language`, `file`.`target_language`, `file`.`created`, `file`.`last_contributor`, `file`.`last_contributed`, `user1`.`name` as `uploader_name`, `user2`.`name` as `last_contributor_name` 
      FROM `{$db->prefix}files` as `file`, `{$db->prefix}users` as `user1` , `{$db->prefix}users` as `user2` 
      WHERE `file`.`file_id` = ? AND `file`.`uploader` = `user1`.`user_id` AND `file`.`last_contributor` = `user2`.`user_id`";
    try {
      $getFile = $db->pdoPrepare($prepareSQL, ["1#int" => $fileId]);
      $getFile->execute();
      $fetch = $getFile->fetch();
      if (!$fetch || !$getFile->rowCount()) throw new \Exception();
      return $fetch;
    } catch ( \Exception $e ) {
      if ($throw) throw new Core\CommonException("file_not_found");
      return false;
    }
  }

  public function getTranslations ($fileId, $start = 1, $end = 100, $bestOnly = false, $throw = false) {
    $db = $this->db();
    $bestOnly = $bestOnly ? "`translation`.`best_translation` = 1 AND " : "";
    $prepareSQL = "SELECT `translation`.*, `user1`.`name` AS `contributor_name`, user2.name as proofreader_name
      FROM `{$db->prefix}translations` AS `translation`
      JOIN `{$db->prefix}users` AS `user1` ON translation.contributor=user1.user_id
      LEFT JOIN `{$db->prefix}users` AS `user2` ON translation.proofreader=user2.user_id
      WHERE `translation`.`file_id` = ? AND {$bestOnly}
       (`translation`.`line` BETWEEN ? AND ?)
      ORDER BY `translation`.`contributed`";
    try {
      $getTranslations = $db->pdoPrepare($prepareSQL, ["1#int" => $fileId, "2#int" => $start,
        "3#int" => $end]);
      $getTranslations->execute();
      $fetchAll = $getTranslations->fetchAll();
      if (!$fetchAll || !$getTranslations->rowCount()) throw new \Exception();
      return $fetchAll;
    } catch ( \Exception $e ) {
      if ($throw) throw new Core\CommonException("translation_not_found");
      return [];
    }
  }

  public function getTranslation ($translationId, $throw = false) {
    $db = $this->db();
    $fields = $db->fieldList($this->translationFields);
    $prepareSQL = $db->select("translations", $fields, $db->condition("translation_id"));
    try {
      $getTranslation = $db->pdoPrepare($prepareSQL, ["1#int" => $translationId]);
      $getTranslation->execute();
      $fetch = $getTranslation->fetch();
      if (!$fetch || !$getTranslation->rowCount()) throw new \Exception();
      return $fetch;
    } catch ( \Exception $e ) {
      if ($throw) throw new Core\CommonException("translation_not_found");
      return false;
    }
  }

  public function getTranslationByLine ($fileId, $line, $throw = false) {
    $db = $this->db();
    $fields = $db->fieldList($this->translationFields);
    $where = "`file_id` = ? AND `line` = ?";
    $prepareSQL = $db->select("translations", $fields, $where);
    try {
      $getTranslationByLine = $db->pdoPrepare($prepareSQL, ["1#int" => $fileId, "2#int" => $line]);
      $getTranslationByLine->execute();
      $fetchAll = $getTranslationByLine->fetchAll();
      if (!$fetchAll || !$getTranslationByLine->rowCount()) throw new \Exception();
      return $fetchAll;
    } catch ( \Exception $e ) {
      if ($throw) throw new Core\CommonException("translation_not_found");
      return false;
    }
  }

  public function getMyTranslation ($fileId, $line, $userId, $throw = false) {
    $db = $this->db();
    $where = "`file_id` = ? AND `line` = ? AND `contributor` = ?";
    $prepareSQL = $db->select("translations", $db->fieldList($this->translationFields), $where);
    try {
      $getMyTranslation = $db->pdoPrepare($prepareSQL, ["1#int" => $fileId, "2#int" => $line,
        "3#int" => $userId]);
      $getMyTranslation->execute();
      $fetch = $getMyTranslation->fetch();
      if (!$fetch || !$getMyTranslation->rowCount()) throw new \Exception();
      return $fetch;
    } catch ( \Exception $e ) {
      if ($throw) throw new Core\CommonException("translation_not_found");
      return false;
    }
  }

  public function setNewTranslation (array $data) {
    return $data;
  }

  public function saveNewTranslation ($db, array $data) {
    $ao = new \ArrayObject($this->translationFields);
    $translationFields = $ao->getArrayCopy();
    array_shift($translationFields);
    $prepareSQL = $db->insert("translations", 7, $db->fieldList($translationFields));
    try {
      $saveNewTranslation = $db->pdoPrepare($prepareSQL, ["1#int" => $data["file_id"],
        "2#int" => $data["line"], "3#str" => $data["text"], "4#int" => $data["best_translation"],
        "5#int" => $data["contributor"], "6#int" => $data["contributed"], "7#int" => 0]);
      if (!$saveNewTranslation->execute() || !$saveNewTranslation->rowCount()) throw new \Exception();
      $this->setLastInsertId();
      return true;
    } catch (\Exception $e) {
      throw new Core\CommonException("translation_save_failed");
    }
  }

  public function deleteTranslation ($translationId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->delete("translations", $db->condition("translation_id"));
    try {
      $deleteTranslation = $db->pdoPrepare($prepareSQL, ["1#int" => $translationId]);
      $result = $deleteTranslation->execute();
      if (!$result || !$deleteTranslation->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("delete_translation_failed");
      return false;
    }
  }

  public function setUpdateTranslation (array $data) {
    $data["text"] = $this->isEmpty($data["text"], null, "text_cannot_be_empty");
    $data["best_translation"] = $this->isBool($data["best_translation"], false);
    return $data;
  }

  public function saveUpdateTranslation($db, array $data) {
    $set = "`text` = ?, `best_translation` = ?, `contributed` = ?, `proofreader` = ?";
    $prepareSQL = $db->update("translations", $set, $db->condition("translation_id"));

    try {
      $updateTranlation = $db->pdoPrepare($prepareSQL, ["1#str" => $data["text"],
        "2#int" => $data["best_translation"], "3#int" => $data["contributed"],
        "4#int" => $data["proofreader"], "5#int" => $data["translation_id"]]);
      $result = $updateTranlation->execute();
      if (!$result || !$updateTranlation->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      throw new Core\CommonException("update_translation_failed");
    }
  }

  public function updateFilePercentage ($fileId, $translatable = 0, $throw = false) {
    $db = $this->db();
    $where = $db->condition("file_id");
    $orderBy = " GROUP BY `line`";
    $prepareSQL = $db->select("translations", $db->field("translation_id"), $where) . $orderBy;
    try {
      $countTranslatable = $db->pdoPrepare($prepareSQL, ["1#int" => $fileId]);
      $result = $countTranslatable->execute();
      $realTranslatable = $countTranslatable->rowCount();
      if (!$result) throw new \Exception();
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("update_percentage_failed");
      return false;
    }

    $translatable = $translatable ? $translatable : $this->getFile($fileId)->translatable;
    $newPercentage = number_format($realTranslatable / $translatable * 100, 2);
    $newPercentage = $newPercentage > 100 ? 100 : $newPercentage;

    $sql = $db->update("files", $db->condition("percentage"), $db->condition("file_id"));
    try {
      $updatePercentage = $db->pdoPrepare($sql, ["1#str" => strval($newPercentage),
        "2#int" => $fileId]);
      $result = $updatePercentage->execute();
      if (!$result) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("update_percentage_failed");
      return false;
    }
  }

  public function updateTaskPercentage ($taskId, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT sum(`percentage`) as `percentage`, count(`percentage`) as `count` FROM
    `{$db->prefix}files` WHERE `set_id` IN 
    (SELECT `set_id` FROM `{$db->prefix}sets` WHERE `task_id` = ?)";
    try {
      $countPercentage = $db->pdoPrepare($prepareSQL, ["1#int" => $taskId]);
      $result = $countPercentage->execute();
      $count = $countPercentage->fetch();
      if (!$result || !$countPercentage->rowCount()) throw new \Exception();
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("update_task_percentage_failed");
      return false;
    }

    $newPercentage = (int)$count->percentage === 0 ? 0 : 
      number_format($count->percentage / ($count->count), 2);
    $newPercentage = $newPercentage > 100 ? 100 : $newPercentage;

    $sql = $db->update("tasks", $db->condition("percentage"), $db->condition("task_id"));
    try {
      $updatePercentage = $db->pdoPrepare($sql, ["1#str" => strval($newPercentage),
        "2#int" => $taskId]);
      $result = $updatePercentage->execute();
      if (!$result) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("update_task_percentage_failed");
      return false;
    }
  }

  public function updateFileName ($fileId, $newFileName, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->update("files", $db->condition("name"), $db->condition("file_id"));
    try {
      $updateFileName = $db->pdoPrepare($prepareSQL, ["1#str" => $newFileName, "2#int" => $fileId]);
      $result = $updateFileName->execute();
      if (!$result || !$updateFileName->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("update_file_name_failed");
      return false;
    }
  }

  public function getBestTranslations ($fileId, $throw = false) {
    $db = $this->db();
    $fields = $db->fieldList(["translation_id", "file_id", "line", "text", "best_translation", "contributor", "contributed"]);
    $where = "{$db->condition("file_id")} AND `best_translation` = 1";
    $prepareSQL = $db->select("translations", $fields, $where);
    try {
      $getBestTranslations = $db->pdoPrepare($prepareSQL, ["1#int" => $fileId]);
      $result = $getBestTranslations->execute();
      $fetchAll = $getBestTranslations->fetchAll();
      if (!$result || !$fetchAll || !$getBestTranslations->rowCount()) throw new \Exception();
      return $fetchAll;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("get_best_translations_failed");
      return false;
    }
  }

  public function getNewestTranslations ($fileId, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT `translations`.`translation_id`, `translations`.`file_id`, `translations`.`line`, `translations`.`text`, `translations`.`best_translation`, `translations`.`contributor`, `translations`.`contributed` 
      from `{$db->prefix}translations` as `translations`, 
      (SELECT  `line`, max(`contributed`) as `cont` 
        from `{$db->prefix}translations` WHERE `file_id` = ? GROUP BY `line`) as `temp` 
      WHERE `translations`.`line` = `temp`.`line` AND `translations`.`contributed` = `temp`.`cont`
      ORDER BY `line`";
    try {
      $getNewestTranslations = $db->pdoPrepare($prepareSQL, ["1#int" => $fileId]);
      $result = $getNewestTranslations->execute();
      $fetchAll = $getNewestTranslations->fetchAll();
      if (!$result || !$fetchAll || !$getNewestTranslations->rowCount()) throw new \Exception();
      return $fetchAll;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("get_newest_translations_failed");
      return false;
    }
  }

  public function deleteFile ($fileId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->delete("files", $db->condition("file_id"));
    try {
      $deleteFile = $db->pdoPrepare($prepareSQL, ["1#int" => $fileId]);
      $result = $deleteFile->execute();
      if (!$result || !$deleteFile->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("delete_file_failed");
      return false;
    }
  }

  public function deleteSetFiles ($setId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->delete("files", $db->condition("set_id"));
    try {
      $deleteFile = $db->pdoPrepare($prepareSQL, ["1#int" => $setId]);
      $result = $deleteFile->execute();
      if (!$result) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("delete_files_failed");
      return false;
    }
  }

  public function deleteSet ($setId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->delete("sets", $db->condition("set_id"));
    try {
      $deleteSet = $db->pdoPrepare($prepareSQL, ["1#int" => $setId]);
      $result = $deleteSet->execute();
      if (!$result || !$deleteSet->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("delete_set_failed");
      return false;
    }
  }

  public function deleteSets ($taskId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->delete("sets", $db->condition("task_id"));
    try {
      $deleteSets = $db->pdoPrepare($prepareSQL, ["1#int" => $taskId]);
      $result = $deleteSets->execute();
      if (!$result) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("delete_sets_failed");
      return false;
    }
  }

  public function deleteTask ($taskId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->delete("tasks", $db->condition("task_id"));
    try {
      $deleteTask = $db->pdoPrepare($prepareSQL, ["1#int" => $taskId]);
      $result = $deleteTask->execute();
      if (!$result || !$deleteTask->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("delete_task_failed");
      return false;
    }
  }

  public function deleteAllTranslationsOfFile ($fileId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->delete("translations", $db->condition("file_id"));
    try {
      $deleteAllTranslationsOfFile = $db->pdoPrepare($prepareSQL, ["1#int" => $fileId]);
      $result = $deleteAllTranslationsOfFile->execute();
      if (!$result) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("delete_translations_failed");
      return false;
    }
  }

  public function updateFrozenState ($taskId, $state, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->update("tasks", $db->condition("frozen"), $db->condition("task_id"));
    try {
      $updateFrozenState = $db->pdoPrepare($prepareSQL, ["1#int" => $state, "2#int" => $taskId]);
      $result = $updateFrozenState->execute();
      if (!$result) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("update_task_status_failed");
      return false;
    }
  }

  public function getTaskByApiKey ($key, $throw = false) {
    return $this->db()->query()
    ->select("task_id", "name", "description")
    ->from("tasks")
    ->where("api_key = ?")
    ->bindData(["1#str" => $key])
    ->ret(Query::FETCH)
    ->rowsPromise(1)
    ->throwException($throw ? "api_key_error" : null)
    ->execute();
  }

  public function getBestTranslationsForApi ($fileId, $lineStart, $lineEnd, $throw = false) {
    return $this->db()->query()
    ->select(
      "DISTINCT translations.line",
      "translations.text",
      "translations.contributed",
      "translations.contributor as contributor_id",
      "users.name as contributor_name"
    )
    ->from("translations as translations")
    ->join("users as users")
    ->on("translations.contributor = users.user_id")
    ->where("translations.file_id = ? AND translations.line BETWEEN ? AND ?")
    ->orderBy("translations.line")
    ->bindData(["1#int" => $fileId, "2#int" => $lineStart, "3#int" => $lineEnd])
    ->ret(Query::FETCH_ALL)
    ->throwException($throw ? "no_translations_found" : null)
    ->execute();
  }

  public function increaseApiRequestCount ($apiKey, $number, $throw = false) {
    return $this->db()->query()
    ->update("tasks")
    ->set("api_request = api_request + ?")
    ->where("api_key = ?")
    ->bindData(["1#int" => $number, "2#str" => $apiKey])
    ->rowsPromise(1)
    ->ret(Query::TORF)
    ->throwException($throw ? "update_api_request_count_failed" : null)
    ->execute();
  }

  public function updateApiKey ($taskId, $newApiKey, $throw = false) {
    return $this->db()->query()
    ->update("tasks")
    ->set("api_key = ?")
    ->where("task_id = ?")
    ->bindData(["1#str" => $newApiKey, "2#int" => $taskId])
    ->rowsPromise(1)
    ->ret(Query::TORF)
    ->throwException($throw ? "update_api_key_failed" : null)
    ->execute();
  }

  public function clearApiRequestCount ($taskId, $throw = false) {
    return $this->db()->query()
    ->update("tasks")
    ->set("api_request = 0")
    ->where("task_id = ?")
    ->bindData(["1#int" => $taskId])
    ->ret(Query::TORF)
    ->throwException($throw ? "reset_api_request_count_failed" : null)
    ->execute();
  }
}