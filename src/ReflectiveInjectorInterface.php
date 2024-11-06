<?php

namespace Jorro\Reflective;

use Psr\Container\ContainerInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
interface ReflectiveInjectorInterface extends ContainerInterface
{
    /**
     * @param  ContainerInterface|null  $container  Container
     *
     * @return void
     */
    public function setContainer(?ContainerInterface $container): void;

    /**
     * Create instance
     *
     * @param  string  $class      Class name
     * @param  mixed   ...$values  Values to aguments
     */
    public function get(string $class, mixed ...$values): mixed;

    /**
     * @param  string|\Closure  $function   Function name
     * @param  mixed            ...$values  Values to aguments
     *
     * @return mixed Return value of function
     */
    public function invokeFunction(string|\Closure $function, mixed ...$values);

    /**
     * @param  object|string  $instance   Instance or class name
     * @param  string         $method     Method name
     * @param  mixed          ...$values  Values to aguments
     *
     * @return mixed Return value of method
     */
    public function invokeMethod(object|string $instance, string $method, mixed ...$values): mixed;
}