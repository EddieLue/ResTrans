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
use ResTrans\Core\Lang;
use ResTrans\Core\CommonException;
use Hashids\Hashids;

class Proc extends EventConventions {

  public function registrar() {
    $this->listen("sortReIndexTranslations");
    $this->listen("allSetsForWorkTable");
    $this->listen("filesForWorkTable");
    $this->listen("filesForRestful");
    $this->listen("tasksForRestful");
    $this->listen("orgUsers");
    $this->listen("orgUsersForRestful");
    $this->listen("profileOrganizations");
    $this->listen("personalSessions");
    $this->listen("globalUsers");
    $this->listen("discussions");
    $this->listen("discussionComments");
    $this->listen("encodeOrganizationIds");
    $this->listen("decodeOrganizationIds");
    $this->listen("encodeUserIds");
    $this->listen("decodeUserIds");
    $this->listen("encodeTaskIds");
    $this->listen("decodeTaskIds");
    $this->listen("encodeSetIds");
    $this->listen("decodeSetIds");
    $this->listen("encodeFileIds");
    $this->listen("decodeFileIds");
  }

  public function sortReIndexTranslations ($translations) {
    $result = [];
    foreach ($translations as $translation) {
      if (!property_exists($translation, "line")) continue;
      $result[$translation->line] = $translation;
    }
    return $result;
  }

  public function allSetsForWorkTable ($allSets, $lastSetId) {
    $lastSet = false;
    array_walk($allSets, function (&$set, $pos, $lastSetId) use (&$lastSet) {
      $set->set_id === $lastSetId && ($lastSet = $set);
    }, $lastSetId);
    return [$allSets, $lastSet];
  }

  public function filesForWorkTable ($files, $sLastFile, $lastFileId) {
    $app = $this->appi;
    $lastFile = null;
    array_walk($files, function (&$file, $pos, $lastFileId) use (&$lastFile, &$sLastFile, $app) {
      $file->last_update_by = $file->last_contributor_name;
      $file->last_update = $app->fTime($file->last_contributed);
      $file->ext = $file->extension;
      $file->percentage = number_format($file->percentage, 1);
      $file->original_language_name = Lang::get($file->original_language);
      $file->target_language_name = Lang::get($file->target_language);

      if ($file->file_id === $lastFileId) {
        $sLastFile = clone $file;
        $lastFile = $file;
      }

      unset($file->set_id, $file->path, $file->size, $file->uploader, $file->created,
        $file->last_contributed, $file->last_contributor_name, $file->extension);
    }, $lastFileId);

    return [$files, $lastFile, $sLastFile];
  }

  public function filesForRestful ($files) {
    $app = $this->appi;

    array_walk($files, function (&$file) use ($app) {
      $file->last_update_by = $file->last_contributor_name;
      $file->last_update = $app->fTime($file->last_contributed);
      $file->percentage = number_format($file->percentage, 1);
      $file->ext = $file->extension;
      $file->original_language_name = Lang::get($file->original_language);
      $file->target_language_name = Lang::get($file->target_language);
      $file->size_kb = number_format($file->size / 1024, 1);

      unset($file->path, $file->size, $file->uploader, $file->created,
        $file->last_contributed, $file->last_contributor_name, $file->extension);
    });

    return $files;
  }

  public function tasksForRestful ($tasks) {
    $app = $this->appi;
    array_walk($tasks, function (&$task) use (&$app) {
      $task->friendly_time = $app->fTime($task->created);
      $task->original_language_name = Lang::get($task->original_language);
      $task->target_language_name = Lang::get($task->target_language);
      $task->percentage = number_format($task->percentage, 2);
      unset($task->created, $task->original_language, $task->target_language, $task->api_key, $task->api_request);
    });
    return $tasks;
  }

  public function orgUsers ($users, $organizationCreator) {
    array_walk($users, function (&$user) use (&$organizationCreator) {
      $user->weights = 1;
      $user->is_org_admin = $user->is_global_admin = $user->is_org_manager = false;
      if ($user->user_id === $organizationCreator) {
        $user->is_org_admin = true;
        $user->weights += 4;
      }
      if ($user->manage) {
        $user->is_org_manager = true;
        $user->weights += 2;
      }
      if ($user->admin) {
        $user->is_global_admin = true;
        $user->weights += 8;
      }
    });
    return $users;
  }

