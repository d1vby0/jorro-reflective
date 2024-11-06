<?php

namespace ReflectiveInjectorTest;

use Jorro\Reflective\Bootstrap\InjectBootstrap;
use Jorro\Reflective\ReflectiveInjector;
use Jorro\Reflective\Resolve\Optional;
use Jorro\Reflective\Resolve\Resolve;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

class TheClassA
{
    public function __construct(public TheClassB $b, public UndefineClass|TheClassC $c, public UndefineClass|TheClassD $d)
    {
    }
}

#[\Attribute, Qualifier]
class TheExtendsClassA extends TheClassA
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

class TestNotOptionalClass
{
    public function __construct(public ?TheClassA $a = null)
    {
    }
}

class TestOptionalClass
{
    public function __construct(#[Optional] public ?TheClassA $a = null)
    {
    }
}

class TestOptionalResolveClass
{
    public function __construct(#[Optional(TheExtendsClassA::class)] public ?TheClassA $a = null)
    {
    }
}

class TestResolveClass
{
    public function __construct(#[Resolve(TheExtendsClassA::class)] public $a)
    {
    }
}

class TestResloveTypeErrorClass
{
    public function __construct(#[Resolve(TheExtendsClassA::class)] public TheClassC $a)
    {
    }
}

class TestResolveWithClassD extends TheClassD
{
}

class TestResolveClassName extends TheClassB
{
}

class ReflectiveInjectorTest extends TestCase
{
    private ReflectiveInjector $injector;

    public function setUp(): void
    {
        $this->injector ??= new ReflectiveInjector();
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

    public function testOptional()
    {
        $i = $this->injector->get(TestNotOptionalClass::class);
        $this->assertNull($i->a);
        $i = $this->injector->get(TestOptionalClass::class);
        $this->assertEquals(TheClassA::class, get_class($i->a));
        $i = $this->injector->get(TestOptionalResolveClass::class);
        $this->assertEquals(TheExtendsClassA::class, get_class($i->a));
    }

    public function testResolve()
    {
        $i = $this->injector->get(TestResolveClass::class);
        $this->assertEquals(TheExtendsClassA::class, get_class($i->a));
        $this->expectException(\TypeError::class);
        $i = $this->injector->get(TestResloveTypeErrorClass::class);
    }
}
