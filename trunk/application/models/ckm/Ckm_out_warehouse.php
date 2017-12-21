<?php

/*
 * 描述TODO
 * @author:jacky Version 1.0.0 2016-7-17
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Ckm_out_warehouse extends Modelbase
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
            $select = array_merge($this->getCols($this->_table), $this->getCols('khm_customer'));
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("khm_customer", "khm_customer.id = ckm_out_warehouse.customer_id", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

}
