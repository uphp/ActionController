<?php
namespace UPhp\ActionController\Exception;

use \src\UphpException;

class LayoutNotExist extends UphpException
{
    public function __construct($layoutName, $fileError){
        $this->uphpFile = ucwords($fileError);
        parent::__construct("Layout " . $layoutName . " not found", __CLASS__);
    }
}