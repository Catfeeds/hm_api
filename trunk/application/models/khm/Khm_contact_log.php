<?php

/*
 * 描述TODO
 * @author:jacky Version 1.0.0 2016-7-17
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Khm_contact_log extends Modelbase
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
            $select = $this->getCols($this->_table);
            $select[] = "khm_customer.id as 'khm_customer.id'";
            $select[] = "khm_customer.name as 'khm_customer.name'";
            $select[] = "khm_contact_book.id as 'khm_contact_book.id'";
            $select[] = "khm_contact_book.username as 'khm_contact_book.username'";
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("khm_customer", "khm_contact_log.customer_id = khm_customer.id", "left");
        $it->db->join("khm_contact_book", "khm_contact_book.id = khm_contact_log.contact_user", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }
}
