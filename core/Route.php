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

class Route {

  protected $routeRules = [
    [ "paths" => [ "/" ], "to" => "Home::Login" ], // 登录页面
    [ "paths" => [ "cron" ], "to" => "Home::Cron" ], // 登录页面
    [ "paths" => [ "login" ], "to" => "Home::Login" ],
    [ "paths" => [ "register" ], "to" => "Home::Register" ],
    [ "paths" => [ "user", "verify", "$$" ], "to" => "Home::Verify" ],
    [ "paths" => [ "user", "email", "resend" ], "to" => "Home::ResendEmail" ],
    [ "paths" => [ "retrieve" ], "to" => "Home::Retrieve" ],
    [ "paths" => [ "user", "password", "reset" ], "to" => "Home::SetNewPassword" ],
    [ "paths" => [ "user", "password", "reset", "$$" ], "to" => "Home::SetNewPassword" ],
    [ "paths" => [ "captcha" ], "to" => "Home::captcha" ],
    [ "paths" => [ "user", "login" ], "to" => "Home::login" ],
    [ "paths" => [ "user", "register" ], "to" => "Home::register" ],
    [ "paths" => [ "user", "retrieve" ], "to" => "Home::retrieve" ],
    [ "paths" => [ "user", "logout" ], "to" => "Home::Logout" ],
    [ "paths" => [ "start" ], "to" => "Start::Index" ], // 开始（首页）
    [ "paths" => [ "start", "task" ], "to" => "Start::Task" ],
    /** 开始页面 */
    [ "paths" => [ "setting", "global" ], "to" => "Setting::Global" ],
    [ "paths" => [ "setting", "personal" ], "to" => "Setting::Personal" ],
    [ "paths" => [ "setting", "personal", "profile" ], "to" => "Setting::PersonalProfile" ],
    [ "paths" => [ "setting", "personal", "common" ], "to" => "Setting::PersonalCommon" ],
    [ "paths" => [ "setting", "personal", "security" ], "to" => "Setting::PersonalSecurity" ],
    [ "paths" => [ "setting", "personal", "session", "$$", "$$" ], "to" => "Setting::PersonalSession" ],
    [ "paths" => [ "setting", "global", "user" ], "to" => "Setting::GlobalUsers" ],
    [ "paths" => [ "setting", "global", "organization" ], "to" => "Setting::GlobalOrganizations" ],
    [ "paths" => [ "setting", "global", "task" ], "to" => "Setting::GlobalTasks" ],
    [ "paths" => [ "setting", "global", "common" ], "to" => "Setting::GlobalCommon" ],
    [ "paths" => [ "setting", "global", "register" ], "to" => "Setting::GlobalRegister" ],
    // 设置页面
    [ "paths" => [ "organization" ], "to" => "Org::Organization" ],
    [ "paths" => [ "organization", "$$" ], "to" => "Org::SingleOrganization" ],
    [ "paths" => [ "organization", "$$", "user" ], "to" => "Org::OrgUsers" ],
    [ "paths" => [ "organization", "$$", "user", "join" ], "to" => "Org::OrgUserJoin" ],
    [ "paths" => [ "organization", "$$", "user", "exit" ], "to" => "Org::OrgUserExit" ],
    [ "paths" => [ "organization", "$$", "user", "$$" ], "to" => "Org::OrgSingleUser" ],
    [ "paths" => [ "organization", "$$","task" ], "to" => "Org::OrgTasks" ],
    [ "paths" => [ "organization", "$$","setting" ], "to" => "Org::OrgSetting" ],
    [ "paths" => [ "organization", "$$", "discussion", "$$" ], "to" => "Org::Discussion" ],
    [ "paths" => [ "organization", "$$", "discussion" ], "to" => "Org::Discussion" ],
    [
      "paths" => [ "organization", "$$", "discussion", "$$", "comment" ],
      "to" => "Org::DiscussionComment"
    ],
    [
      "paths" => [ "organization", "$$", "discussion", "$$", "comment", "$$" ],
      "to" => "Org::DiscussionComment"
    ],
    /** 组织 */
    [ "paths" => [ "task" ], "to" => "Task::Task" ],
    [ "paths" => [ "task", "$$" ], "to" => "Task::SingleTask" ],
    [ "paths" => [ "task", "$$", "set" ], "to" => "Task::Set" ],
    [ "paths" => [ "task", "$$", "set", "$$", "file" ], "to" => "Task::File" ],
    [ "paths" => [ "task", "$$", "set", "$$", "file", "$$" ], "to" => "Task::File" ],
    [ "paths" => [ "task", "$$", "set", "$$", "file", "$$", "preview" ], "to" => "Task::Preview" ],
    [ "paths" => [ "task", "$$", "set", "$$", "file", "$$", "line" ], "to" => "Task::Line" ],
    [ "paths" => [ "task", "$$", "set", "$$" ], "to" => "Task::SingleSet" ],
    [ "paths" => [ "task", "$$", "setting" ], "to" => "Task::TaskSetting" ],
    [ "paths" => [ "task", "$$", "setting", "api", "key" ], "to" => "Task::ApiKey" ],
    /** 任务 */
    [ "paths" => [ "worktable" ], "to" => "WorkTable::Id" ],
    [ "paths" => [ "worktable", "$$" ], "to" => "WorkTable::Main" ],
    [ "paths" => [ "worktable", "$$", "set", "$$", "file", "$$", "translation" ], 
        "to" => "Task::Translation" ],
    [ "paths" => [ "worktable", "$$", "set", "$$", "file" ], "to" => "Task::WFile" ],
    [ "paths" => [ "worktable", "$$", "set", "$$", "file", "$$" ], "to" => "Task::WFile" ],
    [ "paths" => 
      [ "worktable", "$$", "set", "$$", "file", "$$", "translation", "$$" ],
      "to" => "Task::SingleTranslation"
    ],
    [ "paths" => [ "worktable", "$$", "download", "link" ], "to" => "WorkTable::DownloadLink" ],
    [ "paths" => [ "worktable", "$$", "download", "$$" ], "to" => "WorkTable::Download" ],
    /** 工作台 */
    [ "paths" => [ "profile", "$$" ], "to" => "UserProfile::Profile" ],
    [ "paths" => [ "profile", "$$", "organization" ], "to" => "UserProfile::Organization" ],
    [ "paths" => [ "profile", "$$", "setting" ], "to" => "UserProfile::Setting" ],
    [ "paths" => [ "profile", "$$", "emailVerification" ], "to" => "UserProfile::EmailVerification" ],
    /** 个人资料 */
    [ "paths" => [ "conversation" ], "to" => "Message::Conversation" ],
    [ "paths" => [ "conversation", "otherside", "$$" ], "to" => "Message::Conversation" ],
    [ "paths" => [ "message" ], "to" => "Message::Message" ],
    [ "paths" => [ "message", "$$", "$$" ], "to" => "Message::Message" ],
    [ "paths" => [ "message", "$$", "$$", "readed" ], "to" => "Message::MessageReaded" ],
    [ "paths" => [ "message", "$$", "$$", "$$" ], "to" => "Message::Message" ],
    [ "paths" => [ "message", "receiver" ], "to" => "Message::SearchReceiver" ],
    /** 私信 */
    [ "paths" => [ "notification", "organization", "$$"], "to" => "Notification::OrganizationNotification"],
    [ "paths" => [ "notification", "organization"], "to" => "Notification::OrganizationNotification"],
    /** 提醒 */
    [ "paths" => [ "draft"], "to" => "WorkTable::Draft"],
    [ "paths" => [ "draft", "$$"], "to" => "WorkTable::Draft"],
    /** 工作集 */
    [ "paths" => [ "search" ], "to" => "Search::Index" ],
    /** 搜索 */
    [ "paths" => [ "api", "translation" ], "to" => "Api::Translation" ]
  ];

