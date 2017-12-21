<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Hm_login extends Modelbase {

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = "hm_admin";
    }

}
