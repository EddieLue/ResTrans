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

abstract class ModelConventions {

  public $dbSlient = false;

  private  $dbi;

  private  $pdoi;

  public $appi;

  protected $invalid = [];

  public $data = [];

  public $modelMethod;

  public $lastInsertId;

  protected $otherModels = [];

  const FIRST = "FIRST";

  const LAST = "LAST";

  public function __construct( Core\App $app ) {

    $this->appi = $app;
    // 自动加载
    $this->dbSlient || $this->databaseConnect();
  }

  private function databaseConnect() {

    //连接 + 获得PDO实例
    $this->dbi = Core\Database::instance( $this->appi );
    $this->pdoi = $this->dbi->connect()->pdo();

    return $this;
  }

  public function db() {
    return isset( $this->dbi ) ? $this->dbi : $this->databaseConnect()->dbi;
  }

  public function pdo() {
    return isset( $this->pdoi ) ? $this->pdoi : $this->databaseConnect()->pdoi;
  }

  public function setLastInsertId() {

    try {
      $id = (int)$this->pdo()->lastInsertId();
      if ( $id <= 0 ) return false;
      return $this->lastInsertId = $id;
    } catch ( \Exception $e ) {
      return false;
    }
  }

  public function getOtherModel( $otherModelName ) {

    $modelNameWithNS = "ResTrans\\Core\\Model\\" . $otherModelName;

    foreach ( $this->otherModels as $model ) {
      if ( get_class( $model ) === $modelNameWithNS && $model instanceof self ) return $model;
    }

    return $this->otherModels[] = new $modelNameWithNS( $this->appi );
  }

  public function invalid( $index ) {

    $invalid = array_values( $this->invalid );

    $index = (int)$index;
    if ( $index === self::FIRST ) $index = 0;
    if ( $index === self::LAST ) $index = $invalid[ count( $invalid ) - 1 ];

    return isset( $invalid[$index] ) ? $invalid[$index] : false;
  }

  public function lastInvalid() {
    return $this->invalid( self::LAST );
  }

  public function firstInvalid() {
    return $this->invalid( self::FIRST );
  }

  public function isEmpty( $value, $unexpected = null, $invalid = null ) {
    if ( empty( $value ) ) {
      if ( ! is_null( $invalid ) ) $this->putInvalid( $invalid );
      if ( ! is_null( $unexpected ) ) return $unexpected;
    }

    return $value;
  }

  public function putInvalid( $invalid ) {
    return $this->invalid[] = $invalid;
  }

  public function isValid( $value, $expects, $unexpected = null, $invalid = null ) {

    $corrent = false;
    if ( is_array( $expects ) ) {
      array_walk( $expects, function ( $expect ) use ( $value, &$corrent ) {
        ($value === $expect) && ($corrent = true);
      } );
    } else {
      $expect = (string)$expects;
      $corrent = ($value !== $expect);
    }

    if ( ! $corrent ) {
      ( ! is_null( $invalid ) ) && $this->putInvalid( $invalid );
      return is_null( $unexpected ) ? $value : $unexpected;
    }

    return $value;
  }

  public function isBool( $value, $unexpected, $convertToInt = false, $invalid = null ) {
    if ( ! is_bool( $unexpected ) || ! is_bool( $convertToInt ) ) return;
    if ( ! is_bool( $value ) ) {
      is_null( $invalid ) || $this->putInvalid( $invalid );
      return $convertToInt ? (int)$unexpected : $unexpected;
    }

    return $convertToInt ? (int)$value : $value;
  }

  public function set( $modelMethod, $data = [] ) {

    $this->clearInvalid();

    $setModelMethod = "set" . $modelMethod;
    if ( ! is_callable( [ $this, $setModelMethod ] ) ) return false;

    $this->modelMethod = $modelMethod;

    $data = $this->$setModelMethod( $data );
    is_array( $data ) ? $this->data = $data : null;

    return $data;
  }

  public function save( $modelMethod = null, $data = [] ) {

    if ( is_null( $modelMethod ) && is_null( $this->modelMethod ) ) return false;
    $modelMethod = is_null( $modelMethod ) ? $this->modelMethod : $modelMethod;

    if ( ! is_array( $data ) || empty( $data ) ) {
      $data = is_array( $this->data ) ? $this->data : [];
    }

    $modelMethod = "save" . $modelMethod;
    if ( ! is_callable( [ $this, $modelMethod ] ) ) return false;
    $save = $this->$modelMethod( $this->db(), $data );

    return $save;
  }

  public function clear() {
    $this->modelMethod = "";
    $this->data = [];

    return $this;
  }

  public function clearInvalid() {
    $this->invalid = [];
    return $this;
  }

  public function exists ($tableName, $fieldName, $id, $throwException = false, $throw = "") {
    $db = $this->db();
    $prepareSQL = $db->select($tableName, "count(1)", $db->condition($fieldName));

    try {
      $exists = $db->pdoPrepare($prepareSQL, ["1#int" => $id]);
      $exists->execute();
      return !! $exists->fetchColumn();
    } catch ( \PDOException $e ) {
      if ($throwException) throw new Core\CommonException($throw);
      return false;
    }
  }

  public function __invoke($m) {
    return $this->getOtherModel($m);
  }
}