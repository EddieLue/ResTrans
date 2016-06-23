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

class Organization extends ModelConventions {

  public function setUpdateOrganization (array $data) {
    return $this->setOrganization($data);
  }

  public function saveUpdateOrganization ($db, array $data) {
    $set = $db->condition("name"). ", ".$db->condition("description").", ".$db->condition("maximum") . ", " . $db->condition("join_mode").", ".$db->condition("accessibility").", ".$db->condition("default_privileges").", ".$db->condition("member_create_task");
    $prepareSQL = $db->update("organizations", $set, $db->condition("organization_id"));

    $params = [ 
      "1#str" => $data["name"], "2#str" => $data["description"], "3#int" => $data["limit"],
      "4#int" => $data["join_mode"], "5#int" => $data["accessibility"], "6#int" => $data["dp"],
      "7#int" => $data["create_task"], "8#int" => $data["organization_id"]
    ];

    try {
      $updateOrganization = $db->pdoPrepare($prepareSQL, $params);
      $exec = $updateOrganization->execute();
      if(!$exec) throw new \Exception();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException( "update_organization_failed" );
    }
  }

  public function setOrganization( array $data ) {

    $data["name"]          = $this->isEmpty( $data["name"], null, "organization_name_is_too_long_or_empty" );
    mb_strlen($data["name"]) > 20 && $this->putInvalid("organization_name_is_too_long_or_empty");
    ( mb_strlen($data["description"]) > 500 ) && $this->putInvalid("description_too_long");
    // 新成员加入方式
    // 0: 无验证 1: 管理员确定 2: 不允许加入
    $data["join_mode"]     = $this->isValid( $data["join_mode"], [ 0, 1, 2 ], 1);
    $data["limit"]         = $this->isValid( $data["limit"], [ 100, 200, 500 ], 100);
    $data["accessibility"] = $this->isValid( $data["accessibility"], [0, 1], 0);
    // 新成员加入的默认权限
    // 1:普通翻译人员 2: 带有翻译权限的校对人员
    $data["dp"]            = $this->isValid( $data["dp"], [ 0, 1, 2, 3 ], 1);
    $data["create_task"]   = $this->isValid( $data["create_task"], [0, 1], 0);
    return $data;
  }

  public function saveOrganization( Core\Database $db, array $data ) {

    $fields = [
      "user_id", "name", "description", "maximum", "created", "join_mode", "accessibility",
      "default_privileges", "member_create_task"
    ];

    $prepareSQL = $db->insert( "organizations", 9, $db->fieldList( $fields ) );
    $params = [ 
      "1#int" => $data["user_id"], "2#str" => $data["name"], "3#str" => $data["description"],
      "4#int" => $data["limit"], "5#int" => time(), "6#int" => $data["join_mode"],
      "7#int" => $data["accessibility"], "8#int" => $data["dp"], "9#int" => $data["create_task"] 
    ];
    try {
      $newOrganization = $db->pdoPrepare( $prepareSQL, $params );
      if (!$newOrganization->execute() || !$newOrganization->rowCount()) {
        throw new \Exception();
      }
      $this->setLastInsertId();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException( "create_organization_failed" );
    }
  }

  public function setNewOrganizationMember( array $data ) {
    return $data;
  }

  public function saveNewOrganizationMember( Core\Database $db, array $data ) {
    $prepareSQL = $db->insert("user_organization", 2, $db->fieldList(["organization_id", "user_id"]));
    $params = array_combine(["1#int", "2#int"], array_values($data));

    try {
      $newOrganizationMember = $db->pdoPrepare( $prepareSQL, $params );
      $newOrganizationMember->execute();
      $this->setLastInsertId();
      return true;
    } catch ( \PDOException $e ) {
      throw new Core\CommonException( "save_new_organization_member_failed" );
    }
  }

  public function memberJoined($userId, $organizationId, $throw = false) {
    return $this->db()->query()
      ->update("user_organization")
      ->set("joined = ?")
      ->where("user_id = ? AND organization_id = ?")
      ->bindData(["1#int" => time(), "2#int" => $userId, "3#int" => $organizationId])
      ->rowsPromise(1)
      ->ret(Query::TORF)
      ->throwException($throw ? "update_member_joined_time_failed" : "")
      ->execute();
  }

