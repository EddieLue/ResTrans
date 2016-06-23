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

class Start extends ModelConventions {

  public function getAllTasks ($userId, $start) {
    return $this->db()->query()
    ->select("DISTINCT tasks.*", "users.name as user_name", "organizations.name as organization_name")
    ->from("tasks as tasks", "organizations as organizations", "user_organization as uo", "users as users")
    ->where("uo.user_id = ? AND tasks.organization_id = uo.organization_id AND organizations.organization_id = tasks.organization_id AND tasks.user_id = users.user_id AND tasks.organization_id = organizations.organization_id")
    ->orderBy("tasks.created", "DESC")
    ->limit(true)
    ->bindData(["1#int" => $userId, "2#int" => $start - 1, "3#int" => 20])
    ->ret(Query::FETCH_ALL)
    ->execute();
  }

  public function getAllOrganizations ($start) {
    return $this->db()->query()
    ->select("organization_id", "name", "description", "maximum", "member_total", "task_total")
    ->from("organizations")
    ->where("accessibility = 1")
    ->orderBy("created", "DESC")
    ->limit(true)
    ->bindData(["1#int" => $start - 1, "2#int" => 20])
    ->ret(Query::FETCH_ALL)
    ->execute();
  }

  public function cleanSessions () {
    $currentTime = time();
    return $this->db()->query()
    ->delete("sessions")
    ->where("expire < ?")
    ->bindData(["1#int" => $currentTime])
    ->execute();
  }

  public function cleanVerifications () {
    $currentTime = time();
    return $this->db()->query()
    ->delete("verifications")
    ->where("expire < ?")
    ->bindData(["1#int" => $currentTime])
    ->execute();
  }
}