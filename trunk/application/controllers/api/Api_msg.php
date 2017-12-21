<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';


class Api_msg extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('msg/msg', 'model');
    }
	
	
	public function index_post() {
        $request_data = $this->check_param([
            'page' => ['当前页', 'integer'],
            'rows'  =>   ['每页多少内容', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'order' =>  ['排序规则'],
            
        ]);
        if(!$request_data['page_size'] && $request_data['rows']){
            $request_data['page_size'] = $request_data['rows'];
        }


		if(!$request_data['order']){
            $request_data['order'] = 'id desc';
        }
		
		$data = $this->model->grid("*", $condition, $request_data['page'], $request_data['page_size'], 'id desc', TRUE);
		$this->returnData($data);
	}
	
}