  public function updateMemberTotal( $id ) {

    $organization = $this->getOrganization( $id, true );

    // 尝试对比两表中的人数
    // 如果一致即直接返回
    $db = $this->db();

    $countTotal = $db->query()->select("count(joined)")
    ->from("user_organization")
    ->where("organization_id = ? AND joined <> 0")
    ->bindData([ "1#int" => $id ])
    ->ret(Query::FETCH_COLUMN)
    ->throwException("update_member_total_failed")
    ->execute();

    $memberTotal      = (int)$organization->member_total; // 组织表中
    $countMemberTotal = (int)$countTotal; // 组织与用户表中
    if ( $countMemberTotal === $memberTotal ) return true;

    // 若不一致，执行更新
    return $db->query()
    ->update("organizations")
    ->set("member_total = ?")
    ->where("organization_id = ?")
    ->bindData([ "1#int" => $countMemberTotal, "2#int" => $id ])
    ->ret(Query::TORF)
    ->rowsPromise(1)
    ->throwException("update_member_total_failed")
    ->execute();
  }

  public function getOrganization( $organizationId, $throwException = false ){
    $db = $this->db();
    $prepareSQL = "SELECT `organizations`.*, `users`.`name` as `user_name`, `users`.`user_id`, `users`.`email`
      FROM `{$db->prefix}organizations` as `organizations`, 
           `{$db->prefix}users` as `users` 
      WHERE `organizations`.`organization_id` = ? AND 
            `users`.`user_id` = `organizations`.`user_id`";
    try {
      $organization = $db->pdoPrepare($prepareSQL, ["1#int" => $organizationId]);
      $result = $organization->execute();
      $fetch = $organization->fetch();
      if (!$result || !$fetch) throw new \Exception();
      return $fetch;
    } catch ( \Exception $e ) {
      if ( $throwException ) throw new Core\CommonException("organization_not_found", 404);
      return false;
    }
  }

  public function isMemberOf( $orgId, $userId, $throw = false ) {
    $result = $this->db()->query()->select("count(*) as count")->from("user_organization")
      ->where("organization_id = ? AND user_id = ? AND joined > 0")
      ->bindData([ "1#int" => $orgId, "2#int" => $userId ])
      ->rowsPromise(1)
      ->ret(Query::FETCH)
      ->throwException($throw ? "user_isnt_a_member_of_the_organization" : null)
      ->execute();
    if (is_object($result) && property_exists($result, "count")) {
     return (bool)$result->count;
   }
  }

  public function setNewDiscussion( array $data ) {
    if ( ! mb_strlen( $data["content"] ) || mb_strlen( $data["content"] ) > 500 ) {
      $this->putInvalid( "content_is_too_long" );
    }

    return $data;
  }

  public function saveNewDiscussion( Core\Database $db, array $data ) {

    $fields = [ "organization_id",
                "user_id",
                "content",
                "comment_total",
                "created" ];
    $prepareSQL = $db->insert( "discussions", 5, $db->fieldList( $fields ) );
    $params = array_combine( [ "1#int", "2#int", "3#str", "4#int", "5#int" ], $data );

    try {

      $newDiscussion = $db->pdoPrepare( $prepareSQL, $params );
      return $newDiscussion->execute() && $newDiscussion->rowCount();
    } catch ( \PDOException $e ) {

      throw new Core\CommonException( "created_discussion_failed" );
    }
  }

  public function updateDiscussionTotal( $organizationId ) {
    try {

      $organization = $this->getOrganization( $organizationId, true );
      // 尝试对比两表中的人数
      // 如果一致即直接返回
      $db = $this->db();
      $condi = $db->condition( "organization_id" );
      $prepareSQL = $db->select( "discussions", "count(*)", $condi );
      $countTotal = $db->pdoPrepare( $prepareSQL, [ "1#int" => $organizationId ] );
      $countTotal->execute();

      $discussionTotal      = (int)$organization->discussion_total; // 组织表中
      $countDiscussionTotal = (int)$countTotal->fetchColumn(); // 讨论表中
      if ( $discussionTotal === $countDiscussionTotal ) return true;

      // 若不一致，执行更新
      $prepareSQL = $db->update( "organizations",
                                 $db->condition( "discussion_total" ),
                                 $db->condition( "organization_id" ) );
      $params = [ "1#int" => $countDiscussionTotal, "2#int" => $organizationId ];
      $updateDiscussionTotal = $db->pdoPrepare( $prepareSQL, $params );
      $updateDiscussionTotal->execute();
      return true;
    } catch ( \Exception $e ) {

      throw new Core\CommonException( "update_discussion_total_failed" );
    }

  }

