<?php

require_once APPPATH . '/models/Modelbase.php';

class Jzm_acquiring_details extends Modelbase
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
//                $this->getCols('jzm_acquiring_cate')
//            );
//        }
//        $it->db->select($select);
//        $it->db->distinct(TRUE);
//        $it->db->from($this->_table);
//        $it->db->join("jzm_acquiring_cate", "jzm_acquiring_cate.id = jzm_acquiring_details.cate_id", "left");
//        if (!empty($filter)) {
//            $it->db->where($filter);
//        }
//        return $it;
//    }

}
