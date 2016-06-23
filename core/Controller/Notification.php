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
use ResTrans\Core\Event;
use ResTrans\Core\Route;
use ResTrans\Core\SystemNotification;
use ResTrans\Core\Model\ModelConventions;

class Notification extends ControllerConventions {

  public $relationModel = "Organization";

  public function jsonGETOrganizationNotification (
    Route $route,
    App $app,
    Event $event
  ) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);

    $currentUser = $event->trigger("Verify:isLogin");

    $start = $route->queryString("start", Route::HTTP_GET, ["intval"]);
    SystemNotification::init($app)->setReaded($currentUser["user_id"]);

    $notifications = SystemNotification::init($app)
      ->pullOrganizationNotifications($currentUser["user_id"], $start);

    $event->trigger("Proc:encodeUserIds", $notifications, "sender");
    $event->trigger("Proc:encodeUserIds", $notifications, "receiver");
    $event->trigger("Proc:encodeOrganizationIds", $notifications, "target_id");

    $route->jsonReturn($notifications);
  }

  public function jsonPUTOrganizationNotification (ModelConventions $model, Route $route, App $app,
    Event $event) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);

    $currentUser = $event->trigger("Verify:isLogin");

    $notificationId = $route->queryString("notification_id", Route::HTTP_PUT, ["intval"]);
    $newStatus = $route->queryString("new_status", Route::HTTP_PUT, ["intval"]);

    $notification = SystemNotification::init($app)->pullNotification($notificationId);
    if ($notification->receiver !== $currentUser["user_id"]) {
      throw new Core\CommonException("notification_not_found");
    }

    if (
      !in_array($notification->type, [0, 1, 2, 3], true) ||
      !in_array($notification->status, [0, 1, 2, 3], true)
      ) {
      throw new Core\CommonException("notification_not_found");
    }
    $organization = $model->getOrganization($notification->target_id, true);

    $db = $model->db();
    $db->TSBegin();
    $event->on("controllerError", function () use ($db) {
      $db->TSRollBack();
    });
    if ($notification->status === $newStatus) {
      // 这里什么都不做
    } else if ($notification->status === 1 && $newStatus === 2) {
      // 更新加入时间戳
      // 更新用户权限（默认）
      // 发送成功加入通知
      $model->memberJoined($notification->sender, $organization->organization_id, true);

      $defaultPrivileges = (int)$organization->default_privileges;
      if ( $defaultPrivileges === 1 || $defaultPrivileges === 3 ) {
        $model->
        updateUser($organization->organization_id, $notification->sender, "translate", 1, true);
      }
      if ( $defaultPrivileges === 2 || $defaultPrivileges === 3 ) {
        $model->
        updateUser($organization->organization_id, $notification->sender, "proofread", 1, true);
      }

      $model->updateMemberTotal($organization->organization_id);
      SystemNotification::init($app)->push(
        SystemNotification::NT_JOIN_ORGANIZATION_PASSED,
        $organization->organization_id,
        $currentUser["user_id"],
        $notification->sender,
        SystemNotification::STATUS_NONE
      );
    } else if ($notification->status === 1 && $newStatus === 3) {
      // 从 uo 表删除
      // 发送拒绝通知
      $model->deleteUser($organization->organization_id, $notification->sender, true);
      SystemNotification::init($app)->push(
        SystemNotification::NT_JOIN_ORGANIZATION_REJECTED,
        $organization->organization_id,
        $currentUser["user_id"],
        $notification->sender,
        SystemNotification::STATUS_NONE
      );
    } else if ($notification->status === 1 && $newStatus === 4) {
      // 从 uo 表删除
      $model->deleteUser($organization->organization_id, $notification->sender, true);
    }
    // 更新通知状态
    SystemNotification::init($app)->statusUpdate($notification->notification_id, $newStatus);
    $db->TSCommit();
    $route->jsonReturn($this->status("notification_updated"));
    // 返回
  }

  public function jsonDELETEOrganizationNotification ($notificationId, Route $route, App $app, Event $event) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");

    $notification = SystemNotification::init($app)->pullNotification($notificationId);
    if ($notification->receiver !== $currentUser["user_id"] || $notification->status === 1) {
      throw new Core\CommonException("permission_denied");
    }

    SystemNotification::init($app)->destroy($notificationId);
    $route->jsonReturn($this->status("notification_deleted"));
  }

}