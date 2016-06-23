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

class Lang {

  protected $appi;

  protected $langConfig;

  static protected $languageInstance;

  static private $_instance;

  static public $defaultLanguage = "zh-cn";

  static public function init (App $app) {
    return self::$_instance ?  self::$_instance : new self($app);
  }

  private function __construct( App $app ) {

    $this->appi = $app;
    $this->langConfig = $langConfig = isset( $app->config["language"] ) ?
                                      $app->config[ "language" ] :
                                      self::$defaultLanguage;
    $langConfig = str_replace( "-", "_", $langConfig );

    $langClassName = "ResTrans\\Core\\Language\\" . $langConfig;
    self::$languageInstance = new $langClassName;
  }

  static public function get ($index) {
    if (!self::$languageInstance) return false;
    return self::$languageInstance->$index;
  }
}