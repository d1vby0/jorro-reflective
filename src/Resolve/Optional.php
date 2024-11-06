<?php

namespace Jorro\Reflective\Resolve;

/**
 *  Use default valuewhen dependency injection is not possible.
 *
 * function TheFunction (#[Optional()] TheClassA|TheClassB $param = null)
 * 1. Attempts to inject TheClassA instance into $param.
 * 2. (if TheClassA not exists) Attempts to inject TheClassB instance into $param.
 * 3. (if TheClassB not exists) Set null into $param.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
final class Optional implements ResolveInterface
{
    /**
     * @param  string|null  $id  Inject from container using the id
     *
     * function TheFunction (#[Resolve(id:'app')]] $param)
     * $param = $injector->container->get('app')
     *
     * @param  array|null  $parameters  Inject with parameters
     *
     *  function TheFunction (#[Resolve(parameters:['a' => 'data'])]] TheClassA $param)
     *  $param = $injector->container->get(TheClassA::class, a: 'data');
     */
    public function __construct(
        protected ?string $id = null,
    ) {
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isOptional(): bool
    {
        return true;
    }
}
