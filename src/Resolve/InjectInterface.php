<?php

namespace Jorro\Reflective\Resolve;

interface InjectInterface extends ResolveInterface
{
    public function inject(array $values): array;
}

