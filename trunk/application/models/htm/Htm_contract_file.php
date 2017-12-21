<?php

require_once APPPATH . '/models/Modelbase.php';

class Htm_contract_file extends Modelbase
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

    public function changeFile($contract_id, $customer_id, $arr, $type)
    {
        if (empty($arr)) {
            return false;
        }
        foreach ($arr as $i => $item) {
            if (!empty($item['id'])) {
                $all_id[] = $item['id'];
            }
        }
        if (!empty($all_id)) {
            $_arr = implode(',', $all_id);
            $this->del("contract_id = {$contract_id} and id not in ({$_arr}) and type = {$type}");
        } else {
            $this->del("contract_id = {$contract_id} and type = {$type}");
        }
        foreach ($arr as $i => $item) {
            if (!empty($item['id'])) {
                $this->edit(['id' => $item['id']], ['url' => $item['url'], 'name' => $item['name']]);
            } else {
                $saveData = [
                    'contract_id' => $contract_id,
                    'customer_id' => $customer_id,
                    'create_at' => time(),
                    'cid' => $this->loginData['cid'],
                    'type' => $type,
                    'url' => $item['url'],
                    'name' => $item['name']
                ];
                $this->add($saveData);
            }
        }
    }

    public function getFile($info)
    {
        $colsData = $this->get_all('id,url,type,name', ['contract_id' => $info['htm_contract.id']]);
        foreach ($colsData as $i => $item) {
            if ($item['type'] == 1) {
                $img[] = $item;
            } else {
                $file[] = $item;
            }
        }
        $info['HT_image'] = !empty($img) ? $img : [];
        $info['HT_file'] = !empty($file) ? $file : [];
        return $info;
    }
}
