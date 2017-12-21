<?php

/*
 * 描述TODO
 * @author:jacky Version 1.0.0 2016-7-17
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Cwm_achievements_info extends Modelbase
{

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    /**
     * 记账报税添加绩效
     * @param $serviceInfo  服务信息
     * @param $complete     操作人ID
     * @param $time         操作时间
     * @return bool         正确返回ID 错误返回false
     */
    public function add_achievements($serviceInfo, $complete, $time)
    {
        $cateName = '';
        //类型1-收单 2-整单 3-记账 4-客服 5-报税 6-送单
        //客户类型 1-一般纳税人 2-小规模
        switch ($serviceInfo['jzm_service_info.type']) {
            case 1:
                $cateName = '收单';
                break;
            case 2:
                $cateName = '整单';
                break;
            case 3:
                $cateName = '记账';
                break;
            case 4:
                $cateName = '客服';
                break;
            case 5:
                $cateName = '报税';
                break;
            case 6:
                $cateName = '送单';
                break;
        }
        $this->load->model('cwm/cwm_achievements_cate');
        $cate_id = $this->cwm_achievements_cate->get_one('id', ['name' => $cateName, 'type' => $serviceInfo['khm_customer.tax_type'], 'cid' => $serviceInfo['jzm_service_info.cid']]);
        if (!$cate_id) {
            return false;
        }
        $data = [
            'cate_id' => $cate_id['id'],
            'service_id' => $serviceInfo['jzm_service_info.id'],
            'complete_id' => $serviceInfo['staff_info'][0]['id'],
            'complete_time' => $time,
            'contract_id' => $serviceInfo['htm_contract.id'],
            'customer_id' => $serviceInfo['htm_contract.customer_id'],
            'cid' => $serviceInfo['jzm_service_info.cid']
        ];
        $id = $this->add($data);
        if (!$id) {
            return false;
        }
        return $id;

    }
}
