<?php

require_once APPPATH . '/models/Modelbase.php';

class Jzm_csd_smallscale extends Modelbase
{
    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    /*
    * @access public 
    * @param string $select 查询哪些字段条件 
    * @param string $filter 查询条件 
    * @param string $order  排序条件
    * @return array 返回类型
     
    public function f7($select = "*", $filter = NULL, $order = NULL)
*/
    // public function setQuery($it, $select = "*", $filter = NULL)
    // {
    //     if ($select == "*") {
    //         $select = array_merge(
    //             $this->getCols($this->_table)
    //         );
    //     }
    //     $it->db->select($select);
    //     $it->db->distinct(TRUE);
    //     $it->db->from($this->_table);
    //     $it->db->join("htm_contract", "htm_contract.id = jzm_service_info.contract_id", "left");
    //     $it->db->join("khm_customer", "htm_contract.customer_id = khm_customer.id", "left");
    //     if (!empty($filter)) {
    //         $it->db->where($filter);
    //     }
    //     return $it;
    // }

}
