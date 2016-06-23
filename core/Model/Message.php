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
use ResTrans\Core\User;
use ResTrans\Core\Query;

class Message extends ModelConventions {

  public function setNewMessage( array $data ){

    $user = User::instance($this->appi, $this->db());
    if ( ! $user->isRegistered( $data["receiver"], Core\User::BY_ID ) ||
           $user->isBlocked( $data["receiver"] ) ) {
      $this->putInvalid("receiver_not_found");
    }

    $this->isEmpty($data["content"], null, "message_content_is_empty");
    ( mb_strlen( $data["content"] ) > 1000 ) && $this->putInvalid("content_too_long");

    return $data;
  }

  public function saveNewMessage( $db, array $data ) {

    $fields = [ "owner", "otherside", "type", "content", "unread", "created" ];
    $prepareSQL = $db->insert("messages", 6, $db->fieldList($fields) );

    $params = [ "1#int" => $data["sender"],
                "2#int" => $data["receiver"],
                "3#int" => 1,
                "4#str" => $data["content"],
                "5#int" => 0,
                "6#int" => $data["created"] ];

    try {
      $newMessage = $db->pdoPrepare($prepareSQL, $params);
      $params2 = [ "1#int" => $data["receiver"],
                   "2#int" => $data["sender"],
                   "3#int" => 0,
                   "5#int" => 1 ];
      $params = array_merge( $params, $params2 );
      $newMessage2 = $db->pdoPrepare($prepareSQL, $params);
      $execute1 = $newMessage->execute();
      $this->setLastInsertId();
      $execute2 = $newMessage2->execute();
      return $execute1 && $execute2;
    } catch ( \PDOException $e ) {
      throw new Core\CommonException("send_message_error");
    }
  }

  public function lastMessages ( $userId, $start = 1, $amount = 15 ) {

    sleep(1);
    $db = $this->db();
    $prepareSQL = "SELECT `messages`.`message_id`, `messages`.`owner`, `messages`.`otherside`, `messages`.`type`, `messages`.`content`, `messages`.`unread`, `messages`.`created`, `users1`.`name` as `owner_name`, `users2`.`name` as `otherside_name`, `users2`.`email` as `otherside_email` 
    FROM `{$db->prefix}messages` as `messages`, `{$db->prefix}users` as `users1`, `{$db->prefix}users` as `users2` 
    WHERE `messages`.`message_id` in 
      (SELECT max(`messages`.`message_id`) FROM `{$db->prefix}messages` as `messages` WHERE `messages`.`owner` = :user GROUP BY `otherside`) 
    AND `users1`.`user_id` = `messages`.`owner` 
    AND `users2`.`user_id` = `messages`.`otherside` 
    ORDER BY `messages`.`message_id` DESC 
    LIMIT :limit_start, :limit_amount";

    try {
      $params       = [ 
        "user#int"         => $userId,
        "limit_start#int"  => $start - 1,
        "limit_amount#int" => $amount
       ];
      $lastMessages = $db->pdoPrepare($prepareSQL, $params);
      $lastMessages->execute();
      $fetch        = $lastMessages->fetchAll();
      if ( ! $fetch ) throw new \Exception();

      $app          = $this->appi;
      array_walk( $fetch, function ( $message, $key, $app ) {

        if ( ! isset( $message->created ) ) return;
        $message->friendly_time = $app->fTime($message->created);
        unset($message->created);
      }, $app );

      return $fetch;
    } catch ( \Exception $e ) {
      return false;
    }
  }

