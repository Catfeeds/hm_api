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
class Api_contact_log extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('khm/Khm_contact_log', 'model');
    }

    public function index_get()
    {
        $condition = $this->check_param([
            'contact_user' => ['联系人', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'contact_way' => ['联系途径', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'contact_type' => ['联系类型', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'contact_company' => ['联系公司', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'filter' => ['查询条件'],   //  与上面的多个条件会自动拼接起来
        ]);
        $request_data = $this->check_param([
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'rows' => ['每页多少内容', 'integer'],
            'order' => ['排序规则'],

        ]);
        if (!$request_data['page_size'] && $request_data['rows']) {
            $request_data['page_size'] = $request_data['rows'];
        }
        $condition['is_del'] = 0;
        $condition['company_id'] = $this->loginData['company_id'];
        if (!$request_data['order']) {
            $request_data['order'] = 'id desc';
        }
        $data = $this->model->grid("*", $condition, $request_data['page'], $request_data['page_size'], 'id desc', TRUE);
        $this->returnData($data);
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'log_time' => ['联系时间', 'required', 'integer'],
            'customer_id' => ['客户ID', 'required', 'integer'],
            'contact_user' => ['联系人ID', 'required', 'integer'],
            'contact_msg' => ['联系内容', 'required', 'max_length[1000]'],
            'contact_way' => ['联系途径', 'required', 'integer'],//1-电话 2-微信 3-QQ 4-邮箱
            'contact_type' => ['联系类型', 'required', 'integer'],//1-售后服务 2-售前服务
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
            'log_time' => ['联系时间', 'required', 'integer'],
            'customer_id' => ['客户ID', 'required', 'integer'],
            'contact_user' => ['联系人ID', 'required', 'integer'],
            'contact_msg' => ['联系内容', 'required', 'max_length[1000]'],
            'contact_way' => ['联系途径', 'required', 'integer'],//1-电话 2-微信 3-QQ 4-邮箱
            'contact_type' => ['联系类型', 'required', 'integer'],//1-售后服务 2-售前服务
        ], [], 'post');
        $condition = ['id' => $request_data['id']];
        $request_data['last_modify_user'] = $this->loginData['id'];
        $request_data['last_modify_time'] = time();
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
