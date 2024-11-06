<?php

namespace Jorro\Reflective\Resolve;

interface ResolveInterface
{
    public function isOptional(): bool;

    public function getId(): ?string;
}
