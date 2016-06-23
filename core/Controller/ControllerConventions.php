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

abstract class ControllerConventions {

  protected $appi;

  protected $model;

  protected $view;

  protected $modelSilent = false;

  private $addToken = true;

  public $injectInstances = [];

  public function __construct( Core\App $app ) {

    $this->appi = $app;
    $this->view = new Core\View( $app );
    $this->route = $app->route;
    $this->event = $app->event;

    $this->modelSilent || $this->model( $this->relationModel );
    $this->injectInstances = [
      $this->route,
      $this->appi,
      $this->event,
      $this->model,
      $this->view
    ];
  }

  public function model( $relationModel = null ) {

    $relationModel = is_null( $relationModel ) ? $this->relationModel : $relationModel;
    if ( is_null( $relationModel ) ) return false;

    $className = "ResTrans\\Core\\Model\\" . $relationModel;
    if ( isset( $this->model ) && $className === get_class( $this->model ) ) {
      return $this->model;
    }

    return $this->model = new $className( $this->appi );
  }


  /**
   * 控制器方法的依赖注入器
   * 这个方法反射需要注入的方法并分析其需要的对象并注入，
   * 这样控制器方法就可以免除冗余的创建（接收）对象语句。
   * @param  string $injectMethod 要依赖注入的方法名称
   * @param  array  $params       由 Route 对象的方法产生的参数数组
   * @return void                 移交给 invoke 方法, 没有返回值
   */
  public function inject( $injectMethod, array $params ) {

    try {

      $methodReflection = new \ReflectionMethod( $this, $injectMethod );
      $parametersReflection = $methodReflection->getParameters();
      if ( ! $methodReflection->isPublic() ||
             $methodReflection->isInternal() ||
             $methodReflection->isStatic() ||
             $methodReflection->isConstructor() ) {
        throw new \Exception();
      }

      // 根据反射出来的结果注入
      foreach ( $parametersReflection as $param => $paramValue ) {

        array_walk( $this->injectInstances, function ( $inst ) use ( &$params, &$param, &$paramValue ) {
          if ( ! $paramValue->getClass() ||
               ! $paramValue->getClass()->isInstance( (object)$inst ) ) return;

          array_splice( $params, $param, 0, [ $inst ] );
        } );

      }

    } catch ( \Exception $e ) {
      throw new Core\RouteResolveException();
    }

    // 移交给 invoke 方法处理
    $this->invoke( $injectMethod, $params );
  }

  public function invoke( $injectMethod, array $params ) {

    try {
      call_user_func_array( [$this, $injectMethod], $params );
    } catch ( Core\RouteResolveException $e ) {
      throw $e;
    } catch ( Core\CommonException $e ) {
      $this->event->trigger("controllerError", $e);
      $this->route->setResponseCode( $e->getCode() ? $e->getCode() : 400 );
      $this->route->isJsonRequest() &&
      $this->route->jsonReturn( $this->status( $e->getMessage(), $this->addToken ) );
    } catch ( \Exception $e ) {
      throw new Core\UnknownException($e->getMessage(), $e->getCode(), $e);
    }
  }

  public function addTokenOnError($trueOrFalse = null) {
    if ( $trueOrFalse === null ) return $this->addToken;
    if ( is_bool($trueOrFalse) ) $this->addToken = $trueOrFalse;
  }

  public function status( $status, $addToken = true, array $attributes = [] ){

    $returnStatus["status_short"] = $status;
    $returnStatus["status_detail"] = Lang::get($status);

    $addToken && ($returnStatus["token"] = Core\Token::instance( $this->appi )->setToken());

    return array_merge($returnStatus, $attributes);
  }

}