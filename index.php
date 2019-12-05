<?php

use function Couchbase\defaultDecoder;

class Ioc
{
    public $binding = [];

    public function bind($abstract, $concrete)
    {
        if (!$concrete instanceof Closure) {
            $concrete = function ($ioc) use ($concrete) {
                var_dump(2);
                return $ioc->build($concrete);
            };
        }
        $this->binding[$abstract]['concrete'] = $concrete;
    }

    public function make($abstract)
    {
        $concrete = $this->binding[$abstract]['concrete'];
        return $concrete($this);

    }


    public function build($concrete) {
        $reflector = new ReflectionClass($concrete);
        $constructor = $reflector->getConstructor();
        if(is_null($constructor)) {
            var_dump(5);
            return $reflector->newInstance();
        }else {
            var_dump(3);
            $dependencies = $constructor->getParameters();
            $instances = $this->getDependencies($dependencies);
            var_dump(6);
            return $reflector->newInstanceArgs($instances);
        }
    }

    protected function getDependencies($paramters) {
        $dependencies = [];
        foreach ($paramters as $paramter) {
            var_dump(4);
            $dependencies[] = $this->make($paramter->getClass()->name);
        }
        return $dependencies;
    }
}
interface log
{
    public function write();
}
// 文件记录日志
class FileLog implements Log
{
    public function write(){
        echo 'file log write...';
    }
}
// 数据库记录日志
class DatabaseLog implements Log
{
    public function write(){
        echo 'database log write...';
    }
}
class User
{
    protected $log;
    public function __construct(log $log)
    {
        $this->log = $log;
    }
    public function login()
    {
        // 登录成功，记录登录日志
        echo 'login success...';
        $this->log->write();
    }
}
//实例化IoC容器
$ioc = new Ioc();
$ioc->bind('log','FileLog');
$ioc->bind('user','User');
$user = $ioc->make('user');
$user->login();
exit;

//$f = function ($val) {
//    return $val + 7;
//};
//
//// 使用闭包也很简单
////$f(); //这样就调用了闭包，输出 7
//
//// 当然更多的时候是把闭包作为参数(回调函数)传递给函数
//function testClosure (Closure $callback) {
//    return $callback(666);
//}
//
//// $f 作为参数传递给函数 testClosure，如果是普遍函数是没有办法作为testClosure的参数的
//echo testClosure($f);