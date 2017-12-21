<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Hm_goods extends Modelbase {

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = "hp_addonshop";
    }

    public function setQuery($it, $select = "*", $filter = NUll)
    {

        if ($select == "*") {
            $select = array_merge($this->getCols($this->_table), $this->getCols('hm_admin'));
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("hm_admin", "  hm_admin.id = hp_addonshop.create_by", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

}
