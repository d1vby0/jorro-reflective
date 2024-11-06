<?php

namespace Jorro\Reflective\Handler;

/**
 * Alter handler
 *
 * The handler method is called after instance is created, or instance creation fails.
 *
 * @param  string  $className      Created class name.
 * @param  string  $originalClass  Original class name (If proxy is applied the class name before it is applied)
 * @param ?object  $instance       Create instance (it will be null if instance creation fails)
 *
 * protected fucntion AlterHandler(string $className, string $orginalClass, ?object $instance): void
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class AlterHandler extends Handler
{
}
