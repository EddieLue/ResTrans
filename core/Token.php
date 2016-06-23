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

class Token {

  protected $appi;

  private static $_instance;

  private function __construct( App $app ) {
    $this->appi = $app;

  }

  public static function instance( App $app ) {
    if ( self::$_instance instanceof self ) return self::$_instance;

    self::$_instance = new self( $app );
    return self::$_instance;

  }

  public function setToken() {
    $result = $_SESSION["token"] = $this->appi->sha1Gen();
    session_commit();
    return $result;
  }

  public function verifyToken( $tokenString ) {
    if ( ! preg_match("/^[0-9a-f]{40}$/", strtolower($tokenString) ) ) return false;

    if ( isset( $_SESSION["token"] ) &&
      $_SESSION["token"] === $tokenString ) {
      unset( $_SESSION["token"] );
      return true;
    }

    return false;
  }

}