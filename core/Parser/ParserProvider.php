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

namespace ResTrans\Core\Parser;

use ResTrans\Core;
use ResTrans\Core\App;
use ResTrans\Core\CommonException;

class ParserProvider {

  public $appi;

  public $parserInstance;

  public static $_instance;

  public $acceptList = ["txt" => "ResTrans\\Core\\Parser\\TxtParser"];

  public static function init (App $app) {
    if ( self::$_instance instanceof self ) return self::$_instance;
    self::$_instance = new self($app);
    return self::$_instance;
  }

  public function __construct (App $app) {
    $this->appi = $app;
  }

  public function getParserName ($extension) {
    if (!isset($this->acceptList[$extension])) {
      throw new CommonException("parse_failed");
    }

    return $this->acceptList[$extension];
  }

  public function getParser ($path, $extension) {
    $parserClassName = $this->getParserName($extension);
    return new $parserClassName(realpath(DATA_PATH . $path[0] . "/" . $path . ".json"), $this->appi);
  }

  public function getParserByCustomize ($path, $extension) {
    $parserClassName = $this->getParserName($extension);
    return new $parserClassName($path, $this->appi);
  }
}