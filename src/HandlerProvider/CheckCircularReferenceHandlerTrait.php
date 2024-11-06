<?php

namespace Jorro\Reflective\HandlerProvider;

use Jorro\Reflective\Handler\AlterHandler;
use Jorro\Reflective\Handler\HandlerAwareTrait;
use Jorro\Reflective\Handler\PrepareHandler;
use Psr\Container\ContainerExceptionInterface;

/**
 * Check circular reference
 */
trait CheckCircularReferenceHandlerTrait
{
    use HandlerAwareTrait;

    protected array $circularReferences = [];

    /**
     * Check if class is already in the process of creation.
     *
     * @param  string  $class  class name
     *
     * @return void
     */
    #[PrepareHandler]
    protected function checkCircularReference(string $class): void
    {
        if (isset($this->circularReferences[$class])) {
            throw new class("circular reference class detected : " . implode(' , ', array_keys($this->circularReferences))) extends \Exception implements ContainerExceptionInterface {
            };
        }
        $this->circularReferences[$class] = true;
    }

    /**
     * @param  string  $class  class name
     *
     * @return void
     */
    #[AlterHandler]
    protected function unsetCircularReference(string $class): void
    {
        unset($this->circularReferences[$class]);
    }
}