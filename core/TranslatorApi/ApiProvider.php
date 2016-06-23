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

class ApiProvider {

  public $appi;

  public $apiConfig;

  public $pos = 0;

  public $apiInstances = [];

  public $apiConfigNum;

  public function __construct (Core\App $app) {
    $this->appi = $app;
    $this->apiConfig = $app->config["api"];
    $this->apiConfigNum = count($this->apiConfig);
  }

  public function getApi () {
    $pos = $this->pos;
    if (!isset($this->apiConfig[$pos])) throw new Core\ApiResourceExhaustedException();

    $apiConfig = $this->apiConfig[$pos];
    $apiName = $apiConfig["api_name"];
    $classPrefix = "ResTrans\\Core\\TranslatorApi\\";
    $fullName = $classPrefix . $apiName;
    if ( isset($this->apiInstances[$pos]) && get_class($this->apiInstances[$pos]) === $fullName ) {
      return $this->apiInstances[$pos];
    }

    if (class_exists($fullName)) {
      return $apiInstances[$pos] = (new $fullName($this->appi))->setConfig($apiConfig)->init();
    } else throw new Core\CommonException("api_config_error");
  }

  public function isExhausted ($pos) {
    return ($pos + 1) > $this->apiConfigNum;
  }

  public function nextApi () {
    $this->pos++;
    if ($this->isExhausted($this->pos)) throw new Core\ApiResourceExhaustedException();
    try {
      return $this->getApi();
    } catch (\Exception $e) {
      return $this->nextApi();
    }
  }

}