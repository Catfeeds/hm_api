<?php

require_once APPPATH . '/models/Modelbase.php';

class Khm_wages extends Modelbase
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
//        $it->db->join("khm_customer", "khm_customer.id = khm_wages.kh_id", "left");
//        if (!empty($filter)) {
//            $it->db->where($filter);
//        }
//        return $it;
//    }

}
