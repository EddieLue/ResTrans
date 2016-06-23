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

class ResTransCoreException extends \Exception {
  protected $message = "unknown_error_occurred";
}

class CommonException extends ResTransCoreException {}

class RouteResolveException extends ResTransCoreException {
  protected $message = "page_not_found";
}

class DatabaseConnectionException extends ResTransCoreException {
  protected $message = "db_connection_error";
}

class UnknownException extends ResTransCoreException {
  protected $message = "unknown_error_occurred";
}

class ApiException extends ResTransCoreException {

  protected $line = 0;

  protected $result = [];

  public function lastParsedLine ($line = null) {
    return is_null($line) ? $this->line : ($this->line = $line);
  }

  public function lastResult ($result = []) {
    return (!count($result)) ? $this->result : ($this->result = $result);
  }
}

class ApiResourceExhaustedException extends ResTransCoreException {
  protected $message = "api_resource_exhausted";
}