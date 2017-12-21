<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_tax_bureau extends Apibase {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cwm/Cwm_tax_bureau', 'model');
    }
    public function index_get(){
        $condition = $this->check_param([
            'area_name' => ['区域名称', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
            'type' => ['类型', 'integer'],
        ]);
        $request_data = $this->check_param([
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'rows' => ['每页多少内容', 'integer'],
            'order' =>  ['排序规则'],
            'filter' => ['查询条件'],   //  与上面的多个条件会自动拼接起来
        ]);

        if(!$request_data['page_size'] && $request_data['rows']){
            $request_data['page_size'] = $request_data['rows'];
        }
        foreach ($condition as $k => $v){
            if(!$v){
                continue;
            }
            if(is_int($v)){
                $condition[$k] = $v;
            }elseif (is_string($v)){
                $condition[$k] = ['like', $v];
            }
        }
        $condition['is_del'] = 0;
        $condition['company_id'] = $this->loginData['company_id'];
        if(!$request_data['sort_rule']){
            $request_data['sort_rule'] = $request_data['order'];
        }
        if (!$request_data['sort_rule']) {
            $request_data['sort_rule'] = 'id desc';
        }
        if($request_data['filter']){
            $this->model->db->where($request_data['filter']);
        }
        $data = $this->model->grid("*", $condition, $request_data['page'], $request_data['page_size'], $request_data['sort_rule'], TRUE);
        $this->returnData($data);
    }
    public function add_post(){
        $request_data = $this->check_param([
            'area_name' => ['区域', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
            'link' => ['链接', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
            'type' => ['类型', 'required', 'integer']
        ], [], 'post');
        if(!in_array($request_data['type'], [1, 2])){
            $this->returnError('类型参数错误');
        }
        // 还需添加角色判断
        $res = $this->model->get_one('*', ['area_name' => $request_data['area_name'], 'type' => $request_data['type'], 'is_del' => 0, 'company_id' => $this->loginData['company_id']]);
        if ($res) {
            $this->returnError('该区域链接已存在');
        }
        $request_data['create_user'] = $this->loginData['id'];
        $request_data['create_time'] = time();
        $request_data['company_id'] = $this->loginData['company_id'];
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData();
    }
    public function edit_post(){
        $request_data = $this->check_param([
            'id' => ['类型', 'required', 'integer'],
            'link' => ['链接', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]', 'required']
        ], [], 'post');

        $res = $this->model->edit(['id' => $request_data['id']], $request_data);
        if($res === false){
            $this->returnError('编辑失败');
        }
        $this->returnData([]);
    }
    public function del_post(){
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
        ], [], 'post');
        $res = $this->model->edit($request_data, ['is_del' => 1]);
        if($res === false){
            $this->returnError('删除失败');
        }
        $this->returnData([]);
    }

}
