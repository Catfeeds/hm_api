<?php

/*
 * 描述TODO
 * @author:jacky Version 1.0.0 2016-7-17
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Mbm_assistant extends Modelbase {

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

//    public function setQuery($it, $select = "*", $filter = NULL)
//    {
//        $select = $this->getCols($this->_table);
//        $it->db->select($select);
//        $it->db->distinct(TRUE);
//        $it->db->from($this->_table);
//        if (!empty($filter)) {
//foreach ($filter as $k =>&$v){
//if(is_array($v) && $v[0] === 'like'){
//$it->db->like($k, $v[1]);
//unset($filter[$k]);
//}
//}
//$it->db->where($filter);
//}
//        return $it;
//    }

}
