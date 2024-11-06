<?php

namespace Jorro\Reflective\Resolve;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Using extends Inject
{
    public function __construct(
        mixed ...$values
    ) {
        parent::__construct(values: $values);
    }
}
