<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class Service 记账管理
 */
class Api_service extends Apibase
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('jzm/jzm_service_info');
        $this->model = $this->jzm_service_info;
    }

    /**
     * 服务期
     */
    public function index_get()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'], //类型1-收单 2-整单 3-记账 4-客服 5-报税 6-送单
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
        ], [], 'get');
        /**
         * 验证成功后的逻辑
         */
        switch ($request_data['type']) {
            case 1:
                $typeName = '收单';
                break;
            case 2:
                $typeName = '整单';
                break;
            case 3:
                $typeName = '记账';
                break;
            case 4:
                $typeName = '客服';
                break;
            case 5:
                $typeName = '报税';
                break;
            case 6:
                $typeName = '送单';
                break;
        }
        $cid = $this->loginData['cid'];
        $month = date('Ym', time());
        $where = [
            'jzm_service_info.type' => $request_data['type'],
            'jzm_service_info.time <=' => $month,
            'htm_contract.status' => 1
        ];
        $grid = $this->model->grid("*", $where, $request_data['page'], $request_data['page_size'], 'time desc', TRUE);
        //获取员工信息
        $this->load->model('bmm/bmm_employees');
        $this->load->model('htm/htm_task_staff');
        $emploInfo = $this->bmm_employees->getEmployees($cid);
        //获取审批人
//        $this->load->model('spm/spm_manage');
//        $auth_ids = $this->spm_manage->getUserId($typeName, $cid);
//        foreach ($auth_ids as $kk => $vv) {
//            $authList[] = $emploInfo[1][$vv];
//        }
        $items = $grid['items'];
        foreach ($items as $i => $item) {
            $items[$i] = $this->serviceChange($item, $emploInfo, $typeName);
        }
        $grid['items'] = $items;
        $this->returnData($grid);
    }

    private function serviceChange($item, $emploInfo, $typeName)
    {
        $this->load->model('htm/htm_task_staff');
        $item['HT_create_info'] = $emploInfo[1][$item['htm_contract.create_by']];
        $item['HT_auth_info'] = $emploInfo[1][$item['jzm_service_info.auth_id']];
        $staff_ids = $this->htm_task_staff->getStaff($item['htm_contract.id'], $typeName);
        foreach ($staff_ids as $kk => $vv) {
            $staffList[] = $emploInfo[1][$vv];
        }
        $item['HT_staff_info'] = $staffList;
        return $item;
    }

    /**
     * 记账管理TOP
     */
    public function assistant_list_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'], //类型1-收单 2-整单 3-记账 4-客服 5-报税 6-送单
        ], [], 'post');
        $this->load->model('mbm/mbm_assistant');
        $this->load->model('htm/htm_contract');
        //目标条件
        $where = ['user_id' => $this->get_login_info()['id']];
        $assistant = $this->mbm_assistant->get_one('*', $where);
        //查询条件
        $startTime = strtotime(date("Y-m-01"));
        $endTime = strtotime(date("Y-m-01") . " +1 month -1 day");
