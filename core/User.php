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

class User {

  protected $appi;

  protected $database;

  protected $smtp;

  protected $lastUserInfo;

  const COOKIE_DURATION = 604800;

  const VERC_DRUATION = 3600;

  const RETP_DURATION = 3600;

  const BY_ID = "id";

  const BY_EMAIL = "email";

  const BY_USERNAME = "username";

  private static $_instance;

  private $currentLoginUserId;

  public $escape = "/[!#$%^&*(){}|`\/\\~\s:;\"'?<>,=]/u";

  public static function instance( App $app, Database $database ) {

    if ( self::$_instance instanceof self ) return self::$_instance;

    return self::$_instance = new self( $app, $database );
  }

  private function __construct( App $app, Database $database ) {

    $this->appi = $app;
    $this->database = $database;
    $this->smtp = $app->config["mail"];

  }

  public function login( $userIdentity, $password ) {
    $db = $this->database;

    if ( mb_strlen( $userIdentity, "UTF-8" ) < 100 &&
         filter_var( $userIdentity, FILTER_VALIDATE_EMAIL ) ) {

      // 是否已经注册
      if ( ! $this->isRegistered( $userIdentity, self::BY_EMAIL ) ) {
        throw new CommonException( "user_not_found" );
      }
      // 得到注册用户的信息
      $user = $this->findUserInfo( $userIdentity, self::BY_EMAIL );
    } elseif (   mb_strlen( $userIdentity, "UTF-8" ) >= 2  &&
                 mb_strlen( $userIdentity, "UTF-8" ) <= 12 &&
               ! preg_match( $this->escape , $userIdentity ) ) {

      // 是否已经注册
      if ( ! $this->isRegistered( $userIdentity ) ) {
        throw new CommonException( "user_not_found" );
      }
      // 得到注册用户的信息
      $user = $this->findUserInfo( $userIdentity );
    } else {

      throw new CommonException( "user_input_incorrect" );
    }

    // 当账户被 block
    // 当密码错误
    if (   $this->isBlocked( $user->user_id ) ||
         ! password_verify( $password, $user->password ) ) {
      throw new CommonException( "user_can_not_login" );
    }

    return $this->afterLoginCheck( $user );
  }

  private function afterLoginCheck( $user ) {
    $session = $this->createSession( $user->user_id );
    $this->lastUserInfo = $user;
    return $this->updateLastLoginTime( $user->user_id ) && $this->setLoginCookies( $session );
  }

  public function updateLastLoginTime( $userId ) {

    $db         = $this->database;
    $prepareSQL = $db->update( "users",
                                $db->condition( "last_login", "time" ),
                                $db->condition( "user_id", "userId" ) );

    try {

      $updateLastLoginTime = $db->pdoPrepare( $prepareSQL,
                                              [ "time#int" => time(), "userId#int" => $userId ] );
      $updateLastLoginTime->execute();
      if ( ! $updateLastLoginTime->rowCount() ) throw new \Exception();

      return true;
    } catch ( \Exception $e ) {

      throw new CommonException( "update_last_login_time_failed" );
    }
  }

  protected function setLoginCookies( $session ) {
    $url = parse_url($this->appi->config["site_url"]);
    $domain = $url["host"];
    $secure = $url["scheme"] === "https";
    // 用户ID
    $setUserId = setcookie(
      "reuid",
      $this->appi->event->trigger("Proc:encodeUserIds", $session["session_user"]),
      $session["session_expire"],
      $this->appi->config["site_uri"],
      $domain,
      $secure,
      true
    );
    // 用户 session id
    $setSessionId = setcookie(
      "ressid",
      $session["session_id"],
      $session["session_expire"],
      $this->appi->config["site_uri"],
      $domain,
      $secure,
      true
    );

    return $setUserId && $setSessionId;
  }

