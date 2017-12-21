<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * 新增编辑开票
 */
class Api_comprehensive_bill extends Apibase
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('zhm/zhm_comprehensive_bill');
        $this->model = $this->zhm_comprehensive_bill;
    }

    /**
     * 获取符合要求的公司信息
     */
    public function check_customer_post()
    {
        $this->load->model('htm/htm_contract');
        $this->load->model('khm/khm_customer');
        $this->db->distinct(TRUE);
        $cus_ids = $this->htm_contract->get_all('customer_id', ['amount_beceived >' => 0]);
        if (empty($cus_ids)) {
            $this->returnError('没有信息');
        }
        foreach ($cus_ids as $item) {
            $ids[] = $item['customer_id'];
        }
        $ids_s = implode(',', $ids);
        $list = $this->khm_customer->get_all('*', "id in ({$ids_s})");
        $this->returnData($list);
    }

    /**
     * 获取符合要求的合同信息
     */
    public function check_contract_post()
    {
        $request_data = $this->check_param([
            'id' => ['公司ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('htm/htm_contract');
        $this->load->model('khm/khm_customer');
        $list = $this->htm_contract->get_all('*', ['amount_beceived >' => 0, 'customer_id' => $request_data['id']]);
        if (!$list) {
            $this->returnError('没有信息');
        }
        $this->returnData($list);
    }

    /**
     * 新增编辑开票
     */
    public function add_edit_bill_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'integer'],
            'order_number' => ['订单编号', 'required'],
            'bill_date' => ['开票日期', 'required', 'integer'],
            'bill_money' => ['开票金额', 'required', 'numeric'],
            'bill_type' => ['开票类型', 'required', 'integer'], //1-增值税专用发票 2-普通
            'bill_number' => ['发票号码', 'required'],
            'bill_header' => ['开票抬头', 'required'],
            'bill_header_type' => ['开票抬头类型', 'required'],
//            'name' => ['客户名称', 'required'],
//            'social_credit_code' => ['信用代码', 'required']
        ], [], 'post');
        if (empty($request_data['id'])) {
            unset($request_data['id']);
            $loginData = $this->get_login_info();
            $request_data['bill_id'] = $this->get_num('KP');
            $request_data['create_id'] = $loginData['id'];
            $request_data['create_time'] = time();
            $this->model->add($request_data);
        } else {
            $info = $this->model->get_one('*', ['id' => $request_data['id']]);
            if (!$info) {
                $this->returnError('未查询到信息');
            }
            $this->model->edit(['id' => $request_data['id']], $request_data);
        }
        $this->returnData();
    }

    /**
     * 审核开票
     */
    public function auth_bill_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'status' => ['审核状态', 'required', 'integer'], //0-未开始 1-未通过 2-通过
            'auth_remark' => ['确认审批'],
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $loginData = $this->get_login_info();
        $request_data['auth_id'] = $loginData['id'];
        $request_data['auth_time'] = time();
        $id = $this->model->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('审核失败');
        }
        $this->returnData();
    }

    /**
     * 批量审核开票
     */
    public function batch_auth_bill_post()
    {
        $request_data = $this->check_param([
            'data' => ['数据', 'required'],//格式[{'id':'1','status':'2','auth_remark':'123'},{}]
        ], [], 'post');
        $batch = json_decode($request_data['data'], true);
        if (!$batch) {
            $this->returnError('数据格式错误，请检查！');
        }
        foreach ($batch as $item) {
            $this->auth_bill($item);
        }
        $this->returnData();
    }

    /**
     * 审批方法
     */
    private function auth_bill($request_data)
    {
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError($request_data['id'] . '未查询到信息');
        }
        $loginData = $this->get_login_info();
        $request_data['auth_id'] = $loginData['id'];
        $request_data['auth_time'] = time();
        $res = $this->model->edit(['id' => $request_data['id']], $request_data);
//        if ($res === false) {
//            $this->returnError($request_data['id'] . '审核失败');
//        }
    }

    /**
     * 审核详情
     */
    public function info_bill_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
        ], [], 'post');
        $info = $this->model->info('*', ['zhm_comprehensive_bill.id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $this->returnData($info);
    }

}