//        $where2 = ['assign_staff_id' => $this->get_login_info()['id']];
        $where2['create_time >='] = $startTime;
        $where2['create_time <='] = $endTime;
        $dMonth = date("Ym");
        switch ($request_data['type']) {
            case 1:
                $name = 'shd';
                $typeName = '收单';
                break;
            case 2:
                $name = 'zd';
                $typeName = '整单';
                break;
            case 3:
                $name = 'jz';
                $typeName = '记账';
                break;
            case 4:
                $name = 'kf';
                $typeName = '客服';
                break;
            case 5:
                $name = 'bs';
                $typeName = '报税';
                break;
            case 6:
                $name = 'sd';
                $typeName = '送单';
                break;
        }
        $this->load->model('htm/htm_task_staff');
        $contractIds = $this->htm_task_staff->processGetContract($this->loginData['id'], $typeName);
        /*
         * 本月新增
         */
        $contracts = $this->htm_contract->get_all('id', $where2);
        foreach ($contracts as $item) {
            $contract_ids[] = $item['id'];
        }
        $contract_ids = array_intersect($contractIds, $contract_ids);
        $contract_ids_s = implode(',', $contract_ids);
        $this->db->distinct(TRUE);
        if ($contract_ids_s) {
            $service_ids = $this->model->get_all('contract_id', "contract_id in ({$contract_ids_s}) and type = {$request_data['type']}");
        }
        $months = [
            'total' => 0,
            'complete' => 0,
            'none' => 0,
            'name' => '本月新增'
        ];
        if ($service_ids) {
            foreach ($service_ids as $item) {
                $service_con_ids[] = $item['contract_id'];
            }
            $service_con_ids_s = implode(',', $service_con_ids);
            $num = $this->model->get_all('contract_id', "id in ({$service_con_ids_s}) and status = 3");
            $months = [
                'total' => count($service_ids),
                'complete' => count($num),
                'none' => count($service_ids) - count($num),
                'name' => '本月新增'
            ];
        }
        /*
         * 目标
         */
        $all = $this->model->get_all('id', "type = {$request_data['type']} and time = {$dMonth} and status = 2");
        $to = isset($assistant[$name]) ? $assistant[$name] : 0;
        $assistants = [
            'total' => $to,
            'complete' => count($all),
            'none' => $to - count($all),
            'name' => '目标'
        ];
        /*
         * 累计任务
         */
        $all = $this->model->get_all('id', "type = {$request_data['type']}");
        $all2 = $this->model->get_all('id', "type = {$request_data['type']} and status = 2");
        $total = [
            'total' => count($all),
            'complete' => count($all2),
            'none' => count($all) - count($all2),
            'name' => '累计任务'
        ];
        /*
         * 历史积压
         */
        $all = $this->model->get_all('id', "type = {$request_data['type']} and time < {$dMonth}");
        $all2 = $this->model->get_all('id', "type = {$request_data['type']} and status = 2 and time < {$dMonth}");
        $history = [
            'total' => count($all),
            'complete' => count($all2),
            'none' => count($all) - count($all2),
            'name' => '历史积压'
        ];
        $data = [
            'months' => $months,
            'assistants' => $assistants,
            'total' => $total,
            'history' => $history
        ];
        $this->returnData($data);
    }

    /**
     * 报税TOP圈圈
     */
    public function bs_assistant_list_post()
    {
        $request_data = $this->check_param([
            'month' => ['月份', 'integer'], //201701
        ], [], 'post');
        $month = empty($request_data['month']) ? date("Ym") : $request_data['month'];
        $service_list = $this->model->get_all('id', ['time' => $month]);
        if (!$service_list) {
            $this->returnData2();
        }
        foreach ($service_list as $item) {
            $slist[] = $item['id'];
        }
        $slist_s = implode(',', $slist);
        $this->load->model('jzm/jzm_tax_details');
        $tax_list = $this->jzm_tax_details->get_one('sum(is_invoice) as is_invoice,sum(is_state_tax) as is_state_tax,sum(is_local_tax) as is_local_tax', "service_id in ({$slist_s})");
        $data = [
            'invoice' => ['total' => count($slist), 'complete' => count($tax_list['is_invoice']), 'none' => count($slist) - count($tax_list['is_invoice']), 'name' => '发票认证'],
            'state_tax' => ['total' => count($slist), 'complete' => count($tax_list['is_state_tax']), 'none' => count($slist) - count($tax_list['is_state_tax']), 'name' => '国税申报'],
            'local_tax' => ['total' => count($slist), 'complete' => count($tax_list['is_local_tax']), 'none' => count($slist) - count($tax_list['is_local_tax']), 'name' => '地税申报'],
        ];
        $this->returnData($data);
    }

    /**
     * 客户服务
     */
    public function customer_post()
    {
        $request_data = $this->check_param([
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'where' => ['自定义查询'], //高级搜索  xxx字段=1 and xxx字段=2 or xxx字段=3
        ], [], 'post');
        $filter = "htm_contract.status = 1 and htm_contract.contract_type =1";
        if (isset($request_data['where'])) {
            $filter .= ' and ' . $request_data['where'];
        }
        $this->load->model('htm/htm_contract');
        $data = $this->htm_contract->grid("htm_contract.customer_id", $filter, $request_data['page'], $request_data['page_size'], 'htm_contract.id desc', TRUE);
        $items = $data['items'];
        $this->load->model('khm/khm_customer');
        if ($items) {
            foreach ($items as $i => $v) {
                $items[$i]['info'] = $this->khm_customer->info('*', ['khm_customer.id' => $v['customer_id']]);
            }
        }
        $data['items'] = $items;
        $this->returnData($data);
    }

    /**
     *  具体的业务
     */
    public function service_list_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'], //类型1-收单 2-整单 3-记账 4-客服 5-报税 6-送单
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'month1' => ['查询月份-头', 'integer'], //格式 201701 不传默认查询今年1月份
            'month2' => ['查询月份-尾', 'integer'], //格式 201701 不传默认查询今年12月份
            'name' => ['客户名称'],
            'where' => ['自定义查询'], //高级搜索  xxx字段=1 and xxx字段=2 or xxx字段=3
        ], [], 'get_post');
        switch ($request_data['type']) {
            case 1:
                $typeName = '收单';
                break;
            case 2:
                $typeName = '整单';
                break;
            case 3:
                $typeName = '记账';
                break;
            case 4:
                $typeName = '客服';
                break;
            case 5:
                $typeName = '报税';
                break;
            case 6:
                $typeName = '送单';
                break;
        }
        $filter = "jzm_service_info.type = {$request_data['type']} and htm_contract.status = 1 ";
        $month = date('Y', time());
        if (!isset($request_data['month1'])) {
            $request_data['month1'] = $month . '01';
        }
        if (!isset($request_data['month2'])) {
            $request_data['month2'] = $month . '12';
        }
        $filter .= "and jzm_service_info.time >= {$request_data['month1']} and jzm_service_info.time <= {$request_data['month2']} ";
        if (isset($request_data['name'])) {
            $filter .= "and (khm_customer.name like '%{$request_data['name']}%' or htm_contract.assign_staff_name like '%{$request_data['name']}%')";
        }
        if (isset($request_data['where'])) {
            $filter .= ' and ' . $request_data['where'];
        }
        /**
         * 验证成功后的逻辑
         */
        $cid = $this->loginData['cid'];
        $this->load->model('htm/htm_task');
        $this->load->model('bmm/bmm_employees');
        $this->load->model('htm/htm_task_staff');
        $this->load->model('ckm/ckm_position_num');
        $emploInfo = $this->bmm_employees->getEmployees($cid);
        $select = '';
        $data = $this->model->grid("jzm_service_info.contract_id,jzm_service_info.customer_id", $filter, $request_data['page'], $request_data['page_size'], 'time desc', TRUE);
        $list = $data['items'];
        if ($list) {
            foreach ($list as $i => $v) {
                $staff_ids = $this->htm_task_staff->getStaff($v['contract_id'], $typeName);
                $list[$i]['position'] = $this->ckm_position_num->get_one('*', ['customer_id' => $v['customer_id']]);
                foreach ($staff_ids as $kk => $vv) {
                    $staffList[] = $emploInfo[1][$vv];
                }
                $list[$i]['HT_staff_info'] = $staffList;
                $list[$i]['task'] = $this->htm_task->get_one("*", ['contract_id' => $v['contract_id'], 'is_del' => 0]);
                $where = $filter . " and jzm_service_info.contract_id = {$v['contract_id']}";
                $list[$i]['list'] = $this->model->f7("*", $where, 'time asc', TRUE);
            }
        }
        $data['items'] = $list;
        $this->returnData($data);
    }

    /**
     * 整单提交审批
     */
    public function arrange_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID编号', 'required', 'integer'],
            'status' => ['状态', 'required', 'integer']
        ], [], 'post');
        $info = $this->model->info('*', ['jzm_service_info.id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('没有查询到信息');
        }
        $info = $this->taskChange($info, '整单');
        $id = $this->model->edit(['id' => $request_data['id']], ['status' => 1, 'update_at' => time()]);
        //提交到审批
        $this->load->model('spm/spm_approves');
        $this->spm_approves->add_approves(15, $this->loginData, $info, $info['khm_customer.name'], $info['khm_customer.id']);
        if (!$id) {
            $this->returnError('提交失败');
        }
        $this->returnData();
    }

    private function taskChange($info, $typeName)
    {
        $cid = $this->loginData['cid'];
        $this->load->model('htm/htm_task');
        $this->load->model('htm/htm_task_staff');
        $this->load->model('bmm/bmm_employees');
        $emploInfo = $this->bmm_employees->getEmployees($cid);
        $info['task'] = $this->htm_task->get_one('*', ['contract_id' => $info['htm_contract.id']]);
        $staff_ids = $this->htm_task_staff->getStaff($info['htm_contract.id'], $typeName);
        foreach ($staff_ids as $kk => $vv) {
            $staffList[] = $emploInfo[1][$vv];
        }
        $info['staff_info'] = $staffList;
        return $info;
    }

    /**
     * 记账提交页面
     */
    public function accounting_add_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
            'this_year' => ['截至本年收入', 'required', 'numeric'],
            'lx_11_month' => ['连续11个月收入', 'required', 'numeric'],
            'receivable' => ['应收账款', 'required', 'numeric'],
            'account_payable' => ['应付账款', 'required', 'numeric'],
            'receivable_others' => ['其他应收款', 'required', 'numeric'],
            'payable_others' => ['其他应付款', 'required', 'numeric'],
            'tax_bearing_rate' => ['税负率', 'required', 'integer'],
            'lack_costing_invoice' => ['欠成本发票', 'required', 'numeric'],
            'lack_expense_invoice' => ['欠费用发票', 'required', 'numeric'],
            'accumulated_loss' => ['本年累计亏损', 'required', 'numeric'],
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['service_id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $this->load->model('jzm/jzm_accounting_details');
        /*
         *  开启事务
         */
        $this->db->trans_start();
        $id = $this->jzm_accounting_details->add($request_data);
        $this->model->edit(['id' => $request_data['service_id']], ['status' => 1, 'update_at' => time()]);
        /*
         *  添加审批流程
         */
        $info = $this->model->info('*', ['jzm_service_info.id' => $request_data['service_id']]);
        $info = $this->taskChange($info, '记账');
        $this->load->model('spm/spm_approves');
        $this->spm_approves->add_approves(6, $this->get_login_info(), $info, $info['khm_customer.name'], $info['khm_customer.id']);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 记账详情
     */
    public function accounting_info_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('jzm/jzm_accounting_details');
        $info = $this->jzm_accounting_details->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $this->returnData($info);
    }

    /**
     * 记账详情
     */
    public function accounting_info2_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('jzm/jzm_accounting_details');
        $info = $this->jzm_accounting_details->get_one('*', ['service_id' => $request_data['service_id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $this->returnData($info);
    }

    /**
     * 记账编辑页面
     */
    public function accounting_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
            'service_id' => ['服务ID', 'required', 'integer'],
            'this_year' => ['截至本年收入', 'required', 'numeric'],
            'lx_11_month' => ['连续11个月收入', 'required', 'numeric'],
            'receivable' => ['应收账款', 'required', 'numeric'],
            'account_payable' => ['应付账款', 'required', 'numeric'],
            'receivable_others' => ['其他应收款', 'required', 'numeric'],
            'payable_others' => ['其他应付款', 'required', 'numeric'],
            'tax_bearing_rate' => ['税负率', 'required', 'integer'],
            'lack_costing_invoice' => ['欠成本发票', 'required', 'numeric'],
            'lack_expense_invoice' => ['欠费用发票', 'required', 'numeric'],
            'accumulated_loss' => ['本年累计亏损', 'required', 'numeric'],
        ], [], 'post');
        $this->load->model('jzm/jzm_accounting_details');
        $info = $this->jzm_accounting_details->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $id = $this->jzm_accounting_details->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('编辑失败');
        }
        $this->returnData();
    }

    /**
     * 客服详情提交添加销项采集数据页面
     */
    public function customerser_sell_add_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
            'invoice' => ['正常发票（份）', 'required', 'integer'],
            'money' => ['金额', 'required', 'numeric'],
            'amount_of_tax' => ['税额', 'required', 'numeric'],
            'tax_rate' => ['税率', 'required', 'integer'],
            'type' => ['业务类型', 'required', 'integer'],
            'charge_mode' => ['计税方式', 'required', 'integer'],
            'tax_project' => ['征税项目', 'required'],
            'is_invalid' => ['是否作废', 'required', 'integer'],
            'whether_tax' => ['是否即征即退', 'required'],
            'tax_money_period' => ['税款所属期', 'required'],
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['service_id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $this->load->model('jzm/jzm_customer_details_sell');
        $id = $this->jzm_customer_details_sell->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData();
    }

    /**
     * 客服详情编辑销项采集表页面
     */
    public function customerser_sell_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
            'service_id' => ['服务ID', 'required', 'integer'],
            'invoice' => ['正常发票（份）', 'required', 'integer'],
            'money' => ['金额', 'required', 'numeric'],
            'amount_of_tax' => ['税额', 'required', 'numeric'],
            'tax_rate' => ['税率', 'required', 'integer'],
            'type' => ['业务类型', 'required', 'integer'],
            'charge_mode' => ['计税方式', 'required', 'integer'],
            'tax_project' => ['征税项目', 'required'],
            'is_invalid' => ['是否作废', 'required', 'integer'],
            'whether_tax' => ['是否即征即退', 'required'],
            'tax_money_period' => ['税款所属期', 'required'],
        ], [], 'post');
        $this->load->model('jzm/jzm_customer_details_sell');
        $info = $this->jzm_customer_details_sell->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $id = $this->jzm_customer_details_sell->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('编辑失败');
        }
        $this->returnData();
    }

    /**
     * 客服详情提交添加进项采集数据页面
     */
    public function customerser_income_add_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
            'total' => ['总份数（份）', 'required', 'integer'],
            'money' => ['金额', 'required', 'numeric'],
            'amount_of_tax' => ['税额', 'required', 'numeric'],
            'ticket_type' => ['专票类型', 'required'],
            'whether_tax' => ['是否即征即退', 'required'],
            'whether_deduction' => ['是否抵扣', 'required', 'integer'],
            'sum' => ['合计', 'required', 'numeric'],
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['service_id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $this->load->model('jzm/jzm_customer_details_income');
        $id = $this->jzm_customer_details_income->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData();
    }

    /**
     * 客服详情编辑进项采集表页面
     */
    public function customerser_income_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
            'service_id' => ['服务ID', 'required', 'integer'],
            'total' => ['总份数（份）', 'required', 'integer'],
            'money' => ['金额', 'required', 'numeric'],
            'amount_of_tax' => ['税额', 'required', 'numeric'],
            'ticket_type' => ['专票类型', 'required'],
            'whether_tax' => ['是否即征即退', 'required'],
            'whether_deduction' => ['是否抵扣', 'required', 'integer'],
            'sum' => ['合计', 'required', 'numeric'],
        ], [], 'post');
        $this->load->model('jzm/jzm_customer_details_income');
        $info = $this->jzm_customer_details_income->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $id = $this->jzm_customer_details_income->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('编辑失败');
        }
        $this->returnData();
    }

    /**
     * 新增收单交接表
     */
    public function add_acquiring_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
            'dataList' => ['收单数据集', 'required'] //[{'name':'123','cate':1,'num':1,'money':11},{}]
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['service_id'], 'status !=' => 1]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $list = json_decode($request_data['dataList'], true);
        if (!$list) {
            $this->returnError('收单数据集错误，不是json格式');
        }
        $this->load->model('jzm/jzm_acquiring_details');
        /*
         * 开启事务
         * 进行具体的业务逻辑
         * .添加到详情表
         * .添加审核表
         */
        $add_ids = [];
        $this->db->trans_start();
        foreach ($list as $item) {
            $item['service_id'] = $request_data['service_id'];
            if (!$item['cate']) {
                $item['cate'] = $item['cate_id'];
                unset($item['cate_id']);
            }
            $id = $this->jzm_acquiring_details->add($item);
            if (!$id) {
                $this->returnError('添加数据出错');
            }
        }
        //更新审核状态到 提交审核
        $id = $this->model->edit(['id' => $request_data['service_id']], ['status' => 1, 'update_at' => time()]);
        $info = $this->model->info('*', ['jzm_service_info.id' => $request_data['service_id']]);
        $info = $this->taskChange($info, '收单');
        $ser_list = $this->jzm_acquiring_details->f7('*', ['service_id' => $request_data['service_id']]);
        $data = ['service_info' => $info, 'list' => $ser_list];
        //提交审批
        $this->load->model('spm/spm_approves');
        $this->spm_approves->add_approves(20, $this->get_login_info(), $data, $info['khm_customer.name'], $info['khm_customer.id']);
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 增加报税信息
     */
    public function add_tax_details_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
            'is_invoice' => ['发票认证', 'required', 'integer'], //1-已认证 0-未
            'is_state_tax' => ['国税申报', 'required', 'integer'],//1-已认证 0-未
            'is_local_tax' => ['地税申报', 'required', 'integer'],//1-已认证 0-未
            'is_business_tax' => ['工商申报', 'required', 'integer'],//1-已认证 0-未
            'customer_id' => ['客户ID', 'required', 'integer'],
            'printing' => ['印花税', 'required', 'numeric'],
            'individual_tax' => ['个人所得税', 'required', 'numeric'],
            'corporate_tax' => ['企业所得税', 'required', 'numeric'],
            'construction_tax' => ['城建税', 'required', 'numeric'],
            'tuition' => ['教育费', 'required', 'numeric'],
            'local_tuition' => ['地方教育费', 'required', 'numeric'],
            'culture' => ['文化事业建设费', 'required', 'numeric'],
            'excise_tax' => ['消费税', 'required', 'numeric'],
            'water_fund' => ['水利基金', 'required', 'numeric'],
            'state_other_tax' => ['国税其他税', 'required', 'numeric'],
            'local_other_tax' => ['地税其他税', 'required', 'numeric'],
            'cumulative' => ['本年累计销售额', 'required', 'numeric'],
        ], [], 'post');
        $serviceInfo = $this->model->get_one('*', ['id' => $request_data['service_id']]);
        $request_data['declare_id'] = $this->get_login_info()['id'];
        $request_data['declare_name'] = $this->get_login_info()['name'];
        if (!$serviceInfo) {
            $this->returnError('未查到信息');
        }
        $this->load->model('jzm/jzm_tax_details');
        $this->db->trans_start();
        $info = $this->jzm_tax_details->get_one('*', ['service_id' => $request_data['service_id']]);
        // 如果存在 走编辑流程
        if ($info) {
            $this->jzm_tax_details->edit(['service_id' => $request_data['service_id']], $request_data);
        } else {
            $this->jzm_tax_details->add($request_data);
        }
        //提交审核步骤
        $this->model->edit(['id' => $request_data['id']], ['status' => 1, 'update_at' => time()]);
        $this->load->model('spm/spm_approves');
        $serviceinfo = $this->model->info('*', ['jzm_service_info.id' => $request_data['service_id']]);
        $serviceinfo = $this->taskChange($serviceinfo, '报税');
        $taxinfo = $this->jzm_tax_details->get_one('*', ['service_id' => $request_data['service_id']]);
        $data = ['service_info' => $serviceinfo, 'tax_info' => $taxinfo];
        $this->spm_approves->add_approves(18, $this->get_login_info(), $data, $serviceinfo['khm_customer.name'], $serviceinfo['khm_customer.id']);
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 送单获取收单列表
     */
    public function get_acquiring_list_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
        ], [], 'post');
        $info = $this->model->get_one('*', "id = {$request_data['service_id']}");
        $this->load->model('ckm/ckm_out_warehouse');
        $list = $this->ckm_out_warehouse->get_all('*', ['customer_id' => $info['customer_id'], 'month' => $info['time'], 'status' => 2]);
        if (!$list) {
            $this->returnError('未查询到数据');
        }
        $this->returnData($list);
    }

    /**
     * 添加送单交接列表
     */
    public function add_send_list_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
            'dataList' => ['收单数据集', 'required'] //[{'name':'123','cate':1,'num':1,'money':11,'ck_id':1},{}]
        ], [], 'post');
        $info = $this->model->get_one('*', "id = {$request_data['service_id']}");
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $list = json_decode($request_data['dataList'], true);
        if (!$list) {
            $this->returnError('收单数据集错误，不是json格式');
        }
        $this->load->model('jzm/jzm_acquiring_details');
        /*
         * 开启事务
         * 进行具体的业务逻辑
         * .添加到详情表
         */
        $add_ids = [];
        $this->db->trans_start();
        foreach ($list as $item) {
            $item['service_id'] = $request_data['service_id'];
            $item['type'] = 2;
            $id = $this->jzm_acquiring_details->add($item);
            if (!$id) {
                $this->returnError('添加数据出错');
            }
        }
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 新增送单详情
     */
    public function add_send_info_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
            'customer_id' => ['客户ID', 'required', 'integer'],
            'contacts' => ['联系人', 'required'],
            'tel' => ['联系电话', 'required', 'integer'],
            'address' => ['地址', 'required'],
            'remark' => ['待办事项'],
            'is_send' => ['是否赠送成功', 'required', 'integer'],//1-送单成功 0-失败
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['service_id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $this->load->model('jzm/jzm_send_info');
        $this->load->model('jzm/jzm_acquiring_details');
        $ser_list = $this->jzm_acquiring_details->f7('*', ['service_id' => $request_data['service_id']]);
        if (!$ser_list) {
            $this->returnError('未查到送单列表数据');
        }
        $this->db->trans_start();
        $id = $this->jzm_send_info->add($request_data);
        if ($request_data['is_send'] == 1) {
            /*
             *  配送成功
             */
            $this->model->edit(['id' => $request_data['service_id']], ['status' => 1, 'update_at' => time()]);
            /*
             *  添加审批流程
             */
            $info = $this->model->info('*', ['jzm_service_info.id' => $request_data['service_id']]);
            $info = $this->taskChange($info, '送单');
            $data = ['service_info' => $info, 'list' => $ser_list];
            $this->load->model('spm/spm_approves');
            $this->spm_approves->add_approves(7, $this->get_login_info(), $data, $info['khm_customer.name'], $info['khm_customer.id']);
            if (!$id) {
                $this->returnError('添加失败');
            }
        } else {
            /*
             * 送单失败，添加到退单列表
             */
            $this->load->model('ckm/ckm_retreat');
            $loginData = $this->get_login_info();
            $ret_data = [
                'service_id' => $request_data['service_id'],
                'creata_at' => time(),
                'status' => 2,
                'create_id' => $loginData['id'],
                'create_time' => time(),
            ];
            $this->ckm_retreat->add($ret_data);
        }
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 客服新增编辑
     */
    public function add_customer_details_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
            'colData' => ['提交信息', 'required'],//提交资料[{},{}]字符串
        ], [], 'post');
        $info = $this->model->info('*', ['jzm_service_info.id' => $request_data['service_id']]);
        $info = $this->taskChange($info, '客服');
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $colData = json_decode($request_data['colData'], true);
        if (!$colData) {
            $this->returnError('数据格式错误');
        }
        $this->load->model('jzm/jzm_customer_details');
        $cinfo = $this->jzm_customer_details->get_all('*', ['service_id' => $request_data['service_id']]);
        $this->db->trans_start();
        foreach ($colData as $item) {
            $item['service_id'] = $request_data['service_id'];
            //编辑
            if ($cinfo) {
                $this->jzm_customer_details->edit(['service_id' => $request_data['service_id'], 'ztype' => $item['ztype']], $item);
            } else {
                $this->jzm_customer_details->add($item);
            }
        }
        //添加审批
        $ser_list = $this->jzm_customer_details->get_all('*', ['service_id' => $request_data['service_id']]);
        $data = ['service_info' => $info, 'list' => $ser_list];
        $this->load->model('spm/spm_approves');
        $this->spm_approves->add_approves(19, $this->get_login_info(), $data, $info['khm_customer.name'], $info['khm_customer.id']);
        $this->model->edit(['jzm_service_info.id' => $request_data['service_id']], ['status' => 1]);
        $this->db->trans_complete();
        $this->returnData($data);
    }

    /**
     * 客服列表详情
     */
    public function customer_details_list_post()
    {
        $request_data = $this->check_param([
            'service_id' => ['服务ID', 'required', 'integer'],
        ], [], 'post');
        $info = $this->jzm_customer_details->get_all('*', ['service_id' => $request_data['service_id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        $this->returnData($info);
    }

    /**
     * 新增编辑投诉
     */
    public function add_complaint_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'integer'],//新增时不传 编辑传
            'customer_id' => ['客户ID', 'required', 'integer'],
            'customer_name' => ['客户姓名', 'required'],
            'customer_tel' => ['客户联系电话', 'required'],
            'type' => ['类型', 'required', 'integer'],
            'time' => ['时间', 'required', 'integer'],
            'content' => ['内容', 'required'],
            'duto' => ['负责人', 'required'],
            'remark' => ['备注'],
        ], [], 'post');
        $this->load->model('jzm/jzm_complaint');
        if ($request_data['id']) {
            $info = $this->jzm_complaint->get_one('*', ['id' => $request_data['id']]);
            if (!$info) {
                $this->returnError('未查到相关信息');
            }
            $this->jzm_complaint->edit(['id' => $request_data['id']], $request_data);
        } else {
            unset($request_data['id']);
            $request_data['create_id'] = $this->get_login_info()['id'];
            $request_data['create_time'] = time();
            $this->jzm_complaint->add($request_data);
        }
        $this->returnData();
    }

    /**
     * 投诉列表
     */
    public function list_complaint_post()
    {
        $this->load->model('jzm/jzm_complaint');
        $select = $this->input->get_post('select'); //查询字段
        $page_size = $this->input->get_post('limit'); //每页显示多少条
        $page = $this->input->get_post('page'); //第几页
        $filter = $this->input->get_post('filter'); //查询条件
        $order = $this->input->get_post('order'); //排序
        $grid = $this->jzm_complaint->grid($select, $filter, $page, $page_size, '', '', $order);
        $this->returnData($grid);
    }

    /**
     * 投诉修改资料审批接口
     */
    public function batch_complaint_post()
    {
        $request_data = $this->check_param([
            'colData' => ['数据', 'required'],//[{id,status,auth_status,auth_remark},{}]  status 0-未解决 1-已解决 2-解决中 auth_status 0-未开始 1-未通过 2- 通过 3-提交审核
        ], [], 'post');
        $data = json_decode($request_data['colData'], true);
        $this->load->model('jzm/jzm_complaint');
        foreach ($data as $i => $item) {
            if ($item['status'] == 1) {
                $item['auth_status'] = 3;
            }
            if ($item['auth_status'] != 0) {
                $item['auth_id'] = $this->get_login_info()['id'];
                $item['anth_time'] = time();
            }
            $this->jzm_complaint->edit(['id' => $item['id']], $item);
        }
        $this->returnData();
    }

    /**
     * 投诉修改状态
     */
    public function edit_status_complaint_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
            'status' => ['解决状态', 'required', 'integer'],//0-未解决 1-已解决 2-解决中
        ], [], 'post');
        $this->load->model('jzm/jzm_complaint');
        $info = $this->jzm_complaint->info('*', ['jzm_complaint.id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到相关信息');
        }
        if ($request_data['status'] == 1) {
            //提交审批
            $request_data['auth_status'] = 3;
        }
        $this->jzm_complaint->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 投诉审批
     */
    public function auth_complaint_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
            'auth_status' => ['审批状态', 'required', 'integer'],//0-未开始 1-未通过 2- 通过 3-提交审核
            'auth_remark' => ['审批回复', 'max_length[1000]']
        ], [], 'post');
        $this->load->model('jzm/jzm_complaint');
        $info = $this->jzm_complaint->info('*', ['jzm_complaint.id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到相关信息');
        }
        $request_data['auth_id'] = $this->get_login_info()['id'];
        $request_data['anth_time'] = time();
        $this->jzm_complaint->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 导出
     */
    public function serviceExport_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],//1-收单 2-整单 3-记账 4-客服 5-报税 6-送单
            'title' => ['文件名 ', 'required'],
        ], [], 'post');
        $title = empty($request_data['title']) ? "FINANCE" : $request_data['title'];
        $this->load->model('khm/khm_customer');
        $customerList = $this->khm_customer->get_all('*', $where);
        foreach ($customerList as $item) {
            $customer_list[$item['id']] = $item['name'];
        }
        $where = ['cid' => $this->get_login_info()['cid']];
        $where['type'] = $request_data['type'];
        $grid = $this->model->get_all('*', $where);
        foreach ($grid as $i => $item) {
            if ($item['get_money'] == 0) {
                $grid[$i]['get_money'] = '否';
            } else {
                $grid[$i]['get_money'] = '是';
            }
        }
        $type_list = ['1' => '收单', '2' => '整单', '3' => '记账', '4' => '客服', '5' => '报税', '6' => '送单'];
        $status_list = ['0' => '未开始', '1' => '提交审核', '2' => '通过', '3' => '未通过'];
        $colModel = [
            ["label" => "编号", "name" => "id", "sorttype" => "string"],
            ["label" => "客户名称", "name" => "customer_id", "sorttype" => "checkbox", 'checkbox' => $customer_list, 'width' => 300],
            ["label" => "服务内容", "name" => "type", "sorttype" => "checkbox", 'checkbox' => $type_list],
            ["label" => "服务月份", "name" => "time", "sorttype" => "string"],
            ["label" => "审批状态", "name" => "status", "sorttype" => "checkbox", 'checkbox' => $status_list],
            ["label" => "是否收款", "name" => "get_money", "sorttype" => "string"],
            ["label" => "创建时间", "name" => "create_at", "sorttype" => "datetime", 'width' => 150],
        ];
        require_once APPPATH . '/third_party/PHPExcel.php';
        require_once APPPATH . '/third_party/PHPExcel/Writer/Excel5.php';
        $fileName = $title . date("YmdHis");
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("税脉财务");
        $objPHPExcel->getProperties()->setLastModifiedBy("http://finance.yunkepai.net");
        $objPHPExcel->getProperties()->setTitle("Office 2007 XLSX Document");
        $objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Document");
        $objPHPExcel->getProperties()->setDescription("税脉财务");
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(30);
        //填充数据
        $this->_export($objPHPExcel, $colModel, $grid);
        $objPHPExcel->getActiveSheet()->setTitle($title);
        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
        //$pub_dir = FCPATH . "statics/uploads/export_excels/";
        $pub_dir = FCPATH . "resource/admin/uploads/export_excels/";
        if (!is_dir($pub_dir)) {
            @mkdir($pub_dir, 0777, TRUE);
        }
        $file_name = $pub_dir . $fileName . ".xls";
//        $file_name = iconv("UTF-8", "gb2312", $file_name); //windows需要转换为gb2312
        $objWriter->save($file_name);
        $file_name = base_url() . "resource/admin/uploads/export_excels/" . $fileName . ".xls";
        $this->returnData($file_name);
    }

    /**
     * 记账服务导入
     */
    public function upload_batch_post()
    {
        $excel = $_FILES['service'];
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
        //['0' => '未开始', '1' => '提交审核', '2' => '通过', '3' => '未通过'];
        $dataKey = [
            'A' => ['key' => 'id', 'name' => '编号', 'required' => true],
            'E' => ['key' => 'status', 'name' => '审批状态', 'required' => true, 'key_type' => 'obj', 'key_obj' => ['未开始' => 0, '提交审核' => 1, '通过' => 2, '未通过' => 3]],
            'F' => ['key' => 'get_money', 'name' => '是否收款', 'required' => true, 'key_type' => 'obj', 'key_obj' => ['否' => 0, '是' => 1]],
        ];
        $dataF = [];
        unset($excelRes[1]);
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
            $this->model->edit(['id' => $tem['id']], $tem);
            $dataF[] = $tem;
        }
        $this->returnData();
    }
}



