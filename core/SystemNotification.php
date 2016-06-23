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

namespace ResTrans\Core;

class SystemNotification {
  /** 通知提醒类型 */
  const NT_JOIN_ORGANIZATION = 0;
  const NT_EXIT_ORGANIZATION = 1;
  const NT_JOIN_ORGANIZATION_PASSED = 2;
  const NT_JOIN_ORGANIZATION_REJECTED = 3;
  /** 通知提醒自定义状态 */
  const STATUS_NONE = 0;
  const STATUS_JOIN_ORGANIZATION_WAIT = 1;
  const STATUS_JOIN_ORGANIZATION_ALLOWED = 2;
  const STATUS_JOIN_ORGANIZATION_REJECTED = 3;
  const STATUS_JOIN_ORGANIZATION_IGNORED = 4;
  /** 通知提醒阅读（浏览）状态 */
  const VS_UNREAD = 0;
  const VS_READED = 1;

  public $appi;

  public $database;

  public static $_inst;

  public static function init (App $app) {
    self::$_inst = new self();
    self::$_inst->appi = $app;
    self::$_inst->database = Database::instance($app)->connect();
    return self::$_inst;
  }

  public function push($type, $targetId, $sender, $receiver, $status = 0, $viewStatus = 0) {
    return $this
      ->database
      ->query()
      ->insertInto("notifications")
      ->insertFields("type", "target_id", "sender", "receiver", "view_status", "status", "created")
      ->rowsPromise(1)
      ->ret(Query::TORF)
      ->bindData(["1#int" => $type, "2#int" => $targetId,
        "3#int" => $sender, "4#int" => $receiver, "5#int" => $viewStatus, "6#int" => $status,
        "7#int" => time()])
      ->throwException("push_notification_failed")
      ->execute();
  }

  public function pullOrganizationNotifications($receiverId, $start) {
    return $this
      ->database
      ->query()
      ->select("notifis.*", "users.name as sender_name", "orgs.name as organization_name")
      ->from("notifications as notifis", "users as users", "organizations as orgs")
      ->where("receiver = ? AND users.user_id = notifis.sender AND notifis.target_id = orgs.organization_id AND notifis.type in (0,1,2,3)")
      ->orderBy("created", "DESC")
      ->limit(true)
      ->ret(Query::FETCH_ALL)
      ->bindData(["1#int" => $receiverId, "2#int" => (int)$start - 1, "3#int" => 20])
      ->throwException("no_notification")
      ->execute();
  }

  public function isOrganizationNotificationExists ($type, $targetId, $sender, $receiver, $status = 0) {
    $result = $this->database->query()
      ->select("count(notification_id) as c")
      ->from("notifications")
      ->where("type = ? AND target_id = ? AND sender = ? AND receiver = ? AND status = ?")
      ->ret(Query::FETCH)
      ->bindData(["1#int" => $type, "2#int" => $targetId, "3#int" => $sender, "4#int" => $receiver, "5#int" => $status])
      ->execute();

    if ($result && (int)$result->c >= 1) {
      throw new CommonException("notification_existed");
    }
    return $this;
  }

  public function pullNotification ($notificationId) {
    return $this
      ->database
      ->query()
      ->select("*")
      ->from("notifications")
      ->where("notification_id = ?")
      ->rowsPromise()
      ->ret(Query::FETCH)
      ->bindData(["1#int" => $notificationId])
      ->throwException("notification_not_found")
      ->execute();
  }

  public function statusUpdate ($notificationId, $newStatus) {
    return $this->database->query()
    ->update("notifications")
    ->set("status = ?")
    ->where("notification_id = ?")
    ->ret(Query::TORF)
    ->bindData(["1#int" => $newStatus, "2#int" => $notificationId])
    ->throwException("update_status_failed")
    ->execute();
  }

  public function destroy ($notificationId) {
    return $this->database->query()
    ->delete("notifications")
    ->where("notification_id = ?")
    ->bindData(["1#int" => $notificationId])
    ->rowsPromise(1)
    ->ret(Query::TORF)
    ->throwException("notification_destroy_failed")
    ->execute();
  }

  public function destroyAboutOrganization ($organizationId) {
    return $this->database->query()
    ->delete("notifications")
    ->where("type in (0, 1, 2, 3) AND target_id = ?")
    ->bindData(["1#int" => $organizationId])
    ->ret(Query::TORF)
    ->throwException("notification_destroy_failed")
    ->execute();
  }

  public function setReaded ($receiverId) {
    $this->database->query()
    ->update("notifications")
    ->set("view_status = 1")
    ->where("receiver = ?")
    ->bindData(["1#int" => $receiverId])
    ->disableExecutionCheck()
    ->execute();
  }

  public function hasUnread ($receiverId) {
    $count = $this->database->query()
    ->select("count(notification_id) as sum")
    ->from("notifications")
    ->where("receiver = ? AND view_status = 0")
    ->bindData(["1#int" => $receiverId])
    ->rowsPromise(1)
    ->ret(Query::FETCH)
    ->execute();

    return property_exists($count, "sum") ? (bool)$count->sum : false;
  }
}