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

class Event {

  protected $listens = [];

  private $appi;

  public function __construct( App $app ) {
    $this->appi = $app;
  }

  public function on($ev, callable $callback, $once = false) {
      return (bool)$this->listens[$ev][] = ["callback" => $callback, "once" => $once];
  }

  public function once($ev, callable $callback) {
    return $this->on($ev, $callback, true);
  }

  public function remove($ev, $index = null) {
    if (is_null($index)) {
      unset($this->listens[$ev]);
    } else {
      unset($this->listens[$ev][$index]);
    }
  }

  public function trigger() {
    $callbackReturn = [];
    $args = func_get_args();
    $argsNum = func_num_args();

    switch ($argsNum) {
      case 0:
        return false;
      break;
      default:
        $ev = (string)func_get_arg(0);
        array_shift($args);
      break;
    }

    if( ! isset($this->listens[$ev]) ) return false;
    $evCount = count( $this->listens[$ev] );

    foreach ( $this->listens[$ev] as $index => $callback ) {
      $callbackReturn[$index] = call_user_func_array( $callback["callback"], $args );
      $callback["once"] && $this->remove( $ev, $index );
    }

    return ( 1 === $evCount ) ? $callbackReturn[0] : $callbackReturn;
  }

}