  protected function createSession( $userId ) {
    $sessionId = $this->appi->sha1Gen();
    $expireOn = time() + self::COOKIE_DURATION;

    $db = $this->database;
    $prepareSQL = $db->insert( "sessions", 3 );
    $params = [ "1#int" => $userId,
                "2#str" => $sessionId,
                "3#int" => $expireOn ];

    try {

      $createSession = $db->pdoPrepare( $prepareSQL, $params );
      $createSession->execute();
      if ( !$createSession->rowCount() ) throw new \Exception();

      return [ "session_user" => $userId,
               "session_id" => $sessionId,
               "session_expire" => $expireOn ];
    } catch ( \Exception $e ) {
      throw new CommonException( "create_session_failed" );
    }
  }

  public function getLoginCookies() {
    $cookie = $_COOKIE;
    if (
      !isset($cookie["reuid"]) ||
      !isset($cookie["ressid"]) ||
      !preg_match("/^[0-9a-f]{40}$/", strtolower($cookie["ressid"]))
    ) {
      throw new CommonException("get_login_cookies_failed");
    }
    return [
      "user_id" => $this->appi->event->trigger("Proc:decodeUserIds", $cookie["reuid"]),
      "session_id" => $cookie["ressid"]
    ];
  }

  public function isLogin( $quickly = true ) {

    if ( $quickly &&
         is_numeric( $this->currentLoginUserId ) &&
         $this->currentLoginUserId !== 0 ) return $this->currentLoginUserId;

    $session = $this->getLoginCookies();
    $db = $this->database;

    $where = "{$db->condition('user_id')} AND {$db->condition('session_id')}";
    $prepareSQL = $db->select(
      "sessions",
      $db->fieldList(["user_id", "session_id", "expire"]),
      $where
    );

    $params = [ "1#int" => $session["user_id"], "2#str" => $session["session_id"] ];

    try {
      $getSessionInfo = $db->pdoPrepare( $prepareSQL, $params );
      $getSessionInfo->execute();
      $sessionInfo = $getSessionInfo->fetch();
      if ( !$sessionInfo || time() > $sessionInfo->expire ) throw new \Exception();

      $thisUser = $this->findUserInfo($session["user_id"], self::BY_ID);

      if ($this->isBlocked($session["user_id"])) throw new \Exception();

      if (
        $this->appi->getOption("send_email") &&
        !$this->isEmailChecked($session["user_id"], true)
      ) throw new \Exception();

    } catch ( \PDOException $e ) {

      $this->clearLoginCookie();
      throw new CommonException("not_logged_in");

    } catch ( \Exception $e ) {

      $this->clearLoginCookie();
      $this->removeSession( $session["session_id"] );
      throw new CommonException("not_logged_in");

    }

    return $this->currentLoginUserId = $sessionInfo->user_id;
  }

  public function removeSession( $sessionId ) {

    $db = $this->database;
    $prepareSQL = $db->delete( "sessions", $db->condition( "session_id" ) );
    try {

      $deleteSession = $db->pdoPrepare( $prepareSQL, [ "1#str" => $sessionId ] );
      $result = $deleteSession->execute();
      if ( !$result || !$deleteSession->rowCount() ) throw new \Exception();
    } catch ( \Exception $e ) {
      throw new CommonException( "delete_session_failed" );
    }

    return true;
  }

  public function clearLoginCookie() {
    $url = parse_url($this->appi->config["site_url"]);
    $domain = $url["host"];
    $secure = $url["scheme"] === "https";

    foreach ( $_COOKIE as $cookieName => $cookieValue ) {
      if ($cookieName !== "reuid" && $cookieName !== "ressid") continue;
      setcookie(
        $cookieName,
        "",
        time() - 3600,
        $this->appi->config["site_uri"],
        $domain,
        $secure,
        true
      );
    }

    return $this;
  }

  public function verificationCode( $code ) {
    $db = $this->database;
    $prepareSQL = $db->select( "verifications", "*", $db->condition( "code" ) );

    try {

      $verificationCode = $db->pdoPrepare( $prepareSQL, [ "1#str" => $code ] );
      $verificationCode->execute();
      $fetch = $verificationCode->fetch();
      if (!$fetch) throw new \Exception();
    } catch ( \Exception $e ) {
      throw new CommonException( "verification_code_not_found" );
    }

    return $fetch;
  }

  public function isVerificationExpire( $code ) {

    $verificationCode = $this->verificationCode( $code );
    if ( time() < $verificationCode->expire ) return false;

    return true;
  }

