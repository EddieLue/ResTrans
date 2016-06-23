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

namespace ResTrans\Core\TranslatorApi;
use ResTrans\Core;

abstract class ApiConventions {

  public $appi;

  public $translatorName;

  abstract function translate ($lines);

  abstract function init ();

  public function __construct (Core\App $app) {
    $this->appi = $app;
  }

  public function setConfig ($config) {
    $this->config = $config;
    return $this;
  }

  public function setTargetLanguage ($targetLanguage) {
    $this->targetLanguage = $targetLanguage;
    return $this;
  }

  public function setOriginalLanguage ($originalLanguage) {
    $this->originalLanguage = $originalLanguage;
    return $this;
  }
}