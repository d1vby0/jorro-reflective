<?php

namespace Jorro\Reflective;

use Jorro\Reflective\Attributes\ResolveFirst;
use Psr\Container\ContainerInterface;

/**
 * Base class for factory method classes with dependency injection.
 */
abstract class ReflectiveFactory
{
    /**
     * @param  ContainerInterface  $container  Dependency injection container
     */
    final public function __construct(#[Resolve(optional: true)] private ContainerInterface|ReflectiveInjectorInterface $container = new ReflectiveInjector())
    {
    }

    /**
     * factory method
     *
     * @param  string  $class      Class name
     * @param  mixed   ...$values  Values to aguments
     *
     * @return mixed
     */
    protected function get(string $class, mixed ...$values): mixed
    {
        return $this->container->get($class, ...$values);
    }
}
