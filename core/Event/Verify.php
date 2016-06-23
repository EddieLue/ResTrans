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

namespace ResTrans\Core\Event;

use ResTrans\Core;
use ResTrans\Core\User;
use ResTrans\Core\Database;
use ResTrans\Core\Model;
use ResTrans\Core\CommonException;

class Verify extends EventConventions {

  public function registrar() {
    $this->listen("checkToken");
    $this->listen("checkCaptcha");
    $this->listen("clearCaptchaSession");
    $this->listen("worktableRecord");
    $this->listen("isLogin");
    $this->listen("notLoggedIn");
    $this->listen("createOrganization");
    $this->listen("isAdmin");
    $this->listen("accessOrganization");
    $this->listen("createTask");
    $this->listen("accessOrganizationSettings");
    $this->listen("manageSetsAndFiles");
    $this->listen("accessTaskSetting");
    $this->listen("isTaskFrozen");
  }

  public function checkToken( $requestMethod ) {
    $token = (string)$this->appi->route->queryString("token", $requestMethod, ["trim"]);
    if (!preg_match_all("/^[a-zA-Z0-9]{40}$/", $token) ||
    !Core\Token::instance($this->appi)->verifyToken($token)) {
      throw new Core\CommonException("token_auth_failed");
    }
  }

  public function isLogin () {
    $user = User::instance($this->appi, Database::instance($this->appi));
    return (array)$user->findUserInfo($user->isLogin(), USER::BY_ID);
  }

  public function checkCaptcha( $requestMethod ) {
    $captcha = (int)$this->appi->route->queryString( "captcha", $requestMethod, [ "trim" ] );

    $captchaResult = (int)$_SESSION["captcha_result"];
    $this->clearCaptchaSession();

    return round( $captcha ) == round( $captchaResult );
  }

  public function clearCaptchaSession() {
    unset( $_SESSION["captcha_result"] );
  }

  public function worktableRecord(Model\WorkTable $model, $hash, $userId) {
    if (!preg_match("/^[a-zA-Z0-9]{40}$/", $hash)) return false;
    return $model->getWorkingSetRecord($hash, $userId);
  }

  public function notLoggedIn ($view) {
    $view->setValue("USER.is_login", false);
  }

  public function createOrganization ($currentUserId) {
    $createOrganization = (bool)$this->appi->getOption("member_create_organization");
    if (!$createOrganization) {
      try {
        $this->trigger("Verify:isAdmin", $currentUserId);
      } catch (CommonException $e) {
        throw new CommonException("permission_denied");
      }
    }
  }

  private function returnIsAdmin ($userId) {
    try {
      $isAdmin = $this->trigger("Verify:isAdmin", $userId);
    } catch (CommonException $e) {
      $isAdmin = false;
    }

    return (bool)$isAdmin;
  }

  public function isAdmin ($userId) {
    $userInfo = User::instance($this->appi, Database::instance($this->appi))->findUserInfo(
      $userId,
      USER::BY_ID,
      ["admin"]
    );

    if (!$userInfo->admin) {
      throw new Core\CommonException("non_admin");
    }

    return $userInfo;
  }

  public function accessOrganization ($model, $organization, $organizationId, $currentUserId) {
    $memberOfOrganization = $model->isMemberOf( $organizationId, $currentUserId );
    $isAdmin = $this->returnIsAdmin($currentUserId);

    if (
      !$memberOfOrganization &&
      !$organization->accessibility &&
      !$isAdmin
    ) {
      throw new CommonException("organization_not_found");
    }

    return $memberOfOrganization;
  }

  public function createTask ($organization, $currentUserId) {
    $isAdmin = $this->returnIsAdmin($currentUserId);

    if (
      !$organization->member_create_task &&
      $organization->user_id !== $currentUserId &&
      !$isAdmin
    ) {
      throw new CommonException("permission_denied");
    }
  }

  public function accessOrganizationSettings ($organization, $currentUserId) {
    $isAdmin = $this->returnIsAdmin($currentUserId);

    if ($organization->user_id !== $currentUserId && !$isAdmin) {
      throw new CommonException("permission_denied");
    }
  }

  public function manageSetsAndFiles ($model, $organization, $task, $currentUserId, $priviliege = "upload") {
    $user = (object)$model->getUser($organization->organization_id, $currentUserId);
    $priviliege = property_exists($user, "priviliege") ? $user->$priviliege : 0;

    $isAdmin = $this->returnIsAdmin($currentUserId);

    if (
      !$isAdmin && $organization->user_id !== $currentUserId &&
      $task->user_id !== $currentUserId && !$priviliege
    ) {
      throw new CommonException("permission_denied");
    }
  }

  public function accessTaskSetting ($task, $organization, $currentUserId) {
    $isAdmin = $this->returnIsAdmin($currentUserId);
    if (!$isAdmin && $organization->user_id !== $currentUserId && $task->user_id !== $currentUserId) {
      throw new CommonException("permission_denied");
    }
  }

  public function isTaskFrozen ($task) {
    if ($task->frozen) throw new CommonException("task_frozen", 403);
  }
}