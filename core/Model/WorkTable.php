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

class WorkTable extends ModelConventions {

  public function getWorkingSetRecord ($hash, $userId, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT `ws`.`hash`, `ws`.`user_id`, `ws`.`organization_id`, `ws`.`task_id`, `ws`.`last_set_id`, `ws`.`last_file_id`, `ws`.`last_line`, `ws`.`created`, `organizations`.`name` as `organization_name`, `tasks`.`name` as `task_name`, `tasks`.`frozen` as `task_frozen` 
    FROM `{$db->prefix}working_sets` as `ws`, `{$db->prefix}organizations` as `organizations`, `{$db->prefix}tasks` as `tasks` 
    WHERE `organizations`.`organization_id` = `ws`.`organization_id` AND `tasks`.`task_id` = `ws`.`task_id` AND `ws`.`hash` = ? AND `ws`.`user_id` = ?";
    try {
      $getWorkingSetRecord = $db->pdoPrepare($prepareSQL, ["1#str" => $hash, "2#int" => $userId]);
      $getWorkingSetRecord->execute();
      return $getWorkingSetRecord->rowCount() ? $getWorkingSetRecord->fetch() : false;
    } catch ( \PDOException $e ) {
      if ($throw) throw new Core\CommonException("record_not_found");
      return false;
    }
  }

  public function updateRecord ($hash, $setId, $fileId, $lastLine, $throw = false) {
    $db = $this->db();
    $set = "`last_set_id` = ?, `last_file_id` = ?, `last_line` = ?";
    $prepareSQL = $db->update("working_sets", $set, $db->condition("hash"));
    try {
      $updateRecord = $db->pdoPrepare($prepareSQL, ["1#int" => $setId, "2#int" => $fileId,
        "3#int" => $lastLine, "4#str" => $hash]);
      if (!$updateRecord->execute() || !$updateRecord->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("update_record_failed");
      return false;
    }
  }

  public function searchRecord ($organizationId, $taskId, $userId, $throw = false) {
    $db = $this->db();
    $where = "{$db->condition("organization_id")} AND {$db->condition("task_id")} AND {$db->condition("user_id")}";
    $prepareSQL = $db->select("working_sets", "*", $where);
    try {
      $searchRecord = $db->pdoPrepare(
        $prepareSQL,
        [
          "1#int" => $organizationId,
          "2#int" => $taskId,
          "3#int" => $userId
        ]
      );

      if (!$searchRecord->execute() || !$searchRecord->rowCount()) throw new \Exception();
      return $searchRecord->fetch();
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("no_record");
      return false;
    }
  }

  public function setNewRecord (array $data) {
    return $data;
  }

  public function saveNewRecord ($db, array $data) {
    $fields = ["hash", "user_id", "organization_id", "task_id", "last_set_id", "last_file_id",
      "last_line", "created"];
    $prepareSQL = $db->insert("working_sets", 8, $db->fieldList($fields));
    try {
      $saveNewRecord = $db->pdoPrepare($prepareSQL, [
        "1#str" => $data["hash"],
        "2#int" => $data["user_id"],
        "3#int" => $data["organization_id"],
        "4#int" => $data["task_id"],
        "5#int" => 0,
        "6#int" => 0,
        "7#int" => 0,
        "8#int" => $data["created"]
      ]);

      if (!$saveNewRecord->execute() || !$saveNewRecord->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      throw new Core\CommonException("worktable_link_create_failed");
    }
  }

  public function getRecords ($userId, $throw = false) {
    return $this->db()->query()
    ->setSql("SELECT ws .*, tasks.name as task_name, sets.name as set_name, files.name as file_name
      FROM {$this->db()->prefix}working_sets as ws
      LEFT JOIN {$this->db()->prefix}sets as sets ON ws.last_set_id = sets.set_id
      LEFT JOIN {$this->db()->prefix}files as files ON ws.last_file_id = files.file_id
      JOIN {$this->db()->prefix}tasks as tasks
      WHERE ws.user_id = ? AND ws.task_id = tasks.task_id")
    ->bindData(["1#int" => $userId])
    ->rowsPromise()
    ->ret(Query::FETCH_ALL)
    ->throwException($throw ? "no_record" : null)
    ->execute();
  }

  public function deleteRecord ($recordId, $throw = false) {
    return $this->db()->query()
    ->delete("working_sets")
    ->where("hash = ?")
    ->bindData(["1#str" => $recordId])
    ->ret(Query::TORF)
    ->rowsPromise(1)
    ->throwException($throw ? "delete_record_failed" : null)
    ->execute();
  }

  public function deleteWorkingByTaskId ($taskId) {
    $this
      ->db()
      ->query()
      ->delete("working_sets")
      ->where("task_id = ?")
      ->bindData(["1#int" => $taskId])
      ->disableExecutionCheck()
      ->execute();
  }

  public function deleteWorkingByOrganizationId ($organizationId) {
    $this
      ->db()
      ->query()
      ->delete("working_sets")
      ->where("organization_id = ?")
      ->bindData(["1#int" => $organizationId])
      ->disableExecutionCheck()
      ->execute();
  }
}