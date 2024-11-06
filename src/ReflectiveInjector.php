<?php

namespace Jorro\Reflective;

use Jorro\Reflective\Attributes\ResolveFirst;
use Jorro\Reflective\Attributes\ResolveBy;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ReflectiveInjector implements ReflectiveInjectorInterface
{
    /**
     * コンストラクタ
     *
     * @param \Psr\Container\ContainerInterface $container コンテナ
     */
    public function __construct(#[ResolveFirst] protected ?ContainerInterface $container = null)
    {
        $this->container ??= new class($this) implements ContainerInterface {
            public function __construct(protected $injector)
            {
            }

            public function get(string $id, ...$values): mixed
            {
                if (!$this->has($id)) {
                    throw new class("class not found : $id") extends \Exception implements NotFoundExceptionInterface {
                    };
                }

                return $this->injector->get($id, ...$values);
            }

            public function has(string $id): bool
            {
                return class_exists($id);
            }
        };
    }

    /**
     * @param \Psr\Container\ContainerInterface|null $container
     * @return void
     */
    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * インスタンスの生成
     *
     * @param string $class クラス名
     * @param mixed ...$values 利用値
     * @return object 生成されたインスタンス
     */
    public function get(string $class, mixed ...$values): object
    {
        $reflection = new \ReflectionClass($class);

        return ($constructor = $reflection->getConstructor()) ? new $class(
            ...
            $this->resolveParameters($constructor, ...$values)
        ) : new $class();
    }

    /**
     * パラメータの解決
     *
     * @param \ReflectionFunctionAbstract $function ReflectionFunctionAbstract
     * @param mixed ...$values 利用する値
     * @return array 注入されたパラメータ
     */
    public function resolveParameters(\ReflectionFunctionAbstract $function, mixed ...$values): array
    {
        $parameters = [];

        foreach ($function->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (isset($values[$name])) {
                $parameters[$name] = $values[$name];
                continue;
            }
            $allowNotFound = true;
            if ($parameter->isDefaultValueAvailable()) {
                // デフォルトパラメータが指定されており
                $parameters[$name] = $parameter->getDefaultValue();
                if (empty($resolveFirst = $parameter->getAttributes(ResolveFirst::class))) {
                    //　#[ResolveFirst] が指定されていなければ、デフォルトパラメータを利用
                    continue;
                }
                $allowNotFound = $resolveFirst[0]->newInstance()->allowNotFound;
            }
            // #[ResolveBy] 属性による、クラスの指定もしくは、初期化パラメータの指定がある場合
            $resolveId = null;
            $resolveValues = null;
            foreach ($parameter->getAttributes(ResolveBy::class, \ReflectionAttribute::IS_INSTANCEOF) as $resolveParameter) {
                $resolveParameter = $resolveParameter->newInstance();
                $resolveId ??= $resolveParameter->getId();
                $resolveValues ??= $resolveParameter->getValues();
            }
            $resolveValues ??= [];
            if ($resolveId) {
                $parameters[$name] = $this->container->get($resolveId, ... $resolveValues);
                continue;
            }
            $type = $parameter->getType();
            // 依存注入
            if (($type instanceof \ReflectionNamedType) && (!$type->isBuiltin())) {
                try {
                    $parameters[$name] = $this->container->get($type->getName(), ... $resolveValues);
                } catch (\Throwable $e) {
                    if (!$allowNotFound) {
                        throw $e;
                    }
                }
            } elseif ($type instanceof \ReflectionUnionType) {
                foreach ($type->getTypes() as $one) {
                    if (!$one->isBuiltin()) {
                        $parameters[$name] = $this->container->get($one->getName(), ... $resolveValues);
                    }
                }
                if ($type->allowsNull()) {
                    throw $notfound;
                }
            }
        }

        return $parameters;
    }

    /**
     * @param string|\Closure $function 関数名
     * @param mixed ...$values 利用値
     * @return mixed 関数の戻り値
     */
    public function callFunction(string|\Closure $function, mixed ...$values): mixed
    {
        return $function(...$this->resolveParameters(new \ReflectionFunction($function), ...$values));
    }

    /**
     * メソッドの実行
     *
     * @param object $instance インスタンス
     * @param string $method メソッド名
     * @param mixed ...$values 利用値
     * @return mixed メソッドの戻り値
     */
    public function callMethod(object $instance, string $method, mixed ...$values): mixed
    {
        return $instance->$method(...$this->resolveParameters(new \ReflectionMethod($instance, $method), ...$values));
    }
}
