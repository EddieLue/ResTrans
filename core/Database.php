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

class Database {

  protected $appi;

  protected $dsn;

  protected $pdoi;

  protected $dbType;

  public $prefix;

  public $transactionActivated = false;

  protected $supportedDatabaseType = array( "Mysql" );

  private static $_instance;

  private function __construct( App $app ) {
    $this->appi = $app;
    $this->dsn = $dsn = $app->config["database"]["dsn"];
    $this->dbType = $app->config["database"]["type"];
    $this->prefix = $app->config["database"]["table_prefix"];
  }

  public static function instance( App $app ) {
    if ( self::$_instance instanceof self ) return self::$_instance;
    self::$_instance = new self( $app );
    return self::$_instance;
  }

  public function __clone(){}

  public function connect() {

    // 确定是否拥有 PDO 实例
    if ( isset( $this->pdoi ) ) return $this;

    try {
      
      // 获得 PDO 实例
      $this->pdoi = new \PDO(
        $this->dsn,
        $this->appi->config["database"]["user"],
        $this->appi->config["database"]["pass"]
      );
      // 设置
      $this->pdoi->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ );
      $this->pdoi->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
      $this->pdoi->setAttribute( \PDO::ATTR_EMULATE_PREPARES, false );
      // 返回自身
      return $this;
    } catch ( \PDOException $e ) {
      // 抛出预定义的数据库连接异常
      throw new DatabaseConnectionException();
    }

  }

  public function databaseType() {
    return $this->dbType;
  }

  public function pdo() {
    return isset( $this->pdoi ) ? $this->pdoi : $this->connect()->pdoi ;
  }

  public function TSBegin() {
    if ( ! $this->transactionActivated ) {
      return $this->transactionActivated = $this->pdo()->beginTransaction();
    }
    return $this->transactionActivated;
  }

  public function TSCommit() {
    if ( $this->transactionActivated ) {
      $this->transactionActivated = false;
      return $this->pdo()->commit();
    }
  }

  public function TSRollBack() {
    if ( $this->transactionActivated ) {
      $this->transactionActivated = false;
      return $this->pdo()->rollBack();
    }
  }

  public function mergePrefix( $table ) {
    return "`{$this->prefix}{$table}`";
  }

  public function select( $table,
                          $fields,
                          $where = null,
                          $orderBy = null,
                          $limitStart = null,
                          $limitNum = null ) {

    $table = $this->mergePrefix( $table );

    $selectSQL = "SELECT {$fields} FROM {$table}";
    $whereSQL = "WHERE {$where}";
    $orderBySQL = "{$orderBy}";
    $limitSQLShort = "limit {$limitStart}";
    $limitSQL = "limit {$limitStart},{$limitNum}";

    $resultSql = "{$selectSQL}";
    ( ! is_null( $where ) ) ? $resultSql .= " {$whereSQL}" : null;
    ( ! is_null( $orderBy ) ) ? $resultSql .= " {$orderBySQL}" : null;
    ( ! is_null( $limitStart ) && is_null( $limitNum ) ) ? $resultSql .= " {$limitSQLShort}" : null;
    ( ! is_null( $limitStart ) && ! is_null( $limitNum ) ) ? $resultSql .= " {$limitSQL}" : null;

    return $resultSql;

  }

  public function orderBy( $field, $condition = "ASC" ) {
    return "ORDER BY `{$field}` {$condition}";
  }

  public function condition( $field, $valueName = null ) {
    return ( ! is_null( $valueName ) ) ? "`{$field}` = :{$valueName}" : "`{$field}` = ?";
  }

  public function fieldList( array $fields ) {
    return "`" . implode( "`, `", array_values( $fields ) ) . "`";
  }

  public function field( $field ) {
    return $this->fieldList( [ $field ] );
  }

  public function placeholders( array $placeholders ) {
    return ":" . implode( ", :", $placeholders );
  }

  public function insert( $table, $placeholders, $fields = null ) {
    $table = $this->mergePrefix($table);

    if ( is_numeric( $placeholders ) ) {
      $placeholders = implode( ",", array_fill( 0, $placeholders, "?" ) );
    }

    if ( is_null( $fields ) ) return "INSERT INTO {$table} VALUES ({$placeholders})";
    return "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
  }

  public function delete( $table, $where ) {
    $table = $this->mergePrefix( $table );
    $deleteSQL = "DELETE FROM {$table}";
    $whereSQL = " WHERE {$where}";

    (!is_null( $where )) && $deleteSQL .= $whereSQL;

    return $deleteSQL;
  }

  public function update($table, $set, $where = null) {

    $table = $this->mergePrefix($table);

    $updateSQL = "UPDATE {$table} SET {$set}";
    $whereSQL = " WHERE {$where}";

    $resultSql = $updateSQL;
    ( ! is_null( $whereSQL ) ) && $resultSql .= $whereSQL;

    return $resultSql;
  }

  public function pdoPrepare( $statement, array $properties = [] ) {

    $prepare = $this->pdo()->prepare( $statement );
    $typeMap = [ "int"  => \PDO::PARAM_INT ,
                 "str"  => \PDO::PARAM_STR ,
                 "bool" => \PDO::PARAM_BOOL,
                 "null" => \PDO::PARAM_NULL ];

    foreach ( $properties as $nameType => $value ) {
      $nameTypeArray = explode( "#", $nameType );
      // 确定是否使用数字式参数名
      $index = is_numeric( $nameTypeArray[0] ) ? (int)$nameTypeArray[0] : ":{$nameTypeArray[0]}";
      $type = $typeMap[$nameTypeArray[1]];
      $prepare->bindValue( $index, $value, $type );
    }

    return $prepare;
  }

  public function query () {
    return new Query($this->appi, $this);
  }
}