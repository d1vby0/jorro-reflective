<?php

namespace Jorro\Reflective\ReflectionHelper;

class ReflectionFunctionHelper
{
    public array $parameters;
    public array $returnTypes;
    public array $parameterTypes;
    public array $completed;

    /**
     * @param  \ReflectionFunctionAbstract  $function
     */
    public function __construct(protected \ReflectionFunctionAbstract $function)
    {
    }

    /**
     * @param  array  $values
     * @param  bool   $setNull
     * @param  bool   $setDefault
     *
     * @return array
     */
    public function fillParameters(?array $values = null, bool $setNull = true, bool $setDefault = true): array
    {
        $parameters = [];
        if ($values) {
            foreach ($this->parameters ??= $this->getParameters() as $name => $parameter) {
                if (key_exists($name, $values)) {
                    $parameters[$name] = $values[$name];
                    continue;
                }
                $index = $parameter->getPosition();
                if (key_exists($index, $values)) {
                    $parameters[$name] = $values[$index];
                    continue;
                }
                if (($setDefault) && ($parameter->isDefaultValueAvailable())) {
                    $parameters[$name] = $parameter->getDefaultValue();
                    continue;
                }
                if ($setNull) {
                    $parameters[$name] = null;
                }
            }
            return $parameters;
        } else {
            foreach ($this->parameters ??= $this->getParameters() as $name => $parameter) {
                if (($setDefault) && ($parameter->isDefaultValueAvailable())) {
                    $parameters[$name] = $parameter->getDefaultValue();
                    continue;
                }
                if ($setNull) {
                    $parameters[$name] = null;
                }
            }
            return $parameters;
        }
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        if (!isset($this->parameters)) {
            $this->parameters = [];
            foreach ($this->function->getParameters() as $parameter) {
                $this->parameters[$parameter->getName()] = $parameter;
            }
        }
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getParameterNames(): array
    {
        return array_keys($this->getParameters());
    }

    /**
     * @return array
     */
    public function getReturnTypes(): array
    {
        return $this->returnTypes ??= explode('|', (string)$this->function->getReturnType());
    }

    /**
     * @param  string|null  $name
     *
     * @return array
     */
    public function getParameterTypes(?string $name = null): array
    {
        if ($name) {
            $this->parameters ??= $this->getParameters();
            return $this->parameterTypes[$name] ??= explode('|', (string)$this->parameters[$name]->getType());
        }
        if (!isset($this->completed['paramterTypes'])) {
            $this->completed['paramterTypes'] = true;
            $this->parameterTypes ??= [];
            $this->parameters ??= $this->getParameters();
            foreach ($this->parameters as $name => $parameter) {
                $this->parameterTypes[$name] ??= explode('|', (string)$parameter->getType());
            }
        }
        return $this->parameterTypes;
    }

    public function getModifiers(): array
    {
        return \Reflection::getModifierNames($this->function->getModifiers());
    }

    public function getName(): string
    {
        return $this->function->getName();
    }

    public function buildDeclare(): string
    {
        if ($return = (string)$this->function->getReturnType()) {
            $return = ':' . $return;
        }
        $attributes = [];
        foreach ($this->function->getAttributes() as $attribute) {
            $attributes[] = new ReflectionAttributeHelper($attribute)->buildDeclare();
        }
        $parameters = [];
        foreach ($this->getParameters() as $parameter) {
            $parameters[] = new ReflectionParameterHelper($parameter)->buildDeclare();
        }
        return implode('', [
            implode('', $attributes),
            implode(' ', \Reflection::getModifierNames($this->function->getModifiers())),
            ' function ',
            $this->function->getName(),
            '(' . implode(',', $parameters) . ')',
            $return,
        ]);
    }

    public function __toString(): string
    {
        return $this->buildDeclare();
    }

}