  public function getDiscussions( $orgId, $start, $amount ) {

    $db = $this->db();
    $start = (int)$start - 1;
    $amount = (int)$amount;

    $prepareSQL = "SELECT `user`.`user_id`,`user`.`name` as `user_name`,`user`.`email` as `user_email`,`discuss`.`discussion_id`,`discuss`.`organization_id`,`discuss`.`user_id`,`discuss`.`content`, `discuss`.`comment_total`, `discuss`.`created` FROM {$db->prefix}discussions as discuss, {$db->prefix}users as user WHERE `discuss`.`organization_id` = ? AND `user`.`user_id` = `discuss`.`user_id` ORDER BY `discuss`.`discussion_id` DESC LIMIT ?,?";

    try {

      $getDiscussions = $db->pdoPrepare( $prepareSQL, [ "1#int" => $orgId, "2#int" => $start,
        "3#int" => $amount ] );
      $getDiscussions->execute();
      $fetch = $getDiscussions->fetchAll();
      $app = $this->appi;
      return $fetch;
    } catch ( \PDOException $e ) {
      return false;
    }
  }

  public function getDiscussion( $discussionId, $throwException = false ) {

    $db = $this->db();
    $prepareSQL = "SELECT `user`.`user_id`,`user`.`name` as `user_name`,`user`.`email` as `user_email`,`discuss`.`discussion_id`,`discuss`.`organization_id`,`discuss`.`user_id`,`discuss`.`content`, `discuss`.`comment_total`, `discuss`.`created` FROM {$db->prefix}discussions as discuss, {$db->prefix}users as user WHERE `discuss`.`discussion_id` = ?";
    try {

      $getDiscussion = $db->pdoPrepare( $prepareSQL, [ "1#int" => $discussionId ] );
      $getDiscussion->execute();
      $fetch = $getDiscussion->fetch();
      if ( ! $fetch ) throw new \Exception();
      $fetch->can_delete = false;
      return $fetch;
    } catch ( \Exception $e ) {
      if ( $throwException ) throw new Core\CommonException( "discussion_not_found" );
      return false;
    }
  }

  public function getComment( $commentId, $throwException = false ) {

    $db = $this->db();
    $fieldList = $db->fieldList( [ "comment_id",
                                   "organization_id",
                                   "discussion_id",
                                   "user_id",
                                   "parent_id",
                                   "content",
                                   "created" ] );
    $prepareSQL = $db->select( "discussion_comments", $fieldList, $db->condition( "comment_id" ) );
    try {

      $getComment = $db->pdoPrepare( $prepareSQL, [ "1#int" => $commentId ] );
      $getComment->execute();
      return $getComment->fetch();
    } catch ( \PDOException $e ) {

      if ( $throwException ) throw new Core\CommonException( "comment_not_found" );
      return false;
    }
  }

  public function getComments( $discussionId, $start, $amount ) {

    $db = $this->db();
    $start = (int)$start - 1;
    $amount = (int)$amount;

    $prepareSQL = "SELECT `user`.`user_id`,`user`.`name` as `user_name`, `user`.`email` as `user_email`, `comment`.`comment_id`,`comment`.`organization_id`,`comment`.`discussion_id`,`comment`.`user_id`,`comment`.`parent_id`,`comment`.`content`,`comment`.`created` FROM {$db->prefix}discussion_comments as comment, {$db->prefix}users as user WHERE `comment`.`discussion_id` = ? AND `user`.`user_id` = `comment`.`user_id` ORDER BY `comment`.`comment_id` LIMIT ?,?";

    try {

      $getComments = $db->pdoPrepare( $prepareSQL, [ "1#int" => $discussionId,
                                                     "2#int" => $start,
                                                     "3#int" => $amount ] );
      $getComments->execute();
      $fetch = $getComments->fetchAll();
      return $fetch;
    } catch ( \PDOException $e ) {
      return false;
    }
  }

  public function setNewDiscussionComment( array $data ) {
    // 查找父回复
    if ( is_numeric( $data["parent_id"] ) && $data["parent_id"] > 0 ) {

      $comment = $this->getComment( $data["parent_id"] );
      if ( ! $comment ) $this->putInvalid( "parent_comment_not_found" );
    } else {

      $data["parent_id"] = 0;
    }

    if ( ! mb_strlen( $data["content"] ) || mb_strlen( $data["content"] ) > 500 ) {
      $this->putInvalid( "content_is_too_long" );
    }

    return $data;
  }

