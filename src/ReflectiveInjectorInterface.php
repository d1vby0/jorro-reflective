<?php

namespace Jorro\Reflective;

use Psr\Container\ContainerInterface;

interface ReflectiveInjectorInterface
{
    /**
     * コンテナの設定
     *
     * @param \Psr\Container\ContainerInterface|null $container
     * @return void
     */
    public function setContainer(ContainerInterface $container);

    /**
     * @param string|\Closure $function 関数名
     * @param mixed ...$values 利用値
     * @return mixed 関数の戻り値
     */
    public function callFunction(string|\Closure $function, mixed ...$values): mixed;

    /**
     * メソッドの実行
     *
     * @param object $instance インスタンス
     * @param string $method メソッド名
     * @param mixed ...$values 利用値
     * @return mixed メソッドの戻り値
     */
    public function callMethod(object $instance, string $method, mixed ...$values): mixed;

    /**
     * インスタンスの生成
     *
     * @param string $class クラス名
     * @param mixed ...$values 利用値
     * @return object 生成されたインスタンス
     */
    public function get(string $class, mixed ...$values): object;

    /**
     * パラメータの解決
     *
     * @param \ReflectionFunctionAbstract $function ReflectionFunctionAbstract
     * @param mixed ...$values 利用する値
     * @return array 注入されたパラメータ
     */
    public function resolveParameters(\ReflectionFunctionAbstract $function, mixed ...$values): array;
}