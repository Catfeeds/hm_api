<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Zsm_category extends Modelbase
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
//            $select = $this->getCols($this->_table);
//            $select[] = "khm_customer.name as 'khm_customer.name'";
//            $select[] = "khm_customer.social_credit_code as 'khm_customer.social_credit_code'";
////            $select = array_merge(
////                $this->getCols($this->_table),
////                $this->getCols('htm_contract'),
////                $this->getCols('khm_customer')
////            );
//        }
//        $it->db->select($select);
//        $it->db->distinct(TRUE);
//        $it->db->from($this->_table);
//        $it->db->join("htm_contract", "htm_contract.contract_code = zhm_comprehensive_bill.order_number", "left");
//        $it->db->join("khm_customer", "htm_contract.customer_id = khm_customer.id", "left");
//        if (!empty($filter)) {
//            $it->db->where($filter);
//        }
//        return $it;
//    }


}