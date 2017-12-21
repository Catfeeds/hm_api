<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_department extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('bmm/bmm_department');
        $this->model = $this->bmm_department;
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
            'parent' => ['父级', 'integer'],
            'director' => ['主管', 'integer'],
            'name' => ['名称', 'required', 'max_length[200]'],
        ], [], 'post');

        $request_data['cid'] = $this->loginData['id'];
        $request_data['create_at'] = time();
        $request_data['company_id'] = $this->loginData['company_id'];
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData(['id' => $id]);
    }

    public function edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'director' => ['主管', 'integer'],
            'name' => ['名称', 'required', 'max_length[200]'],
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
        $this->load->model('bmm/bmm_employees');
        $list = $this->bmm_employees->get_all('*', ['department' => $request_data['id']]);
        if ($list) {
            $this->returnError('该分组下有员工,无法删除');
        }
        $res = $this->model->edit($request_data, ['is_del' => 1]);
        if ($res === false) {
            $this->returnError('删除失败');
        }
        $this->returnData([]);
    }

}
