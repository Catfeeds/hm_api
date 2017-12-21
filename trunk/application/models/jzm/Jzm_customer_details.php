<?php

require_once APPPATH . '/models/Modelbase.php';

class Jzm_customer_details extends Modelbase
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
//            $select = array_merge(
//                $this->getCols($this->_table),
//                $this->getCols('htm_contract'),
//                $this->getCols('khm_customer')
//            );
//        }
//        $it->db->select($select);
//        $it->db->distinct(TRUE);
//        $it->db->from($this->_table);
//        $it->db->join("htm_contract", "htm_contract.id = jzm_service_info.contract_id", "left");
//        $it->db->join("khm_customer", "htm_contract.customer_id = khm_customer.id", "left");
//        if (!empty($filter)) {
//            $it->db->where($filter);
//        }
//        return $it;
//    }

}
