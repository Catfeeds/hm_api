<?php

require_once APPPATH . '/models/Modelbase.php';

class Htm_contract extends Modelbase
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
//            $select = array_merge($this->getCols($this->_table),$this->getCols('khm_customer'));
            $select = $this->getCols($this->_table);
            $select[] = "khm_customer.id as 'khm_customer.id'";
            $select[] = "khm_customer.name as 'khm_customer.name'";
            $select[] = "khm_customer.tax_type as 'khm_customer.tax_type'";
            $select[] = "khm_customer.introduce as 'khm_customer.introduce'";
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("khm_customer", "khm_customer.id = htm_contract.customer_id", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

}
