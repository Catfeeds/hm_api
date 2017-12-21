<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_task extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('htm/htm_task', 'model');
    }

    /**
     * 获取所有任务
     */
    public function get_task_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required'],
            'page' => ['第几页'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
            'staff' => ['负责人']
        ], [], 'post');
        $cid = $this->loginData['cid'];
        $filter = "htm_task.cid = {$cid} and htm_contract.contract_type = 2";
        $this->load->model('bmm/bmm_employees');
        if (!empty($request_data['staff'])) {
            $all_ids = $this->bmm_employees->getEmps($request_data['staff'], $cid);
            if (!empty($all_ids)) {
                $this->load->model('htm/htm_task_staff');
                $con_ids = $this->htm_task_staff->getContractId($all_ids);
                if (!empty($con_ids)) {
                    $conId = implode(',', $con_ids);
                    $filter .= " and htm_contract.id in ({$conId})";
                }
            }
        }
        if (!empty($request_data['filter'])) {
            $filter .= " and {$request_data['filter']}";
        }
        $grid = $this->model->grid($request_data['select'], $filter, $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        //获取员工信息
        $emploInfo = $this->bmm_employees->getEmployees($cid);
        //获取文件信息
        $items = $grid['items'];
        foreach ($items as $i => $item) {
            $items[$i] = $this->contractChange($item, $emploInfo);
        }
        $grid['items'] = $items;
        $this->returnData($grid);
    }

    /**
     * @param $item
     * @param $emploInfo
     * @return mixed 补充合同信息
     */
    private function contractChange($item, $emploInfo)
    {
        $this->load->model('ckm/ckm_product');
        $this->load->model('ckm/ckm_position_num');
        $this->load->model('htm/htm_contract_file');
        $this->load->model('htm/htm_contract_marketing');
        $this->load->model('htm/htm_task_staff');
        $productInfo = $this->ckm_product->get_one('id,name', ['id' => $item['htm_task.product_id']]);
        $item['productName'] = $productInfo['name'];
        if ($item['htm_contract.hang_time'] > 0) {
            $da = (time() - $item['htm_contract.hang_time']) / (24 * 60 * 60);
            $item['htm_contract.hang'] = $item['htm_contract.hang'] + ceil($da);
        }
        $item['HT_create_info'] = $emploInfo[1][$item['htm_contract.create_by']];
        $item = $this->htm_contract_file->getFile($item);
        $market = $this->htm_contract_marketing->getMarket($item['htm_contract.id']);
        foreach ($market as $val) {
            $_market[] = ['employees_id' => $val, 'info' => $emploInfo[1][$val]];
        }
        $ii = $this->htm_task_staff->get_all('*', ['task_id' => $item['htm_task.id']]);
        foreach ($ii as $kk => $vv) {
            $ii[$kk]['user'] = $emploInfo[1][$vv['staff_id']];
        }
        $item['staff'] = $ii;
        $item['market'] = $_market;
        $item['position'] = $this->ckm_position_num->get_one('*', ['customer_id' => $item['htm_contract.customer_id']]);
        return $item;
    }

    /**
     * 编辑任务
     */
    public function edit_task_post()
    {
        $request_data = $this->check_param([
            'id' => ['任务ID', 'required', 'integer'],
            'urgent' => ['紧急程度', 'required', 'integer'],//1-一般 2-非常 3-紧急
            'remark' => ['任务备注'],
            'complete_time' => ['完成时间', 'integer'],
            'flow_status' => ['流程状态'],
            'is_done' => ['任务完结状态', 'integer'],//0-未 1-已完成
//            'participant' => ['参与人', 'required'],
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到任务');
        }
        if ($request_data['is_done'] == 1) {
            $request_data['done_time'] = time();
            $request_data['status'] = 3;
        }
//        //更改流程状态提交审批
//        if ($request_data['flow_status'] != $info['flow_status']) {
//            $request_data['status'] = 3;
//        }
        //编辑合同数据库
        $this->model->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 审批任务
     */
    public function sh_task_post()
    {
        $request_data = $this->check_param([
            'id' => ['任务ID', 'required', 'integer'],
            'status' => ['任务审批状态', 'required', 'integer'],// 0-未开始 1-不通过 2-通过 3-待审核
            'auth_remark' => ['任务审批备注']
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到任务');
        }
        $request_data['auth_time'] = time();
        $request_data['auth_id'] = $this->loginData['id'];
        $this->model->edit(['id' => $request_data['id']], $request_data);
//        if ($info['is_done'] == 1 && $request_data['status'] == 2) {
//            $this->load->model('');
//        }
        $this->returnData();
    }


//    public function index_get(){
//        $request_data = $this->check_param([
//            'title' => ['任务标题', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
//            'content' => ['任务内容', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
//            'feedback' => ['反馈', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
//            'guid' => ['UUID', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],   //  保留，原有系统存在字段
//            'status' => ['任务状态', 'integer'],    // 状态，根据实际情况前端定状态码
//            'owner_id' => ['任务负责人', 'integer'],
//            'publisher_id' => ['任务发布人', 'integer'],
//            'employee_id' => ['任务执行人', 'integer'],
//        ]);
//        $condition = [];
//        $condition['is_del'] = 0;
//        foreach ($request_data as $k => $v){
//            if(!$v){
//                continue;
//            }
//            if(is_int($v)){
//                $condition[$k] = $v;
//            }elseif (is_string($v)){
//                $condition[$k] = ['like', $v];
//            }
//        }
//        $condition['company_id'] = $this->loginData['company_id'];
//        $data = $this->model->grid("*", $condition, $request_data['page'], $request_data['page_size'], 'id desc', TRUE);
//        $this->returnData($data);
//    }
//
//    public function add_post()
//    {
//        $request_data = $this->check_param([
//            'title' => ['任务标题', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
//            'content' => ['任务内容', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
//            'feedback' => ['反馈', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
//            'guid' => ['UUID', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],   //  保留，原有系统存在字段
//            'status' => ['任务状态', 'required', 'integer'],    // 状态，根据实际情况前端定状态码
//            'start_time' => ['开始时间', 'required', 'integer'],
//            'end_time' => ['结束时间', 'required', 'integer'],
//            'dead_line' => ['任务期限', 'required', 'integer'],
//            'owner_id' => ['任务负责人', 'required', 'integer'],
//            'publisher_id' => ['任务发布人', 'required', 'integer'],
//            'employee_id' => ['任务执行人', 'required', 'integer'],
//        ], [], 'post');
//        $request_data['create_time'] = time();
//        $request_data['publisher_id'] = $this->loginData['id'];
//        $request_data['company_id'] = $this->loginData['company_id'];
//        $id = $this->model->add($request_data);
//
//        if (!$id) {
//            $this->returnError('添加失败');
//        }
//        $this->returnData(['id' => $id]);
//    }
//
//    public function edit_post()
//    {
//        // 判断权限
//
//
//
//        $request_data = $this->check_param([
//            'id' => ['ID', 'integer'],
//            'title' => ['任务标题', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]', 'max_length[200]'],
//            'content' => ['任务内容', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
//            'feedback' => ['反馈', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
//            'guid' => ['UUID', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],   //  保留，原有系统存在字段
//            'status' => ['任务状态', 'integer'],    // 状态，根据实际情况前端定状态码
//            'start_time' => ['开始时间', 'integer'],
//            'end_time' => ['结束时间', 'integer'],
//            'dead_line' => ['任务期限', 'integer'],
//            'owner_id' => ['任务负责人', 'integer'],
//            'publisher_id' => ['任务发布人', 'integer'],
//            'employee_id' => ['任务执行人', 'integer'],
//        ], [], 'post');
//
//        $condition = ['id' => $request_data['id']];
//        $res = $this->model->edit($condition, $request_data);
//        if ($res === false) {
//            $this->returnError('编辑失败');
//        }
//        $this->returnData([]);
//    }
//
//    public function del_post()
//    {
//        $request_data = $this->check_param([
//            'id' => ['ID', 'integer'],
//        ], [], 'post');
//        $res = $this->model->edit($request_data, ['is_del' => 1]);
//        if ($res === false) {
//            $this->returnError('删除失败');
//        }
//        $this->returnData([]);
//    }

}
