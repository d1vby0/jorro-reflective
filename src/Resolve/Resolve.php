<?php

namespace Jorro\Reflective\Resolve;

/**
 *  Inject from container
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
final class Resolve implements ResolveInterface
{
    /**
     * @param  string  $id  Inject from container using the id
     *
     *  function TheFunction (#[Resolve('app')]] $param)
     *  $param = $injector->container->get('app')
     */
    public function __construct(
        protected ?string $id = null,
        protected bool $optional = false,
        protected ?array $parameters = null,
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

    public function getParameters(): ?array
    {
        return $this->parameters;
    }
}
