<?php

namespace Jorro\Reflective\Resolve;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Using extends Inject
{
    public function __construct(
        mixed ...$using
    ) {
        parent::__construct(using: $using);
    }
}
