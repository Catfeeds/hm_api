<?php

/*
 * 文章管理
 * @author:jacky Version 1.0.0 2016-6-12
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * test
 */
class Api_label extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cwm/cwm_label', 'model');
    }

    public function index_get()
    {
        $request_data = $this->check_param([
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'rows' => ['每页多少内容', 'integer'],
            'cat_id' => ['分组ID', 'integer'],
        ]);
        if(!$request_data['page_size'] && $request_data['rows']){
            $request_data['page_size'] = $request_data['rows'];
        }
        $condition = [];
        $condition['is_del'] = 0;
        if($request_data['cat_id']){
            $condition['cat_id'] = $request_data['cat_id'];
        }
        $condition['company_id'] = $this->loginData['company_id'];
        $data = $this->model->grid("*", $condition, $request_data['page'], $request_data['page_size'], 'sort desc', TRUE);
        $this->returnData($data);
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'cat_id' => ['分组ID', 'required', 'integer'],
            'title' => ['标题', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
            'sort' => ['排序', 'integer'],
            'color' => ['标签颜色', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'range' => ['使用领域', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'desc' => ['描述', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
        ], [], 'post');
        $request_data['sort'] = isset($request_data['sort']) ? $request_data['sort'] : 100; //默认100
        /**
         * 验证成功后的逻辑
         * 1.检查是否有重复
         * 2. 写入文章表
         */
        $request_data['create_user'] = $this->loginData['id'];
        $request_data['create_time'] = time();
        $request_data['company_id'] = $this->loginData['company_id'];
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData([]);
    }

    public function edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'title' => ['标题', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
            'sort' => ['排序', 'integer'],
            'color' => ['标签颜色', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'range' => ['使用领域', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'desc' => ['描述', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
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
        $id = $_POST['id'];
        if(!$id){
            $this->returnError('ID不能为空');
        }
        $condition = [];
        if(is_array($id)){
            $this->model->db->where_in('id',$id);
        }else{
            $condition = ['id' => $id];
        }
        $res = $this->model->edit($condition, ['is_del' => 1]);
        if ($res === false) {
            $this->returnError('删除失败');
        }
        $this->returnData([]);
    }

}
