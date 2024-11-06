<?php

namespace Jorro\Reflective\Handler;

/**
 * Proxy handler
 *
 * The handler method is called before instance is created.
 *
 * @param  string  $className      Class name to proxy
 * @param  string  $originalClass  Original class name (If another proxy has already been applied the class name before it is applied)
 *
 * @return ?string Proxy class name if this handler provides a new proxy, or null
 *
 * protected function ProxyHandler(string $className, string $orginalClass): ?string
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class ProxyHandler extends Handler
{
}