  public function removeVerificationCode( $code ) {
    if ( ! $this->verificationCode( $code ) ) return false;
    $db = $this->database;
    $prepareSQL = $db->delete( "verifications", $db->condition( "code" ) );

    try {
      $removeVerificationCode = $db->pdoPrepare( $prepareSQL, [ "1#str" => $code ] );
      $removeVerificationCode->execute();

      if ( ! $removeVerificationCode->rowCount() ) throw new \Exception();
    } catch ( \Exception $e ) {
      throw new CommonException( "remove_verification_code_failed" );
    }

    return true;
  }

  public function setUserVerified( $code ) {
    $verificationCode = $this->verificationCode($code);

    $db = $this->database;
    $prepareSQL = $db->update( "users", $db->condition( "email" ), $db->condition( "user_id" ) );

    $params = [ "1#str" => $verificationCode->email,
                "2#int" => $verificationCode->user ];

    try {
      $setUserVerified = $db->pdoPrepare( $prepareSQL, $params );
      $setUserVerified->execute();
      if ( ! $setUserVerified->rowCount() ) throw new \Exception();
      return true;
    } catch ( \Exception $e ) {
      throw new CommonException( "failed_to_set_user_as_verified" );
    }
  }

  public function register( $username, $email, $password, $allowSendEmail ) {
    // 用户名校验
    if (   mb_strlen( $username, "UTF-8" ) < 2  ||
           mb_strlen( $username, "UTF-8" ) > 12 ||
           preg_match( $this->escape , $username ) ) {

      throw new CommonException( "invalid_username" );
    }
    // 邮箱校验
    if ( $allowSendEmail && 
       ( mb_strlen( $email, "UTF-8" ) > 100 || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) ) {

      throw new CommonException( "invalid_email" );
    }
    // 密码校验
    $this->verifyPassword($password);

    if ( $this->isRegistered( $username ) ||
         ($allowSendEmail && $this->isRegistered($email, "email")) ) {
      throw new CommonException( "user_already_exists" );
    }

    return $this->afterRegisterCheck( $username, $email, $password, $allowSendEmail );
  }

  private function afterRegisterCheck( $username, $email, $password, $allowSendEmail ) {

    $password = password_hash( $password, PASSWORD_BCRYPT );
    $signupTime = time( true );

    $db = $this->database;

    $insertFields = [ "name",
                      "email",
                      "password",
                      "signup",
                      "last_login" ];
    $prepareSQL = $db->insert( "users", 5, $db->fieldList( $insertFields ) );

    $params = [ "1#str" => $username,
                "2#null" => null,
                "3#str" => $password,
                "4#int" => $signupTime,
                "5#int" => 0 ];

    try {

      $userRegister = $db->pdoPrepare( $prepareSQL, $params );
      $userRegister->execute();

      if ( ! $userRegister->rowCount() ) throw new \Exception();

      $lastUserId = $db->pdo()->lastInsertId();
    } catch ( \Exception $e ) {

      throw new CommonException( "user_register_failed" );
    }

    if ( $allowSendEmail ) {

      $verCode = sha1( $email . $this->appi->sha1Gen() );

      $this->setVerificationCode( $verCode, $lastUserId, $email );

      $app = $this->appi;
      $verificationSubject = Lang::get("validate_email_subject");
      $verificationBody = require realpath( CORE_PATH . "/Template/EmailVerification.php" );

      if ( ! $this->sendEmail( $email, $verificationSubject, $verificationBody ) ) {
        throw new CommonException( "register_email_send_failed" );
      }
    }

    return true;
  }

  public function setVerificationCode( $verCode, $userId, $email ) {

    $expireOn = time() + self::VERC_DRUATION;

    $db = $this->database;
    $placeholders = $db->placeholders( [ "code", "expire", "user", "email" ] );
    $params = [ "code#str"   => $verCode,
                "expire#int" => $expireOn,
                "user#str"   => $userId,
                "email#str"  => $email ];
    $prepareSQL = $db->insert( "verifications", $placeholders );
    try {

    $setVerificationCode = $db->pdoPrepare( $prepareSQL, $params );
    $setVerificationCode->execute();
    if ( ! $setVerificationCode->rowCount() ) throw new \Exception();
    } catch ( \Exception $e ) {

      throw new CommonException( "set_verification_code_failed" );
    }

    return true;
  }

