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

class Setting extends ModelConventions {

  public function getMySessions ($userId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->select(
      "sessions",
      $db->fieldList(["user_id", "session_id", "expire"]),
      $db->condition("user_id")
    );
    try {
      $getMySessions = $db->pdoPrepare($prepareSQL, ["1#int" => $userId]);
      if (!$getMySessions->execute()) throw \Exception();
      return $getMySessions->fetchAll();
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("no_sessions");
      return false;
    }
  }

  public function setProfile (array $data) {
    $data["gender"] = $this->isValid($data["gender"], [0, 1, 2, 3], 0);
    $data["public_email"] = $this->isValid($data["public_email"], [0, 1], 0);
    return $data;
  }

  public function saveProfile ($db, array $data) {
    $set = "{$db->condition("public_email")}, {$db->condition("gender")}";
    $prepareSQL = $db->update("users", $set, $db->condition("user_id"));
    try {
      $saveProfile = $db->pdoPrepare(
        $prepareSQL, 
        ["1#int" => $data["public_email"], "2#int" => $data["gender"], "3#int" => $data["user_id"]]
      );
      if (!$saveProfile->execute()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      throw new Core\CommonException("save_profile_setting_failed");
    }
  }

  public function setCommon (array $data) {
    $data["receive_message"] = $this->isValid($data["receive_message"], [0, 1], 1);
    return $data;
  }

  public function saveCommon ($db, array $data) {
    $prepareSQL = $db->update("users", $db->condition("receive_message"), $db->condition("user_id"));
    try {
      $saveCommon = $db->pdoPrepare(
        $prepareSQL, 
        ["1#int" => $data["receive_message"], "2#int" => $data["user_id"]]
      );
      if (!$saveCommon->execute()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      throw new Core\CommonException("save_common_setting_failed");
    }
  }

  public function deleteSessionId ($sessionId, $expire, $userId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->delete("sessions", "`session_id` LIKE ? AND `expire` = ? AND `user_id` = ?");
    try {
      $deleteSessionId = $db->pdoPrepare(
        $prepareSQL,
        [
          "1#str" => "{$sessionId}%",
          "2#int" => $expire,
          "3#int" => $userId
        ]
      );
      if (!$deleteSessionId->execute() || !$deleteSessionId->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("delete_session_failed");
      return false;
    }
  }

    public function getUsers ($start = 1, $amount = 10, $blocked = 0, $throw = false) {
    $db = $this->db();
    $fields = $db->fieldList(["user_id", "name", "email", "signup"]);
    $prepareSQL = $db->select(
      "users",
      $fields,
      $db->condition("blocked")
    ) . " ORDER BY `signup` DESC LIMIT ?, ?";
    try {
      $getUsers = $db->pdoPrepare(
        $prepareSQL,
        ["1#int" => $blocked, "2#int" => --$start,"3#int" => $amount]
      );
      if (!$getUsers->execute() || !$getUsers->rowCount()) throw new \Exception();
      return $getUsers->fetchAll();
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("no_users");
      return false;
    }
  }

    public function getOrganizations ($start = 1, $amount = 10, $throw = false) {
    $db = $this->db();
    $fields = $db->fieldList(
      ["organization_id", "name", "task_total", "member_total", "discussion_total"]
    );
    $prepareSQL = $db->select(
      "organizations",
      $fields
    ) . " ORDER BY `created` DESC LIMIT ?, ?";
    try {
      $getOrganizations = $db->pdoPrepare(
        $prepareSQL,
        ["1#int" => --$start,"2#int" => $amount]
      );
      if (!$getOrganizations->execute() || !$getOrganizations->rowCount()) throw new \Exception();
      return $getOrganizations->fetchAll();
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("no_organizations");
      return false;
    }
  }

    public function getTasks ($start = 1, $amount = 10, $throw = false) {
    $db = $this->db();
    $prepareSQL = "SELECT `tasks`.`task_id`, `tasks`.`name` as `task_name`, `tasks`.`set_total`,
    `organizations`.`organization_id`, `organizations`.`name` as `organization_name`
    FROM `{$db->prefix}tasks` as `tasks`, `{$db->prefix}organizations` as `organizations`
    WHERE `organizations`.`organization_id` = `tasks`.`organization_id`
    ORDER BY `tasks`.`created` DESC LIMIT ?, ?";
    try {
      $getTasks = $db->pdoPrepare(
        $prepareSQL,
        ["1#int" => --$start,"2#int" => $amount]
      );
      if (!$getTasks->execute() || !$getTasks->rowCount()) throw new \Exception();
      return $getTasks->fetchAll();
    } catch (\Exception $e) {
      if ($throw) throw new Core\CommonException("no_tasks");
      return false;
    }
  }

  public function setGlobalCommon (array $data) {
    $data["login_captcha"] = $this->isValid($data["login_captcha"], [0, 1], 1);
    $data["anonymous_access"] = $this->isValid($data["anonymous_access"], [0, 1], 0);
    $data["member_create_organization"] = $this->isValid($data["member_create_organization"], [0, 1], 0);
    return $data;
  }

  public function saveGlobalCommon ($db, array $data) {
    $updateLoginCaptcha = $db->update("options", $db->condition("value"), $db->condition("name"));
    $updateAnonymousAccess = $db->update("options", $db->condition("value"), $db->condition("name"));
    $updateMemberCreateOrganization = $db->update("options", $db->condition("value"), $db->condition("name"));
    try {
      $saveLoginCaptcha = $db->pdoPrepare(
        $updateLoginCaptcha,
        [
          "1#str" => $data["login_captcha"],
          "2#str" => "login_captcha"
        ]
      );
      $saveAnonymousAccess = $db->pdoPrepare(
        $updateAnonymousAccess,
        [
          "1#str" => $data["anonymous_access"],
          "2#str" => "anonymous_access"
        ]
      );
      $saveMemberCreateOrganization = $db->pdoPrepare(
        $updateMemberCreateOrganization,
        [
          "1#str" => $data["member_create_organization"],
          "2#str" => "member_create_organization"
        ]
      );
      if (
        !$saveLoginCaptcha->execute() ||
        !$saveAnonymousAccess->execute() ||
        !$saveMemberCreateOrganization->execute()
      ) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      throw new Core\CommonException("save_global_common_settings_failed");
    }
  }

  public function setGlobalRegister (array $data) {
    $data["register"] = $this->isValid($data["register"], [0, 1], 1);
    $data["send_email"] = $this->isValid($data["send_email"], [0, 1], 1);
    return $data;
  }

  public function saveGlobalRegister ($db, array $data) {
    $updateRegister = $db->update("options", $db->condition("value"), $db->condition("name"));
    $updateSendEmail = $db->update("options", $db->condition("value"), $db->condition("name"));
    try {
      $saveRegister = $db->pdoPrepare(
        $updateRegister,
        [
          "1#str" => $data["register"],
          "2#str" => "register"
        ]
      );
      $saveSendEmail = $db->pdoPrepare(
        $updateSendEmail,
        [
          "1#str" => $data["send_email"],
          "2#str" => "send_email"
        ]
      );
      if (
        !$saveRegister->execute() ||
        !$saveSendEmail->execute()
      ) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      throw new Core\CommonException("save_global_register_settings_failed");
    }
  }
}