<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class Contract 合同模块
 */
class Api_contract extends Apibase
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('htm/htm_contract');
        $this->model = $this->htm_contract;
    }

    public function grid_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required'],
            'page' => ['第几页'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
            'market' => ['营销员']
        ], [], 'post');
        $cid = $this->loginData['cid'];
        $filter = "htm_contract.cid = {$cid}";
        $this->load->model('bmm/bmm_employees');
        if (!empty($request_data['market'])) {
            $all_ids = $this->bmm_employees->getEmps($request_data['market'], $cid);
            if (!empty($all_ids)) {
                $this->load->model('htm/htm_contract_marketing');
                $con_ids = $this->htm_contract_marketing->getContractId($all_ids);
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
     * 合同详情
     */
    public function contract_info_post()
    {
        $request_data = $this->check_param([
            'id' => ['合同ID', 'required', 'integer'],
        ], [], 'post');
        $cid = $this->loginData['cid'];
        $this->load->model('bmm/bmm_employees');
        $emploInfo = $this->bmm_employees->getEmployees($cid);
        $item = $this->model->info('*', ['htm_contract.id' => $request_data['id']]);
        if (!$item) {
            $this->returnError('未查询到信息');
        }
        $item = $this->contractChange($item, $emploInfo);
        $this->returnData($item);
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
        $this->load->model('htm/htm_task');
        $this->load->model('htm/htm_task_staff');
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
        $item['market'] = $_market;
        $taskList = $this->htm_task->getTask($item['htm_contract.id']);
        foreach ($taskList as $k => $v) {
            $ii = $this->htm_task_staff->get_all('*', ['task_id' => $v['id']]);
            foreach ($ii as $kk => $vv) {
                $ii[$kk]['user'] = !empty($emploInfo[1][$vv['staff_id']]) ? $emploInfo[1][$vv['staff_id']] : '';
            }
            $productInfo = $this->ckm_product->get_one('id,name', ['id' => $v['product_id']]);
            $taskList[$k]['productName'] = $productInfo['name'];
            $taskList[$k]['staff'] = $ii;
        }
        $item['task'] = $taskList;
        $item['position'] = $this->ckm_position_num->get_one('*', ['customer_id' => $item['htm_contract.customer_id']]);
        return $item;
    }

    /**
     * 新增长期合同
     */
    public function contract_long_add_post()
    {
        $request_data = $this->check_param([
            'customer_id' => ['客户ID', 'required', 'integer'],
            'contract_num' => ['合同编码'],
            'payment_cycle' => ['维护周期', 'integer'],
            'is_source' => ['来源', 'integer'],//1-线上 2-线下
            'signed_time' => ['签约时间', 'required'],
            'is_tax' => ['发票认证', 'integer'],//0-未 1-认证
            'remark' => ['备注'],
            'discount_total' => ['合同总额', 'numeric'],
            'account_book' => ['账本费用', 'numeric'],
            'receivables_way' => ['收款方式', 'integer'],//1-现金 2-支付宝 3-微信 4-银行卡
        ], [], 'post');
        $task_data = $this->check_param([
            'customer_id' => ['客户ID', 'required', 'integer'],
            'product_id' => ['服务项目', 'required', 'integer'],
            'count' => ['服务数量', 'integer'],
            'count_send' => ['服务赠送数量', 'integer'],
            'pricing' => ['标准价', 'numeric'],
            'discount' => ['合同折扣价', 'numeric'],
            'start_time' => ['开始时间', 'integer'],
            'end_time' => ['结束时间', 'integer'],
        ], [], 'post');
        $other_data = $this->check_param([
            'pos_id' => ['仓位ID', 'required', 'integer'],
            'market' => ['营销员', 'required'],//[1,2,3,4]
            'image' => ['上传图片'],//[{id:'',url:'','name':''},{}]
            'file' => ['上传文件'],//[{id:'',url:''},{}]
        ], [], 'post');
        /**
         * 验证成功后的逻辑
         * 1.新增合同
         * 2.新增合同服务任务
         * 3.新增合同营销员
         * 4.新增合同文件
         */
        $request_data['contract_type'] = 1;
        $request_data['create_by'] = $this->loginData['id'];
        $request_data['cid'] = $this->loginData['cid'];
        $request_data['create_time'] = time();
        $request_data['contract_code'] = $this->get_order_num();
        $request_data['total_monry'] = $request_data['discount_total'] + $request_data['account_book'];
        $this->db->trans_start();
        //新增合同
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        //分配仓位
        $this->load->model('ckm/ckm_position_num');
        $this->ckm_position_num->allotNum($other_data['pos_id'], $request_data['customer_id']);
        //获取服务流程状态
        $this->load->model('ckm/ckm_product');
        $pro_info = $this->ckm_product->info('*', ['ckm_product.id' => $task_data['product_id']]);
        if (!$pro_info) {
            $this->returnError('未查询到服务产品');
        }
        $task_data['contract_id'] = $id;
        $task_data['flow'] = $pro_info['ckm_process.status'];
        $task_data['cid'] = $this->loginData['cid'];
        $this->load->model('htm/htm_task');
        $task_id = $this->htm_task->add($task_data);
        $flow = json_decode($task_data['flow'], TRUE);
        $this->load->model('htm/htm_task_staff');
        foreach ($flow as $item) {
            $saveData = [
                'contract_id' => $id,
                'task_id' => $task_id,
                'process' => $item
            ];
            $this->htm_task_staff->add($saveData);
        }
        //新增营销员
        $marketing = json_decode($other_data['market'], true);
        $this->load->model('htm/htm_contract_marketing');
        $this->htm_contract_marketing->changeMarket($id, $marketing);
        //新增文件
        $image = json_decode($other_data['image'], true);
        $file = json_decode($other_data['file'], true);
        $this->load->model('htm/htm_contract_file');
        $this->htm_contract_file->changeFile($id, $request_data['customer_id'], $image, 1);
        $this->htm_contract_file->changeFile($id, $request_data['customer_id'], $file, 2);
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 编辑长期合同
     */
    public function contract_long_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['合同ID', 'required', 'integer'],
            'customer_id' => ['客户ID', 'required', 'integer'],
            'contract_num' => ['合同编码'],
            'payment_cycle' => ['维护周期', 'integer'],
            'is_source' => ['来源', 'integer'],//1-线上 2-线下
            'signed_time' => ['签约时间', 'required'],
            'is_tax' => ['发票认证', 'integer'],//0-未 1-认证
            'remark' => ['备注'],
            'discount_total' => ['合同总额', 'numeric'],
            'account_book' => ['账本费用', 'numeric'],
            'receivables_way' => ['收款方式', 'integer'],//1-现金 2-支付宝 3-微信 4-银行卡
        ], [], 'post');
        $task_data = $this->check_param([
            'customer_id' => ['客户ID', 'required', 'integer'],
            'count' => ['服务数量', 'integer'],
            'count_send' => ['服务赠送数量', 'integer'],
            'pricing' => ['标准价', 'numeric'],
            'discount' => ['合同折扣价', 'numeric'],
            'start_time' => ['开始时间', 'integer'],
            'end_time' => ['结束时间', 'integer'],
        ], [], 'post');
        $other_data = $this->check_param([
            'pos_id' => ['仓位ID', 'required', 'integer'],
            'market' => ['营销员', 'required'],//[1,2,3,4]
            'image' => ['上传图片'],//[{id:'',url:''},{}]
            'file' => ['上传文件'],//[{id:'',url:''},{}]
        ], [], 'post');
        /**
         * 验证成功后的逻辑
         * 1.新增合同
         * 2.新增合同服务任务
         * 3.新增合同营销员
         * 4.新增合同文件
         */
        $this->db->trans_start();
        $request_data['total_monry'] = $request_data['discount_total'] + $request_data['account_book'];
        //新增合同
        $request_data['auth_status1'] = 0;
        $this->model->edit(['id' => $request_data['id']], $request_data);
        $id = $request_data['id'];
        $this->load->model('htm/htm_task');
        $this->htm_task->edit(['contract_id' => $id, 'customer_id' => $task_data['customer_id']], $task_data);
        //分配仓位
        $this->load->model('ckm/ckm_position_num');
        $this->ckm_position_num->allotNum($other_data['pos_id'], $request_data['customer_id']);
        //营销员
        $marketing = json_decode($other_data['market'], true);
        $this->load->model('htm/htm_contract_marketing');
        $this->htm_contract_marketing->changeMarket($id, $marketing);
        //文件
        $image = json_decode($other_data['image'], true);
        $file = json_decode($other_data['file'], true);
        $this->load->model('htm/htm_contract_file');
        $this->htm_contract_file->changeFile($id, $request_data['customer_id'], $image, 1);
        $this->htm_contract_file->changeFile($id, $request_data['customer_id'], $file, 2);
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 新增一次性合同
     */
    public function contract_one_add_post()
    {
        $request_data = $this->check_param([
            'customer_id' => ['客户ID', 'required', 'integer'],
            'contract_num' => ['合同编码'],
            'is_source' => ['来源', 'integer'],//1-线上 2-线下
            'signed_time' => ['签约时间', 'required'],
            'is_tax' => ['发票认证', 'integer'],//0-未 1-认证
            'remark' => ['备注'],
            'discount_total' => ['合同总额', 'numeric'],
            'receivables_way' => ['收款方式', 'integer'],//1-现金 2-支付宝 3-微信 4-银行卡
        ], [], 'post');
        $other_data = $this->check_param([
            'task_json' => ['服务信息', 'required'],//[{"customer_id":"10010","product_id":"1","start_time":"123","end_time":"123"}]
            'pos_id' => ['仓位ID', 'required', 'integer'],
            'market' => ['营销员', 'required'],//[1,2,3,4]
            'image' => ['上传图片'],//[{id:'',url:''},{}]
            'file' => ['上传文件'],//[{id:'',url:''},{}]
        ], [], 'post');
        /**
         * 验证成功后的逻辑
         * 1.新增合同
         * 2.新增合同服务任务
         * 3.新增合同营销员
         * 4.新增合同文件
         */
        $request_data['contract_type'] = 2;
        $request_data['create_by'] = $this->loginData['id'];
        $request_data['cid'] = $this->loginData['cid'];
        $request_data['create_time'] = time();
        $request_data['contract_code'] = $this->get_order_num();
        $request_data['total_monry'] = $request_data['discount_total'];
        $this->db->trans_start();
        //新增合同
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        //分配仓位
        $this->load->model('ckm/ckm_position_num');
        $this->ckm_position_num->allotNum($other_data['pos_id'], $request_data['customer_id']);
        //添加服务
        $this->load->model('htm/htm_task');
        $task = json_decode($other_data['task_json'], true);
        $this->htm_task->changeProduct($id, $task);
        //新增营销员
        $marketing = json_decode($other_data['market'], true);
        $this->load->model('htm/htm_contract_marketing');
        $this->htm_contract_marketing->changeMarket($id, $marketing);
        //新增文件
        $image = json_decode($other_data['image'], true);
        $file = json_decode($other_data['file'], true);
        $this->load->model('htm/htm_contract_file');
        $this->htm_contract_file->changeFile($id, $request_data['customer_id'], $image, 1);
        $this->htm_contract_file->changeFile($id, $request_data['customer_id'], $file, 2);
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 编辑一次性合同
     */
    public function contract_one_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['合同ID', 'required', 'integer'],
            'customer_id' => ['客户ID', 'required', 'integer'],
            'contract_num' => ['合同编码'],
            'is_source' => ['来源', 'integer'],//1-线上 2-线下
            'signed_time' => ['签约时间', 'required'],
            'is_tax' => ['发票认证', 'integer'],//0-未 1-认证
            'remark' => ['备注'],
            'discount_total' => ['合同总额', 'numeric'],
            'receivables_way' => ['收款方式', 'integer'],//1-现金 2-支付宝 3-微信 4-银行卡
        ], [], 'post');
        $other_data = $this->check_param([
            'task_json' => ['服务信息', 'required'],//[{"customer_id":"10010","product_id":"1","start_time":"123","end_time":"123"}]
            'pos_id' => ['仓位ID', 'required', 'integer'],
            'market' => ['营销员', 'required'],//[1,2,3,4]
            'image' => ['上传图片'],//[{id:'',url:''},{}]
            'file' => ['上传文件'],//[{id:'',url:''},{}]
        ], [], 'post');
        /**
         * 验证成功后的逻辑
         * 1.新增合同
         * 2.新增合同服务任务
         * 3.新增合同营销员
         * 4.新增合同文件
         */
        $this->db->trans_start();
        $request_data['total_monry'] = $request_data['discount_total'];
        //新增合同
        $request_data['auth_status1'] = 0;
        $this->model->edit(['id' => $request_data['id']], $request_data);
        $id = $request_data['id'];
        //分配仓位
        $this->load->model('ckm/ckm_position_num');
        $this->ckm_position_num->allotNum($other_data['pos_id'], $request_data['customer_id']);
        //添加服务
        $this->load->model('htm/htm_task');
        $task = json_decode($other_data['task_json'], true);
        $this->htm_task->changeProduct($id, $task);
        //新增营销员
        $marketing = json_decode($other_data['market'], true);
        $this->load->model('htm/htm_contract_marketing');
        $this->htm_contract_marketing->changeMarket($id, $marketing);
        //新增文件
        $image = json_decode($other_data['image'], true);
        $file = json_decode($other_data['file'], true);
        $this->load->model('htm/htm_contract_file');
        $this->htm_contract_file->changeFile($id, $request_data['customer_id'], $image, 1);
        $this->htm_contract_file->changeFile($id, $request_data['customer_id'], $file, 2);
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 合同审批
     */
    public function auth_ht1_post()
    {
        $request_data = $this->check_param([
            'id' => ['合同ID', 'required', 'integer'],
            'auth_status1' => ['审核状态', 'required', 'integer'],//0-未开始 1-未通过 2-通过
            'auth_remark1' => ['审批备注'],
        ], [], 'post');
        $this->auth_ht1($request_data);
        $this->returnData();
    }

    /**
     * 批量合同审批
     */
    public function batch_auth_ht1_post()
    {
        $request_data = $this->check_param([
            'data' => ['数据', 'required'],//格式[{'id':'1','auth_status1':'2','auth_remark1':'123'},{}]
        ], [], 'post');
        $batch = json_decode($request_data['data'], true);
        if (!$batch) {
            $this->returnError('数据格式错误，请检查！');
        }
        foreach ($batch as $item) {
            $this->auth_ht1($item);
        }
        $this->returnData();
    }

    /**
     * 合同审批方法
     */
    public function auth_ht1($request_data)
    {
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        if ($info['auth_status1'] == 0 && $info['status'] == 2 && in_array($info['auth_status2'], [0, 2])) {
            $this->returnError('该合同已激活,无法撤销');
        }
        $loginData = $this->get_login_info();
        $request_data['auth_id1'] = $loginData['id'];
        $request_data['auth_time1'] = time();
        $this->model->edit(['id' => $request_data['id']], $request_data);
    }

    /**
     * 合同授权编辑
     */
    public function auth_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['合同ID', 'required', 'integer'],
            'status' => ['合同状态', 'integer'],//0-未激活 1-已激活 2-已挂起 3-已结束 4-已作废
            'outh_remark' => ['备注'],
            'assign' => ['分配员工'],
            //长期合同：[{"task_id":"10","process":"收单","staff_id":"1"}]  一次性合同：[{"task_id":"10","staff_id":"1"}]
        ], [], 'post');
        $info = $this->model->info('*', ['htm_contract.id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到数据');
        }
        //激活时间
        if ($request_data['status'] == 1 && $info['htm_contract.activate_time'] == 0) {
            $request_data['activate_time'] = time();
        }
        //获取挂起
        if ($request_data['status'] == 1 && $info['hang_time'] > 0) {
            $day = (time() - $info['hang_time']) / (24 * 60 * 60);
            $request_data['hang'] = $info['hang'] + ceil($day);
            $request_data['hang_time'] = 0;
        }

        if ($request_data['status'] == 2) {
            $request_data['hang_time'] = time();
        }
        $request_data['auth_status2'] = 0;
        $assignList = json_decode($request_data['assign'], true);
        unset($request_data['assign']);
        $this->model->edit(['id' => $request_data['id']], $request_data);
        $this->load->model('htm/htm_task_staff');
        foreach ($assignList as $i => $item) {
            $where = [
                'contract_id' => $request_data['id'],
                'task_id' => $item['task_id'],
            ];
            if (isset($item['process'])) {
                $where['process'] = $item['process'];
            }
            $this->htm_task_staff->edit($where, $item);
        }
        $this->returnData();
    }

    /**
     * 授权审批
     */
    public function auth_ht2_post()
    {
        $request_data = $this->check_param([
            'id' => ['合同ID', 'required', 'integer'],
            'auth_status2' => ['审核状态', 'required', 'integer'],//0-未开始 1-未通过 2-通过
            'auth_remark2' => ['审批备注'],
        ], [], 'post');
        $this->auth_ht2($request_data);
        $this->returnData();
    }

    /**
     * 批量授权审批
     */
    public function batch_auth_ht2_post()
    {
        $request_data = $this->check_param([
            'data' => ['数据', 'required'],//格式[{'id':'1','auth_status2':'2','auth_remark2':'123'},{}]
        ], [], 'post');
        $batch = json_decode($request_data['data'], true);
        if (!$batch) {
            $this->returnError('数据格式错误，请检查！');
        }
        foreach ($batch as $item) {
            $this->auth_ht2($item);
        }
        $this->returnData();
    }

    /**
     * 授权审批方法
     */
    private function auth_ht2($request_data)
    {
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $loginData = $this->get_login_info();
        $request_data['auth_id2'] = $loginData['id'];
        $request_data['auth_time2'] = time();
        $res = $this->model->edit(['id' => $request_data['id']], $request_data);
        if ($res === false) {
            $this->returnError('审核失败');
        }
        //授权成功并类型为记账报税 加入服务期
        if ($request_data['auth_status2'] == 2 && $info['contract_type'] == 1 && $info['is_service'] == 0) {
            $this->load->model('htm/htm_task');
            $taskInfo = $this->htm_task->get_one('*', ['contract_id' => $request_data['id']]);
            $this->load->model('jzm/jzm_service_info');
            $this->jzm_service_info->add_service($taskInfo);
            $this->model->edit(['id' => $request_data['id']], ['is_service' => 1]);
        }
        $this->load->model('khm/khm_customer');
        if ($info['contract_type'] == 1) {
            $this->khm_customer->edit(['id' => $info['customer_id']], ['status' => 1, 'z_type' => 1]);
        } else {
            $this->khm_customer->edit(['id' => $info['customer_id']], ['status' => 1, 'c_type' => 1]);
        }
    }

    /**
     * 核销列表
     */
    public function hx_list_post()
    {
        $request_data = $this->check_param([
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'where' => ['自定义查询'], //高级搜索  xxx字段=1 and xxx字段=2 or xxx字段=3
        ], [], 'post');
        $cid = $this->loginData['cid'];
        $this->load->model('htm/htm_contract');
        $this->db->distinct(TRUE);
        $data = $this->htm_contract->get_all("htm_contract.customer_id");
        if (!$data) {
            $this->returnError('信息为空');
        }
        foreach ($data as $item) {
            $customer_ids[] = $item['customer_id'];
        }
        $customer_ids_s = implode(',', $customer_ids);
        $filter = "khm_customer.introduce != '' and khm_customer.id in ({$customer_ids_s})";
        if (isset($request_data['where'])) {
            $filter .= ' and ' . $request_data['where'];
        }
        $f7 = $this->htm_contract->f7("khm_customer.introduce", $filter);
        foreach ($f7 as $item) {
            $f7 = $this->htm_contract->f7("htm_contract.id", ['khm_customer.introduce' => $item['introduce']]);
            $f8 = $this->htm_contract->f7("htm_contract.id", ['khm_customer.introduce' => $item['introduce'], 'htm_contract.status' => 3]);
            $name[$item['introduce']] = [count($f7), count($f8)];
        }
        $grid = $this->htm_contract->grid("*", $filter, $request_data['page'], $request_data['page_size'], 'htm_contract.id desc', TRUE);
        $items = $grid['items'];
        foreach ($items as $ie => $item) {
            foreach ($name as $i => $ii) {
                if ($item['khm_customer.introduce'] == $i) {
                    $items[$ie]['total'] = $ii[0];
                    $items[$ie]['success'] = $ii[1];
                }
            }
        }
        $grid['items'] = $items;


        $this->load->model('bmm/bmm_employees');
        //获取员工信息
        $emploInfo = $this->bmm_employees->getEmployees($cid);
        $items = $grid['items'];
        foreach ($items as $i => $item) {
            $items[$i] = $this->contractChange($item, $emploInfo);
        }
        $grid['items'] = $items;

        $this->returnData($grid);
    }

    /**
     * 合同作废
     */
    public function cancel_post()
    {
        $request_data = $this->check_param([
            'id' => ['合同ID', 'required', 'integer'],
            'status' => ['订单状态', 'required', 'integer'],//0-未执行 1-执行中 2-已挂起 3-已结束 4-已作废
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $request_data['auth_status2'] = 0;
        $id = $this->model->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('审核失败');
        }
        $this->returnData();
    }

    /**
     * 合同导入
     */
    public function upload_batch_post()
    {
        $excel = $_FILES['contract'];
        if (!isset($excel['name'])) {
            $this->returnError('上传错误');
        }
        if ($excel["error"] > 0) {
            $this->returnError($excel["error"]);
        }
        $fileExplode = explode('.', $excel['name']);
        if (!in_array($fileExplode[1], ['xls', 'xlsx'])) {
            $this->returnError('文件格式错误');
        }
        $excelRes = readExcel($excel['tmp_name'], $fileExplode[1]);
        if (!$excelRes) {
            $this->returnError('读取表格失败');
        }
        $cid = $this->get_login_info()['cid'];
        $cid = 1;
        $where = ['cid' => $cid];
        $this->load->model('khm/khm_customer');
        $customerList = $this->khm_customer->get_all('*', $where);
        foreach ($customerList as $item) {
            $customer_list[$item['name']] = $item['id'];
        }
        $this->load->model('ckm/ckm_product');
        $productList = $this->ckm_product->f7('*', ['ckm_product.cid' => $cid]);
        foreach ($productList as $item) {
            $product_list[$item['ckm_product.name']] = $item['ckm_product.id'];
            $process_list[$item['ckm_product.id']] = $item['ckm_process.status'];
        }
        $this->load->model('bmm/bmm_employees');
        $employeesList = $this->bmm_employees->get_all('*', $where);
        foreach ($employeesList as $item) {
            $employees_list[$item['name']] = $item['id'];
        }
        $dataKey = [
            'A' => ['key' => 'customer_id', 'name' => '公司名称', 'required' => true, 'key_type' => 'obj', 'key_obj' => $customer_list],
            'B' => ['key' => 'contract_num', 'name' => '合同编号', 'required' => true],
            'C' => ['key' => 'contract_type', 'name' => '合同类型', 'required' => true, 'key_type' => 'obj', 'key_obj' => ['长期合同' => 1, '一次性合同' => 2]],
            'D' => ['key' => 'payment_cycle', 'name' => '维护周期', 'required' => true, 'key_type' => 'obj', 'key_obj' => ['每月' => 1, '季度' => 2, '半年' => 3, '一年' => 4]],
            'E' => ['key' => 'is_source', 'name' => '订单来源', 'required' => true, 'key_type' => 'obj', 'key_obj' => ['线上' => 1, '线下' => 2]],
            'F' => ['key' => 'signed_id', 'name' => '签约员', 'required' => true, 'key_type' => 'obj', 'key_obj' => $employees_list],
            'G' => ['key' => 'signed_time', 'name' => '签约时间', 'required' => true, 'key_type' => 'timestamp'],
            'H' => ['key' => 'status', 'name' => '订单状态', 'required' => true, 'key_type' => 'obj', 'key_obj' => ['未激活' => 0, '已激活' => 1, '已挂起' => 2, '已结束' => 3]],
            'I' => ['key' => 'is_tax', 'name' => '发票认证', 'required' => true, 'key_type' => 'obj', 'key_obj' => ['是' => 1, '否' => 0]],
            'J' => ['key' => 'market_id', 'name' => '营销员', 'required' => true, 'key_type' => 'obj', 'key_obj' => $employees_list],
            'K' => ['key' => 'service_items', 'name' => '服务产品', 'required' => true, 'key_type' => 'obj', 'key_obj' => $product_list],
            'L' => ['key' => 'service_num', 'name' => '数量', 'required' => true],
            'M' => ['key' => 'service_send_num', 'name' => '赠送', 'required' => true],
            'N' => ['key' => 'service_pricing', 'name' => '标准价', 'required' => true],
            'O' => ['key' => 'contract_discount', 'name' => '折后价', 'required' => true],
            'P' => ['key' => 'contract_amount', 'name' => '折后总额', 'required' => true],
            'Q' => ['key' => 'accountbook_cost', 'name' => '账本费'],
            'R' => ['key' => 'receivables_way', 'name' => '收款方式', 'key_type' => 'obj', 'key_obj' => ['现金' => 1, '支付宝' => 2, '微信' => 3, '银行卡' => 4]],
            'S' => ['key' => 'start_time', 'name' => '开始时间', 'key_type' => 'timestamp'],
            'T' => ['key' => 'end_time', 'name' => '结束时间', 'key_type' => 'timestamp'],
            'U' => ['key' => 'remark', 'name' => '备注'],
        ];
        $dataF = [];
        unset($excelRes[1]);
        $create_by = $this->get_login_info()['id'];
        foreach ($excelRes as $line => $row) {
            if (!$row['A']) {
                continue;   //  防止表格空白行
            }
            $tem = [];
            foreach ($dataKey as $dk => $dv) {
                $key = $dv['key'];
                if (isset($dv['required']) && $dv['required'] && (!isset($row[$dk]) || !$row[$dk])) {
                    $this->returnError("{$dv['name']}不能为空，第{$dk}{$line}单元格");
                }
                $value = isset($row[$dk]) ? $row[$dk] : '';
                if (isset($dv['key_type'])) {
                    switch ($dv['key_type']) {
                        case 'timestamp':
                            $value = strtotime($value);
                            break;
                        case 'obj':
                            if (!isset($dv['key_obj'][$value])) {
                                $this->returnError("{$dv['name']}错误，第{$dk}{$line}单元格");
                            }
                            $value = $dv['key_obj'][$value];
                            break;
                    }
                }
                $tem[$key] = $value;
            }
            $tem['flow'] = $process_list[$tem['service_items']];
            $tem['service_items'] = (int)$tem['service_items'];
            $tem['create_by'] = $create_by;
            $tem['create_time'] = time();
            $tem['contract_code'] = $this->get_order_num();
            $dataF[] = $tem;
        }
        $res = $this->model->add_batch($dataF);
        if (!$res) {
            $this->returnError('添加失败');
        }
        $this->returnData();
    }
}
