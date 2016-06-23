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

class Query {

  protected $sql = "";

  protected $data = [];

  protected $database;

  protected $disableExecutionCheck = false;

  protected $rowsPromise = false;

  protected $rows;

  protected $throw;

  protected $exceptionCode;

  protected $exceptionClass;

  protected $ret;

  public $appi;

  const FETCH = 1;

  const FETCH_ALL = 2;

  const TORF = 3;

  const FETCH_COLUMN = 4;

  public function __construct (App $app, Database $database) {
    $this->database = $database;
    $this->appi = $app;
  }

  public function setSql ($sql) {
    $this->sql = $sql;
    return $this;
  }

  public function getSql () {
    return $this->sql;
  }

  public function bindData (array $data) {
    $this->data = $data;
    return $this;
  }

  public function getData () {
    return $this->data;
  }

  public function disableExecutionCheck ($disable = true) {
    $this->disableExecutionCheck = $disable;
    return $this;
  }

  public function rowsPromise ($rows = null) {
    $this->rowsPromise = true;
    $this->rows = $rows;
    return $this;
  }

  public function throwException (
    $throw = null,
    $exceptionCode = 400,
    $exceptionClass = null
  ) {
    $this->throw = $throw;
    $this->exceptionCode = $exceptionCode;
    $this->exceptionClass = is_null($exceptionClass) ? "ResTrans\Core\CommonException" : $exceptionClass;
    return $this;
  }

  public function ret ($mode = self::FETCH) {
    $this->ret = $mode;
    return $this;
  }

  public function select () {
    $this->sql .= "SELECT ";
    $sql = &$this->sql;
    $args = func_get_args();
    array_walk($args, function ($f) use (&$sql) {
      $sql .= $f . ", ";
    });
    $sql = substr($sql, 0, -2) . " ";
    return $this;
  }

  public function from () {
    $sql = &$this->sql;
    $sql .= "FROM ";
    $dbPrefix = $this->database->prefix;
    $args = func_get_args();
    array_walk($args, function ($fr) use (&$sql, $dbPrefix) {
      $sql .= $dbPrefix . $fr . ", ";
    });
    $sql = substr($sql, 0, -2) . " ";
    return $this;
  }

  public function where ($where) {
    $this->sql .= "WHERE " . $where . " ";
    return $this;
  }

  public function orderBy ($field, $descOrAsc = "ASC") {
    $this->sql .= "ORDER BY " . $field . " " . $descOrAsc . " ";
    return $this;
  }

  public function limit ($num = true) {
    $this->sql .= "LIMIT ?";
    $num ? ($this->sql .= ", ?") : ($this->sql .= " ");
    return $this;
  }

  public function insertInto ($table) {
    $table = $this->database->prefix . $table;
    $this->sql .= "INSERT INTO {$table} ";
    return $this;
  }

  public function insertFields () {
    $sql = &$this->sql;
    $sql .= "(";
    $args = func_get_args();
    array_walk($args, function ($f) use (&$sql) {
      $sql .= $f . ", ";
    });
    $sql = substr($sql, 0, -2) . ")";
    $placeholder = implode( ",", array_fill( 0, func_num_args(), "?" ) );
    $this->sql .= "VALUES ({$placeholder}) ";
    return $this;
  }

  public function delete ($from) {
    $this->sql .= "DELETE FROM {$this->database->prefix}{$from} ";
    return $this;
  }

  public function update ($table) {
    $this->sql .= "UPDATE {$this->database->prefix}{$table} SET ";
    return $this;
  }

  public function join ($table) {
    $this->sql .= "JOIN {$this->database->prefix}{$table} ";
    return $this;
  }

  public function on ($on) {
    $this->sql .= "ON {$on} ";
    return $this;
  }

  public function set () {
    $sql = &$this->sql;
    $args = func_get_args();
    array_walk($args, function ($s) use (&$sql) {
      $sql .= $s . ", ";
    });
    $sql = substr($sql, 0, -2) . " ";
    return $this;
  }

  public function execute () {
    $db = $this->database;
    try {
      $prepare = $db->pdoPrepare($this->sql, $this->data);
      $exec = $prepare->execute();
      $rowCount = $prepare->rowCount();
      /** 执行状态检查 */
      if (!$this->disableExecutionCheck && !$exec) {
        throw new \Exception();
      }
      /** 行数检查 */
      if ($this->rowsPromise) {
        if (!is_null($this->rows) && !$rowCount) {
          throw new \Exception();
        } else if (!is_null($this->rows) && $this->rows !== $rowCount) {
          throw new \Exception();
        }
      }
      /** 计数字段检查 */
      if ($this->ret === self::FETCH) {
        return $prepare->fetch();
      } else if ($this->ret === self::FETCH_ALL) {
        return $prepare->fetchAll();
      } else if ($this->ret === self::TORF) {
        return $exec && $rowCount;
      } else if ($this->ret === self::FETCH_COLUMN) {
        return $prepare->fetchColumn();
      }
    } catch (\Exception $e) {
      if (!is_null($this->throw)){
       throw new $this->exceptionClass($this->throw, $this->exceptionCode, $e);
     }
     return false;
    }
  }
}