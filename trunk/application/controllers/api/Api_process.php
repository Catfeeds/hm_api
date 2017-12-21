<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_process extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ckm/ckm_process');
        $this->model = $this->ckm_process;
    }

    public function index_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
        ], [], 'post');
        $grid = $this->model->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $this->returnData($grid);
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'name' => ['流程名称', 'required'],
            'type' => ['分类', 'required', 'integer'],//1-记账报税 2-工商服务 3-知识产权 4-财税服务 5-网站建设
            'status' => ['流程状态', 'required'],//["流程1","流程2","流程3"...]
        ], [], 'post');
        $info = $this->model->get_one('*', ['name' => $request_data['name']]);
        if ($info) {
            $this->returnError('名称重复');
        }
        $request_data['create_user'] = $this->loginData['id'];
        $request_data['create_time'] = time();
        $request_data['company_id'] = $this->loginData['company_id'];
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData($id);
    }

    public function del_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
        ], [], 'post');
        $res = $this->model->edit($request_data, ['is_del' => 1]);
        if ($res === false) {
            $this->returnError('删除失败');
        }
        $this->returnData([]);
    }

}
