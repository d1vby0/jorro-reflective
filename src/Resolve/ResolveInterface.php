<?php

namespace Jorro\Reflective\Resolve;

interface ResolveInterface
{
    public function getId(): ?string;
    public function isOptional(): bool;
    public function getParameters(): ?array;
}
