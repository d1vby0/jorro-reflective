<?php

namespace Jorro\Reflective\Handler;

/**
 * Prepare handler
 *
 * The handler method is called before instance is created.
 *
 * @param  string  $className      Class name to create
 * @param  string  $originalClass  Original class name (If proxy is applied the class name before it is applied)
 *
 * protected function ParepareHandler(string $className, string $orginalClass): void
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class PrepareHandler extends Handler
{
}
