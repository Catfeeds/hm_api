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
class Api_opportunity extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cwm/cwm_opportunity', 'model');
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'name' => ['商机名称', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'customer_name' => ['客户名称', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'source' => ['商家来源', 'required', 'integer'],
            'remark' => ['备注', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[4000]'],
            'price' => ['价格', 'required', 'numeric'],
            'possibility' => ['可能性', 'integer'],
            'status' => ['状态', 'required', 'integer'],         //   状态码未定
            'oppo_time' => ['商机时间', 'required', 'integer'],
            'get_time' => ['商机获取日期', 'required', 'integer'],
            'next_time' => ['下次跟进时间', 'required', 'integer'],
            'last_time' => ['最后跟进时间', 'required', 'integer'],
            'not_follow_day' => ['未跟进天数', 'integer'],
            'master_user' => ['负责人', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'mast_department' => ['负责部门', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'last_master_user' => ['前负责人', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'last_mast_department' => ['前负责部门', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]']
        ], [], 'post');
        $request_data['last_modify_user'] = $request_data['create_user'] = $this->loginData['id'];
        $request_data['last_modify_time'] = $request_data['create_time'] = time();

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
            'id' => ['ID', 'integer'],
            'name' => ['商机名称', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'customer_name' => ['客户名称', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[100]'],
            'source' => ['商家来源', 'required', 'integer'],
            'remark' => ['备注', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[4000]'],
            'price' => ['价格', 'numeric'],
            'possibility' => ['可能性', 'integer'],
            'status' => ['状态', 'integer'],         // 状态码未定
            'oppo_time' => ['商机时间', 'integer'],
            'get_time' => ['商机获取日期', 'integer'],
            'next_time' => ['下次跟进时间', 'integer'],
            'last_time' => ['最后跟进时间', 'integer'],
            'not_follow_day' => ['未跟进天数', 'integer'],
            'master_user' => ['负责人', 'integer'],
            'mast_department' => ['负责部门', 'integer'],
            'last_master_user' => ['前负责人', 'integer'],
            'last_mast_department' => ['前负责部门', 'integer']
        ], [], 'post');
        $request_data['last_modify_user'] = $this->loginData['id'];
        $request_data['last_modify_time'] = time();

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
        if (!$id) {
            $this->returnError('ID不能为空');
        }
        $condition = [];
        if (is_array($id)) {
            $this->model->db->where_in('id', $id);
        } else {
            $condition = ['id' => $id];
        }
        $res = $this->model->edit($condition, ['is_del' => 1]);
        if ($res === false) {
            $this->returnError('删除失败');
        }
        $this->returnData([]);
    }

}
