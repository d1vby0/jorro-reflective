<?php

namespace Jorro\Reflective\Resolve;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
final class Resolve implements ResolveInterface
{
    /**
     * @param  string|null  $id          Inject from container using the id
     *
     * function TheFunction (#[Resolve(id:'app')]] $param)
     * $param = $injector->container->get('app')
     *
     * @param  bool         $optional    Use default valuewhen dependency injection is not possible.
     *
     * function TheFunction (#[Optional()] TheClassA|TheClassB $param = null)
     *
     * 1. Attempts to inject TheClassA instance into $param.
     * 2. (if TheClassA not exists) Attempts to inject TheClassB instance into $param.
     * 3. (if TheClassB not exists) Set null into $param.
     *
     */
    public function __construct(
        protected ?string $id,
        protected bool $optional = false,
    ) {
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }
}
