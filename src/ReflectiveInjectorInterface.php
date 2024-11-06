<?php

namespace Jorro\Reflective;

use Psr\Container\ContainerInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
interface ReflectiveInjectorInterface extends ContainerInterface
{
    /**
     * Specify the container
     *
     * @param  ContainerInterface|null  $container  Container
     *
     * @return void
     */
    public function setContainer(?ContainerInterface $container = null): void;

    /**
     * Create Specified class instance
     *
     * @param  string  $class      Class name
     * @param  mixed   ...$values  Values to aguments
     */
    public function get(string $class, mixed ...$values): mixed;

    /**
     * Invoke funciton
     *
     * @param  string|\Closure  $function        Function name
     * @param  mixed            ...$values       Values to aguments
     * @param  bool             $checkAttribute  Check function has Inject attribute
     *
     * @return mixed Return value of function
     */
    public function invokeFunction(string|\Closure $function, array $values, bool $checkAttribute = false);

    /**
     * Invoke class method
     *
     * @param  object|string  $instance        Instance or class name
     * @param  string         $method          Method name
     * @param  array          $values          Values to aguments
     * @param  bool           $checkAttribute  Check method has Inject attribute
     *
     * @return mixed Return value of method
     */
    public function invokeMethod(object|string $instance, string $method, array $values, bool $checkAttribute = false): mixed;
}