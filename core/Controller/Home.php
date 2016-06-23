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
use ResTrans\Core\Route;
use ResTrans\Core\View;
use ResTrans\Core\Event;
use ResTrans\Core\User;
use ResTrans\Core\Lang;
use ResTrans\Core\CommonException;

class Home extends ControllerConventions {

  public function __construct( Core\App $app ) {

    // 模型静默加载
    $this->modelSilent = true;
    // 触发上层构造方法
    parent::__construct( $app );

  }

  private function loginCheck ($event, $route, $ajax = false) {
    try {
      $event->trigger("Verify:isLogin");
      $ajax ?
        $event->trigger("Base:loggedInReject",   $route) :
        $event->trigger("Base:loggedInRedirect", $route);
    } catch (CommonException $e) {}
  }

  public function getLogin(Route $route, View $view, Event $event) {
    $this->home( $route, $view, $event, "login" );
  }

  public function getRegister(Route $route, View $view, Event $event, App $app) {
    if (!$app->getOption("register")) {
      $route->redirect($app->config["site_url"] . "login/");
      $event->trigger("Base:appEnd");
      exit;
    }

    $this->home( $route, $view, $event, "register" );
  }

  public function getRetrieve(Route $route, View $view, Event $event) {
    $this->home( $route, $view, $event, "retrieve" );
  }

  public function home( $route, $view, $event, $form ) {
    $this->loginCheck($event, $route);

    $redirect = $route->queryString("redirect", Route::HTTP_GET);
    if (strpos($redirect, $this->appi->config["site_url"]) !== 0) $redirect = null;

    $view->setAppToken()
         ->setValue( "HOME.form", $form )
         ->setValue("HOME.redirect", urlencode($redirect))
         ->setValue("HOME.login_captcha", (bool)$this->appi->getOption("login_captcha"))
         ->setValue("HOME.allow_register", (bool)$this->appi->getOption("register"))
         ->setValue("HOME.need_email", (bool)$this->appi->getOption("send_email"))
         ->feModules( "home" )
         ->feData(["APP.token", "HOME.redirect"])
         ->init( "Home" );
  }

  private function checkSendEmailOptionBeforeResending ($app) {
    if (!$app->getOption("send_email")) {
      throw new CommonException("", 403);
    }

    return true;
  }

  private function checkSessionBeforeResending ($user) {
    $cookies = $user->getLoginCookies();
    $sessionInfo = $user->getSessionInfo($cookies["session_id"], $cookies["user_id"]);
    if (time() > $sessionInfo->expire) throw new CommonException("session_expired", 410);

    return $sessionInfo;
  }

  private function checkEmailBeforeResending ($user, $sessionInfo) {
    $thisUser = $user->findUserInfo($sessionInfo->user_id, USER::BY_ID);
    if ($thisUser->email) {
      throw new CommonException("email_already_existed");
    }

    return $thisUser;
  }

  public function getResendEmail (View $view, App $app, Event $event, Route $route) {
    $event->on("controllerError", function ($e) use (&$route, &$app, &$event) {
      $route->redirect($app->config["site_url"]);
      $event->trigger("Base:appEnd");
      exit;
    });
    $model = $this->model("Start");
    $db = $model->db();
    $user = User::instance($app, $db);
    $this->checkSendEmailOptionBeforeResending($app);
    $sessionInfo = $this->checkSessionBeforeResending($user);
    $this->checkEmailBeforeResending($user, $sessionInfo);

    try {
      $verification = $user->getVerificationByUserId($sessionInfo->user_id);
      if (time() > $verification->expire) {
        $view->setValue("RESEND.try_again_later", false);
      } else {
        $view->setValue("RESEND.try_again_later", true);

      }
      $view->setValue("RESEND.email", $verification->email);
    } catch (CommonException $e) {
      $view->setValue("RESEND.email", "");
    }

    $view->setAppToken()
         ->setValue( "HOME.form", "resend_email" )
         ->feModules( "home" )
         ->feData(["APP.token", "HOME.redirect"])
         ->init( "Home" );
  }

  public function jsonPOSTResendEmail (Route $route, App $app) {

    $this->addTokenOnError(false);

    $email = $route->queryString("email", Route::HTTP_POST, ["trim"]);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new CommonException("email_error");
    }

    $model = $this->model("Start");
    $db = $model->db();
    $db->TSBegin();
    $user = User::instance($app, $db);

    $this->checkSendEmailOptionBeforeResending($app);
    $sessionInfo = $this->checkSessionBeforeResending($user);
    $this->checkEmailBeforeResending($user, $sessionInfo);

    try {
      $verification = $user->getVerificationByUserId($sessionInfo->user_id);
    } catch (CommonException $e) {/** 这里不做任何操作是由于可能没有用于验证的 token */}