  public function getMessages ( $owner, $otherside, $start = 1, $amount = 15 ) {

    $db = $this->db();
    $prepareSQL = "SELECT `message_id`, `owner`, `otherside`, `type`, `content`, `unread`, `created`, `users1`.`name` as `owner_name`, `users1`.`email` as `owner_email`, `users2`.`name` as `otherside_name`, `users2`.`email` as `otherside_email` 
    FROM `{$db->prefix}messages`, `{$db->prefix}users` as `users1`, `{$db->prefix}users` as `users2` 
    WHERE `owner` = :owner 
      AND `otherside` = :otherside 
      AND `users1`.`user_id` = `owner` 
      AND `users2`.`user_id` = `otherside` 
    ORDER BY `message_id` DESC 
    LIMIT :limit_start, :limit_amount";

    try {
      $params      = [
        "owner#int"        => $owner,
        "otherside#int"    => $otherside,
        "limit_start#int"  => $start - 1,
        "limit_amount#int" => $amount
      ];

      $getMessages = $db->pdoPrepare($prepareSQL, $params);
      $getMessages->execute();
      $fetch       = $getMessages->fetchAll();
      if ( ! $fetch ) throw new \Exception();

      $app         = $this->appi;
      array_walk( $fetch, function ( $message, $key, $app ) {

        if ( ! isset( $message->created ) ) return;
        $message->friendly_time = $app->fTime($message->created);
        unset($message->created);
      }, $app );

      return $fetch;
    } catch ( \Exception $e ) {
      return false;
    }
  }

  public function deleteMessage ( $messageId, $ownerId, $throwException ) {
    $db = $this->db();
    $where = $db->condition("message_id") . " AND " . $db->condition("owner");
    $prepareSQL = $db->delete("messages", $where);
    try {
      $deleteMessage = $db->pdoPrepare($prepareSQL, [ "1#int" => $messageId, "2#int" => $ownerId ]);
      return $deleteMessage->execute() && $deleteMessage->rowCount();
    } catch ( \PDOException $e ) {
      if ( $throwException ) throw new Core\CommonException("delete_message_failed");
      return false;
    }
  }

  public function setMessagesReaded( $ownerId, $othersideId ) {
    $db = $this->db();
    $where = $db->condition("owner") . " AND " . $db->condition("otherside");
    $prepareSQL = $db->update("messages", $db->condition("unread"), $where);

    try {
      $params = [ "1#int" => 0, "2#int" => $ownerId, "3#int" => $othersideId ];
      $setMessagesReaded = $db->pdoPrepare($prepareSQL, $params);
      return $setMessagesReaded->execute();
    } catch ( \PDOException $e ) {
      return false;
    }
  }

  public function getMessage( $messageId, $throwException ) {
    $db = $this->db();
    $prepareSQL = $db->select("messages", "*", $db->condition("message_id"));
    try {
      $getMessage = $db->pdoPrepare($prepareSQL, ["1#int" => $messageId]);
      $getMessage->execute();
      $fetch = $getMessage->fetch();
      if ( ! $fetch ) throw new \Exception();
      if ( $fetch->created ) $fetch->friendly_time = $this->appi->fTime($fetch->created);
      return $fetch;
    } catch ( \Exception $e ) {
      if ( $throwException ) throw new Core\CommonException("message_not_found");
      return false;
    }
  }

  public function deleteConversation ($ownerId, $othersideId, $throwException ) {
    $db = $this->db();
    $where = $db->condition("owner") . " AND " . $db->condition("otherside");
    $prepareSQL = $db->delete("messages", $where);
    try {
      $deleteConversation = $db->pdoPrepare($prepareSQL, ["1#int" => $ownerId, "2#int" => $othersideId]);
      return $deleteConversation->execute() && $deleteConversation->rowCount();
    } catch ( \Exception $e ) {
      if ( $throwException ) throw new Core\CommonException("delete_conversation_failed");
      return false;
    }
  }

  public function hasUnread ($receiverId) {
    $count = $this->db()->query()
      ->select("count(message_id) as sum")
      ->from("messages")
      ->where("owner = ? AND type = 0 AND unread = 1")
      ->bindData(["1#int" => $receiverId])
      ->rowsPromise(1)
      ->ret(Query::FETCH)
      ->execute();

    return property_exists($count, "sum") ? (bool)$count->sum : false;
  }
}