  public function saveNewDiscussionComment( Core\Database $db, array $data ) {
    $fields = [ "organization_id",
                "discussion_id",
                "user_id",
                "parent_id",
                "content",
                "created" ];
    $prepareSQL = $db->insert( "discussion_comments", 6, $db->fieldList( $fields ) );
    $params = array_combine( [ "1#int", "2#int", "3#int", "4#int", "5#str", "6#int" ], $data );
    try {
      $newDiscussionComment = $db->pdoPrepare( $prepareSQL, $params );
      return $newDiscussionComment->execute() &&
             $newDiscussionComment->rowCount() &&
             $this->setLastInsertId();
    } catch ( \PDOException $e ) {
      throw new Core\CommonException( "created_discussion_comment_failed" );
    }
  }

  public function updateCommentTotal( $discussionId ) {
    try {
      $discussion = $this->getDiscussion( $discussionId, true );
      // 尝试对比两表中的人数
      // 如果一致即直接返回
      $db = $this->db();
      $condi = $db->condition( "discussion_id" );
      $prepareSQL = $db->select( "discussion_comments", "count(*)", $condi );
      $countTotal = $db->pdoPrepare( $prepareSQL, [ "1#int" => $discussionId ] );
      $countTotal->execute();

      $commentTotal      = (int)$discussion->comment_total; // 讨论表中
      $countCommentTotal = (int)$countTotal->fetchColumn(); // 讨论与回复表中
      if ( $commentTotal === $countCommentTotal ) return true;

      // 若不一致，执行更新
      $prepareSQL = $db->update( "discussions",
                                 $db->condition( "comment_total" ),
                                 $db->condition( "discussion_id" ) );
      $params = [ "1#int" => $countCommentTotal, "2#int" => $discussionId ];
      $updateCommentTotal = $db->pdoPrepare( $prepareSQL, $params );
      $updateCommentTotal->execute();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException( "update_comment_total_failed" );
    }
  }

  public function deleteAllComments ($discussionId, $throw = false) {
    return $this->db()->query()
    ->delete("discussion_comments")
    ->where("discussion_id = ?")
    ->ret(Query::TORF)
    ->throwException($throw ? "delete_all_comments_failed" : null)
    ->bindData(["1#int" => $discussionId])
    ->execute();
  }

  public function deleteComment ($orgId, $discussionId, $commentId, $throw = false) {
    return $this->db()->query()
    ->delete("discussion_comments")
    ->where("organization_id = ? AND discussion_id = ? AND comment_id = ?")
    ->ret(Query::TORF)
    ->throwException($throw ? "delete_comment_failed" : null)
    ->bindData(["1#int" => $orgId, "2#int" => $discussionId, "3#int" => $commentId])
    ->execute();
  }

  public function getUsers ($organizationId, $start = 1, $amount = 20, $throw = false) {
    return $this
      ->db()
      ->query()
      ->select("uo.*", "users.name as user_name", "users.email as email", "users.admin as admin")
      ->from("user_organization as uo", "users as users")
      ->where("uo.organization_id = ? AND uo.joined <> 0 AND uo.user_id = users.user_id")
      ->limit(true)
      ->bindData(["1#int" => $organizationId, "2#int" => --$start, "3#int" => $amount])
      ->rowsPromise()
      ->ret(Query::FETCH_ALL)
      ->throwException($throw ? "users_not_found": null)
      ->execute();
  }

  public function getUser ($organizationId, $userId, $throw = false) {
    return $this
      ->db()
      ->query()
      ->select("*")
      ->from("user_organization")
      ->where("organization_id = ? AND user_id = ?")
      ->bindData(["1#int" => $organizationId, "2#int" => $userId])
      ->rowsPromise(1)
      ->ret(Query::FETCH)
      ->throwException($throw ? "user_not_found": null)
      ->execute();
  }

  public function updateUser ($orgId, $userId, $property, $value, $throw = false) {
    return $this->
    db()->
    query()->
    update("user_organization")->
    set("$property = ?")->
    where("user_id = ? AND organization_id = ?")->
    bindData(["1#int" => $value, "2#int" =>$userId, "3#int" => $orgId])->
    // rowsPromise(1)->
    ret(Query::TORF)->
    throwException("update_user_privilege_failed")->
    execute();
  }