    if (isset($verification) && time() < $verification->expire) {
      throw new CommonException("try_again_later", 403);
    }

    $verCode = $app->sha1Gen();
    $user->setVerificationCode($verCode, $sessionInfo->user_id, $email);
    $db->TSCommit();

    $verificationSubject = Lang::get("validate_email_subject");
    $verificationBody = require realpath( CORE_PATH . "/Template/EmailVerification.php" );
    $user->sendEmail( $email, $verificationSubject, $verificationBody );

    $route->jsonReturn($this->status("verification_email_resended", false));
  }

  public function getVerify ($verificationCode, View $view, App $app, Event $event, Route $route) {
    $this->loginCheck($event, $route);

    $model = $this->model("Start");
    $db = $model->db();
    $db->TSBegin();
    $user = User::instance($app, $db);

    try {
      if ($user->isVerificationExpire($verificationCode)) {
        throw new CommonException("verification_expired", 410);
      }

      $user->setUserVerified($verificationCode);
      $user->removeVerificationCode($verificationCode);
      $db->TSCommit();

      $view->setValue("USER.verification_succeed", true);
    } catch (\Exception $e) {
      try {
        $db->TSRollBack();
        $user->removeVerificationCode($verificationCode);
      } catch (CommonException $e) {}
      $view->setValue("USER.verification_succeed", false);
    }

    $view->setAppToken()
         ->setValue( "HOME.form", "email_verify" )
         ->feModules( "home" )
         ->feData(["APP.token", "HOME.redirect"])
         ->init( "Home" );
  }

  public function getSetNewPassword ($retrieveToken, View $view, App $app, Route $route) {
    $this->loginCheck($event, $route);

    $model = $this->model("Start");
    $db = $model->db();
    $db->TSBegin();
    $user = User::instance($app, $db);

    try {
      if (
        !$user->isRetrieveTokenExists($retrieveToken) ||
        $user->isRetrieveTokenExpire($retrieveToken)
      ) {
        throw new \Exception();
      }
    } catch (\Exception $e) {
      $route->redirect($app->config["site_url"] . "login/");
      $event->trigger("Base:appEnd");
      exit();
    }

    $view->setAppToken()
         ->setValue( "HOME.form", "password_reset" )
         ->setValue("USER.retrieve_token", $retrieveToken)
         ->feModules( "home" )
         ->feData(["APP.token", "USER.retrieve_token"])
         ->init( "Home" );
  }

  public function getCaptcha (Core\App $app) {
    $captcha = $app->plugin("Captcha", "Captcha");

    $formula = $captcha->generateFormula();
    $operator = $captcha->generateOperator( $captcha->getOperatorName() );
    $captchaImage = $captcha->captcha( $formula, $operator );

    $captcha->output( $captchaImage );

    $_SESSION["captcha_result"] = $captcha->result;

  }

  public function jsonPOSTLogin ( Route $route, Event $event, App $app) {
    $this->loginCheck($event, $route, true);

    $post = Route::HTTP_POST;
    $userIdent = $route->queryString( "userident", $post, [ "trim" ] );
    $password  = $route->queryString( "password", $post, [ "trim" ] );
    // 检查 token
    $event->trigger( "Verify:checkToken", $post);
    //检查验证码
    if ($app->getOption("login_captcha") && !$event->trigger( "Verify:checkCaptcha", $post) ) {
      throw new Core\CommonException("invalid_captcha");
    }
    // 检查用户名（邮箱）和密码
    if ( empty( $userIdent ) || empty( $password ) ) {
      throw new Core\CommonException("user_input_incorrect");
    }

    $db = $this->model("Start")->db();
    $user = User::instance($app, $db);

    $db->TSBegin();
    $login = $user->login( $userIdent, $password );
    $db->TSCommit();

    $thisUser = $user->getLastUserInfo();
    if ($app->getOption("send_email") && empty($thisUser->email)) {
      $route->jsonReturn( $this->status( "login_successful_verify_email", false ) );
    } else {
      $route->jsonReturn( $this->status( "login_successful", false ) );
    }
  }

  public function jsonPOSTRegister( Route $route, Event $event, App $app ) {
    $this->loginCheck($event, $route, true);

    if (!$app->getOption("register")) {
      throw new CommonException("registration_is_off", 403);
    }

    $allowSendEmail = (bool)$app->getOption("send_email");
    $requestMethod = Core\Route::HTTP_POST;
    $username       = $route->queryString( "username", $requestMethod, [ "trim" ] );
    $useremail      = $route->queryString( "useremail", $requestMethod, [ "trim" ] );
    $password       = $route->queryString( "password", $requestMethod, [ "trim" ] );
    // 检查 token
    $event->trigger( "Verify:checkToken", $requestMethod);
    //检查验证码
    if ( !$event->trigger( "Verify:checkCaptcha", $requestMethod) ) {
      throw new Core\CommonException("invalid_captcha");
    }
    if ( empty( $username ) || empty( $password ) ) {
      $route->setResponseCode( 400 )
            ->jsonReturn( $this->status( "user_input_incorrect" ) );
      return;
    }

    $db = $this->model("Start")->db();
    $user = User::instance($app, $db);
    try {

      $db->TSBegin();
      $register = $user->register( $username, $useremail, $password, $allowSendEmail );
      $db->TSCommit();

      $status = $allowSendEmail ? "register_successful_and_email_sended" : "register_successful";
      $route->setResponseCode( 200 )
            ->jsonReturn( $this->status( $status, false ) );
    } catch ( Core\CommonException $e ) {

      $db->TSRollBack();
      $route->setResponseCode( 401 )
            ->jsonReturn( $this->status( $e->getMessage() ) );
    }
  }

  public function jsonPOSTRetrieve(Route $route, Event $event, App $app) {
    $this->loginCheck($event, $route, true);

    $allowSendEmail = (bool)$app->getOption("send_email");
    // 如果发送邮件功能不允许，则无法使用此功能
    if (!$allowSendEmail) {
      $event->trigger( "Verify:clearCaptchaSession" );
      throw new CommonException("send_email_failed", 403);
    }

    // 检查 token
    $event->trigger("Verify:checkToken", Route::HTTP_POST);

    $email =  $route->queryString( "useremail", Route::HTTP_POST, [ "trim" ] );
    $captcha = $route->queryString( "captcha", Route::HTTP_POST, [ "trim" ] );
    //检查验证码
    if ( !$event->trigger( "Verify:checkCaptcha", Route::HTTP_POST) ) {
      throw new Core\CommonException("invalid_captcha");
    }
    // 检查输入
    if ( empty( $email ) ) {
      $route->setResponseCode( 400 )
            ->jsonReturn( $this->status( "user_input_incorrect" ) );
      return;
    }

    $db = $this->model("Start")->db();
    $user = User::instance($app, $db);
    try {

      $db->TSBegin();
      $retrieve = $user->retrieve( $email );
      $db->TSCommit();

      $route->setResponseCode( 200 )
            ->jsonReturn( $this->status( "retrieve_email_sended", false ) );
    } catch ( Core\CommonException $e ) {
      $route->setResponseCode( 401 )
            ->jsonReturn( $this->status( $e->getMessage() ) );
    }
  }

  public function jsonPOSTLogout( Route $route, App $app, Event $event ) {
    $event->trigger("Verify:isLogin");

    // 检查 token
    $event->trigger("Verify:checkToken", Route::HTTP_POST);

    $db = $this->model("Start")->db();
    $logout = User::instance($app, $db)->logout();

    if (!$logout) {
      $route
        ->setResponseCode( 400 )
        ->jsonReturn( $this->status( "logout_failed" ) );
    } else {
      $route->jsonReturn( $this->status( "logout_successful" ) );
    }
  }

  public function jsonPOSTSetNewPassword (Route $route, Event $event, App $app) {
    $this->loginCheck($event, $route, true);

    $retrieveToken = $route->queryString("retrieve_token", Route::HTTP_POST, ["trim"]);
    $newPassword = $route->queryString("new_password", Route::HTTP_POST, ["trim"]);

    // 检查 token
    $event->trigger("Verify:checkToken", Route::HTTP_POST);

    $db = $this->model("Start")->db();
    $user = User::instance($app, $db);
    $db->TSBegin();
    try {
      if (
        !$user->isRetrieveTokenExists($retrieveToken) ||
        $user->isRetrieveTokenExpire($retrieveToken)
      ) {
        throw new \Exception();
      }

      $tokenDetail = $user->getTokenDetail($retrieveToken);
      $user->setPassword($tokenDetail->user_id, $newPassword, null, false);
      $user->removeRetrieveToken($tokenDetail->token);
      $db->TSCommit();
      $route->jsonReturn($this->status("new_password_saved"));
    } catch (\Exception $e) {
      $db->TSRollBack();
      $route->setResponseCode(400)->jsonReturn(
        ["status_short" => "set_password_failed", "status_detail" => L("set_password_failed")]
      );
    }
  }

  public function getCron (App $app, Route $route) {
    $cronKey = $route->queryString("key", Route::HTTP_GET);
    if ($app->config["cron_key"] === $cronKey) {
      $this->model("Start")->cleanSessions();
      $this->model("Start")->cleanVerifications();
    }
  }
}