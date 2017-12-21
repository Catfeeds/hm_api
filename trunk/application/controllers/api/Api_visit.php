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
class Api_visit extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cwm/cwm_visit', 'model');
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'name' => ['客户名称', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'theme' => ['拜访主题', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'desc' => ['服务详情', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'service_id' => ['服务项目', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'helper_id' => ['协访人员', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'visit_id' => ['负责人', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'is_timeout' => ['是否超时', 'required', 'integer'],
            'status' => ['状态', 'required', 'integer'],
            'expect_time' => ['预计成交时间', 'required', 'integer'],
            'visit_time' => ['拜访时间', 'required', 'integer'],
        ], [], 'post');
        $request_data['create_user'] = $this->loginData['id'];
        $request_data['create_time'] = time();
        $request_data['cid'] = $this->loginData['cid'];
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
            'name' => ['客户名称', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'theme' => ['拜访主题', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'desc' => ['服务详情', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'service_id' => ['服务项目', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'helper_id' => ['协访人员', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'visit_id' => ['负责人', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'is_timeout' => ['是否超时', 'integer'],
            'status' => ['状态', 'integer'],
            'expect_time' => ['预计成交时间', 'integer'],
            'visit_time' => ['拜访时间', 'integer'],
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
