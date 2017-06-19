<?php
namespace UPhp\ActionController;

use UPhp\ActionDispach\Routes;
use UPhp\ActionDispach\Exception\NoRouteException;
use UPhp\ActionController\Exception\LayoutNotExist;
use UPhp\ActionView\BootstrapStyle;

class ActionController
{
    public static function callController($config)
    {
        $url = explode("?", $config["URI"])[0];
        $route = Routes::getControllerActionByURL($url);
        if ($route["VERB"] == $config["METHOD"]) {
            $className = "\\controllers\\" . ucwords($route["CONTROLLER"])."Controller";
            $controller = new $className();
            $controller->callSet = "controller";
            $controller->controllerName = $route["CONTROLLER"];
            $controller->actionName = $route["ACTION"];            
            //$controller->funcBeforeFilter($controller, $actionName);
            call_user_func(array($controller, $route["ACTION"]));
            //$controller->funcAfterFilter($controller, $actionName);
        } else {
            throw new NoRouteException($config["URI"]);
        }
    }

    public function render($viewObject, $options = [])
    {
        //OPTIONS:
        // layout => informar qual layout sera utilizado
        $properties = array_keys(get_object_vars($viewObject));
        $this->callSet = "controller";
        foreach ($properties as $propertie) {
            if ($propertie != "callSet") {
                $this->$propertie = $viewObject->$propertie;
            }
        }
        $this->callSet = "view";
        //$this->bootstrap = new BootstrapStyle();        
        $bootstrap = new BootstrapStyle();        
        
        if (isset($options["layout"])) {
            if ($options["layout"] != false) {
                if ($this->verifyExistLayout($options["layout"])) {
                    $layout = $this->getTemplate("app/views/layouts/" . $options["layout"] . ".php", $bootstrap);
                } else {
                    throw new LayoutNotExist($options["layout"], $this->controllerName . "Controller.php");
                }
            } elseif ($options["layout"] == false) {
                $layout = false;
            }
        } else {
            if ($this->verifyExistLayout($this->controllerName)) {
                $layout = $this->getTemplate("app/views/layouts/" . $this->controllerName . ".php", $bootstrap);
            } else {
                $layout = $this->getTemplate("app/views/layouts/application.php", $bootstrap);
            }
        }
        
        $page_html = $this->getTemplate("app/views/" . $this->controllerName . "/" . $this->actionName . ".php", $bootstrap);
        if ($layout != false) {
            $page = str_replace("{{ PAGE }}", $page_html, $layout);
        } else {
            $page = $page_html;
        }

        echo $page;
    }

    private function getTemplate($file, $bootstrap)
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

    protected function params()
    {
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            $get_vars = $_GET;
            unset($get_vars["_method"]);
            return $get_vars;
        } elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
            return $_POST;
        } elseif ($_SERVER['REQUEST_METHOD'] == "PUT" || $_SERVER['REQUEST_METHOD'] == "DELETE") {
            parse_str(file_get_contents("php://input"), $post_vars);
            unset($post_vars["_method"]);
            return $post_vars;
        };
    }

    protected function paramsJSON(string $key = ""){
        $params = $this->params();
        $arrP = explode("&", $params[$key]);
        $arrReturn = [];
        foreach ($arrP as $element) {
            $attr = explode("=", $element);
            $arrReturn[$attr[0]] = $attr[1];
        }
        return $arrReturn;
    }

    /*public function __set($name, $value){
        if ($name == "callSet") {
            $this->$name = $value;
        } else {
            echo $this->callSet . "| " . $name ." | " . $value . "<br>";
                if ($this->callSet == "controller") {
                    $this->$name = $value;
                } elseif ($this->callSet == "view") {
                    echo "Nao pode definir na view";
                }
        }
        
    }*/
}
