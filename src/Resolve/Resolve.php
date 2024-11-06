<?php

namespace Jorro\Reflective\Resolve;

/**
 * Resolve parameter options
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
final class Resolve
{
    /**
     * @param  bool         $optional  Prefer dependency injection.
     *
     *   (target parameter only)
     *   Use default values when dependency injection is not possible.
     *
     *   function TheFunction (#[Resolve(optional:true)] TheClassA|TheClassB $param = null)
     *   1. Attempts to inject TheClassA instance into $param.
     *   2. (if TheClassA not found) Attempts to inject TheClassB instance into $param.
     *   3. (if TheClassB not found) Set null into $param.
     *
     * @param  string|null  $id        Inject from the container using the id
     *
     *  function TheFunction (#[Resolve(id:'app')]] $param)
     *  $param = $injector->container->get('app')
     *
     * @param  array|null   $values    Resolve using the values
     *
     *  function TheFunction (#[Resolove(values: ['a' => 1])] TheClass $param)
     *  $param = $container->get(TheClass::class, ['a' => 1]);
     *  (similar to)
     *  $param = new TheClass(a: 1);
     *
     */
    public function __construct(
        protected(set) bool $optional = false,
        protected(set) ?string $id = null,
        protected(set) ?array $values = null
    ) {
    }
}
