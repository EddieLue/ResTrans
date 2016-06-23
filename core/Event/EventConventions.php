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

namespace ResTrans\Core\Event;
use ResTrans\Core;

abstract class EventConventions {

  public $appi;

  abstract public function registrar();

  public function __construct( Core\App $app ) {
    $this->appi = $app;
    $this->registrar();
  }

  protected function reflectionThisMethod( $methodName ) {

    $thisClass = new \ReflectionClass( $this );
    $thisMethod = $thisClass->getMethod( $methodName );

    if ( ! $thisMethod->isPublic() ) return false;

    return $thisMethod->getClosure( $this );
  }

  public function listen( $ev, $callback = null, $once = false ) {
    $thisClassName = explode( "\\", get_class( $this ) );
    $thisClassName = array_pop( $thisClassName );
    $evName = $thisClassName . ":" . $ev;
    $callback = $callback ? $callback : $ev;
    return $this->appi->event->on($evName, $this->reflectionThisMethod($callback), $once);
  }

  public function once( $ev, $callback ) {
    return $this->listen( $ev, $callback, true );
  }

  public function remove( $ev, $index = null ) {
    return $this->appi->event->remove( $ev, $index );
  }

  public function trigger() {
    return call_user_func_array([$this->appi->event,"trigger"], func_get_args());
  }

}