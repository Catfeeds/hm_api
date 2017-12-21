<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Zsm_question extends Modelbase
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
            $select[] = "zsm_category.name as 'zsm_category.name'";
//            $select = array_merge(
//                $this->getCols($this->_table),
//                $this->getCols('htm_contract'),
//                $this->getCols('khm_customer')
//            );
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("zsm_category", "zsm_category.id = zsm_question.cate_id", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }
}