  public function orgUsersForRestful ($users, $organization, $myself) {
    $users = $this->trigger("Proc:orgUsers", $users, $organization->user_id);
    $app = $this->appi;

    array_walk($users, function (&$user) use (&$organization, &$myself, &$app) {
      $user->show_base = $user->show_manage = $user->show_delete = false;
      $user->avatar_link = $app->getAvatarLink($user->email);

      if (
        ($myself->is_global_admin || $myself->is_org_admin || $myself->is_org_manager) && // 有任何一项管理权限
        !$user->is_org_admin && // 非组织创建者（组织创建者无人可改）
        $myself->id !== $user->user_id // 非自身权限
      ) {
        $user->show_base = true;
      }

      if ($myself->is_org_admin && $myself->id !== $user->user_id) $user->show_manage = true;
      if ($myself->weights > $user->weights && !$user->is_org_admin) $user->show_delete = true;

      // 清理数据
      if (!$user->show_base) {
        unset($user->translate, $user->proofread, $user->upload);
      }

      if (!$user->show_manage) {
        unset($user->manage);
      }

      unset($user->is_global_admin, $user->is_org_admin, $user->is_org_manager, $user->weights, $user->email);
    });

    return $users;
  }

  public function profileOrganizations($organizations) {
    array_walk($organizations, function (&$organization) {
      unset(
        $organization->user_id,
        $organization->created,
        $organization->maximum,
        $organization->join_mode,
        $organization->accessibility,
        $organization->default_privileges,
        $organization->member_create_task,
        $organization->discussion_total,
        $organization->joined,
        $organization->translate,
        $organization->proofread,
        $organization->manage,
        $organization->upload
      );
    });

    return $organizations;
  }

  public function personalSessions ($sessions, $currentSessionId) {
    $app = $this->appi;
    array_walk($sessions, function (&$session) use (&$currentSessionId, $app) {
      if ($session->session_id === $currentSessionId) {
        $session->current = true;
      } else {
        $session->current = false;
      }
      $session->session_id = substr($session->session_id, 0, 16);
      $session->friendly_time = $app->fTime($session->expire - 7 * 24 * 3600, true);
    });
    return $sessions;
  }

  public function globalUsers (array $users) {
    $app = $this->appi;
    array_walk($users, function (&$user) use ($app) {
      $user->friendly_time = $app->fTime($user->signup);
      unset($user->signup);
    });
    return $users;
  }

  public function discussions (array $discussions, $organization, $currentUserId) {
    $app = $this->appi;

    try {
      $this->trigger("Verify:isAdmin", $currentUserId);
      $isAdmin = true;
    } catch (CommonException $e) {
      $isAdmin = false;
    }

    array_walk(
      $discussions,
      function (&$discussion) use (&$organization, &$currentUserId, &$isAdmin, &$app) {
      $discussion->friendly_time = $app->fTime($discussion->created);
      $discussion->avatar_link = $app->getAvatarLink($discussion->user_email);
      unset($discussion->user_email);
      $discussion->can_delete = false;

      if (
        $isAdmin ||
        $organization->user_id === $currentUserId ||
        $discussion->user_id === $currentUserId
        ) {
        $discussion->can_delete = true;
      }
    });

    return $discussions;
  }

  public function discussionComments (array $comments, $discussion, $organization, $currentUserId) {
    $app = $this->appi;

    try {
      $this->trigger("Verify:isAdmin", $currentUserId);
      $isAdmin = true;
    } catch (CommonException $e) {
      $isAdmin = false;
    }

    array_walk(
      $comments,
      function (&$comment) use (&$organization, &$discussion, &$currentUserId, &$isAdmin, &$app) {
      $comment->friendly_time = $app->fTime($comment->created);
      $comment->can_delete = false;
      $comment->avatar_link = $app->getAvatarLink($comment->user_email);
      unset($comment->user_email);

      if (
        $isAdmin ||
        $organization->user_id === $currentUserId ||
        $discussion->user_id === $comment->user_id ||
        $comment->user_id === $currentUserId
        ) {
        $comment->can_delete = true;
      }
    });

    return $comments;
  }

