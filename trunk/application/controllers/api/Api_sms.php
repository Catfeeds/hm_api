<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_sms extends Apibase {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('msg/sms', 'model');
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
            'autograph' => ['短信签名', 'required'],
            'msg' => ['短信内容', 'required',],
            'Relation' => ['流程状态', 'required'],
                ], [], 'post');
        $info = $this->model->get_one('*', ['autograph' => $request_data['autograph']]);
        if ($info) {
            $this->returnError('签名重复重复');
        }
        $request_data['is_del'] = 0;
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData($id);
    }

    public function edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'autograph' => ['短信签名', 'required'],
            'msg' => ['短信内容', 'required',],
            'Relation' => ['流程状态', 'required'],
                ], [], 'post');
        $condition = ['id' => $request_data['id']];
        $res = $this->model->edit($condition, $request_data);
        if ($res === false) {
            $this->returnError('编辑失败');
        }
        $this->returnData([]);
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
