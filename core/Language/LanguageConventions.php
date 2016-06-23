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

namespace ResTrans\Core\Language;

abstract class LanguageConventions {
  public function __get( $index ) {
    return array_key_exists( $index, $this->lang ) ? $this->lang[$index] : null ;
  }

  public function __isset( $index ) {
    return isset( $this->lang[$index] );
  }
}