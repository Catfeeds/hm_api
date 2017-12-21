<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class Api_approves 审批管理
 */
class Api_approves extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('spm/spm_approves');
        $this->model = $this->spm_approves;
    }

    /**
     * 查询列表
     */
    public function get_list_post()
    {
        $select = $this->input->get_post('select'); //查询字段
        $page_size = $this->input->get_post('limit'); //每页显示多少条
        $page = $this->input->get_post('page'); //第几页
        $filter = $this->input->get_post('filter'); //查询条件
        $order = $this->input->get_post('order'); //排序
        $grid = $this->model->grid($select, $filter, $page, $page_size, '', '', $order);
        $items = $grid['items'];
        foreach ($items as $i => $v) {
            $items[$i]['submitted_data'] = json_decode($v['spm_approves.submitted_data'], true);
        }
        $grid['items'] = $items;
        $this->returnData($grid);
    }

    /**
     * 审批状态
     */
    public function edit_status_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
            'approve_result' => ['审批状态', 'required', 'integer'], //1-待审核 2-通过 3-不通过
            'approve_reply' => ['审批回复']
        ], [], 'post');
        $this->appro($request_data);
        $this->returnData();
    }

    /**
     * 批量审批
     */
    public function batch_edit_post()
    {
        $request_data = $this->check_param([
            'data' => ['数据', 'required'],//格式[{'id':'1','approve_result':'3','approve_reply':'123'},{}]
        ], [], 'post');
        $batch = json_decode($request_data['data'], true);
        if (!$batch) {
            $this->returnError('数据格式错误，请检查！');
        }
        foreach ($batch as $item) {
            $this->appro($item);
        }
        $this->returnData();
    }

    /**
     * 审批详情
     */
    public function get_info_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
        ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('查询失败！');
        }
        $info['submitted_data'] = json_decode($info['submitted_data'], true);
        $this->returnData($info);
    }

    /**
     * 审批方法
     */
    private function appro($request_data)
    {
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到对应的信息');
        }
        /*
         * 审批项目类型
         * 1-客户 2-合同 3-放款 4-查账 5-开票 6-记账 7-送单 8-任务 9-支出 10-仓位 11-工资
         * 12-绩效 13-退单 14-出库 15-整单 16-入库 17-贷款 18-报税 19-客服 20-收单
         */
        $aData = json_decode($info['submitted_data'], true);
        $this->load->model('cwm/cwm_achievements_info');
        $this->load->model('jzm/jzm_service_info');
        $updateDat = [
            'status' => $request_data['approve_result'],
            'com_time' => time(),
            'update_at' => time(),
            'auth_id' => $this->loginData['id'],
            'auth_time' => time()
        ];
        switch ($info['approve_type']) {
            //具体分类的业务逻辑
            case 6:
                $this->jzm_service_info->edit(['id' => $aData['jzm_service_info.id']], $updateDat);
                if ($request_data['approve_result'] == 2) {
                    $this->cwm_achievements_info->add_achievements($aData, $info['submit_employee_id'], $info['submit_time']);
                }
                break;
            case 7:
                $this->db->trans_start();
                $this->jzm_service_info->edit(['id' => $aData['jzm_service_info.id']], $updateDat);
                if ($request_data['approve_result'] == 2) {
                    /*
                     * 审批通过
                     * 1.查询送单列表
                     * 2.查询出库记录
                     * 3.将出库未送单的入库
                     */
                    $this->load->model('ckm/ckm_in_warehouse');
                    $this->load->model('ckm/ckm_out_warehouse');
                    $this->load->model('jzm/jzm_acquiring_details');
                    //1.查询送单列表
                    $list = $aData['list'];
                    //2.查询出库记录
                    $out_list = $this->ckm_out_warehouse->get_all('*', [
                        'customer_id' => $aData['service_info']['htm_contract.customer_id'],
                        'month' => $aData['service_info']['jzm_service_info.time'],
                        'status' => 2
                    ]);
                    //计算是否出库
                    $in_liat = [];
                    $send_out_ids = [];
                    foreach ($list as $item) {
                        $send_out_ids[] = $item['jzm_acquiring_details.ck_id'];
                    }
                    foreach ($out_list as $val) {
                        if (in_array($val['id'], $send_out_ids)) {
                            foreach ($list as $item) {
                                if ($item['jzm_acquiring_details.ck_id'] == $val['id']) {
                                    $in_liat[] = [
                                        'out_id' => $val['id'],
                                        'num' => $val['number'] - $item['jzm_acquiring_details.num'],
                                        'info' => $val
                                    ];
                                }
                            }
                        } else {
                            $in_liat[] = [
                                'out_id' => $val['id'],
                                'num' => $val['number'],
                                'info' => $val
                            ];
                        }
                    }
                    foreach ($in_liat as $item) {
                        //保存入库记录
                        if ($item['num'] > 0) {
                            $saveData = [
                                'num' => $this->get_num('RK'),
                                'type' => $item['info']['type'],
                                'customer_id' => $aData['service_info']['htm_contract.customer_id'],
                                'number' => $item['num'],
                                'goods' => $item['info']['goods'],
                                'month' => $aData['service_info']['jzm_service_info.time'],
                                'time' => time(),
                                'jb_id' => $this->get_login_info()['id'],
                                'create_id' => $info['submit_employee_id'],
                                'create_time' => $info['submit_time'],
                                'qr_id' => $info['submit_employee_id'],
                                'qr_time' => time(),
                                'status' => 2
                            ];
                            $this->ckm_in_warehouse->add($saveData);
                        }
                    }
                    $this->cwm_achievements_info->add_achievements($aData['service_info'], $info['submit_employee_id'], $info['submit_time']);
                }
                $this->db->trans_complete();
                break;
            // ....
            case 15:
                $this->jzm_service_info->edit(['id' => $aData['jzm_service_info.id']], $updateDat);
                if ($request_data['approve_result'] == 2) {
                    $this->cwm_achievements_info->add_achievements($aData, $info['submit_employee_id'], $info['submit_time']);
                }
                break;
            case 18:
                $this->jzm_service_info->edit(['id' => $aData['jzm_service_info.id']], $updateDat);
                if ($request_data['approve_result'] == 2) {
                    $this->cwm_achievements_info->add_achievements($aData, $info['submit_employee_id'], $info['submit_time']);
                }
                break;
            case 19:
                $this->jzm_service_info->edit(['id' => $aData['jzm_service_info.id']], $updateDat);
                if ($request_data['approve_result'] == 2) {
                    $this->cwm_achievements_info->add_achievements($aData, $info['submit_employee_id'], $info['submit_time']);
                }
                break;
            case 20:
                $this->db->trans_start();
                $this->jzm_service_info->edit(['id' => $aData['jzm_service_info.id']], $updateDat);
                if ($request_data['approve_result'] == 2) {
                    /*
                     * 审批通过，添加入库
                     */
                    $list = $aData['list'];
                    $this->load->model('ckm/ckm_in_warehouse');
                    $this->load->model('jzm/jzm_acquiring_details');
                    foreach ($list as $item) {
                        //保存入库记录
                        $saveData = [
                            'num' => $this->get_num('RK'),
                            'type' => $item['jzm_acquiring_details.cate'],
                            'customer_id' => $aData['service_info']['htm_contract.customer_id'],
                            'number' => $item['jzm_acquiring_details.num'],
                            'goods' => $item['jzm_acquiring_details.name'],
                            'month' => $aData['service_info']['jzm_service_info.time'],
                            'time' => time(),
                            'jb_id' => $this->get_login_info()['id'],
                            'create_id' => $info['submit_employee_id'],
                            'create_time' => $info['submit_time'],
                            'qr_id' => $info['submit_employee_id'],
                            'qr_time' => time(),
                            'status' => 2
                        ];
                        $id = $this->ckm_in_warehouse->add($saveData);
                        $this->jzm_acquiring_details->edit(['id' => $item['jzm_acquiring_details.id']], ['ck_id' => $id]);
                    }
                    $this->cwm_achievements_info->add_achievements($aData['service_info'], $info['submit_employee_id'], $info['submit_time']);
                }
                $this->db->trans_complete();
                break;

        }
        $id = $this->model->edit(['id' => $request_data['id']],
            ['approve_result' => $request_data['approve_result'],
                'approve_reply' => $request_data['approve_reply'],
                'approve_time' => time(),
                'approve_employee_id' => $this->get_login_info()['id']
            ]);
        if (!$id) {
            $this->returnError('提交失败');
        }
    }
}
