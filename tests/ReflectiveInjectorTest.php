<?php

namespace ReflectiveInjectorTest;

use Jorro\Reflective\HandlerProvider\CheckCircularReferenceHandler;
use Jorro\Reflective\Handler\HandlerAware;
use Jorro\Reflective\ReflectiveInjector;
use Jorro\Reflective\Resolve\Resolve;
use Jorro\Reflective\Resolve\ResolveAware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TheClassA
{
    public function __construct(public TheClassB $b, public UndefineClass|TheClassC $c, public UndefineClass|TheClassD $d)
    {
    }
}

class TheX extends TheClassA
{
}

class TheClassB
{
    public function __construct(public TheClassC $c)
    {
    }
}

class TheClassC
{
    public function __construct(public TheClassD $d)
    {
    }
}

class TheClassD
{
}

class TheClassE
{
}

class TestNotFoundClass
{
    public function __construct(public UndefinedClass $u)
    {
    }
}

class TestNotFoundClassUnionType
{
    public function __construct(public UndefinedClass1|UndefinedClass2|UndefinedClass3 $u)
    {
    }
}

class TestNotResolveFirstClass
{
    public function __construct(public ?TheClassA $a = null)
    {
    }
}

class TestResolveFirstClass
{
    public function __construct(#[Resolve(optional: true)] public ?TheClassA $a = null)
    {
    }
}

class TestResolveToClass
{
    public function __construct(#[Resolve(id: TheClassA::class)] public $a)
    {
    }
}

class TestResloveToTypeErrorClass
{
    public function __construct(#[Resolve(id: TheClassA::class)] public TheClassC $a)
    {
    }
}

class TestCirculateReferenceClassA
{
    public function __construct(public TestCirculateReferenceClassB $b)
    {
    }
}

class TestCirculateReferenceClassB
{
    public function __construct(public TestCirculateReferenceClassA $a)
    {
    }
}

class TestResolveWithClassD extends TheClassD
{
}

class TestResolveWithClass
{
    public function __construct(#[Resolve(values: ["d" => new TestResolveWithClassD()])] public TheClassC $c)
    {
    }
}

class TestResolveClassName extends TheClassB
{
}

class ReflectiveInjectorTest extends TestCase
{
    private ReflectiveInjector $injector;

    public function __construct(string $name)
    {
        $this->injector = new class() extends ReflectiveInjector {
            use ResolveAware;
            use CheckCircularReferenceHandler;
        };
        parent::__construct($name);
    }

    public function testConstractorInjection()
    {
        $a = $this->injector->get(TheClassA::class);

        $this->assertInstanceOf(TheClassA::class, $a);
        $this->assertInstanceOf(TheClassB::class, $a->b);
        $this->assertInstanceOf(TheClassC::class, $a->c);
        $this->assertInstanceOf(TheClassD::class, $a->d);
        $this->assertInstanceOf(TheClassC::class, $a->b->c);
        $this->assertInstanceOf(TheClassD::class, $a->b->c->d);
    }

    public function testNotFoundException()
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->injector->get(TestNotFoundClass::class);
    }

    public function testNotFoundExceptionUnionType()
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $this->injector->get(TestNotFoundClassUnionType::class);
    }

    public function testResolveFirst()
    {
        $i = $this->injector->get(TestNotResolveFirstClass::class);
        $this->assertNull($i->a);
        $i = $this->injector->get(TestResolveFirstClass::class);
        $this->assertInstanceOf(TheClassA::class, $i->a);
    }

    public function testResolveTo()
    {
        $i = $this->injector->get(TestResolveToClass::class);
        $this->assertInstanceOf(TheClassA::class, $i->a);
        $this->expectException(\TypeError::class);
        $i = $this->injector->get(TestResloveToTypeErrorClass::class);
    }

    public function testResolveWith()
    {
        $i = $this->injector->get(TestResolveWithClass::class);
        $this->assertInstanceOf(TheClassC::class, $i->c);
        $this->assertInstanceOf(TestResolveWithClassD::class, $i->c->d);
    }

    public function testCirculateReferenceException()
    {
        $this->expectException(ContainerExceptionInterface::class);
        $i = $this->injector->get(TestCirculateReferenceClassA::class);
    }

    public function testCirculateResolveClassName()
    {
        $i = $this->injector->get(TheClassA::class, ... [TheClassB::class => $this->injector->get(TestResolveClassName::class)]);
        $this->assertInstanceOf(TestResolveClassName::class, $i->b);
    }

    public function testPerformance()
    {
        $injector = new class() extends ReflectiveInjector {
            use ResolveAware;
        };

        $total = 0;
        for ($i = 0; $i < 10; $i ++) {
            $start = microtime(true);
            for ($j = 0; $j < 10000; $j ++) {
                $injector->get(TheClassA::class);
            }
            $end = microtime(true);
            $total += ($end - $start);
        }
        echo '=====================================================' . PHP_EOL;
        echo 'performance: ' . floor(($total / 10) * 100000) / 100 . 'ms' . PHP_EOL;
        echo '=====================================================' . PHP_EOL;

        $this->assertTrue(true);
    }
}
