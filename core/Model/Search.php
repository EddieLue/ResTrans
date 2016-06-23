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

class Search extends ModelConventions {

  public function searchUsers( $keyword, $start, $amount ) {

    $start = (int)$start;
    $amount = (int)$amount;

    $db = $this->db();
    $where = "`user_id` = ? OR `name` like ?";
    $fields = $db->fieldList(["user_id", "name", "email"]);
    $prepareSQL = $db->select("users", $fields, $where, null, $start - 1, $amount );
    try {

      $searchUsers = $db->pdoPrepare($prepareSQL, ["1#int" => $keyword, "2#str" => "%{$keyword}%"]);
      $searchUsers->execute();
      return $searchUsers->fetchAll();
    } catch ( \Exception $e ) {
      return false;
    }
  }

  public function searchTasks ($keyword, $start, $throw = false) {
    return $this->db()->query()
    ->select("task_id", "name", "description")
    ->from("tasks")
    ->where("name LIKE ?")
    ->limit(true)
    ->bindData(["1#str" => "%{$keyword}%", "2#int" => $start - 1, "3#int" => 20])
    ->ret(Query::FETCH_ALL)
    ->throwException($throw ? "no_tasks" : null)
    ->execute();
  }

  public function searchOrganizations ($keyword, $start, $throw = false) {
    return $this->db()->query()
    ->select("organization_id", "name", "description")
    ->from("organizations")
    ->where("accessibility = 1 AND name LIKE ?")
    ->limit(true)
    ->bindData(["1#str" => "%{$keyword}%", "2#int" => $start - 1, "3#int" => 20])
    ->ret(Query::FETCH_ALL)
    ->throwException($throw ? "no_organizations" : null)
    ->execute();
  }
}