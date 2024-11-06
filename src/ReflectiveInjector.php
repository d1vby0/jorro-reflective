<?php

namespace Jorro\Reflective;

use Jorro\Reflective\Resolve\ResolveAttributeInterface;
use Jorro\Reflective\Resolve\ResolveInterface;
use Jorro\Reflective\Resolve\InjectInterface;
use Psr\Container\ContainerExceptionInterface;
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
        if (($values) || (method_exists($class, '__construct'))) {
            $this->injectParameters(new \ReflectionMethod($class, '__construct'), $values);
            return new $class(... $values);
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
                $property->setValue($instance, $this->resolveValue($property));
            }
        }
    }

    /**
     * @param  \ReflectionFunctionAbstract  $function
     * @param  array                        $values  Values to be assigned by parameter name / type name
     *
     * @return array Values to be injected
     */
    protected function injectParameters(\ReflectionFunctionAbstract $function, array &$parameters): void
    {
        $injectValues = [];
        foreach ($function->getParameters() as $index => $parameter) {
            $name = $parameter->getName();
            if (key_exists($name, $parameters)) {
                continue;
            }
            if (key_exists($index, $parameters)) {
                $parameters[$name] = $parameters[$index];
                unset($parameters[$index]);
                continue;
            }
            if ($attributes = $parameter->getAttributes()) {
                foreach ($attributes as $attribute) {
                    $attribute = $attribute->newInstance();
                    if ($attribute instanceof ResolveInterface) {
                        if ($attribute instanceof InjectInterface) {
                            $injectValues[$name] = [$parameter, $attribute];
                            unset($parameters[$name]);
                            continue 2;
                        }
                        $parameters[$name] = $this->resolveValue($parameter, $attribute);
                        continue 2;
                    }
                }
            } else {
                $parameters[$name] = $this->resolveValue($parameter, null);
            }
        }
        if ($injectValues) {
            $this->injectValues($injectValues, $parameters);
        }
    }

    /**
     * @param  array  $injectValues
     * @param  array  $parameters
     *
     * @return array
     */
    protected function injectValues(array &$injectValues, array &$parameters): void
    {
        $cantInject = [];
        while ($injectValues) {
            foreach ($injectValues as $name => $cache) {
                try {
                    [$parameter, $attribute] = $cache;
                    $values = $attribute->inject($parameters);
                } catch (NotFoundExceptionInterface $e) {
                    $cantInject[$name] ??= 0;
                    if (++ $cantInject[$name] > 10) {
                        throw new class("can not inject value " . $parameter->getDeclaringClass()->getName() . '::' . $parameter->getDeclaringFunction()->getName() . '($' . $parameter->getName() . ')') extends \Exception implements NotFoundExceptionInterface {
                        };
                    }
                    continue;
                }
                $parameters[$name] = $this->resolveValueWithValues($parameter, $attribute, $values);
                unset($injectValues[$name]);
            }
        }
    }

    /**
     * @param  \ReflectionParameter|\ReflectionProperty  $target
     *
     * @return mixed Value to be injected
     */
    protected function resolveValueWithValues(\ReflectionParameter|\ReflectionProperty $target, ?ResolveInterface $attribute, array $values): mixed
    {
        if ($attribute) {
            if ($target->isDefaultValueAvailable()) {
                if (!$attribute->isOptional()) {
                    // and not specified #[Resolve(optional: true)], use default value
                    return $target->getDefaultValue();
                }
            }
            if ($id = $attribute->getId()) {
                return $this->container->get($id, ...$values);
            }
        } else {
            if ($target->isDefaultValueAvailable()) {
                return $target->getDefaultValue();
            }
        }
        if ($type = $target->getType()) {
            if ($type instanceof \ReflectionNamedType) {
                if (!$type->isBuiltin()) {
                    try {
                        return $this->container->get($type->getName(), ...$values);
                    } catch (NotFoundExceptionInterface $e) {
                    }
                }
            } else {
                foreach ($type->getTypes() as $one) {
                    if (!$one->isBuiltin()) {
                        try {
                            return $this->container->get($one->getName(), ...$values);
                        } catch (NotFoundExceptionInterface $e) {
                        }
                    }
                }
            }
            if ($target->isDefaultValueAvailable()) {
                return $target->getDefaultValue();
            }
            throw new class("can not resolve " . $target->getDeclaringClass()->getName() . '::' . $target->getDeclaringFunction()->getName() . '(' . (string)$type . '$' . $target->getName() . ')') extends \Exception implements NotFoundExceptionInterface {
            };
        } else {
            if ($target->isDefaultValueAvailable()) {
                return $target->getDefaultValue();
            }
            throw new class("can not inject paramter " . $target->getDeclaringClass()->getName() . '::' . $target->getDeclaringFunction()->getName() . '($' . $target->getName() . ')') extends \Exception implements ContainerExceptionInterface {
            };
        }
    }

    /**
     * @param  \ReflectionParameter|\ReflectionProperty  $target
     *
     * @return mixed Value to be injected
     */
    protected function resolveValue(\ReflectionParameter|\ReflectionProperty $target, ?ResolveInterface $attribute): mixed
    {
        if ($attribute) {
            if ($target->isDefaultValueAvailable()) {
                if (!$attribute->isOptional()) {
                    // and not specified #[Resolve(optional: true)], use default value
                    return $target->getDefaultValue();
                }
            }
            if ($id = $attribute->getId()) {
                return $this->container->get($id);
            }
        } else {
            if ($target->isDefaultValueAvailable()) {
                return $target->getDefaultValue();
            }
        }
        if ($type = $target->getType()) {
            if ($type instanceof \ReflectionNamedType) {
                if (!$type->isBuiltin()) {
                    try {
                        return $this->container->get($type->getName());
                    } catch (NotFoundExceptionInterface $e) {
                    }
                }
            } else {
                foreach ($type->getTypes() as $one) {
                    if (!$one->isBuiltin()) {
                        try {
                            return $this->container->get($one->getName());
                        } catch (NotFoundExceptionInterface $e) {
                        }
                    }
                }
            }
            if ($target->isDefaultValueAvailable()) {
                return $target->getDefaultValue();
            }
            throw new class("can not resolve " . $target->getDeclaringClass()->getName() . '::' . $target->getDeclaringFunction()->getName() . '(' . (string)$type . '$' . $target->getName() . ')') extends \Exception implements NotFoundExceptionInterface {
            };
        } else {
            if ($target->isDefaultValueAvailable()) {
                return $target->getDefaultValue();
            }
            throw new class("can not inject paramter " . $target->getDeclaringClass()->getName() . '::' . $target->getDeclaringFunction()->getName() . '($' . $target->getName() . ')') extends \Exception implements ContainerExceptionInterface {
            };
        }
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
        $this->injectParameters($function, $values);
        return $function->invokeArgs($values);
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
            $this->injectParameters($method, $values);
            return $reflection->invokeArgs(null, $values);
        } else {
            if (!is_object($instance)) {
                $instance = $this->container->get($instance);
            }
            $this->injectParameters($method, $values);
            return $method->invokeArgs($instance, $this->injectParameters($method, $values));
        }
    }
}
