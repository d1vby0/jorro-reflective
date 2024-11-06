<?php

namespace FunctionHelperTest;

use Jorro\Reflective\ReflectionHelper\ReflectionFunctionHelper;
use PHPUnit\Framework\TestCase;

class ReflectionFunctionHelperTest extends TestCase
{

    protected function theFunctionA(int $a, ?string $b, ?object $c = null, int|string|null $d = 1): int|string
    {
    }

    protected function theFunctionB()
    {
    }

    public function testGetParameterNames()
    {
        $helper = new ReflectionFunctionHelper(new \ReflectionMethod($this, 'theFunctionA'));
        $this->assertEquals($helper->getParameterNames(), ['a', 'b', 'c', 'd']);
        $helper = new ReflectionFunctionHelper(new \ReflectionMethod($this, 'theFunctionB'));
        $this->assertEquals($helper->getParameterNames(), []);
    }

    public function testFillParameters()
    {
        $helper = new ReflectionFunctionHelper(new \ReflectionMethod($this, 'theFunctionA'));

        $this->assertEquals($helper->fillParameters(null), ['a' => null, 'b' => null, 'c' => null, 'd' => 1]);
        $this->assertEquals($helper->fillParameters(null, false), ['c' => null, 'd' => 1]);
        $this->assertEquals($helper->fillParameters(null, true, false), ['a' => null, 'b' => null, 'c' => null, 'd' => null]);
        $this->assertEquals($helper->fillParameters(null, false, false), []);
        $values = ['a' => 'a', 'z' => 'z'];
        $this->assertEquals($helper->fillParameters($values), ['a' => 'a', 'b' => null, 'c' => null, 'd' => 1]);
        $this->assertEquals($helper->fillParameters($values, false), ['a' => 'a', 'c' => null, 'd' => 1]);
        $this->assertEquals($helper->fillParameters($values, true, false), ['a' => 'a', 'b' => null, 'c' => null, 'd' => null]);
        $this->assertEquals($helper->fillParameters($values, false, false), ['a' => 'a']);
    }

    public function testGetReturnTypes()
    {
        $helper = new ReflectionFunctionHelper(new \ReflectionMethod($this, 'theFunctionA'));
        $this->assertEquals($helper->getReturnTypes(), ['string', 'int']);
    }

}
