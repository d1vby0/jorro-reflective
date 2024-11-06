<?php

namespace Jorro\Reflective\Resolve;

use Psr\Container\NotFoundExceptionInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Inject implements ResolveInterface, InjectInterface
{
    public function __construct(
        protected ?string $id = null,
        protected bool $optional = false,
        protected array $using = [],
        protected array $values = [],
    ) {
    }

    public function inject(array $values): array
    {
        $parameters = [];
        foreach ($this->using as $name => $value) {
            if (is_numeric($name)) {
                $name = $value;
            }
            $key = $value;
            if ((!key_exists($key, $values)) || ($values[$key] instanceof Using)) {
                throw new class() extends \Exception implements NotFoundExceptionInterface {
                };
            }
            $parameters[$name] = $values[$key];
        }
        return array_merge($this->values, $parameters);
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