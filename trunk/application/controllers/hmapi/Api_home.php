<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_home extends Apibase {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('hm/home', 'model');
    }

    function index_post()
    {
        $data = $this->model->get_one('*', ['id' => 1]);
        $this->returnData($data);
    }

}
