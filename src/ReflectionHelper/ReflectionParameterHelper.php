<?php

namespace Jorro\Reflective\ReflectionHelper;

class ReflectionParameterHelper
{
    public function __construct(protected \ReflectionParameter $parameter)
    {
    }
    public function buildDeclare(): string
    {
        $attributes = [];
        foreach ($this->parameter->getAttributes() as $attribute) {
            $attributes[] = new ReflectionAttributeHelper($attribute)->buildDeclare();
        }

        if (!preg_match('/^Parameter #[0-9]+ \[( <optional>| <required>)? (.+) \]$/', (string)$this->parameter, $match)) {
            throw new \Exception ('parse error: ' .  (string)$this->parameter);
        }

        return implode('', [
            implode('', $attributes),
            $match[2]
        ]);
    }

    public function __toString(): string
    {
        return implode('', $this->buildDeclare());
    }

}