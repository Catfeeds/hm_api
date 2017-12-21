<?php

require_once APPPATH . '/models/Modelbase.php';

class Htm_task extends Modelbase
{

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    public function setQuery($it, $select = "*", $filter = NULL)
    {
        if ($select == "*") {
            $select = array_merge($this->getCols($this->_table),$this->getCols('htm_contract'));
            $select[] = "khm_customer.id as 'khm_customer.id'";
            $select[] = "khm_customer.name as 'khm_customer.name'";
            $select[] = "khm_customer.tax_type as 'khm_customer.tax_type'";
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("khm_customer", "khm_customer.id = htm_task.customer_id", "left");
        $it->db->join("htm_contract", "htm_contract.id = htm_task.contract_id", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

    public function changeProduct($contract_id, $arr)
    {
        if (empty($arr)) {
            return false;
        }
        foreach ($arr as $i => $item) {
            if (!empty($item['product_id'])) {
                $all_id[] = $item['product_id'];
            }
        }
        if (!empty($all_id)) {
            $_arr = implode(',', $all_id);
            $this->del("contract_id = {$contract_id} and product_id not in ({$_arr})");
        }
        foreach ($arr as $i => $item) {
            $this->load->model('ckm/ckm_product');
            $pro_info = $this->ckm_product->info('*', ['ckm_product.id' => $item['product_id']]);
            $info = $this->get_one('id', ['contract_id' => $contract_id, 'product_id' => $item['product_id']]);
            $item['contract_id'] = $contract_id;
            $item['flow'] = $pro_info['ckm_process.status'];
            $item['cid'] = $this->loginData['cid'];
            if ($info) {
                $this->edit(['contract_id' => $contract_id, 'product_id' => $item['product_id']], $item);
            } else {
                $task_id = $this->add($item);
                $flow = json_decode($item['flow'], TRUE);
                $this->load->model('htm/htm_task_staff');
                foreach ($flow as $item) {
                    $saveData = [
                        'contract_id' => $contract_id,
                        'task_id' => $task_id,
                        'process' => $item
                    ];
                    $this->htm_task_staff->add($saveData);
                }
            }
        }
    }

    public function getTask($contract_id)
    {
        $list = $this->get_all('*', ['contract_id' => $contract_id, 'is_del' => 0]);
        return $list;
    }
}
