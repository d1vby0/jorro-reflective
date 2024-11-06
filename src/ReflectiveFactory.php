<?php

namespace Jorro\Reflective;

use Jorro\Contracts\Reflective\Attributes\ResolveFirst;
use Psr\Container\ContainerInterface;

abstract class ReflectiveFactory
{
    /**
     * コンストラクタ
     *
     * @param ContainerInterface|ReflectiveInjector $injector
     */
    final public function __construct(#[ResolveFirst] protected ContainerInterface|ReflectiveInjectorInterface $injector = new ReflectiveInjector())
    {
    }

    /**
     * インスタンスの生成
     *
     * @param string $class
     * @param mixed ...$values
     * @return mixed
     */
    protected function get(string $class, mixed ...$values): mixed
    {
        return $this->injector->get($class, ...$values);
    }
}
