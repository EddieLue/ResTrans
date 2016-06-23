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

class UserProfile extends ModelConventions {

  public function getMyOrganizations ($userId, $all = false, $start = 1, $end = 10, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT `organizations`.*, `uo`.*
    FROM `{$db->prefix}user_organization` as `uo`, {$db->prefix}organizations as `organizations` 
    WHERE `uo`.`user_id` = ? AND `organizations`.`organization_id` = `uo`.`organization_id`";
    $all || ($prepareSQL = $prepareSQL .= " LIMIT ?, ?");
    try {
      $getMyOrganizations = $db->pdoPrepare(
        $prepareSQL,
        $all ? ["1#int" => $userId] : ["1#int" => $userId, "2#int" => --$start, "3#int" => $end]
      );
      $result = $getMyOrganizations->execute();
      $fetchAll = $getMyOrganizations->fetchAll();
      if (!$result || !$fetchAll) throw new \Exception();
      return $fetchAll;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("organizations_not_found");
    }
  }

  public function getOrganizations ($userId, $all = false, $start = 1, $end = 10, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT `organizations`.*, `uo`.*
    FROM `{$db->prefix}user_organization` as `uo`, {$db->prefix}organizations as `organizations` 
    WHERE `uo`.`user_id` = ? AND `organizations`.`organization_id` = `uo`.`organization_id` AND `organizations`.`accessibility` = 1";
    $all || ($prepareSQL = $prepareSQL .= " LIMIT ?, ?");
    try {
      $getOrganizations = $db->pdoPrepare(
        $prepareSQL,
        $all ? ["1#int" => $userId] : ["1#int" => $userId, "2#int" => --$start, "3#int" => $end]
      );
      $result = $getOrganizations->execute();
      $fetchAll = $getOrganizations->fetchAll();
      if (!$result || !$fetchAll) throw new \Exception();
      return $fetchAll;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("organizations_not_found");
    }
  }

  public function setUserSettings (array $data) {
    $data["blocked"] = $this->isValid($data["blocked"], [0, 1], 0);
    $data["send_message"] = $this->isValid($data["send_message"], [0, 1], 1);
    return $data;
  }

  public function saveUserSettings ($db, array $data) {
    $set = $db->condition('blocked') . "," . $db->condition('send_message');
    $prepareSQL = $db->update("users", $set, $db->condition("user_id"));

    try {
      $saveUserSettings = $db->pdoPrepare(
        $prepareSQL,
        [
          "1#int" => $data["blocked"],
          "2#int" => $data["send_message"],
          "3#int" => $data["user_id"]
        ]
      );
      if (!$saveUserSettings->execute() || !$saveUserSettings->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      throw new Core\CommonException("save_user_settings_failed");
    }
  }
}