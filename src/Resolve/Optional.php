<?php

namespace Jorro\Reflective\Resolve;

/**
 *  Prefer dependency injection.
 *
 *   Use default values when dependency injection is not possible.
 *
 *   function TheFunction (#[Optional()] TheClassA|TheClassB $param = null)
 *   1. Attempts to inject TheClassA instance into $param.
 *   2. (if TheClassA not exists) Attempts to inject TheClassB instance into $param.
 *   3. (if TheClassB not exists) Set null into $param.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
final class Optional implements ResolveInterface
{
    public function __construct(
        protected ?string $id = null,
        protected ?array $parameters = null,
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

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

}
