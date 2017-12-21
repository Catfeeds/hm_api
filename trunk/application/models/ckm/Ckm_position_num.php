<?php

/*
 * 描述TODO
 * @author:jacky Version 1.0.0 2016-7-17
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Ckm_position_num extends Modelbase
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
//            $select = array_merge($this->getCols($this->_table), $this->getCols('khm_customer'));
//        }
//        $it->db->select($select);
//        $it->db->distinct(TRUE);
//        $it->db->from($this->_table);
//        $it->db->join("khm_customer", "khm_customer.id = ckm_in_warehouse.customer_id", "left");
//        if (!empty($filter)) {
//            $it->db->where($filter);
//        }
//        return $it;
//    }

    public function getSeriesData($position,$where=[],$cid=0,$type){
        $select = [];
        $legend = [];
        foreach ($position as $key=>$val){
            $legend[] = $val['name'];
            $select[] = "sum(case when pos_id=".$val['id']." then 1 else 0 end) as count".$key;
        }

        $select = implode(',',$select);

        $sql = "SELECT $select FROM ".$this->_table." WHERE EXISTS(
        select customer_id from jzm_service_info 
        where ".$this->_table.".customer_id=customer_id and cid=".$cid." and type=$type 
        and com_time>={$where['start_date']} and com_time<{$where['end_date']}
        )";
        $res = $this->db->query($sql)->result_array();
        return [
            'legend_title' => $legend,
            'legend_data'  => $res[0]
        ];
    }

    public function allotNum($pos_id, $customer_id)
    {
        $num_id = $this->get_one('id', "pos_id = {$pos_id} and customer_id = 0");
        $this->edit(['customer_id' => $customer_id], ['customer_id' => 0]);
        $this->edit(['id' => $num_id['id']], ['customer_id' => $customer_id]);
        return $num_id;
    }
}
