<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Auser extends Modelbase {

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = "hm_admin";
    }

}
