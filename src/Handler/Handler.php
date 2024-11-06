<?php

namespace Jorro\Reflective\Handler;

abstract class Handler
{
    public function __construct(public readonly int $priority = 0)
    {
    }
}
