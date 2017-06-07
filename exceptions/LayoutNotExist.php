<?php
namespace UPhp\ActionController\Exception;

use \src\UphpException;

class LayoutNotExist extends UphpException
{
    public function __construct(){
        parent::__construct("Layout not found", __CLASS__);
    }
}