<?php

namespace Jorro\Reflective;

use Jorro\Reflective\Attributes\Inject;
use Jorro\Reflective\Attributes\Resolve;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
class ReflectiveInjector implements ReflectiveInjectorInterface
{
    protected array $circularReference = [];

    /**
     * @param  ContainerInterface  $container  Container
     */
    public function __construct(#[Resolve(optional: true)] protected ?ContainerInterface $container = null)
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
     * Specify the container
     *
     * @param  ContainerInterface|null  $container  Container
     *
     * @return void
     */
    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * Create Specified class instance
     *
     * @param  string  $class      Class name
     * @param  mixed   ...$values  Values to aguments
     */
    public function get(string $class, mixed ...$values): mixed
    {
        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new class("class not found : $class") extends \Exception implements NotFoundExceptionInterface {
            };
        }

        if ($constructor = $reflection->getConstructor()) {
            if (isset($this->circularReference[$class])) {
                $classes = implode(' , ', array_keys($this->circularReference));
                throw new class("circular reference class detected : $classes") extends \Exception implements ContainerExceptionInterface {
                };
            }
            $this->circularReference[$class] = true;
            try {
                $instance = new $class(... $this->injectParameters($constructor, $values));
            } finally {
                unset($this->circularReference[$class]);
            }
        } else {
            $instance = new $class();
        }
        if (!empty($reflection->getAttributes(Inject::class))) {
            $this->injectProperties($instance, $values);
        }
        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return $this->container->has($id) || class_exists($id);
    }

    /**
     * Inject to class proerties
     *
     * @param  object  $instance
     * @param  array   $values  Values to be assigned by property name / type name
     *
     * @return void
     */
    protected function injectProperties(object $instance, array $values): void
    {
        $reflection = new \ReflectionClass($instance);
        foreach ($reflection->getProperties() as $index => $property) {
            // Only inject not intialized property
            if ($property->isInitialized($instance)) {
                continue;
            }
            // Only inject #[Inject] specified property
            if (empty($property->getAttributes(Inject::class))) {
                continue;
            }
            $name = $property->getName();
            if (key_exists($name, $values)) {
                $property->setValue($instance, $values[$name]);
                continue;
            }
            $property->setValue($instance, $this->resolveValue($property, $values, false));
        }
    }

    /**
     * Inject to function arguments
     *
     * @param  \ReflectionFunctionAbstract  $function  ReflectionFunction
     * @param  array                        $values    Values to be assigned by parameter name / type name
     *
     * @return array Values to be injected
     */
    protected function injectParameters(\ReflectionFunctionAbstract $function, array $values): array
    {
        $parameters = [];
        foreach ($function->getParameters() as $index => $parameter) {
            $name = $parameter->getName();
            if (key_exists($name, $values)) {
                $parameters[$name] = $values[$name];
                continue;
            }
            $index = $parameter->getPosition();
            if (key_exists($index, $values)) {
                $parameters[$name] = $values[$index];
                continue;
            }
            $hasDefault = $parameter->isDefaultValueAvailable();
            if (!is_null($value = $this->resolveValue($parameter, $values, $hasDefault)) || ($hasDefault)) {
                $parameters[$name] = $value;
            }
        }
        return $parameters;
    }

    /**
     * Resolve value
     *
     * @param  \ReflectionParameter|\ReflectionProperty  $target
     * @param  array                                     $values  Values to be assigned by type name
     * @param  bool                                      $hasDefault
     *
     * @return mixed Value to be injected
     */
    protected function resolveValue(\ReflectionParameter|\ReflectionProperty $target, array $values, bool $hasDefault): mixed
    {
        $value = null;
        $resolveOption = ($target->getAttributes(Resolve::class)[0] ?? null)?->newInstance();
        if ($hasDefault) {
            // if default value available
            $value = $target->getDefaultValue();
            if (!$resolveOption?->optional) {
                // and not specified #[Resolve(optional: true)], use default value
                return $value;
            }
        }
        $resolveId = $resolveOption?->id;
        $resolveValues = $resolveOption?->values ?? [];
        if ($resolveId) {
            return $this->container->get($resolveId, ... $resolveValues);
        }
        $type = $target->getType();
        if (($type instanceof \ReflectionNamedType) && (!$type->isBuiltin())) {
            $typeName = $type->getName();
            return $values[$typeName] ?? $this->container->get($typeName, ... $resolveValues);
        } elseif ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $one) {
                if (!$one->isBuiltin()) {
                    try {
                        $typeName = $one->getName();
                        return $values[$typeName] ?? $this->container->get($typeName, ... $resolveValues);
                    } catch (NotFoundExceptionInterface $e) {
                    }
                }
            }
            if ((!$hasDefault) && (!$type->allowsNull())) {
                $classes = [];
                foreach ($type->getTypes() as $one) {
                    if (!$one->isBuiltin()) {
                        $classes[] = $one->getName();
                    }
                }
                $classes = implode(',', $classes);
                throw new class("union type classes not found: $classes") extends \Exception implements NotFoundExceptionInterface {
                };
            }
        }
        return $value;
    }

    /**
     * Invoke funciton
     *
     * @param  string|\Closure  $function        Function name
     * @param  mixed            ...$values       Values to aguments
     * @param  bool             $checkAttribute  Check function has Inject attribute
     *
     * @return mixed Return value of function
     */
    public function invokeFunction(string|\Closure $function, array $values, bool $checkAttribute = false)
    {
        $reflection = new \ReflectionFunction($function);
        if ((!$checkAttribute) || (!empty($reflection->getAttributes(Inject::class)))) {
            return $function(...$this->injectParameters($reflection, $values));
        } else {
            return $function(... $values);
        }
    }

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
    public function invokeMethod(object|string $instance, string $method, array $values, bool $checkAttribute = false): mixed
    {
        $reflection = new \ReflectionMethod($instance, $method);
        if ((!$checkAttribute) || (!empty($reflection->getAttributes(Inject::class)))) {
            $values = $this->injectParameters($reflection, $values);
        }
        if ($reflection->isStatic()) {
            return $instance::$method(...$values);
        } else {
            if (!is_object($instance)) {
                $instance = $this->container->get($instance);
            }
            return $instance->$method(...$values);
        }
    }

}
