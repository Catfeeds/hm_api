<?php

/*
 * 描述TODO
 * @author:jacky Version 1.0.0 2016-7-17
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Ckm_retreat extends Modelbase
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
                $this->getCols('jzm_service_info'),
                $this->getCols('htm_contract'),
                $this->getCols('khm_customer')
            );
//            $select[] = "khm_customer.id as 'khm_customer.id'";
//            $select[] = "khm_customer.name as 'khm_customer.name'";
//            $select[] = "htm_contract.assign_staff_id as 'htm_contract.assign_staff_id'";
//            $select[] = "htm_contract.assign_staff_name as 'htm_contract.assign_staff_name'";
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("jzm_service_info", "jzm_service_info.id = ckm_retreat.service_id", "left");
        $it->db->join("htm_contract", "htm_contract.id = jzm_service_info.contract_id", "left");
        $it->db->join("khm_customer", "htm_contract.customer_id = khm_customer.id", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }
}
