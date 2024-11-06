<?php

namespace Jorro\Reflective\Resolve;

use Jorro\Reflective\InjectorHandler\Bootstrap;
use Jorro\Reflective\InjectorHandler\PrepareHandler;
use Jorro\Reflective\InjectorHandler\AlterHandler;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Perform resolve by Resolve attributes option.
 */
trait ResolveAware
{
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
}
