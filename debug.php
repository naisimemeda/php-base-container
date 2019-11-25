<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/25 0025
 * Time: 11:32
 */

require_once "./Container.php";

class  Family
{
    private $level;
    private $children_num;

    /**
     * @var  $container Container
     * **/
    private $container;

    /**
     * Family constructor.
     * @param $level int
     * @param $children_num string
     * @param $container
     */
    public function __construct($level, $children_num, $container)
    {
        $this->level = $level;
        $this->children_num = $children_num;
        $this->container = $container;
    }
}

class Student
{
    /**
     * @var  $name string
     * **/
    private $name;
    /**
     * @var  $family Family
     * **/
    private $family;

    /**
     * Student constructor.
     * @param $name
     * @param Family $family
     */
    public function __construct($name, Family $family)
    {
        $this->name = $name;
        $this->family = $family;
    }
}

/*$family=new Family();*/
$container = new Container();
$container->newBind(Family::class, function (Container $container) {
    $family = new Family(10, 3, $container);
    return $family;
});
$inst = $container->make(Student::class, ["name" => "obama"]);
print_r($inst);