  public function sendEmail( $to, $subject, $body ) {
    
    $mailer = new \PHPMailer;

    $mailer->isSMTP();
    $mailer->Host = $this->smtp["smtp_host"];
    $mailer->SMTPAuth = true;
    $mailer->Username = $this->smtp["smtp_user"];
    $mailer->Password = $this->smtp["smtp_password"];
    $mailer->SMTPSecure = $this->smtp["smtp_secure"];
    $mailer->Port = $this->smtp["smtp_port"];

    $mailer->setFrom( $this->smtp["smtp_user"] );
    $mailer->addAddress( $to );
    $mailer->isHTML( true );

    $mailer->Subject = $subject;
    $mailer->Body = $body;

    $mailer->SMTPOptions = [
      "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
        "allow_self_signed" => true
      ]
    ];

    return $mailer->send();
  }


  public function isRegistered( $userIdentity, $identityType = self::BY_USERNAME ) {

    $db = $this->database;

    if ( $identityType === self::BY_EMAIL ) { // 当userIdentity是邮箱的情况

      $prepareSQLIsEmail         = $db->select( "users",
                                                "count(`email`)",
                                                $db->condition( "email" ) );

      $prepareSQLUnverifiedEmail = $db->select( "verifications",
                                                  "count(`email`)",
                                                  $db->condition( "email" ) );

      try {

        $isEmail = $db->pdoPrepare( $prepareSQLIsEmail, [ "1#str" => $userIdentity ] );
        $isEmail->execute();

        $isUnverifiedEmail         = $db->pdoPrepare( $prepareSQLUnverifiedEmail,
                                                      [ "1#str" => $userIdentity ] );

        $isUnverifiedEmail->execute();
        if ( ! $isEmail->fetchColumn() && ! $isUnverifiedEmail->fetchColumn() ) {
          throw new \Exception();
        }

        return true;
      } catch ( \Exception $e ) {

        return false;
      }
    } elseif ( $identityType === self::BY_USERNAME ) {

      $prepareSQLIsUser = $db->select( "users", "count(`name`)", $db->condition( "name" ) );

      try {
        $isUser = $db->pdoPrepare( $prepareSQLIsUser, [ "1#str" => $userIdentity ] );
        $isUser->execute();
        if ( ! $isUser->fetchColumn() ) throw new \Exception();

        return true;
      } catch ( \Exception $e ) {

        return false;
      }
    } elseif ( $identityType === self::BY_ID ) {

      $prepareSQLIsId = $db->select( "users", "count(`user_id`)", $db->condition( "user_id" ) );

      try {

        $isId = $db->pdoPrepare( $prepareSQLIsId, [ "1#int" => $userIdentity ] );
        $isId->execute();
        if ( ! $isId->fetchColumn() ) throw new \Exception();

        return true;
      } catch ( \Exception $e ) {

        return false;
      }
    }

    return false;
  }

  public function findUserInfo( $userIdentity,
                                $identityType = self::BY_USERNAME,
                                array $columns = [],
                                $force = false ) {

    if ( ! $force && isset( $this->lastUserInfo ) ) {
      $lastUserInfo = $this->lastUserInfo;
      if ( ( self::BY_USERNAME === $identityType && $userIdentity === $lastUserInfo->name ) ||
           ( self::BY_EMAIL === $identityType && $userIdentity === $lastUserInfo->email )   ||
           ( self::BY_ID === $identityType && $userIdentity === $lastUserInfo->user_id ) ) {
        return $this->lastUserInfo;
      }
    }

    $db = $this->database;

    $fields = "*";
    if ( ! empty( $columns ) ) {
      $fields = $db->fieldList( $columns );
    }

    try {
      switch ( $identityType ) {
        case self::BY_USERNAME:
          $findUser = $db->pdoPrepare( $db->select( "users", $fields, $db->condition( "name" ) ),
                                       [ "1#str" => $userIdentity ] );
        break;
        case self::BY_EMAIL:
          $findUser = $db->pdoPrepare( $db->select( "users", $fields, $db->condition( "email" ) ),
                                       [ "1#str" => $userIdentity ] );
        break;
        case self::BY_ID:
          $findUser = $db->pdoPrepare( $db->select( "users", $fields, $db->condition( "user_id" ) ),
                                       [ "1#int" => $userIdentity ] );
        break;
      }

      if ( ! isset( $findUser ) ) throw new \Exception();
      $findUser->execute();
      $lastUserInfo = $fetch = $findUser->fetch();
      if (!$fetch) throw new \Exception();

      return $fetch;
    } catch ( \Exception $e ) {
      throw new CommonException( "user_not_found" );
    }
  }

  public function isEmailChecked( $userId, $force = false ) {
    $findUserInfo = $this->findUserInfo( $userId, self::BY_ID, ["email"], $force );
    $email = $findUserInfo->email;
    return !is_null( $findUserInfo->email ) && filter_var($email, FILTER_VALIDATE_EMAIL );
  }

  public function isBlocked( $userId, $force = false ) {
    $findUserInfo = $this->findUserInfo( $userId, self::BY_ID, [ "blocked" ], $force );
    return $findUserInfo ? (int)$findUserInfo->blocked : false;
  }

  public function retrieve( $email ) {

    if (   mb_strlen( $email, "UTF-8" ) > 100 ||
         ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ||
         ! $this->isRegistered( $email, self::BY_EMAIL ) ||
         ! (bool)$this->appi->globalOptions["send_email"] ) {

      throw new CommonException( "retrieve_email_invalid" );
    }

    $user = $this->findUserInfo( $email, self::BY_EMAIL );

    if ( $this->isBlocked( $user->user_id ) ) {
      throw new CommonException( "account_unavailable" );
    }


    $userRetrieveToken = $this->listRetrieveToken( $user->user_id );
    $userRetrieveTokenCount = count( $userRetrieveToken );

    array_walk( $userRetrieveToken , function( $list ) use ( &$userRetrieveTokenCount ) {
      $this->removeRetrieveToken( $list->token );
    } );

    return $this->afterRetrieveCheck( $user );
  }

  private function afterRetrieveCheck( $user ) {

    $db = $this->database;
    $token = $this->appi->sha1Gen();
    $expireOn = time() + self::RETP_DURATION;
    $retrieveUser = $user->user_id;

    $prepareSQL = $db->insert( "retrieve", $db->placeholders( [ "token", "expire", "user" ] ) );
    $params = [ "token#str" => $token, "expire#int" => $expireOn, "user#int" => $retrieveUser ];

    try {

      $setRetrieveToken = $db->pdoPrepare( $prepareSQL, $params );
      $setRetrieveToken->execute();
      if ( ! $setRetrieveToken->rowCount() ) throw new \Exception();
    } catch ( \Exception $e ) {
      throw new CommonException( "retrieve_account_failed" );
    }

    $retrieveSubject = Lang::get( "retrieve_email_subject" );
    $app = $this->appi;
    $retrieveBody = require_once realpath( CORE_PATH . "/Template/EmailRetrieve.php" );

    $sendEmail = $this->sendEmail( $user->email, $retrieveSubject, $retrieveBody );
    if ( ! $sendEmail ) throw new CommonException( "send_email_failed" );

    return $sendEmail;
  }

  public function listRetrieveToken( $userId ) {

    $db = $this->database;
    $prepareSQL = $db->select( "retrieve", "*", $db->condition( "user_id" ) );

    try {

      $listRetrieveToken = $db->pdoPrepare( $prepareSQL, [ "1#int" => $userId ] );
      $listRetrieveToken->execute();
      return $listRetrieveToken->fetchAll();
    } catch ( \Exception $e ) {
      throw new CommonException( "list_retrieve_token_failed" );
    }
  }

  public function isRetrieveTokenExists( $token ) {

    $db = $this->database;
    $where = $db->condition( "token" );
    $prepareSQL = $db->select( "retrieve", "count(`token`)", $where );

    try {

      $checkRetrieveToken = $db->pdoPrepare( $prepareSQL,
                                             [ "1#str" => $token ] );
      $checkRetrieveToken->execute();
      if ( ! $checkRetrieveToken->fetchColumn() ) throw new \Exception();
    } catch ( \Exception $e ) {
      return false;
    }

    return true;
  }

  /**
   * 检查并返回找回密码 token 是否过期
   * @param  string $token 找回密码 token
   * @return bool          false: 未过期 true: 已过期
   */
  public function isRetrieveTokenExpire( $token ) {

    $db = $this->database;
    $prepareSQL = $db->select( "retrieve",
                               $db->field( "expire" ),
                               $db->condition( "token" ) );

    try {

      $isRetrieveTokenExpire = $db->pdoPrepare( $prepareSQL, [ "1#str" => $token ] );
      $isRetrieveTokenExpire->execute();
      if (
        !$isRetrieveTokenExpire->rowCount() ||
        time() < $isRetrieveTokenExpire->fetchColumn()
      ) throw new \Exception();
    } catch ( \Exception $e ) {
      return false;
    }

    return true;
  }

  public function removeRetrieveToken( $token ){

    $db = $this->database;
    $where = $db->condition( "token" );
    $prepareSQL = $db->delete( "retrieve", $where );

    try {
      $removeRetrieveToken = $db->pdoPrepare(
        $prepareSQL,
        [ "1#str" => $token ]
      );
      $removeRetrieveToken->execute();
      if ( ! $removeRetrieveToken->rowCount() ) throw new \Exception();
    } catch ( \Exception $e ) {
      throw new CommonException( "remove_retrieve_token_failed" );
    }

    return true;
  }

  public function getTokenDetail ($token) {
    return $this->database->query()
    ->select("token", "expire", "user_id")
    ->from("retrieve")
    ->where("token = ?")
    ->bindData(["1#str" => $token])
    ->ret(Query::FETCH)
    ->rowsPromise(1)
    ->throwException("token_not_found")
    ->execute();
  }

  public function logout() {
    $session = $this->getLoginCookies();
    $this->removeSession( $session["session_id"] );

    return $this->clearLoginCookie();
  }

  public function verifyPassword ($password) {
    if (   mb_strlen( $password, "UTF-8" ) > 32 ||
         ! preg_match_all( "/^[a-zA-Z0-9%.,!~@#&*\-_+]{8,}$/", $password ) ) {
      throw new CommonException( "invalid_password" );
    }
  }

  public function setPassword ($userId, $newPassword, $oldPassword = null, $chkOld = true) {
    $this->verifyPassword($newPassword);
    if ($chkOld) {
      $this->verifyPassword($oldPassword);
      $user = $this->findUserInfo($userId, self::BY_ID, ["password"]);
      if (!password_verify($oldPassword, $user->password)) {
        throw new CommonException("old_password_verify_failed");
      }
    }

    $db = $this->database;
    $prepareSQL = $db->update("users", $db->condition("password"), $db->condition("user_id"));
    try {
      $setPassword = $db->pdoPrepare(
        $prepareSQL,
        ["1#str" => password_hash($newPassword, PASSWORD_BCRYPT), "2#int" => $userId]
      );
      if (!$setPassword->execute() || !$setPassword->rowCount()) throw new \Exception();
      return true;
    } catch (\Exception $e) {
      throw new CommonException("set_password_failed");
    }
  }

  public function getLastUserInfo () {
    return $this->lastUserInfo;
  }

  public function getSessionInfo ($sessionId, $userId) {
    $db = $this->database;
    return $db->query()
    ->select("user_id", "session_id", "expire")
    ->from("sessions")
    ->where("session_id = ? AND user_id = ?")
    ->rowsPromise(1)
    ->bindData(["1#str" => $sessionId, "2#int" => $userId])
    ->throwException("session_not_found")
    ->ret(Query::FETCH)->execute();
  }

  public function getVerificationByUserId ($userId) {
    $db = $this->database;
    return $db->query()
    ->select("code", "expire", "user", "email")
    ->from("verifications")
    ->where("user = ?")
    ->rowsPromise(1)
    ->bindData(["1#int" => $userId])
    ->throwException("verification_not_found")
    ->ret(Query::FETCH)->execute();
  }
}