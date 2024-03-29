<?php
/**
 * Created by PhpStorm.
 * User: @DennisRitche
 * Date: 2019/11/25 0025
 * Time: 9:00
 */

class  Container
{
    /**
     * @var  $instance array
     * **/
    private $instance = [];
    /**
     * @var  $binds array
     * **/
    private $binds = [];

    public function __construct()
    {
        $this->instance[Container::class] = $this;
    }

    /**
     * add new instance
     * @param $name string
     * @param $value mixed
     */
    public function addInstance($name, $value)
    {
        $this->instance[$name] = $value;
    }


    /**
     * @param $name string
     * @param $value  mixed
     * @param bool $shared
     */
    public function newBind($name, $value, $shared = false)
    {
        if ($value instanceof Closure) {
            $this->binds[$name] = ['concrete' => $value, "shared" => $shared];
        } else {
            if (!is_string($value) || !class_exists($value, true)) {
                throw new InvalidArgumentException("value must be callback or class name");
            }
        }

        $this->binds[$name] = ['concrete' => $value, 'shared' => $shared];

    }


    /**
     * @param $name string
     * @param array $real_args
     * @return  mixed
     */
    public function make($name, $real_args = [])
    {
        //check firstly
        if (!isset($this->instance[$name]) && !isset($this->binds[$name])) {
            if (!class_exists($name, true)) {
                throw new  InvalidArgumentException("class not exist");
            }
        }

        if (isset($this->instance[$name])) {
            return $this->instance[$name];
        }
        try {

            if (isset($this->binds[$name])) {

                $concrete = $this->binds[$name]['concrete'];
                if (is_callable($concrete)) {
//                    var_dump($concrete);var_dump($real_args);die;
                    $instance = $this->parseCallable($concrete, $real_args);
                } else {
                    $instance = $this->parseClass($concrete, $real_args);
                }
            } else {
                $instance = $this->parseClass($name, $real_args);
            }
            if (isset($this->binds[$name]) && $this->binds[$name]['shared']) {
                $this->instance[$name] = $instance;
            }
            return $instance;
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * @param callable $callback
     * @param $real_args
     * @return mixed
     * @throws ReflectionException
     */
    private function parseCallable(callable $callback, $real_args)
    {

        $refl_function = new ReflectionFunction($callback);
        $parameters = $refl_function->getParameters();
        $parsed_args = [];
        if (count($parameters) > 0) {
            $this->resolveDependencies($parameters, $parsed_args, $real_args);
        }
        return $refl_function->invokeArgs($parsed_args);
    }

    /**
     * @param $class_name
     * @param array $real_args
     * @return object
     * @throws ReflectionException
     */
    private function parseClass($class_name, $real_args = [])
    {
        $refl_class = new ReflectionClass($class_name);
//        var_dump($real_args);die;
        if (!$refl_class->isInstantiable()) {
            throw  new RuntimeException("{$class_name} can not be initialized");
        }
        if (!($constructor = $refl_class->getConstructor())) {
            //no default constructor,so create it directly
            return new $class_name;
        } else {
            $args = [];
            return $refl_class->newInstanceArgs($this->resolveDependencies($constructor->getParameters(), $args, $real_args));
        }
    }

    /**
     * @param $parameters
     * @param $parsed_args
     * @param $real_args
     * @return array|mixed
     * @throws ReflectionException
     */
    private function resolveDependencies($parameters, &$parsed_args, $real_args = [])
    {
        /**
         * @var $parameter ReflectionParameter
         * **/
        foreach ($parameters as $parameter) {
            if ($parameter->getClass() != null) {
                if (!class_exists($parameter->getClass()->getName(), true)) {
                    throw new RuntimeException($parameter->getClass()->getName() . " not exist");
                } else {
                    var_dump($parameters[1]->getClass()->getName());die;
                    $parsed_args[] = $this->make($parameter->getClass()->getName());
                }
            } else {
                if (!$parameter->isDefaultValueAvailable()) {
                    if (!isset($real_args[$parameter->getName()])) {
                        throw  new RuntimeException($parameter->getName() . " has no  value");
                    } else {
                        $parsed_args[] = $real_args[$parameter->getName()];
                    }
                } else {
                    if (isset($real_args[$parameter->getName()])) {
                        $parsed_args[] = $real_args[$parameter->getName()];
                    } else {
                        $parsed_args[] = $parameter->getDefaultValue();
                    }
                }
            }
        }
        return $parsed_args;
    }
}