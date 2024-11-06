<?php

namespace Jorro\Reflective;

/**
 * Injector bootstrap point
 *
 * The attributed methods is called at ReflectiveInjector startup.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class ReflectiveInjectorBootstrap
{
    public function __construct(public readonly int $priority = 0)
    {
    }
}