  public function deleteUser ($orgId, $userId, $throw = false) {
    $db = $this->db();
    $prepareSQL = $db->delete("user_organization", $db->condition("organization_id") . " AND " . $db->condition("user_id"));
    try {
      $deleteUser = $db->pdoPrepare($prepareSQL, ["1#int" => $orgId, "2#int" => $userId]);
      if (!$deleteUser->execute() || !$deleteUser->rowCount()) throw new \Exception();
      return true;
    } catch ( \Exception $e ) {
      if ($throw) throw new Core\CommonException("delete_user_failed");
      return false;
    }
  }

  public function deleteUsers ($organizationId, $throw = false) {
    return $this->db()->query()
    ->delete("user_organization")
    ->where("organization_id = ?")
    ->bindData(["1#int" => $organizationId])
    ->ret(Query::TORF)
    ->throwException($throw ? "delete_users_failed" : null)
    ->execute();
  }

  public function deleteDiscussions ($organizationId, $throw = false) {
    return $this->db()->query()
    ->delete("discussions")
    ->where("organization_id = ?")
    ->bindData(["1#int" => $organizationId])
    ->ret(Query::TORF)
    ->throwException($throw ? "delete_discussions_failed" : null)
    ->execute();
  }

  public function deleteDiscussionComments ($organizationId, $throw = false) {
    return $this->db()->query()
    ->delete("discussion_comments")
    ->where("organization_id = ?")
    ->bindData(["1#int" => $organizationId])
    ->ret(Query::TORF)
    ->throwException($throw ? "delete_discussion_comments_failed" : null)
    ->execute();
  }

  public function deleteDiscussion ($discussionId, $throw = false) {
    return $this->db()->query()
    ->delete("discussions")
    ->where("discussion_id = ?")
    ->bindData(["1#int" => $discussionId])
    ->rowsPromise(1)
    ->ret(Query::TORF)
    ->throwException($throw ? "delete_discussion_failed" : null)
    ->execute();
  }

  public function deleteWorkingSetRecords ($organizationId, $throw = false) {
    return $this->db()->query()
    ->delete("working_sets")
    ->where("organization_id = ?")
    ->bindData(["1#int" => $organizationId])
    ->ret(Query::TORF)
    ->throwException($throw ? "delete_working_set_records_failed" : null)
    ->execute();
  }

  public function organizationExists ($orgId, $throw = false) {
    return $throw ? $this->exists("organizations", "organization_id", $orgId, true, "organization_not_found"):
                    $this->exists("organizations", "organization_id", $orgId);
  }

  public function updateTaskTotal( $organizationId ) {

    try {
      $organization = $this->getOrganization($organizationId);
      $db = $this->db();
      $prepareSQL = $db->select("tasks", "count(*)", $db->condition("organization_id"));
      $countTotal = $db->pdoPrepare($prepareSQL, [ "1#int" => $organizationId ]);
      $countTotal->execute();

      $taskTotal      = (int)$organization->task_total; // 组织表中
      $countTaskTotal = (int)$countTotal->fetchColumn(); // 任务表中
      if ( $countTaskTotal === $taskTotal ) return true;

      // 若不一致，执行更新
      $prepareSQL = $db->update("organizations", $db->condition("task_total"), $db->condition("organization_id"));
      $params = ["1#int" => $countTaskTotal, "2#int" => $organizationId];
      $updateTaskTotal = $db->pdoPrepare($prepareSQL, $params);
      $updateTaskTotal->execute();
      if ( !$updateTaskTotal->rowCount() ) throw new \Exception();
      return true;
    } catch ( \Exception $e ) {
      throw new Core\CommonException("update_task_total_failed");
    }
  }

  public function deleteTasks ($organizationId, $throw = false) {
    return $this->db()->query()
    ->delete("tasks")
    ->where("organization_id = ?")
    ->bindData(["1#int" => $organizationId])
    ->ret(Query::TORF)
    ->throwException($throw ? "delete_tasks_failed" : null)
    ->execute();
  }

  public function deleteOrganization ($organizationId, $throw = false) {
    return $this->db()->query()
    ->delete("organizations")
    ->where("organization_id = ?")
    ->bindData(["1#int" => $organizationId])
    ->rowsPromise(1)
    ->ret(Query::TORF)
    ->throwException($throw ? "delete_organization_failed" : null)
    ->execute();
  }
}