  const USER = 7;
  const ORG = 8;
  const TASK = 11;
  const SET = 12;
  const FILE = 13;

  private function hashids($type) {
    return new Hashids(
      $this->appi->config["url_encrypt_key"] . $type,
      $type,
      "ABCDEFGHJKLMNPRTUVWXYZabcdefhiklmnorstuvwxz1234567890"
    );
  }

  private function hashidsEecode (Hashids $hashids, $idOrArray, $idAttr) {
    if (is_array($idOrArray)) {
      array_walk($idOrArray, function (&$obj) use (&$hashids, &$idAttr) {
        is_object($obj) ? ($obj->$idAttr = $hashids->encode($obj->$idAttr)) :
                          ($obj[$idAttr] = $hashids->encode($obj[$idAttr]));
      });
      return $idOrArray;
    }

    return $hashids->encode($idOrArray);
  }

  private function hashidsDecode (Hashids $hashids, $idOrArray, $idAttr) {
    if (is_array($idOrArray)) {
      array_walk($idOrArray, function (&$obj) use (&$hashids, &$idAttr) {
        $sourceId = $hashids->decode(is_object($obj) ? $obj->$idAttr : $obj[$idAttr]);
        if (is_array($sourceId) && isset($sourceId[0])) {
          is_object($obj) ? ($obj->$idAttr = $sourceId[0]) : ($obj[$idAttr] = $sourceId[0]);
        } else {
          $obj->$idAttr = 0;
        }
      });
      return $idOrArray;
    }

    $sourceId = $hashids->decode($idOrArray);
    return (is_array($sourceId) && isset($sourceId[0])) ? $sourceId[0] : 0;
  }

  public function encodeOrganizationIds ($idOrArray, $idAttr = "organization_id") {
    $hashids = $this->hashids(self::ORG);
    return $this->hashidsEecode($hashids, $idOrArray, $idAttr);
  }

  public function decodeOrganizationIds ($idOrArray, $idAttr = "organization_id") {
    $hashids = $this->hashids(self::ORG);
    return $this->hashidsDecode($hashids, $idOrArray, $idAttr);
  }

  public function encodeUserIds ($idOrArray, $idAttr = "user_id") {
    $hashids = $this->hashids(self::USER);
    return $this->hashidsEecode($hashids, $idOrArray, $idAttr);
  }

  public function decodeUserIds ($idOrArray, $idAttr = "user_id") {
    $hashids = $this->hashids(self::USER);
    return $this->hashidsDecode($hashids, $idOrArray, $idAttr);
  }

  public function encodeTaskIds ($idOrArray, $idAttr = "task_id") {
    $hashids = $this->hashids(self::TASK);
    return $this->hashidsEecode($hashids, $idOrArray, $idAttr);
  }

  public function decodeTaskIds ($idOrArray, $idAttr = "task_id") {
    $hashids = $this->hashids(self::TASK);
    return $this->hashidsDecode($hashids, $idOrArray, $idAttr);
  }

  public function encodeSetIds ($idOrArray, $idAttr = "set_id") {
    $hashids = $this->hashids(self::SET);
    return $this->hashidsEecode($hashids, $idOrArray, $idAttr);
  }

  public function decodeSetIds ($idOrArray, $idAttr = "set_id") {
    $hashids = $this->hashids(self::SET);
    return $this->hashidsDecode($hashids, $idOrArray, $idAttr);
  }

  public function encodeFileIds ($idOrArray, $idAttr = "file_id") {
    $hashids = $this->hashids(self::FILE);
    return $this->hashidsEecode($hashids, $idOrArray, $idAttr);
  }

  public function decodeFileIds ($idOrArray, $idAttr = "file_id") {
    $hashids = $this->hashids(self::FILE);
    return $this->hashidsDecode($hashids, $idOrArray, $idAttr);
  }
}