<?php
namespace UPhp\ActionController;

use UPhp\ActionDispach\Routes;
use UPhp\ActionDispach\Exception\NoRouteException;
use UPhp\ActionController\Exception\LayoutNotExist;

class ActionController
{
    public static function callController($config)
    {
        $url = explode("?", $config["URI"])[0];
        $route = Routes::getControllerActionByURL($url);
        if ($route["VERB"] == $config["METHOD"]) {
            $className = "\\controllers\\" . ucwords($route["CONTROLLER"])."Controller";
            $controller = new $className();
            $controller->controllerName = $route["CONTROLLER"];
            $controller->actionName = $route["ACTION"];
            //$controller->funcBeforeFilter($controller, $actionName);
            call_user_func(array($controller, $route["ACTION"]));
            //$controller->funcAfterFilter($controller, $actionName);
        } else {
            throw new NoRouteException();
        }
    }

    public function render($viewObject, $options = [])
    {
        //OPTIONS:
        // layout => informar qual layout sera utilizado
        $properties = array_keys(get_object_vars($viewObject));
        foreach ($properties as $propertie) {
            $this->$propertie = $viewObject->$propertie;
        }
        
        if (isset($options["layout"])) {
            if ($options["layout"] != false) {
                if ($this->verifyExistLayout($options["layout"])) {
                    $layout = $this->getTemplate("app/views/layouts/" . $options["layout"] . ".php");
                } else {
                    throw new LayoutNotExist();
                }
            } elseif ($options["layout"] == false) {
                $layout = false;
            }
        } else {
            if ($this->verifyExistLayout($this->controllerName)) {
                $layout = $this->getTemplate("app/views/layouts/" . $this->controllerName . ".php");
            } else {
                $layout = $this->getTemplate("app/views/layouts/application.php");
            }
        }
        
        $page_html = $this->getTemplate("app/views/" . $this->controllerName . "/" . $this->actionName . ".php");
        if ($layout != false) {
            $page = str_replace("{{ PAGE }}", $page_html, $layout);
        } else {
            $page = $page_html;
        }
        
        echo $page;
    }

    private function getTemplate($file)
    {
        ob_start(); // start output buffer
        include $file;
        $template = ob_get_contents(); // get contents of buffer
        ob_end_clean();
        return $template;
    }

    private function verifyExistLayout($file)
    {
        $path_layouts = "app/views/layouts";
        $dir_layouts = dir($path_layouts);
        $layout_html = "";
        while ($layout_file = $dir_layouts -> read()) {
            if ($layout_file == $file . ".php") {
                return true;
            }
        }
        return false;
    }
}
