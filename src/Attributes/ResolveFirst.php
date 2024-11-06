<?php

namespace Jorro\Reflective\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ResolveFirst
{
    public function __construct(public bool $allowNotFound = false)
    {
    }
}