  public $controller;

  public $action;

  public $verified;

  public $parameters = [];

  public $pathInfo;

  protected $pathSum;

  public $lastMatchRule;

  public $jsonRequest;

  public $input;

  protected $appi;

  public function __construct( App $app ) {

    $this->appi = $app;
    $this->jsonRequest = $this->isJsonRequest();
    $pathInfo = $this->pathInfo = $this->getPathInfoArray();
    $this->parameters = $this->resolveRule( $pathInfo, $this->routeRules );

  }

  public function start() {

    $this->dispatch( $this->controller,
                     $this->action,
                     $this->parameters );
  }

  public function isJsonRequest() {
    if ( isset( $this->jsonRequest ) ) return $this->jsonRequest;

    $accept = $_SERVER["HTTP_ACCEPT"];
    return is_numeric(stripos($accept , "application/json"));
  }

  public function resolveRule( $pathInfo, $rules ) {
    $prepare = [];
    $params = [];
    $this->verified = false;
    // 单纯通过长度来确定规则
    array_walk( $rules, function( $rule ) use ( &$prepare ) {
      (count($rule["paths"]) === $this->pathSum) ? $prepare[] = $rule : null ;
    } );

    // 查找 100% 命中的规则
    $matchRule = $this->findMatchRule( $prepare, $pathInfo );

    if ( $matchRule ) {
      $this->verified = true;
      $lastMatchRule = $matchRule;
    } else {
      //获得预备数组
      foreach ( $prepare as $rule ) {
        foreach ( $rule["paths"] as $pos => $path ) {
          if ( $path === "$$" ) {
            $this->verified = true;
            $params[] = $pathInfo[$pos];
            continue;
          }

          if ( $path === $pathInfo[$pos] ) {
            $this->verified = true;
            continue;
          }

          $this->verified = false;
          break;
        }

        if ( $this->verified ) {
          $lastMatchRule = $rule; // 找到第一条命中的规则
          break;
        } else {
          $params = [];
        }
      }
    }

    if ( isset( $lastMatchRule ) && is_array( $lastMatchRule ) ) {

      $temp = explode( "::", $lastMatchRule["to"] );

      $this->lastMatchRule = $lastMatchRule;

      $this->controller = $temp[0];
      $this->action = $temp[1];
    } else {
      throw new RouteResolveException();
    }

    return $params;
  }

/**
 * 用于跳过任何参数的匹配直接查找一条规则
 * @param $rules
 * @param $pathInfo
 * @return bool
 */
  public function findMatchRule( $rules, $pathInfo ) {

    $matchRule = false;
    array_walk( $rules, function ( $rule ) use ( &$matchRule, &$pathInfo ) {

      $rulePathInfos = $rule["paths"];
      foreach ( $rulePathInfos as $pos => $rulePathInfo ) {

        if ( $rulePathInfo === "$$" ) {
          $matchRule = false;
          break;
        }
        // 忽略所有带匹配符号的规则
        if ( $rulePathInfo === $pathInfo[$pos] ) $matchRule = $rule;

          $matchRule = false;
          break;
      }

    } );

    return $matchRule;
  }

