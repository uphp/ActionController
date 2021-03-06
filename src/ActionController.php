<?php
namespace UPhp\ActionController;

use UPhp\ActionView\FormHelper;
use UPhp\web\Application as App;
use UPhp\ActionDispach\Routes;
use UPhp\ActionController\Exception\LayoutNotExist;
use src\Inflection;
use UPhp\ActionView\BootstrapStyle;

class ActionController
{
    public $beforeFilter = array();

    public static function callController($config)
    {
        $url = explode("?", $config["URI"])[0];
        $route = Routes::getControllerAction($config);
        $className = "\\controllers\\" . ucwords(Inflection::classify($route["CONTROLLER"]))."Controller";
        $controller = new $className();
        $controller->callSet = "controller";
        $controller->controllerName = $route["CONTROLLER"];
        $controller->actionName = $route["ACTION"];            
        $controller->funcBeforeAction($controller->controllerName, $controller->actionName);
        call_user_func(array($controller, $route["ACTION"]));
        //$controller->funcAfterAction($controller, $actionName);
    }

    public function render($viewObject, $options = [])
    {
        //OPTIONS:
        // layout => informar qual layout sera utilizado
        // view => nome da view que deverá ser carregada
        $properties = array_keys(get_object_vars($viewObject));
        foreach ($properties as $property) {
            $this->$property = $viewObject->$property;
        }

        $helperName = "\\helpers\\" . ucwords(Inflection::classify(Inflection::singularize($this->controllerName)))."Helper";

        if (isset(App::$appConfig["template"])) {
            $template = "UPhp\\ActionView\\Templates\\" . App::$appConfig["template"] . "\\Layout";
            $template = new $template;
            $template->controllerName = $this->controllerName;
            $template->actionName = $this->actionName;
        } else {
            $template = null;
        }

        $arrGetTemplate = [
            "bootstrap" => new BootstrapStyle(),
            "helper" => new $helperName,
            "inflection" => new Inflection(),
            "template" => $template,
            "form" => new FormHelper()
        ];
        
        if (isset($options["layout"])) {
            if ($options["layout"] != false) {
                if ($this->verifyExistLayout($options["layout"])) {
                    $layout = $this->getTemplate("app/views/layouts/" . $options["layout"] . ".php", $arrGetTemplate);
                } else {
                    throw new LayoutNotExist($options["layout"], $this->controllerName . "Controller.php");
                }
            } elseif ($options["layout"] == false) {
                $layout = false;
            }
        } else {
            if ($this->verifyExistLayout($this->controllerName)) {
                $layout = $this->getTemplate("app/views/layouts/" . $this->controllerName . ".php", $arrGetTemplate);
            } else {
                $layout = $this->getTemplate("app/views/layouts/application.php", $arrGetTemplate);
            }
        }

        if (isset($options["view"])) {
            $loadView = $options["view"];
        } else {
            $loadView = $this->actionName;
        }

        $page_html = $this->getTemplate("app/views/" . $this->controllerName . "/" . $loadView . ".php", $arrGetTemplate);
        if ($layout != false) {
            $page = str_replace("{{ PAGE }}", $page_html, $layout);
        } else {
            $page = $page_html;
        }

        echo $page;
    }

    private function getTemplate($file, Array $variables)
    {
        foreach ($variables as $var => $value) {
            $$var = $value;
        }
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

    protected function params($strArray = "")
    {
        if ($_SERVER['REQUEST_METHOD'] == "GET") {
            if (empty($strArray)) {
                $get_vars = $_GET;
            } else {
                $get_vars = $_GET[$strArray];
            }
            unset($get_vars["_method"]);
            return $get_vars;
        } elseif ($_SERVER['REQUEST_METHOD'] == "POST") {
            if (empty($strArray)) {
                $post_vars = $_POST;
            } else {
                $post_vars = $_POST[$strArray];
            }
            return $post_vars;
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
            if (isset($attr[1])) {
                $arrReturn[$attr[0]] = $attr[1];
            } else {
                $arrReturn[$attr[0]] = null;
            }
        }
        return $arrReturn;
    }

    protected function funcBeforeAction($controller, $action)
    {
        foreach ($this->beforeFilter as &$func) {
            if (isset($func["except"]) && isset($func["only"])) {
                throw new Exception('Não é possível usar EXCEPT e ONLY no mesmo beforeFilter'); //TODO Criar novo exception
            } else {
                if (isset($func["except"])) {
                    if (!in_array($action, $func["except"])) {
                        if (method_exists($this, $func["function"])) {
                            call_user_func(array($this, $func["function"]));
                        } else {
                            throw new Exception("Método não definido na classe:: EXCEPT");
                        }
                    }
                } elseif (isset($func["only"])) {
                    foreach ($func["only"] as &$value) {
                        if ($value == $action) {
                            if (method_exists($this, $func["function"])) {
                                call_user_func(array($this, $func["function"]));
                            } else {
                                throw new Exception("Método não definido na classe:: ONLY");
                            }
                        }
                    }
                } else {
                    call_user_func(array($this, $func["function"]));
                }
            }
        }
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
