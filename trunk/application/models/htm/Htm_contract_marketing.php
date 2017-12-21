<?php

require_once APPPATH . '/models/Modelbase.php';

class Htm_contract_marketing extends Modelbase
{

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
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


    public function changeMarket($contract_id, $arr)
    {
        if (empty($arr)) {
            $this->del("contract_id = {$contract_id}");
            return false;
        }
        $_arr = implode(',', $arr);
        $this->del("contract_id = {$contract_id} and marketing_id not in ({$_arr})");
        foreach ($arr as $i => $item) {
            $info = $this->get_one("id", ['contract_id' => $contract_id, 'marketing_id' => $item]);
            if (!$info) {
                $saveData = [
                    'contract_id' => $contract_id,
                    'marketing_id' => $item,
                    'create_at' => time(),
                    'cid' => $this->loginData['cid']
                ];
                $this->add($saveData);
            }
        }
    }

    public function getMarket($contract_id)
    {
        $colsData = $this->get_all('marketing_id', ['contract_id' => $contract_id]);
        foreach ($colsData as $i => $item) {
            $allIds[] = $item['marketing_id'];
        }
        return array_unique($allIds);
    }

    public function getContractId($market = [])
    {
        $mark = implode(',', $market);
        $colsData = $this->get_all('contract_id', "marketing_id in ({$mark})");
        foreach ($colsData as $i => $item) {
            $allIds[] = $item['contract_id'];
        }
        return array_unique($allIds);
    }
}