  public function getPathInfoArray() {

    $pathInfo = trim( @$_SERVER["PATH_INFO"] );
    $pathInfo = str_replace("\\", "/", $pathInfo);
    $pathInfo = preg_replace("/(^\/|\/$)/", "", $pathInfo);
    $pathInfo = explode("/", $pathInfo);

    $this->pathSum = count($pathInfo);

    empty($pathInfo[0]) ? $pathInfo[0] = "/" : null ;

    return $pathInfo;

  }

  public function dispatch( $controller = null,
                            $action = null,
                            array $params = [],
                            $reDispatch = false ) {

    $controller = $controller ? $controller : $this->controller;
    $action     = $action     ? $action     : $this->action;

    // 重新调度可能需要清空缓冲
    if ( $reDispatch && ob_get_contents() ) ob_clean();

    // 拼接 + 调用控制器
    $controller = __NAMESPACE__ . "\\" . "Controller\\" . $controller;
    $controllerInstance = new $controller( $this->appi );

    // 拼接 + 调用控制器方法
    $action = $_SERVER["REQUEST_METHOD"] . $action;
    ( $this->jsonRequest ) ? $action = "json" . $action : null ;

    if ( ! is_callable( [ $controllerInstance, $action ] ) ) {
      throw new RouteResolveException();
    }

    // 调用控制器的注入方法，由注入方法确定需要注入的参数
    // 交给控制器的注入方法完成调用即可

    $controllerInstance->inject( $action, $params );

    return $this;
  }

  public function jsonReturn( $jsonData ) {
    header( "Content-Type: application/json" );
    echo json_encode( $jsonData );
    return $this;
  }

  public function redirect( $url ) {
    header("Location: " . $url);
    return $this;
  }

  public function setResponseCode( $code, $message = null ) {

    $common  = [ 404 => "Not Found", 200 => "OK", 201 => "Created", 204 => "No Content",
      400 => "Bad Request", 401 => "Unauthorized", 403 => "Forbidden", 410 => "Gone" ];

    $message = $message ? $message : $common[(int)$code];

    header( $_SERVER["SERVER_PROTOCOL"]. " " . (int)$code . " " . $message );
    return $this;
  }

  public function phpInput( $inputType = "text" ) {
    $phpInput = $this->input ? $this->input : ($this->input = file_get_contents("php://input"));

    if (empty($phpInput)) return false;

    if ( "text" === $inputType ) {
      return empty( trim( $phpInput ) ) ? false : $phpInput;
    } elseif ( "json" === $inputType ) {
      $jsonArray = @json_decode( $phpInput, true );
      if ($jsonArray && JSON_ERROR_NONE === json_last_error()) return $jsonArray;
      return false;
    }
  }

  const HTTP_GET = 1;
  const HTTP_POST = 2;
  const HTTP_DELETE = 3;
  const HTTP_PUT = 4;

  public function queryString( $name, $method = self::HTTP_GET, array $filters = [] ) {
    if ( self::HTTP_GET === $method ) {
      // 这里要提前结束
      if ( isset( $_GET[$name] ) ) return $this->qsFilters( $_GET[$name], $filters );
      return false;
    } elseif ( self::HTTP_POST === $method ) {
      if ( isset( $_POST[$name] ) ) return $this->qsFilters( $_POST[$name], $filters );
    }

    $otherMethod = [ self::HTTP_POST, self::HTTP_DELETE, self::HTTP_PUT ];
    if ( ! in_array( $method, $otherMethod ) ) return false;

    $jsonArray = $this->phpInput("json");
    if ( $jsonArray && array_key_exists($name, $jsonArray) ) {
      return $this->qsFilters( $jsonArray[$name], $filters );
    }

    return false;
  }

  private function qsFilters($str, array $filters) {
    $filtersList = ["trim", "intval", "floatval"];

    array_walk( $filters, function ( $filter ) use ( &$str, $filtersList ) {
      if (!in_array( $filter, $filtersList)) return;
      $str = $filter($str);
    } );

    return $str;
  }

}
