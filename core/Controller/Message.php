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
use ResTrans\Core\User;
use ResTrans\Core\App;
use ResTrans\Core\Event;
use ResTrans\Core\Route;
use ResTrans\Core\Model;
use ResTrans\Core\Model\ModelConventions;
use ResTrans\Core\CommonException;

class Message extends ControllerConventions {

  public $relationModel = "Message";

  public function __construct( App $app ) {
    // 触发上层构造方法
    parent::__construct( $app );
  }

  public function jsonGETSearchReceiver(
    Route $route,
    Event $event,
    App $app,
    ModelConventions $model
  ) {
    $event->trigger("Verify:isLogin");
    $user = User::instance($app, $model->db());

    $keyword = $route->queryString("keyword", Route::HTTP_GET);

    try {
      $search = $this->model("Search");

      if ( preg_match( $user->escape, $keyword ) ||
           empty( trim( $keyword ) ) ) throw new CommonException("user_not_found", 404);

      $receiver = $search->searchUsers( $keyword, 1, 1 );
      if ( ! $receiver || empty($receiver) ) throw new CommonException("no_result", 404);

      $receiver[0]->user_id = $event->trigger("Proc:encodeUserIds", $receiver[0]->user_id);
      $receiver[0]->avatar_link = $app->getAvatarLink($receiver[0]->email);
      unset($receiver[0]->email);
      $route->jsonReturn($receiver[0]);
    } catch ( CommonException $e ) {
      $route->setResponseCode(404)->jsonReturn( $this->status( $e->getMessage() ) );
    }
  }

  public function jsonPOSTMessage(
    Route $route,
    ModelConventions $model,
    Event $event,
    App $app
  ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $receiver = $route->queryString("receiver", Core\Route::HTTP_POST);
    $receiver = $event->trigger("Proc:decodeUserIds", $receiver);
    $content = $route->queryString("content", Core\Route::HTTP_POST);

    try {
      
      $db = $model->db();
      $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

      if (!$currentUser["send_message"]) {
        throw new CommonException("cannot_send_message", 403);
      }

      $receiverInfo = User::instance($app, $model->db())
        ->findUserInfo($receiver, USER::BY_ID, ["receive_message"]);

      if (!$receiverInfo->receive_message) {
        throw new CommonException("cannot_send_message", 403);
      }

      $db->TSBegin();
      $common = $model( "Start" );
      $data = [ "sender" => $userId,
                "receiver" => $receiver,
                "content" => $content,
                "created" => time() ];

      $model->set("NewMessage", $data);
      $invalid = $model->firstInvalid();
      if ( $invalid ) throw new Core\CommonException($invalid);

      $model->save();
      $db->TSCommit();
      $route->jsonReturn(
        $this->status(
          "message_sended",
          true,
          [
            "message_id" => $model->lastInsertId,
            "avatar_link" => $app->getAvatarLink($currentUser["email"])
          ]
        )
      );
    } catch ( Core\CommonException $e ) {
      $db->TSRollBack();
      $route->setResponseCode( $e->getCode() ? $e->getCode() : 400 )
            ->jsonReturn( $this->status($e->getMessage(), true) );
    }
  }

  public function jsonGETConversation (
    Route $route,
    ModelConventions $model,
    Event $event,
    App $app
  ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $start = $route->queryString( "start", Core\Route::HTTP_GET );
    $amount = $route->queryString( "amount", Core\Route::HTTP_GET );

    try {

      $lastMessages = $model->lastMessages($userId, $start, $amount);
      if ( ! $lastMessages ) throw new Core\CommonException( "messages_not_found", 404 );

      array_walk($lastMessages, function (&$message) use (&$app) {
        $message->avatar_link = $app->getAvatarLink($message->otherside_email);
        unset($message->otherside_email);
      });

      $event->trigger("Proc:encodeUserIds", $lastMessages, "owner");
      $event->trigger("Proc:encodeUserIds", $lastMessages, "otherside");
      $route->jsonReturn($lastMessages);

    } catch ( Core\CommonException $e ) {
      $route->setResponseCode( $e->getCode() ? $e->getCode() : 400 )
            ->jsonReturn($this->status( $e->getMessage(), false ));
    }
  }

  public function jsonDELETEConversation ( $othersideId, Core\Route $route,
                                           Model\ModelConventions $model, Event $event ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $othersideId = $event->trigger("Proc:decodeUserIds", $othersideId);
    try {
      $model->deleteConversation($userId, $othersideId, true);
      $route->jsonReturn($this->status("conversation_has_been_deleted", false));
    } catch ( Core\CommonException $e ) {
      $route->setResponseCode( $e->getCode() ? $e->getCode() : 400 )
            ->jsonReturn($this->status( $e->getMessage(), false ));
    }

  }

  public function jsonGETMessage ( $owner, $otherside, Route $route,
                                   ModelConventions $model, Event $event, App $app ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];
    $owner = (int)$event->trigger("Proc:decodeUserIds", $owner);
    $otherside = (int)$event->trigger("Proc:decodeUserIds", $otherside);

    $start = $route->queryString( "start", Core\Route::HTTP_GET );
    $amount = $route->queryString( "amount", Core\Route::HTTP_GET );

    try {

      if ( $owner !== $userId ) throw new Core\CommonException("owner_error");

      $lastMessages = $model->getMessages( $owner, $otherside, $start, $amount );
      if ( ! $lastMessages ) throw new Core\CommonException( "messages_not_found", 404 );

      $event->trigger("Proc:encodeUserIds", $lastMessages, "owner");
      $event->trigger("Proc:encodeUserIds", $lastMessages, "otherside");

      array_walk($lastMessages, function (&$message) use (&$app) {
        $message->owner_avatar_link = $app->getAvatarLink($message->owner_email);
        $message->otherside_avatar_link = $app->getAvatarLink($message->otherside_email);
        unset($message->otherside_email, $message->owner_email);
      });

      $route->jsonReturn($lastMessages);

    } catch ( Core\CommonException $e ) {
      $route->setResponseCode( $e->getCode() ? $e->getCode() : 400 )
            ->jsonReturn($this->status( $e->getMessage(), false ));
    }
  }

  public function jsonDELETEMessage ( $ownerId, $othersideId, $messageId, Core\Route $route,
                                      Model\ModelConventions $model, Event $event ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $ownerId = (int)$event->trigger("Proc:decodeUserIds", $ownerId);

    try {
      if ( $ownerId !== $userId ) throw new Core\CommonException("owner_error");

      $model->deleteMessage( $messageId, $ownerId, true );

      $route->jsonReturn($this->status("delete_message_completed", false));
    } catch ( Core\CommonException $e ) {
      $route->setResponseCode( $e->getCode() ? $e->getCode() : 400 )
            ->jsonReturn($this->status( $e->getMessage(), false ));
    }
  }

  public function jsonPOSTMessageReaded( $ownerId, $othersideId, Model\ModelConventions $model,
                                         Core\Route $route, Core\Event $event ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $ownerId = (int)$event->trigger("Proc:decodeUserIds", $ownerId);
    $othersideId = (int)$event->trigger("Proc:decodeUserIds", $othersideId);

    try {
      if ( $ownerId !== $userId ) throw new Core\CommonException("owner_error");
      $model->setMessagesReaded($ownerId, $othersideId);
    } catch ( Core\CommonException $e ) {}
  }
}