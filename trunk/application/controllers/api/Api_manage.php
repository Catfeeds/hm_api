<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';



class Api_manage extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('spm/spm_manage', 'model');
    }
	//获取审批列表
	public function index_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
        ], [], 'post');
        $grid = $this->model->grid('distinct (name)', $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
		$data = $grid['items'];
		$arr = [];
		foreach ($data as $key) {
			array_push($arr,$this->model->f7('*', ['spm_manage.name' => $key['name']]));
		}
		foreach($arr as $i=>$key) {
			$a = [];
			foreach($key as $k1) {
				$a[] = $k1['spm_manage.user'];
			}
			$arr[$i]['user'] = $a;
		}
		$this->load->model('bmm/bmm_employees');
		$userArr = [];
		$name;
        foreach ($arr as $i=>$key ) {
        	$b = [];
        	foreach($key['user'] as $k2) {
        		$b[] = $this->bmm_employees->info('bmm_employees.name',[ 'bmm_employees.id' => $k2])['name'];
        	}
        	$arr[$i]['user'] = $b;
        }

		$grid['items'] = $arr;
        $this->returnData($grid);
    }
	
	
	//改变审批状态
	public function changeStatus_post() 
	{
		$request_data = $this->check_param([
            'name' => ['审批项目名称', 'required'],
            'status' => ['状态', 'required', ],  //0未启用   1启用
        ], [], 'post');
		
		$info = $this->model->get_one('*', ['name' => $request_data['name']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
		$this->model->edit(['name' => $request_data['name']], ['status' => $request_data['status']]);
		$this->returnData();
	}
	
	//添加审批项目
	public function add_post() {
		$request_data = $this->check_param([
            'name' => ['审批项目名称', 'required'],
            'user' => ['审批人', 'required', ],  
        ], [], 'post');
		$user = explode(',',$request_data['user']);
		$item['name'] = $request_data['name'];
		foreach ($user as $key => $value) {
			$item['user'] = $value;
			$info = $this->model->get_one('*',$item);
			if($info) {
				$this->returnError('同一项目审批人不能重复添加');
			}else {
				$this->model->add($item);
			}
		}
		$this->returnData();
	}
	
	//获取审批详情
	public function info_post() {
		$request_data = $this->check_param([
            'name' => ['审批项目名称', 'required']
        ], [], 'post');
		$arr = $this->model->f7('*', ['spm_manage.name' => $request_data['name']]);
		$result = [];
		foreach ($arr as $key) {
			array_push($result,$key['spm_manage.user']);
		}
		$this->returnData($result);
	}
	
	
	//修改审批
	public function edit_post() {
		$request_data = $this->check_param([
            'name' => ['审批项目名称', 'required'],
            'user' => ['审批人', 'required', ], 
        ], [], 'post');
		$user = explode(',',$request_data['user']);
//		$where = "spm_manage.name='{$request_data['name']}' and spm_manage.user in ({$request_data['user']})";
		$where = "spm_manage.name='{$request_data['name']}'";
		$result = $this->model->del($where);
		foreach ($user as $key => $value) {
			$item['name'] = $request_data['name'];
			$item['user'] = $value;
			$this->model->add($item);
		}
		$this->returnData();
	}
	
	
}