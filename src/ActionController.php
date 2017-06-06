<?php
namespace UPhp\ActionController;

use UPhp\ActionDispach\Routes;

class ActionController
{
    public static function callController($config)
    {
        $url = explode("?", $config["URI"])[0];
        echo Routes::action($url);
        //echo $url;
        //$controllerName = explode("#", $ctrlAction)[0];
        //$actionName = explode("#", $ctrlAction)[1];

        //require_once("kernel/controller/kernelController.php");


/*
        $className = ucwords($controllerName)."Controller";
        
        $controller = new $className();
        //$controller->beforeFilter(array("verificaLogin"));
        $controller->funcBeforeFilter($controller, $actionName);
        call_user_func(array($controller, $actionName));
        $controller->funcAfterFilter($controller, $actionName);
*/
    }
}