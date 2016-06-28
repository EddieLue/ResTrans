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
use ResTrans\Core\View;
use ResTrans\Core\Event;
use ResTrans\Core\Route;
use ResTrans\Core\SystemNotification;
use ResTrans\Core\Model;
use ResTrans\Core\Model\ModelConventions;
use ResTrans\Core\CommonException;
use ResTrans\Core\RouteResolveException;

class Org extends ControllerConventions {

  public $relationModel = "Organization";

  public function __construct (App $app) {
    parent::__construct($app);
  }

  /**
   * 组织列表主页 ( /organizations )
   * @param  Core\Event $event 依赖注入的 Event 对象
   * @param  Core\View  $view  依赖注入的 View 对象
   */
  public function getOrganization( Event $event, View $view, ModelConventions $model ) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $event->trigger("Base:hasUnreadNotifications", $currentUser["user_id"], $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $currentUser["user_id"], $view);
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $view->setValue("USER.is_login", true);
      $view->setArray("USER", $currentUser);
    } catch (CommonException $e) {
            /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $organizations = $model("Start")->getAllOrganizations(1);
    $organizations = $event->trigger("Proc:encodeOrganizationIds", $organizations);

    $view->setValue( "NAV.highlight", "org" )
      ->setValue("HOME.organizations", $organizations)
      ->title( [ "组织" ] )
      ->setAppToken()
      ->feData( [ "APP.token", "USER.is_login", "HOME.organizations" ] )
      ->feModules( "navbar,start" )
      ->init( "Org" );
  }

  public function jsonGETOrganization (Event $event, ModelConventions $model, Route $route) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);

    try {
      $currentUser = $event->trigger("Verify:isLogin");
    } catch (CommonException $e) {
      $event->trigger("Base:needLogin", $route);
    }

    $organizations = $model("Start")->getAllOrganizations(
      $route->queryString("start", Route::HTTP_GET, ["intval"])
    );
    $organizations = $event->trigger("Proc:encodeOrganizationIds", $organizations);


    $route->jsonReturn((array)$organizations);
  }

  /**
   * 单个组织主页( /organization/*id )
   * @param  int                    $orgId 从 URL 传入的组织 ID
   * @param  Model\ModelConventions $model 依赖注入的 Organization 模型
   * @param  Core\View              $view  依赖注入的 View 对象
   * @param  Core\Event             $event 依赖注入的 Event 对象
   */
  public function getSingleOrganization(
    $orgId,
    ModelConventions $model,
    View $view,
    Event $event,
    Route $route,
    App $app
  ) {
    /** 登录状态校验 */
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
      $event->trigger("Base:hasUnreadNotifications", $userId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $userId, $view);
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $view->setValue("USER.is_login", true)
           ->setArray("USER", (array)$currentUser);
    } catch (CommonException $e) {
      $userId = 0;
      $view->setValue("USER.is_login", true);
      /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    try {
      $organization = $model->getOrganization($orgId, true);
    } catch (CommonException $e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode());
    }
    $orgId = $organization->organization_id;

    $orgAdditional = [ 
      "organization_created" => $this->appi->fTime( $organization->created ),
      "page_type"            => 1
    ];
    $org = array_merge( (array)$organization, $orgAdditional );
    $org["organization_id"] = $event->trigger("Proc:encodeOrganizationIds", $org["organization_id"]);
    $org["user_id"] = $event->trigger("Proc:encodeUserIds", $org["user_id"]);

    try {
      $event->trigger("Verify:isAdmin", $userId);
      $view->setValue("USER.is_admin", true);
    } catch (CommonException $e) {
      $view->setValue("USER.is_admin", false);
    }

    try {
      // 是否是这个组织的成员
      $memberOfOrganization = $event->trigger(
        "Verify:accessOrganization",
        $model,
        $organization,
        $organization->organization_id,
        $userId
      );

      // 读取组织讨论
      $only = (int)$route->queryString("discussion_id", Route::HTTP_GET);
      $org["discussion_only"] = false;
      if ( $only > 0 ) {
        $org["discussion_only"] = true;
        $orgDiscussions = [$model->getDiscussion( $only, true )];
      } else {
        $orgDiscussions = $model->getDiscussions( $orgId, 1, 20 );
      }

      if ($orgDiscussions) {
        $event->trigger("Proc:discussions", $orgDiscussions, $organization, $userId);
        $orgDiscussions = $event->trigger("Proc:encodeUserIds", $orgDiscussions);
        $orgDiscussions = $event->trigger("Proc:encodeOrganizationIds", $orgDiscussions);
      }

      $view->setValue([
        "USER.is_member_of_org" => $memberOfOrganization,
        "NAV.highlight"         => "org",
        "ORG.discussions"       => $orgDiscussions,
        "ORG.real_accessibility"=> true
      ]);

    } catch (CommonException $e) {
      $view->setValue("ORG.real_accessibility", false);
    }

    try {
      $event->trigger("Verify:accessOrganizationSettings", $organization, $userId);
      $view->setValue("ORG.access_setting_pages", true);
    } catch (CommonException $e) {
      $view->setValue("ORG.access_setting_pages", false);
    }

    try {
      SystemNotification::init($app)->isOrganizationNotificationExists(
        SystemNotification::NT_JOIN_ORGANIZATION,
        $organization->organization_id,
        $userId,
        $organization->user_id,
        1
      );
      $view->setValue("ORG.join_request_sended", false);
    } catch (CommonException $e) {
      $view->setValue("ORG.join_request_sended", true);
    }

    $view->feModules( "org,navbar" )
      ->title( [ $organization->name, "组织" ] )
      ->setAppToken()
      ->setArray( "ORG", (array)$org )
      ->feData( [
        "USER.is_login", "APP.token", "USER.is_member_of_org", "ORG.discussions", "USER.user_id",
        "USER.name", "ORG.organization_id", "ORG.discussion_total", "ORG.name", "ORG.page_type",
        "USER.is_admin" ] )
      ->init( "OrgDiscussions" );
  }

  /**
   * 新建组织 ( /organization )
   * @param  Core\Route             $route 依赖注入的 Route 对象
   * @param  Model\ModelConventions $model 依赖注入的 Organization 模型
   * @param  Core\Event             $event 依赖注入的 Event 对象
   * @param  Core\App               $app   依赖注入的 App 对象
   */
  public function jsonPOSTOrganization(
    Route $route,
    ModelConventions $model,
    Event $event,
    App $app
  ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $event->trigger("Verify:createOrganization", $currentUser["user_id"]);

    $method = Core\Route::HTTP_POST;
    // 验证 token
    $event->trigger("Verify:checkToken", $method);

    // 验证输入
    $data = [
      "user_id"       => $currentUser["user_id"],
      "name"          => $route->queryString("name", $method, [ "trim" ] ),
      "description"   => $route->queryString("description", $method ),
      "limit"         => 100,
      "join_mode"     => 1,
      "accessibility" => 1,
      "dp"            => 1,
      "create_task"   => 0,
    ];

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use ($db) {
      $db->TSRollBack();
    });

    // 创建新的组织
    $model->set( "Organization", $data );
    // 把 invalid 的信息交给 catch 处理
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException( $invalid );

    $model->save();
    $organizationId = $model->lastInsertId;

    // 添加当前用户到组织管理员
    $data = [
      "organization_id" => $organizationId,
      "user_id"         => $data["user_id"]
    ];
    $model->set( "newOrganizationMember", $data );

    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException( $invalid );

    $model->save();
    // 更新组织人数
    $model->memberJoined($data["user_id"], $organizationId, true);
    $model->clearInvalid()->updateMemberTotal( $organizationId );
    $model->updateUser($organizationId, $data["user_id"], "translate", 1, true);
    $model->updateUser($organizationId, $data["user_id"], "proofread", 1, true);
    $model->updateUser($organizationId, $data["user_id"], "manage", 1, true);
    $model->updateUser($organizationId, $data["user_id"], "upload", 1, true);
    $db->TSCommit();

    $organizationId = $event->trigger("Proc:encodeOrganizationIds", $organizationId);
    $attrs = [ "url" => "{$app->config['site_url']}organization/{$organizationId}" ];
    $route->jsonReturn( $this->status( "create_organization_succeed", false, $attrs ) );
  }

  public function jsonPOSTDiscussion( 
    Route $route,
    Event $event,
    ModelConventions $model,
    $orgId
  ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model,
      $organization,
      $organization->organization_id,
      $userId
    );

    $method = Core\Route::HTTP_POST;
    $content = $route->queryString( "content", $method );

    try {

      $db = $model->db();
      $db->TSBegin();

      $data = [ "organization_id" => $orgId,
                "user_id"         => $userId,
                "content"         => $content,
                "total"           => 0,
                "created"         => time() ];
      $model->set( "newDiscussion", $data );

      $invalid = $model->firstInvalid();
      if ( $invalid ) throw new Core\CommonException( $invalid );

      $model->save();

      $model->clearInvalid()->updateDiscussionTotal( $orgId );

      $db->TSCommit();
      $route->jsonReturn( $this->status( "discussion_has_been_created", false ) );
    } catch ( Core\CommonException $e ) {
      $db->TSRollBack();
      $route->setResponseCode( $e->getCode() ? $e->getCode() : 400 )
            ->jsonReturn( $this->status( $e->getMessage() ) );
    }
  }

  public function jsonPOSTDiscussionComment(
    $orgId,
    $discussionId,
    Route $route,
    ModelConventions $model,
    Event $event
  ) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model,
      $organization,
      $organization->organization_id,
      $userId
    );

    $discussion = $model->getDiscussion($discussionId, true);
    if ($discussion->organization_id !== $orgId) {
      throw new CommonException("params_error", 400);
    }

    $method = Core\Route::HTTP_POST;
    $parentId = $route->queryString( "parent_id", $method, [ "intval" ] );
    $content = $route->queryString( "content", $method );

    try {

      $db = $model->db();
      $db->TSBegin();

      $data = [ "organization_id" => $orgId,
                "discussion_id" => $discussionId,
                "user_id" => $userId,
                "parent_id" => $parentId,
                "content" => $content,
                "created" => time() ];

      $model->set( "newDiscussionComment", $data );

      $invalid = $model->firstInvalid();
      if ( $invalid ) throw new Core\CommonException( $invalid );

      $model->save();

      // 更新回复计数
      $model->clearInvalid()->updateCommentTotal( $discussionId );

      $db->TSCommit();
      $route->jsonReturn( $this->status( "discussion_comment_has_been_created",
                          true,
                          [ "comment_id" => $model->lastInsertId ] ) );
    } catch ( Core\CommonException $e ) {
      $db->TSRollBack();
      $route->setResponseCode( $e->getCode() ? $e->getCode() : 400 )
            ->jsonReturn( $this->status( $e->getMessage() ) );
    }
  }

  public function jsonGETDiscussionComment(
    $orgId,
    $discussionId,
    Model\ModelConventions $model,
    Core\Route $route,
    Event $event
     ) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);

    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
    } catch (CommonException $e) {
      $event->trigger("Base:needLogin", $route);
    }

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model,
      $organization,
      $organization->organization_id,
      $userId
    );

    $discussion = $model->getDiscussion($discussionId, true);
    if ($discussion->organization_id !== $orgId) {
      throw new CommonException("params_error", 400);
    }

    $start = (int)$route->queryString( "start", Core\Route::HTTP_GET );
    $amount = $route->queryString( "amount", Core\Route::HTTP_GET );

    try {
      // 查找当前用户
      $commentCount = $discussion->comment_total;

      if ( $start > $commentCount ||
           $amount > $commentCount ||
           ( $start + $amount ) > $commentCount ||
           "end" === $amount ) {
        $amount = $commentCount;
      }

      $comments = (array)$model->getComments( $discussionId, $start, $amount );
      if ($comments) {
        $event->trigger("Proc:discussionComments", $comments, $discussion, $organization, $userId);
        $comments = $event->trigger("Proc:encodeOrganizationIds", $comments);
        $comments = $event->trigger("Proc:encodeUserIds", $comments);
      }
      $return = ["total" => $commentCount, "data" => $comments];
      $route->jsonReturn( $return );
    } catch ( Core\CommonException $e ) {

      $route->setResponseCode( $e->getCode() ? $e->getCode : 400 )
            ->jsonReturn( $this->status( $e->getMessage() ) );
    }
  }

  public function jsonGETDiscussion(
    $orgId,
    Model\ModelConventions $model,
    Core\Route $route,
    Event $event
  ) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);

    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
    } catch (CommonException $e) {
      $event->trigger("Base:needLogin", $route);
    }

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model,
      $organization,
      $organization->organization_id,
      $userId
    );

    $start = (int)$route->queryString( "start", Core\Route::HTTP_GET );
    $amount = $route->queryString( "amount", Core\Route::HTTP_GET );

    try {
      $discussionCount = $organization->discussion_total;

      if ( $start > $discussionCount ||
           $amount > $discussionCount ||
           ( $start + $amount ) > $discussionCount ||
           "end" === $amount ) {
        $amount = $discussionCount;
      }

      $discussions = (array)$model->getDiscussions( $orgId, $start, $amount );
      if ($discussions) {
        $discussions = $event->trigger("Proc:discussions", $discussions, $organization, $userId);
        $discussions = $event->trigger("Proc:encodeUserIds", $discussions);
        $discussions = $event->trigger("Proc:encodeOrganizationIds", $discussions);
      }
      $return = [ "total" => $organization->discussion_total, "data" => $discussions ];
      $route->jsonReturn( $return );
    } catch ( CommonException $e ) {

      $route->setResponseCode( $e->getCode() ? $e->getCode() : 400 )
            ->jsonReturn( $this->status( $e->getMessage() ) );
    }

  }

  public function jsonDELETEDiscussion ($orgId, $discussionId, ModelConventions $model,
    Route $route, Event $event) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model,
      $organization,
      $organization->organization_id,
      $userId
    );

    $discussion = $model->getDiscussion($discussionId, true);
    if ($discussion->organization_id !== $orgId) {
      throw new CommonException("params_error", 400);
    }

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $model->deleteAllComments($discussionId, true);
    $model->deleteDiscussion($discussionId, true);
    $model->updateDiscussionTotal($orgId);

    $db->TSCommit();
    $route->jsonReturn($this->status("discussion_deleted", false));
  }

  public function jsonDELETEDiscussionComment ($orgId, $discussionId, $commentId,
    ModelConventions $model, Route $route, Event $event) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model,
      $organization,
      $organization->organization_id,
      $userId
    );

    $discussion = $model->getDiscussion($discussionId, true);
    if ($discussion->organization_id !== $orgId) {var_dump(1);
      throw new CommonException("params_error", 400);
    }

    $comment = $model->getComment($commentId, true);
    if ($comment->discussion_id !== intval($discussionId)) {
      throw new CommonException("params_error", 400);
    }

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $model->organizationExists($orgId, true);
    $model->deleteComment($orgId, $discussionId, $commentId, true);
    $model->updateCommentTotal($discussionId);

    $db->TSCommit();
    $route->jsonReturn($this->status("comment_deleted", false));
  }

  public function getOrgTasks($orgId, View $view, Model\ModelConventions $model,
    Core\Event $event, Core\App $app, Route $route) {
    $userId = 0;
    $currentUser = [];
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
      $event->trigger("Base:hasUnreadNotifications", $userId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $userId, $view);
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $view->setValue("USER.is_login", true);
    } catch (CommonException $e) {
      /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    try {
      $organization = $model->getOrganization($orgId, true);
    } catch (CommonException $e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode());
    }
    $orgId = $organization->organization_id;
    try {
      // 是否是这个组织的成员
      $memberOfOrganization = $event->trigger(
        "Verify:accessOrganization",
        $model,
        $organization,
        $organization->organization_id,
        $userId
      );
    } catch (CommonException $e) {
      $orgId = $event->trigger("Proc:encodeOrganizationIds", $orgId);
      $route->redirect($app->config["site_url"] . "organization/" . $orgId);
      $event->trigger("Base:appEnd");
      exit();
    }

    try {
      $event->trigger("Verify:accessOrganizationSettings", $organization, $userId);
      $view->setValue("ORG.access_setting_pages", true);
    } catch (CommonException $e) {
      $view->setValue("ORG.access_setting_pages", false);
    }

    try {
      SystemNotification::init($app)->isOrganizationNotificationExists(
        SystemNotification::NT_JOIN_ORGANIZATION,
        $organization->organization_id,
        $userId,
        $organization->user_id,
        1
      );
      $view->setValue("ORG.join_request_sended", false);
    } catch (CommonException $e) {
      $view->setValue("ORG.join_request_sended", true);
    }

    $orgAdditional = [
      "creator"              => $organization->user_name,
      "organization_created" => $app->fTime($organization->created),
      "page_type"            => 2
    ];
    $tasks = $model("Task")->getTasks($organization->organization_id, 1, 20, false);
    if ($tasks) {
      $tasks = $event->trigger("Proc:tasksForRestful", $tasks);
      $tasks = $event->trigger("Proc:encodeTaskIds", $tasks);
      $tasks = $event->trigger("Proc:encodeOrganizationIds", $tasks);
      $tasks = $event->trigger("Proc:encodeUserIds", $tasks);
    } else {
      $tasks = [];
    }

    $org = array_merge((array)$organization, $orgAdditional, ["tasks" => $tasks]);
    $org["user_id"] = $event->trigger("Proc:encodeUserIds", $org["user_id"]);
    $org["organization_id"] = $event->trigger("Proc:encodeOrganizationIds", $org["organization_id"]);

    $view->feModules( "org,navbar" )
         ->title( [ $organization->name, "组织任务" ] )
         ->setAppToken()
         ->setValue( [ "USER.is_member_of_org" => $memberOfOrganization,
                       "NAV.highlight"         => "org" ] )
         ->setArray( "USER", (array)$currentUser )
         ->setArray( "ORG", (array)$org )
         ->feData( [ 
            "USER.is_login", "APP.token", "USER.is_member_of_org","USER.user_id", "USER.name",
            "ORG.organization_id", "ORG.task_total", "ORG.name", "ORG.tasks", "ORG.page_type"] )
         ->init( "OrgTasks" );
  }

  public function jsonGETOrgTasks ($orgId, Core\Route $route, Model\ModelConventions $model,
    Core\Event $event) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);
    $userId = 0;
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
    } catch (CommonException $e) {
      $event->trigger("Base:needLogin", $route);
    }

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model,
      $organization,
      $organization->organization_id,
      $userId
    );

    $start  = (int)$route->queryString("start", Core\Route::HTTP_GET);
    $amount = (int)$route->queryString("amount", Core\Route::HTTP_GET);
    $tasks  = $model("Task")->getTasks($organization->organization_id, $start, $amount, true);

    $tasks = $event->trigger("Proc:tasksForRestful", $tasks);
    $tasks = $event->trigger("Proc:encodeTaskIds", $tasks);
    $tasks = $event->trigger("Proc:encodeOrganizationIds", $tasks);
    $tasks = $event->trigger("Proc:encodeUserIds", $tasks);

    $route->jsonReturn($tasks);
  }

  public function getOrgUsers($orgId, View $view, ModelConventions $model, Event $event, App $app,
    Route $route) {
    $userId = 0;
    $currentUser = [];

    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
      $event->trigger("Base:hasUnreadNotifications", $userId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $userId, $view);
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
      $view->setValue("USER.is_login", true);
    } catch (CommonException $e) {
      /** 未登录触发事件 */
      $event->trigger("Verify:notLoggedIn", $view);
      /** 触发跳转控制事件 */
      $event->trigger("Base:loginRedirect", $view);
    }

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    try {
      $organization = $model->getOrganization($orgId, true);
    } catch (CommonException $e) {
      throw new RouteResolveException($e->getMessage(), $e->getCode());
    }
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    try {
      $memberOfOrganization = $event->trigger(
        "Verify:accessOrganization",
        $model,
        $organization,
        $organization->organization_id,
        $userId
      );
    } catch (CommonException $e) {
      $orgId = $event->trigger("Proc:encodeOrganizationIds", $orgId);
      $route->redirect($app->config["site_url"] . "organization/" . $orgId);
      $event->trigger("Base:appEnd");
      exit();
    }

    try {
      $event->trigger("Verify:accessOrganizationSettings", $organization, $userId);
      $view->setValue("ORG.access_setting_pages", true);
    } catch (CommonException $e) {
      $view->setValue("ORG.access_setting_pages", false);
    }

    try {
      SystemNotification::init($app)->isOrganizationNotificationExists(
        SystemNotification::NT_JOIN_ORGANIZATION,
        $organization->organization_id,
        $userId,
        $organization->user_id,
        1
      );
      $view->setValue("ORG.join_request_sended", false);
    } catch (CommonException $e) {
      $view->setValue("ORG.join_request_sended", true);
    }

    $orgAdditional = [
      "creator"              => $organization->user_name,
      "organization_created" => $app->fTime($organization->created),
      "page_type"            => 3
    ];

    $users = $model->getUsers($orgId, 1, 20, true);
    $getMyself = $model->getUser($orgId, $userId);
    if (!$getMyself) $getMyself = [];
    /**
     * 对用户进行加权
     * 普通成员 +1
     * 管理者 +2
     * 创建者 +4
     * 全局管理员 +8
     */
    // 确认自身权重
    $myself = new \stdClass();
    $myself->is_org_admin = ($organization->user_id === $userId);
    $myself->is_org_manager = isset($getMyself->manage) ? $getMyself->manage : false;
    $myself->is_global_admin = isset($currentUser["admin"]) ? $currentUser["admin"] : false;
    // 加权计数
    $myself->weights = (isset($memberOfOrganization) && $memberOfOrganization) ? 1 : 0;
    $myself->is_org_manager && ($myself->weights += 2);
    $myself->is_org_admin && ($myself->weights += 4);
    $myself->is_global_admin && ($myself->weights += 8);

    isset($myself->user_id) && (
      $myself->user_id = $event->trigger("Proc:encodeUserIds", $myself->user_id)
    );
    $view->setValue("ORG.myself", $myself);
    if ($users) {
      $users = $event->trigger("Proc:orgUsers", $users, $organization->user_id);
      $users = $event->trigger("Proc:encodeOrganizationIds", $users);
      $users = $event->trigger("Proc:encodeUserIds", $users);
    }

    $org = array_merge((array)$organization, $orgAdditional, ["users" => $users]);
    $org["organization_id"] = $event->trigger("Proc:encodeOrganizationIds", $org["organization_id"]);
    $org["user_id"] = $event->trigger("Proc:encodeUserIds", $org["user_id"]);

    $view->feModules( "org,navbar" )
         ->title( [ $organization->name, "组织成员" ] )
         ->setAppToken()
         ->setValue( [ "USER.is_login"         => (bool)$currentUser,
                       "USER.is_member_of_org" => $memberOfOrganization,
                       "NAV.highlight"         => "org" ] )
         ->setArray( "USER", (array)$currentUser )
         ->setArray( "ORG", (array)$org )
         ->feData( [ 
            "USER.is_login", "APP.token", "USER.is_member_of_org","USER.user_id", "USER.name",
            "ORG.organization_id", "ORG.member_total", "ORG.name", "ORG.users", "ORG.page_type"] )
         ->init("OrgMembers");
  }

  public function jsonGETOrgUsers ($orgId, Core\Route $route, Model\ModelConventions $model,
    Core\Event $event) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);
    $userId = 0;
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
    } catch (CommonException $e) {
      $event->trigger("Base:needLogin", $route);
    }

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $event->trigger(
      "Verify:accessOrganization",
      $model,
      $organization,
      $organization->organization_id,
      $userId
    );

    $start  = (int)$route->queryString("start", Core\Route::HTTP_GET);
    $amount = (int)$route->queryString("amount", Core\Route::HTTP_GET);

    // 确认自身权限
    $myself = new \stdClass();
    $myself->id = $userId;
    $myself->is_org_admin = ($organization->user_id === $userId);
    $myself->is_org_manager = isset($getMyself->manage) ? $getMyself->manage : false;
    $myself->is_global_admin = isset($currentUser["admin"]) ? $currentUser["admin"] : false;
    // 加权计数
    $myself->weights = (isset($memberOfOrganization) && $memberOfOrganization) ? 1 : 0;
    $myself->is_org_manager && ($myself->weights += 2);
    $myself->is_org_admin && ($myself->weights += 4);
    $myself->is_global_admin && ($myself->weights += 8);

    $users  = $model->getUsers($organization->organization_id, $start, $amount, true);
    if ($users) {
      $users = $event->trigger("Proc:orgUsersForRestful", $users, $organization, $myself);
      $users = $event->trigger("Proc:encodeOrganizationIds", $users);
      $users = $event->trigger("Proc:encodeUserIds", $users);
    }
    $route->jsonReturn($users);
  }

  public function jsonPOSTOrgUserJoin ($orgId, Model\ModelConventions $model, Core\Route $route,
    Core\Event $event, App $app) {
    // 关闭自动发送错误消息时带上的 token
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    $joinMode = $organization->join_mode;
    // 是否是这个组织的成员
    $memberOfOrganization = $model->isMemberOf($orgId, $userId);
    if ($memberOfOrganization) {
      throw new CommonException("already_a_member", 403);
    }

    if ($joinMode === 2) {
      throw new CommonException("unable_to_join", 403);
    }

    if ((int)$organization->member_total >= (int)$organization->maximum) {
      throw new CommonException("organization_full", 403);
    }

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    $data = [
      "organization_id" => $orgId,
      "user_id"         => $userId
    ];
    $model->set("newOrganizationMember", $data);
    $model->save();
    if ($joinMode === 0) {
      $model->memberJoined($userId, $orgId, true);

      $defaultPrivileges = (int)$organization->default_privileges;
      if ( $defaultPrivileges === 1 || $defaultPrivileges === 3 ) {
        $model->updateUser($orgId, $userId, "translate", 1, true);
      }
      if ( $defaultPrivileges === 2 || $defaultPrivileges === 3 ) {
        $model->updateUser($orgId, $userId, "proofread", 1, true);
      }

      $model->updateMemberTotal($orgId);

      $db->TSCommit();
      $route->jsonReturn($this->status("join_organization_succeed", true));
    } else if ($joinMode === 1) {
      // 更新组织人数
      SystemNotification::init($app)
      ->isOrganizationNotificationExists(
        SystemNotification::NT_JOIN_ORGANIZATION,
        $organization->organization_id,
        $userId,
        $organization->user_id
      )
      ->push(
        SystemNotification::NT_JOIN_ORGANIZATION,
        $orgId,
        $userId,
        $organization->user_id,
        SystemNotification::STATUS_JOIN_ORGANIZATION_WAIT
      );

      $db->TSCommit();
      $route->jsonReturn($this->status("join_organization_request_sended", true));
    }
  }

  public function jsonDELETEOrgUserExit ($orgId, ModelConventions $model, Route $route,
    Event $event, App $app) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);

    $currentUser = $event->trigger("Verify:isLogin");
    $userId = $currentUser["user_id"];

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    // 是否是这个组织的成员
    $memberOfOrganization = $model->isMemberOf($orgId, $userId, true);

    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use (&$db) {
      $db->TSRollBack();
    });

    if ($organization->user_id === $userId) {
      $tasks = $model("Task")->getTasks(
        $organization->organization_id,
        1,
        $organization->task_total,
        false
      );
      $tasks = $tasks ? $tasks : [];
      foreach ($tasks as $task) {
        $sets = $model("Task")->getSets($task->task_id);
        if (!$sets) $sets = [];
        foreach ($sets as $set) {
          $files = $model("Task")->getFiles($set->set_id);
          if (!$files) $files = [];
          foreach ($files as $file) {
            //删除这个文件的所有译文
            $model("Task")->deleteAllTranslationsOfFile($file->file_id, true);
          }
          // 这里删除文件集文件
          if ($files) $model("Task")->deleteSetFiles($set->set_id, true);
        }
        // 这里删除文件集
        if ($sets) $model("Task")->deleteSets($task->task_id, true);
      }
      // 删除所有任务
      $model->deleteTasks($organization->organization_id, true);
      $model->deleteUsers($organization->organization_id, true);
      $model->deleteDiscussions($organization->organization_id, true);
      $model->deleteDiscussionComments($organization->organization_id, true);
      $model->deleteWorkingSetRecords($organization->organization_id, true);
      SystemNotification::init($app)->destroyAboutOrganization($organization->organization_id);
      $model->deleteOrganization($organization->organization_id, true);
      $model("WorkTable")->deleteWorkingByOrganizationId($organization->organization_id);
    } else {
      $model->deleteUser($organization->organization_id, $userId, true);
      $model->updateMemberTotal($organization->organization_id);
      SystemNotification::init($app)
      ->push(
        SystemNotification::NT_EXIT_ORGANIZATION,
        $organization->organization_id,
        $userId,
        $organization->user_id
      );
    }
    $db->TSCommit();
    $route->jsonReturn($this->status("user_exited", false));
  }

  public function jsonPUTOrgSingleUser ($orgId, $userId, Core\Route $route,
    Model\ModelConventions $model, Event $event, App $app) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);

    $userId = (int)$event->trigger("Proc:decodeUserIds", $userId);;
    $currentUser = $event->trigger("Verify:isLogin");
    $currentUserId = $currentUser["user_id"];

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    $myself = $model->getUser($orgId, $userId, true);

    $changeProperty = $route->queryString("change_prop", Core\Route::HTTP_PUT);
    $props = ["translate", "proofread", "manage", "upload"];

    if ($currentUser["admin"] || $organization->user_id === $currentUserId || $myself->manage) {
      if (
        $userId === $currentUserId || // 改的是自己的
        $userId === $organization->user_id || // 改的是组织创建者的
        (!$currentUser["admin"] && $userId === $organization->user_id) // 非全局管理的管理者改组织创建者的
      ) throw new CommonException("permission_denied", 403);
    } else {
      throw new CommonException("permission_denied", 403);
    }

    if (!in_array($changeProperty, $props)) {
      throw new Core\CommonException("change_property_error");
    }

    $propertyValue = $route->queryString($changeProperty, Core\Route::HTTP_PUT);
    $userId = $route->queryString("user_id", Core\Route::HTTP_PUT);
    $model->updateUser(
      $orgId,
      $event->trigger("Proc:decodeUserIds", $userId),
      $changeProperty,
      $propertyValue,
      true
    );
    $return = $route->phpInput("json");

    unset($return["change_prop"]);
    $route->jsonReturn($return);
  }

  public function jsonDELETEOrgSingleUser ($orgId, $userId, Core\Route $route,
    Model\ModelConventions $model, Event $event) {
    // 关闭自动发送错误消息时带上的 token
    $this->addTokenOnError(false);

    $userId = (int)$event->trigger("Proc:decodeUserIds", $userId);;
    $currentUser = $event->trigger("Verify:isLogin");
    $currentUserId = $currentUser["user_id"];

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;

    // 权限确认
    if ($currentUser["admin"] || $organization->user_id === $currentUserId || $myself->manage) {
      if (
        $currentUserId === $userId ||// 不能踢掉自己
        $organization->user_id === $userId // 不能踢掉创建者
      ) throw new CommonException("permission_denied");
    } else {
      throw new CommonException("permission_denied");
    }

    $model->deleteUser($orgId, $userId, true);
    $model->updateMemberTotal($orgId);
    $route->jsonReturn($this->status("user_deleted", false));
  }

  public function getOrgSetting( $orgId, View $view, Model\ModelConventions $model,
    Core\App $app, Event $event, Route $route) {
    try {
      $currentUser = $event->trigger("Verify:isLogin");
      $userId = $currentUser["user_id"];
      $event->trigger("Base:hasUnreadNotifications", $userId, $view);
      $event->trigger("Base:hasUnreadMessages", $model("Message"), $userId, $view);
      $currentUser["user_id"] = $event->trigger("Proc:encodeUserIds", $currentUser["user_id"]);
    } catch (CommonException $e) {
      $event->trigger("Base:simplyLoginRedirect");
    }

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;
    try {
      $event->trigger("Verify:accessOrganizationSettings", $organization, $userId);
    } catch (CommonException $e) {
      $orgId = $event->trigger("Proc:encodeOrganizationIds", $orgId);
      $route->redirect($app->config["site_url"] . "organization/" . $orgId);
      $event->trigger("Base:appEnd");
      exit();
    }

    try {
      SystemNotification::init($app)->isOrganizationNotificationExists(
        SystemNotification::NT_JOIN_ORGANIZATION,
        $organization->organization_id,
        $userId,
        $organization->user_id,
        1
      );
      $view->setValue("ORG.join_request_sended", false);
    } catch (CommonException $e) {
      $view->setValue("ORG.join_request_sended", true);
    }

    $orgAdditional = [
      "creator"              => $organization->user_name,
      "organization_created" => $app->fTime($organization->created),
      "page_type"            => 4
    ];

    $org = array_merge((array)$organization, $orgAdditional);
    $org["organization_id"] = $event->trigger("Proc:encodeOrganizationIds", $org["organization_id"]);
    $org["user_id"] = $event->trigger("Proc:encodeUserIds", $org["user_id"]);

    $view->feModules( "org,navbar" )
         ->title( [ $organization->name, "组织成员" ] )
         ->setAppToken()
         ->setValue( [ "USER.is_login"         => (bool)$currentUser,
                       "USER.is_member_of_org" => true,
                       "NAV.highlight"         => "org" ] )
         ->setArray( "USER", (array)$currentUser )
         ->setArray( "ORG", (array)$org )
         ->feData( [ 
            "USER.is_login", "APP.token", "USER.is_member_of_org","USER.user_id", "USER.name",
            "ORG.organization_id", "ORG.member_total", "ORG.name", "ORG.description", "ORG.maximum",
            "ORG.join_mode", "ORG.accessibility", "ORG.default_privileges", "ORG.member_create_task",
            "ORG.page_type"] )
         ->init("OrgSettings");
  }

  public function jsonPOSTOrgSetting ($orgId, Core\Route $route, Model\ModelConventions $model,
    Core\Event $event, Core\App $app) {
    $currentUser = $event->trigger("Verify:isLogin");
    $userId = (int)$currentUser["user_id"];

    $orgId = $event->trigger("Proc:decodeOrganizationIds", $orgId);

    // 是否存在此组织
    $organization = $model->getOrganization($orgId, true);
    $orgId = $organization->organization_id;

    $event->trigger("Verify:accessOrganizationSettings", $organization, $userId);

    $method = Core\Route::HTTP_POST;
    // 验证 token
    $event->trigger( "Verify:checkToken", Core\Route::HTTP_POST );

    // 验证输入
    $data = [
      "organization_id" => $orgId,
      "name"            => $route->queryString("name", $method, ["trim"]),
      "description"     => $route->queryString("description", $method),
      "limit"           => $route->queryString("maximum", $method),
      "join_mode"       => (int)$route->queryString("join_mode", $method, ["intval"]),
      "accessibility"   => (int)$route->queryString("accessibility", $method, ["intval"]),
      "dp"              => (int)$route->queryString("default_privileges", $method, ["intval"]),
      "create_task"     => (int)$route->queryString("member_create_task", $method, ["intval"]),
    ];
    $db = $model->db();
    $db->TSBegin();

    $event->on("controllerError", function () use ($db) {
      $db->TSRollBack();
    });

    // 创建新的组织
    $model->set( "UpdateOrganization", $data );
    // 把 invalid 的信息交给 catch 处理
    $invalid = $model->firstInvalid();
    if ( $invalid ) throw new Core\CommonException( $invalid );

    $model->save();
    $db->TSCommit();
    $route->jsonReturn($this->status("settings_saved", true));
  }
}