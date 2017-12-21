<?php

/*
 * 文章管理
 * @author:jacky Version 1.0.0 2016-6-12
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Api_plan 日程模块
 */
class Api_plan extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('tkm/tkm_plan_out', 'model');
    }

    /**
     * 查询日程列表
     */
    public function index_post()
    {
        $select = $this->input->get_post('select'); //查询字段
        $page_size = $this->input->get_post('limit'); //每页显示多少条
        $page = $this->input->get_post('page'); //第几页
        $filter = $this->input->get_post('filter'); //查询条件
        $order = $this->input->get_post('order'); //排序
        $grid = $this->model->grid($select, $filter, $page, $page_size, '', '', $order);
        $items = $grid['items'];
        $this->load->model('bmm/bmm_employees');
        foreach ($items as $i => $item) {
            $items[$i]['nameList'] = $this->bmm_employees->f7('*', "bmm_employees.id in ({$item['tkm_plan_out.members']})");
        }
        $grid['items'] = $items;
        $this->returnData($grid);
    }

    /**
     * 添加日程
     */
    public function add_plan_out_post()
    {
        $request_data = $this->check_param([
            'pian' => ['片区ID', 'required', 'integer'],
            'title' => ['行程主题', 'required', 'integer'],//行程主题 1-处理税务 2-业务签单 3-注册公司 4-变更公司 5-注册商标 6-注销公司 7-外勤配送 8-网站建设
            'remark' => ['行程备注'],
            'members' => ['参与人员', 'required'],// 逗号分割 1,2,3
            'plan_time' => ['行程时间', 'required', 'integer'],
            'remind_type' => ['提醒方式', 'required', 'integer'],//提醒方式1-事情发生时 2-5分钟 3-15分钟 4-30分钟 5-1小时 6-2小时 7-1天 8-2天 9-1周
        ], [], 'post');
        $request_data['user_id'] = $this->get_login_info()['id'];
        $request_data['user_name'] = $this->get_login_info()['name'];
        $request_data['create_time'] = time();
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData();
    }

    /**
     * 删除日程
     */
    public function del_plan_out_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
        ], [], 'post');
        $this->model->edit($request_data, ['is_del' => 1]);
        $this->returnData();
    }

    /**
     * 查询日志列表
     */
    public function index_plan_post()
    {
        $select = $this->input->get_post('select'); //查询字段
        $page_size = $this->input->get_post('limit'); //每页显示多少条
        $page = $this->input->get_post('page'); //第几页
        $filter = $this->input->get_post('filter'); //查询条件
        $order = $this->input->get_post('order'); //排序
        $this->load->model('tkm/tkm_plan');
        $grid = $this->tkm_plan->grid($select, $filter, $page, $page_size, '', '', $order);
        $this->returnData($grid);
    }

    /**
     * 新增编辑日志
     */
    public function add_plan_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'integer'],
            'type' => ['类型', 'required', 'integer'],//1-日计划 2-周计划 3-月计划
            'summary' => ['总结', 'required'],
            'plan' => ['计划', 'required'],
            'heart' => ['心得', 'required'],
        ], [], 'post');
        $this->load->model('tkm/tkm_plan');
        if (empty($request_data['id'])) {
            unset($request_data['id']);
            $request_data['user_id'] = $this->get_login_info()['id'];
            $request_data['create_time'] = time();
            $id = $this->tkm_plan->add($request_data);
        } else {
            $id = $this->tkm_plan->edit(['id' => $request_data['id']], $request_data);
        }
        if (!$id) {
            $this->returnError('提交失败');
        }
        $this->returnData();
    }

    /**
     * 删除日志
     */
    public function del_plan_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('tkm/tkm_plan');
        $this->tkm_plan->edit($request_data, ['is_del' => 1]);
        $this->returnData();
    }
}
