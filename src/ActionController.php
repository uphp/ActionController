<?php
namespace UPhp\ActionController;

use UPhp\ActionDispach\Routes;
use UPhp\ActionDispach\Exception\NoRouteException;

class ActionController
{
    public static function callController($config)
    {
        $url = explode("?", $config["URI"])[0];
        $route = Routes::getControllerActionByURL($url);
        if ($route["VERB"] == $config["METHOD"]){
            $className = "\\controllers\\" . ucwords($route["CONTROLLER"])."Controller";
            $controller = new $className();
            //$controller->funcBeforeFilter($controller, $actionName);
            call_user_func(array($controller, $route["ACTION"]));
            //$controller->funcAfterFilter($controller, $actionName);
        } else {
            throw new NoRouteException();
        }
    }
}