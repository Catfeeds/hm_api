<?php

require_once APPPATH . '/models/Modelbase.php';

class Htm_task_staff extends Modelbase
{

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    public function getContractId($market = [])
    {
        $mark = implode(',', $market);
        $colsData = $this->get_all('contract_id', "staff_id in ({$mark})");
        foreach ($colsData as $i => $item) {
            $allIds[] = $item['contract_id'];
        }
        return array_unique($allIds);
    }

    public function getStaff($contract, $process)
    {
        $colsData = $this->get_all('staff_id', ['contract_id' => $contract, 'process' => $process]);
        foreach ($colsData as $i => $item) {
            $allIds[] = $item['staff_id'];
        }
        return array_unique($allIds);
    }

    public function processGetContract($staff, $process)
    {
        $colsData = $this->get_all('contract_id', ['staff_id' => $staff, 'process' => $process]);
        foreach ($colsData as $i => $item) {
            $allIds[] = $item['contract_id'];
        }
        return array_unique($allIds);
    }
//    public function setQuery($it, $select = "*", $filter = NULL)
//    {
//        if ($select == "*") {
//            $select = array_merge($this->getCols($this->_table),$this->getCols('khm_customer'));
//        }
//        $it->db->select($select);
//        $it->db->distinct(TRUE);
//        $it->db->from($this->_table);
//        $it->db->join("khm_customer", "khm_customer.id = htm_contract.customer_id", "left");
//        if (!empty($filter)) {
//            $it->db->where($filter);
//        }
//        return $it;
//    }

//    public function changeProduct($contract_id, $arr)
//    {
//        if (empty($arr)) {
//            return false;
//        }
//        foreach ($arr as $i => $item) {
//            if (!empty($item['product_id'])) {
//                $all_id[] = $item['product_id'];
//            }
//        }
//        if (!empty($all_id)) {
//            $_arr = implode(',', $all_id);
//            $this->del("contract_id = {$contract_id} and product_id not in ({$_arr})");
//        }
//        foreach ($arr as $i => $item) {
//            $this->load->model('ckm/ckm_product');
//            $pro_info = $this->ckm_product->info('*', ['ckm_product.id' => $item['product_id']]);
//            $info = $this->get_one('id', ['contract_id' => $contract_id, 'product_id' => $item['product_id']]);
//            $item['contract_id'] = $contract_id;
//            $item['flow'] = $pro_info['ckm_process.status'];
//            $item['cid'] = $this->loginData['cid'];
//            if ($info) {
//                $this->edit(['contract_id' => $contract_id, 'product_id' => $item['product_id']], $item);
//            } else {
//                $this->add($item);
//            }
//        }
//    }
//
//    public function getTask($contract_id)
//    {
//        $list = $this->get_all('*', ['contract_id' => $contract_id, 'is_del' => 0]);
//        return $list;
//    }
}
