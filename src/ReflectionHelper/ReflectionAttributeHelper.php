<?php

namespace Jorro\Reflective\ReflectionHelper;

class ReflectionAttributeHelper
{
    public function __construct(protected \ReflectionAttribute $attribute)
    {
    }

    public function buildDeclare(): string
    {
        $arguments = [];
        foreach(explode("\n", (string)$this->attribute) as $argument) {
            if (preg_match('/^Argument \#[0-9]+ \[ (.+) \]$/', trim($argument), $match)) {
                $match = $match[1];
                $match = preg_replace('/([^\s])+ = /', '$1:', $match);
                $arguments[] = preg_replace_callback('/^([^\']*?)\'(.+)\'$/', function ($string) {
                    return $string[1] . "'" . str_replace("'", "\\'", $string[2]) . "'";
                }, $match);
            }
        }
        $arguments = ($arguments) ? '(' . implode(',', $arguments). ')' : '';

        return '#[' . $this->attribute->getName() . $arguments .']';
    }

    public function __toString(): string
    {
        return $this->buildDeclare();
    }
}