<?php

namespace Jorro\Reflective\Handler;

use Psr\Container\NotFoundExceptionInterface;
use Jorro\Reflective\ReflectiveInjectorBootstrap;

/**
 * Perform handler
 */
trait HandlerAware
{
    protected ?array $prepareHandlers = [];
    protected ?array $alterHandlers = [];
    protected ?array $proxyHandlers = [];
    protected ?array $proxies = [];

    /**
     * Register handler methods assigned to this class
     *
     * @return void
     */
    #[ReflectiveInjectorBootstrap]
    protected function registerHandlers()
    {
        foreach (new \ReflectionClass($this)->getMethods() as $method) {

            if ($handler = ($method->getAttributes(Handler::class,  \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null)?->newInstance()) {
                $name = $method->getName();
                $priority = $handler->priority;
                if ($handler instanceof ProxyHandler) {
                    $this->proxyHandlers[$name] = $priority;
                }
                if ($handler instanceof PrepareHandler) {
                    $this->prepareHandlers[$name] = $priority;
                }
                if ($handler instanceof AlterHandler) {
                    $this->alterHandlers[$name] = $priority;
                }
            }
        }
        arsort($this->proxyHandlers);
        $this->proxyHandlers = array_keys($this->proxyHandlers) ?: null;
        arsort($this->prepareHandlers);
        $this->prepareHandlers = array_keys($this->prepareHandlers) ?: null;
        arsort($this->alterHandlers);
        $this->alterHandlers = array_keys($this->alterHandlers) ?: null;
    }

    /**
     * Create Specified class instance with handlers.
     *
     * @param  string  $class      Class name
     * @param  mixed   ...$values  Values to aguments
     */
    public function get(string $class, mixed ...$values): mixed
    {
        $original = $class;
        if ($this->proxyHandlers) {
            if (!isset($this->proxies[$original])) {
                $this->proxies[$original] = false;
                foreach ($this->proxyHandlers as $proxyHandler) {
                    $class = $this->{$proxyHandler}($class) ?? $class;
                }
                if ($original != $class) {
                    $this->proxies[$original] = $class;
                }
            }
            $class = $this->proxies[$original] ?: $original;
        }
        if (!class_exists($class)) {
            throw new class("class not found : $class") extends \Exception implements NotFoundExceptionInterface {
            };
        }
        try {
            $instance = null;
            if ($this->prepareHandlers) {
                foreach ($this->prepareHandlers as $prepareHandler) {
                    $this->{$prepareHandler}($class, $original);
                }
            }
            if (method_exists($class, '__construct')) {
                $instance = new $class(... $this->injectParameters(new \ReflectionMethod($class, '__construct'), $values));
            } else {
                $instance = new $class();
            }
            return $instance;
        } finally {
            if ($this->alterHandlers) {
                foreach ($this->alterHandlers as $alterHandler) {
                    $this->{$alterHandler}($class, $original, $instance);
                }
            }
        }
    }
}
