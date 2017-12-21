<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_label_cat extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cwm/cwm_label_cat', 'model');
    }

    public function index_get(){
        $request_data = $this->check_param([
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'rows' => ['每页多少内容', 'integer'],
        ]);
        if(!$request_data['page_size'] && $request_data['rows']){
            $request_data['page_size'] = $request_data['rows'];
        }
        $condition = ['is_del' => 0];
        $data = $this->model->grid("*", $condition, $request_data['page'], $request_data['page_size'], 'sort desc, create_time desc', TRUE);
        $this->returnData($data);
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'title' => ['标题', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
            'sort' => ['排序', 'integer'],
        ], [], 'post');
        $request_data['sort'] = isset($request_data['sort']) ? $request_data['sort'] : 100; //默认100

        $request_data['create_user'] = $this->loginData['id'];
        $request_data['create_time'] = time();
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
            'title' => ['标题', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
            'sort' => ['排序', 'integer'],
        ], [], 'post');
        $request_data['sort'] = isset($request_data['sort']) ? $request_data['sort'] : 100; //默认100
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
