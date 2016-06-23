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
use ResTrans\Core\Lang;

class InternalError extends ControllerConventions {

  protected $modelSilent = true;

  public function getIndex( $exception ) {
    $view = $this->view;

    $exceptionClass = (array) explode( "\\", get_class( $exception ) );
    $exceptionClass = array_pop( $exceptionClass );

    $params["cause"] = Lang::get($exception->getMessage());
    $params["exception_class"] = $exceptionClass;
    $params["trace"] = array_reverse( $exception->getTrace() );

    if ( $exceptionClass === "RouteResolveException" )
      header( $_SERVER["SERVER_PROTOCOL"] . " 404 Not Found" );

    $view
      ->setArray("ERROR", $params)
      ->setValue("ERROR.show_backtrace", $this->appi->config["exception_backtrace"])
      ->init( "InternalError" );
  }

}