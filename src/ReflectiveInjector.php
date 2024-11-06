<?php

namespace Jorro\Reflective;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
class ReflectiveInjector implements ReflectiveInjectorInterface
{
    /**
     * @param  ContainerInterface|null  $container  Container
     */
    public function __construct(protected ?ContainerInterface $container = null)
    {
        $this->container ??= $this;
        $this->boot();
    }

    /**
     * @param  ContainerInterface|null  $container  Container
     *
     * @return void
     */
    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container ?? $this;
    }

    /**
     * Call Bootstrap methods
     *
     * @return void
     */
    protected function boot(): void
    {
        $bootstraps = [];
        foreach (new \ReflectionClass($this)->getMethods() as $method) {
            foreach ($method->getAttributes(ReflectiveInjectorBootstrap::class) as $attribute) {
                $bootstraps[$method->getName()] = $attribute->newInstance()->priority;
            }
        }
        arsort($bootstraps);
        foreach (array_keys($bootstraps) as $method) {
            $this->{$method}();
        };
    }

    /**
     * Create instance
     *
     * @param  string  $class      Class name
     * @param  mixed   ...$values  Values to aguments
     */
    public function get(string $class, mixed ...$values): mixed
    {
        if (!class_exists($class)) {
            throw new class("class not found : $class") extends \Exception implements NotFoundExceptionInterface {
            };
        }
        if (method_exists($class, '__construct')) {
            return new $class(... $this->injectParameters(new \ReflectionMethod($class, '__construct'), $values));
        } else {
            return new $class();
        }
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return class_exists($id);
    }

    /**
     * @param  object  $instance
     * @param  array   $values  Values to be assigned by property name / type name
     *
     * @return void
     */
    protected function injectProperty(object $instance, string|\ReflectionProperty $property, array $values = [], bool $forceOverwrite = true): \ReflectionProperty
    {
        if (is_string($property)) {
            $property = new \ReflectionProperty($instance, $property);
        }
        if (($forceOverwrite) || (!$property->isInitialized($instance))) {
            $name = $property->getName();
            if (key_exists($name, $values)) {
                $property->setValue($instance, $values[$name]);
            } else {
                $property->setValue($instance, $this->resolveValue($property, $values, false));
            }
        }
        return $property;
    }

    /**
     * @param  \ReflectionFunctionAbstract  $function
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
     * @param  \ReflectionParameter|\ReflectionProperty  $target
     * @param  array                                     $values  Values to be assigned by type name
     * @param  bool                                      $hasDefault
     *
     * @return mixed Value to be injected
     */
    protected function resolveValue(\ReflectionParameter|\ReflectionProperty $target, array $values, bool $hasDefault): mixed
    {
        if ($hasDefault) {
            return $target->getDefaultValue();
        }
        $type = $target->getType();
        if (($type instanceof \ReflectionNamedType) && (!$type->isBuiltin())) {
            $typeName = $type->getName();
            return $values[$typeName] ?? $this->container->get($typeName);
        } elseif ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $one) {
                if (!$one->isBuiltin()) {
                    try {
                        $typeName = $one->getName();
                        return $values[$typeName] ?? $this->container->get($typeName);
                    } catch (NotFoundExceptionInterface $e) {
                    }
                }
            }
            if ((!$type->allowsNull())) {
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
     * @param  string|\Closure  $function   Function name
     * @param  mixed            ...$values  Values to aguments
     *
     * @return mixed Return value of function
     */
    public function invokeFunction(string|\Closure $function, mixed ...$values)
    {
        $reflection = new \ReflectionFunction($function);
        return $reflection->invokeArgs($this->injectParameters($reflection, $values));
    }

    /**
     * @param  object|string  $instance   Instance or class name
     * @param  string         $method     Method name
     * @param  mixed          ...$values  Values to aguments
     *
     * @return mixed Return value of method
     */
    public function invokeMethod(object|string $instance, string $method, mixed ...$values): mixed
    {
        $reflection = new \ReflectionMethod($instance, $method);
        if ($reflection->isStatic()) {
            return $reflection->invokeArgs(null, $this->injectParameters($reflection, $values));
        } else {
            if (!is_object($instance)) {
                $instance = $this->container->get($instance);
            }
            return $reflection->invokeArgs($instance, $this->injectParameters($reflection, $values));
        }
    }
}
