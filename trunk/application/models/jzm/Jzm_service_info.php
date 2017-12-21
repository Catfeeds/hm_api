<?php

require_once APPPATH . '/models/Modelbase.php';

class Jzm_service_info extends Modelbase
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
            $select = array_merge(
                $this->getCols($this->_table),
                $this->getCols('htm_contract')
            );
            $select[] = "khm_customer.id as 'khm_customer.id'";
            $select[] = "khm_customer.name as 'khm_customer.name'";
            $select[] = "khm_customer.tax_type as 'khm_customer.tax_type'";
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("htm_contract", "htm_contract.id = jzm_service_info.contract_id", "left");
        $it->db->join("khm_customer", "htm_contract.customer_id = khm_customer.id", "left");
//        $it->db->join("htm_task", "htm_contract.id = htm_task.contract_id", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

    /**
     * 根据合同添加到服务
     */
    public function add_service($info)
    {
        $res = $this->get_one('*', ['contract_id' => $info['id'], 'is_del' => 0]);
        if (!$res) {
            $time1 = $info['start_time'];
            $time2 = $info['end_time'];
            $monarr = array();
            $monarr[] = date('Ym', $time1); // 当前月;
            while (($time1 = strtotime('+1 month', $time1)) <= $time2) {
                $monarr[] = date('Ym', $time1); // 取得递增月;
            }
            $monarr[] = date('Ym', $time2);
            $monarr = array_unique($monarr);
            $flow = json_decode($info['flow'], true);
            $this->db->trans_start();
            $data = [];
            foreach ($monarr as $v) {
                //1-收单 2-整单 3-记账 4-客服 5-报税 6-送单
                foreach ($flow as $vv) {
                    switch ($vv) {
                        case '收单':
                            $data[] = ['contract_id' => $info['contract_id'], 'customer_id' => $info['customer_id'], 'time' => $v, 'type' => 1, 'create_at' => time()];
                            break;
                        case '整单':
                            $data[] = ['contract_id' => $info['contract_id'], 'customer_id' => $info['customer_id'], 'time' => $v, 'type' => 2, 'create_at' => time()];
                            break;
                        case '记账':
                            $data[] = ['contract_id' => $info['contract_id'], 'customer_id' => $info['customer_id'], 'time' => $v, 'type' => 3, 'create_at' => time()];
                            break;
                        case '客服':
                            $data[] = ['contract_id' => $info['contract_id'], 'customer_id' => $info['customer_id'], 'time' => $v, 'type' => 4, 'create_at' => time()];
                            break;
                        case '报税':
                            $data[] = ['contract_id' => $info['contract_id'], 'customer_id' => $info['customer_id'], 'time' => $v, 'type' => 5, 'create_at' => time()];
                            break;
                        case '送单':
                            $data[] = ['contract_id' => $info['contract_id'], 'customer_id' => $info['customer_id'], 'time' => $v, 'type' => 6, 'create_at' => time()];
                            break;
                    }
                }
            }
            $this->add_batch($data);
            $this->db->trans_complete();
        }
    }

}
