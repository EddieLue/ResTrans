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

class View {

  public $appi;

  public $data = [];

  protected $defaultList = [ "SYS_CFG.site_url",
                             "SYS_CFG.site_uri",
                             "SYS_CFG.assets_url" ];

  public function __construct( App $app ) {

    $this->appi = $app;
    $this->setArray( "SYS_OPTION", (array)$app->globalOptions )
         ->setArray( "SYS_CFG", (array)$app->config )
         ->setValue( "SYS_CFG.assets_url", $this->data["SYS_CFG.site_url"] . "assets/" )
         ->setValue( "SYS.version", App::VERSION )
         ->feDATA()
         ->title();
  }

  private function setter( $name, $v ) {
    $this->data[$name] = $v;
  }

  public function setValue( $name, $value = "" ) {

    if ( is_scalar( $name ) ) $this->setter( $name, $value );
    if ( is_array( $name ) ) {
      foreach ( $name as $k => $v ) {
        $this->setter( $k, $v );
      }
    }

    return $this;
  }

  public function setArray( $name, array $array ) {

    foreach ( $array as $key => $value ) {
      $this->setValue( strtoupper( $name ) . "." . $key, $value );
    }
    return $this;
  }

  /**
   * 将目前已有的参数放到 FE.data，
   * 并格式化成 json 输出，
   * 这样就可以用作前端读取的数据
   * @param  array  $data 要放到 FE.data 的数据名称
   * @return View         返回自身
   */
  public function feData( array $data = [] ) {
    $res = [];
    $data = array_merge( $this->defaultList, $data );
    foreach ( $data as $value ) {
      if ( ! isset( $this->data[$value] ) ) continue;
      $res[$value] = $this->data[$value];
    }

    $encodeTag = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_BIGINT_AS_STRING;
    $this->setValue( "FE.data", json_encode( $res, $encodeTag ) );
    return $this;
  }

  public function feModules( $modules ) {
    $this->setValue( "FE.modules", $modules );
    return $this;
  }

  public function title( array $titlePiece = [] ) {

    if ( ! count( $titlePiece ) ) {

      $this->setValue( "APP.title", "ResTrans");
      return $this;
    }

    $this->setValue( "APP.title", implode( " / ", $titlePiece ) . " / ResTrans" );
    return $this;
  }

  public function setAppToken() {
    $this->setValue( "APP.token", Token::instance( $this->appi )->setToken() );
    return $this;
  }

  public function init( $templateName ) {
    loadTemplate( $templateName, $this );
  }

  public function __invoke( $name, $toHTMLEntities = true, $direct = true ) {
    if ( ! isset( $this->data[$name] ) ) return null;

    $flags = ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE;
    $data = $this->data[$name];
    if ( $direct ) {
      echo $toHTMLEntities ? htmlentities( $data, $flags, "UTF-8" ) : $data;
      return $this;
    }

    return $toHTMLEntities ? htmlentities( $data, $flags, "UTF-8" ) : $data;
  }

  public function other( $templateName ) {
    loadTemplate( $templateName, $this );
  }

  public function fTime( $timestamp, $direct = true ) {
    if ( $direct ) {
      echo $this->appi->fTime( $timestamp );
      return;
    }

    return $this->appi->fTime( $timestamp );
  }

  public function toHTMLEntities( $string, $newline2br = false, $direct = true ) {

    $flags = ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE;
    $string = htmlentities( $string, $flags, "UTF-8" );
    $string = $newline2br ? str_replace("&NewLine;", "<br>", $string) : $string;

    if ( $direct ) {
      echo $string;
      return;
    }

    return $string;
  }

  public function e( $obj, $index = null, $toHTMLEntities = 1, $nl2br = 0) {
    $text = $index && is_object($obj) && property_exists($obj, $index) ? $obj->$index : $obj;
    $text = is_scalar($text) ? $text : "";
    echo $toHTMLEntities ? $this->toHTMLEntities($text, $nl2br, 0) : $text;
  }

  public function a($obj, $index, $ifTrue = null, $ifFalse = null) {
    if (is_scalar($obj)) {
      /** 当obj是标量，$index作为 iftrue $iftrue作为 iffalse */
      echo $this($obj, false, false) ? $index : $ifTrue;
    } elseif (!$obj || !property_exists($obj, $index)) {
      return;
    } elseif ((bool)$obj->$index) {
      echo $ifTrue;
    } else {
      echo $ifFalse;
    }
  }
}

function loadTemplate( $templateName, $v) {
  $e = function ($obj, $index = null, $toHTMLEntities = 1, $nl2br = 0) use (&$v) {
    $v->e($obj, $index, $toHTMLEntities, $nl2br);
  };

  $vr = function ($name) use (&$v) {
    return $v($name, false, false);
  };

  $a = function ($obj, $index, $ifTrue = null, $ifFalse = null) use (&$v) {
    return $v->a($obj, $index, $ifTrue, $ifFalse);
  };

  $t = function ($templateName) use (&$v) {
    return $v->other($templateName);
  };

  $avatar = function ($email) use (&$v) {
    echo $v->appi->getAvatarLink($email);
  };

  $s = function ($text, $w, $toHTMLEntities = 1, $nl2br = 0, $ending = "···") use (&$v) {
    $op = $toHTMLEntities ?
      $v->toHTMLEntities(mb_substr($text, 0, $w, "UTF-8"), $nl2br, false) :
      mb_substr($text, 0, $w, "UTF-8");
    echo mb_strlen($text, "UTF-8") > $w ? $op . $ending : $op;
  };
  include realpath( CORE_PATH . "Template/" . $templateName . ".php" );
}