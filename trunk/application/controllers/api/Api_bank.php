<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_bank extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('xtm/xtm_kh_bank', 'model');
    }

    public function get_all_bank_post()
    {
        $this->load->model('xtm/xtm_bank');
        $f7 = $this->xtm_bank->f7('*');
        $this->returnData($f7);
    }

    public function index_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],//带上is_del=0
            'order' => ['排序'],
        ], [], 'post');
        $grid = $this->model->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $this->returnData($grid);
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'bank_no' => ['银行卡号', 'required', 'max_length[20]'],
            'bank' => ['开户银行', 'required', 'integer'],//使用xtm_bank的value字段
            'bank_name' => ['账户名称', 'required', 'max_length[100]'],
            'remark' => ['备注', 'max_length[1000]'],
        ], [], 'post');
        $info = $this->model->get_one('*', ['bank_no' => $request_data['bank_no']]);
        if ($info) {
            $this->returnError('银行卡号重复');
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

    public function edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'bank_no' => ['银行卡号', 'required', 'max_length[20]'],
            'bank' => ['开户银行', 'required', 'integer'],
            'bank_name' => ['账户名称', 'required', 'max_length[100]'],
            'remark' => ['备注', 'max_length[1000]'],
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
