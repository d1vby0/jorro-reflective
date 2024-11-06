<?php

namespace Jorro\Reflective;

use Jorro\Bootstrap\BootstrapAwareTrait;
use Jorro\Reflective\Bootstrap\InjectBootstrap;
use Jorro\Reflective\Resolve\ResolveInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
class ReflectiveInjector implements ReflectiveInjectorInterface
{
    use BootstrapAwareTrait;
    /**
     * @param  ContainerInterface|null  $container  Container
     */
    public function __construct(protected ?ContainerInterface $container = null)
    {
        $this->container ??= $this;
        $this->bootstrap();
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
     * Create instance
     *
     * @param  string  $class      Class name
     * @param  mixed   ...$values  Values to aguments
     *
     * Note that values do not have to exact
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
    protected function injectProperty(object $instance, \ReflectionProperty $property, array $values, bool $forceOverwrite = true): void
    {
        if (($forceOverwrite) || (!$property->isInitialized($instance))) {
            $name = $property->getName();
            if (key_exists($name, $values)) {
                $property->setValue($instance, $values[$name]);
            } else {
                $property->setValue($instance, $this->resolveValue($property, false));
            }
        }
    }

    /**
     * @param  \ReflectionFunctionAbstract  $function
     * @param  array                        $values  Values to be assigned by parameter name / type name
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
            if (key_exists($index, $values)) {
                $parameters[$name] = $values[$index];
                continue;
            }
            $parameters[$name] = $this->resolveValue($parameter);
        }
        return $parameters;
    }

    /**
     * @param  \ReflectionParameter|\ReflectionProperty  $target
     *
     * @return mixed Value to be injected
     */
    protected function resolveValue(\ReflectionParameter|\ReflectionProperty $target): mixed
    {
        static $isResolveAttribute = [];
        static $emptyValues = [];
        if ($attributes = $target->getAttributes()) {
            foreach ($attributes as $attribute) {
                $name = $attribute->getName();
                if ($isResolveAttribute[$name] ??= is_subclass_of($name, ResolveInterface::class)) {
                    $resolveAttribute = $attribute->newInstance();
                    if ($target->isDefaultValueAvailable()) {
                        // if default value available
                        if (!$resolveAttribute->isOptional()) {
                            // and not specified #[Resolve(optional: true)], use default value
                            return $target->getDefaultValue();
                        }
                    }
                    if ($id = $resolveAttribute->getId()) {
                        return $this->container->get($id, ... $resolveAttribute->getParameters() ?? []);
                    }
                    break;
                }
            }
        }
        if (!isset($resolveAttribute)) {
            if ($target->isDefaultValueAvailable()) {
                return $target->getDefaultValue();
            }
            $values = $emptyValues;
        } else {
            $values = $resolveAttribute->getParameters() ?? [];
        }
        $type = $target->getType();
        if (($type instanceof \ReflectionNamedType) && (!$type->isBuiltin())) {
            try {
                return $this->container->get($type->getName(), ... $values);
            } catch (NotFoundExceptionInterface $e) {
            }
        } else {
            foreach ($type->getTypes() as $one) {
                if (!$one->isBuiltin()) {
                    try {
                        return $this->container->get($one->getName(), ... $values);
                    } catch (NotFoundExceptionInterface $e) {
                    }
                }
            }
        }
        if ($target->isDefaultValueAvailable()) {
            return $target->getDefaultValue();
        }
        throw new class("can not resolve " . $target->getDeclaringClass()->getName() . '::' . $target->getDeclaringFunction()->getName() . '(' . (string)$type . ' $' . $target->getName() . ')') extends \Exception implements NotFoundExceptionInterface {
        };
    }

    /**
     * @param  string|\Closure  $function   Function name
     * @param  mixed            ...$values  Values to aguments
     *
     * @return mixed Return value of function
     */
    public function invokeFunction(string|\Closure|\ReflectionFunction $function, mixed ...$values)
    {
        if (!is_object($function)) {
            $function = new \ReflectionFunction($function);
        }
        return $function->invokeArgs($this->injectParameters($function, $values));
    }

    /**
     * @param  object|string  $instance   Instance or class name
     * @param  string         $method     Method name
     * @param  mixed          ...$values  Values to aguments
     *
     * @return mixed Return value of method
     */
    public function invokeMethod(object|string $instance, string|\ReflectionMethod $method, mixed ...$values): mixed
    {
        if (!is_object($method)) {
            $method = new \ReflectionMethod($instance, $method);
        }
        if ($method->isStatic()) {
            return $reflection->invokeArgs(null, $this->injectParameters($method, $values));
        } else {
            if (!is_object($instance)) {
                $instance = $this->container->get($instance);
            }
            return $method->invokeArgs($instance, $this->injectParameters($method, $values));
        }
    }
}
