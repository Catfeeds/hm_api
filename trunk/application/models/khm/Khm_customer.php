<?php

require_once APPPATH . '/models/Modelbase.php';

class Khm_customer extends Modelbase
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
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

//    public function setQuery($it, $select = "*", $filter = NULL)
//    {
//        $select = $this->getCols($this->_table);
//        $it->db->select($select);
//        $it->db->distinct(TRUE);
//        $it->db->from($this->_table);
//        if (!empty($filter)) {
//            foreach ($filter as $k =>&$v){
//                if(is_array($v) && $v[0] === 'like'){
//                    $it->db->like($k, $v[1]);
//                    unset($filter[$k]);
//                }elseif(is_null($v)){
//                    unset($filter[$k]);
//                }elseif($k == 'filter'){
//                    $it->db->where($v);
//                    unset($filter[$k]);
//                }
//            }
//            $it->db->where($filter);
//        }
//        return $it;
//    }

    public function getDoYearCount($cid=0){ //本天，本月，本年客户数
        $day = strtotime(date("Y-m-d"));
        $month = strtotime(date("Y-m-01"));
        $year  = strtotime(date("Y-01-01"));
        $sql = "select 
            sum(case when create_at>=$day THEN 1 ELSE 0 END) as day_count,
            sum(case when create_at>=$month THEN 1 ELSE 0 END) as month_count,
            sum(case when create_at>=$year THEN 1 ELSE 0 END) as year_count
             from ".$this->_table;
        if($cid){
            $sql.=" WHERE cid=".$cid;
        }
        $res = $this->db->query($sql);
        $res = $res->result_array();
        return $res[0];
    }

    public function getList($start_date,$month_num=0,$cid=0){
        $sql = "select ";
        $case_when=[];
        if($month_num>=1){
            for ($i=0; $i<=$month_num; $i++){
                $start_time = strtotime(date("Y-m-01",$start_date)." +$i month");
                $end_time   = strtotime(date("Y-m-01",$start_date)." + ".($i+1)." month");
                $case_when[] = "sum(case when create_at>=$start_time AND  create_at<$end_time THEN 1 ELSE 0 END) as count".$i;
            }
        }else{
            $end_time   = strtotime(date("Y-m-01",$start_date)." + 1 month");
            $case_when[] = "sum(case when create_at>=$start_date AND create_at<$end_time THEN 1 ELSE 0 END) as count0";
        }
        $case_when[] = "sum(case when tax_type=1 AND c_type=1 AND status =1  THEN 1 ELSE 0 END) as taxtype_1_ctype";
        $case_when[] = "sum(case when tax_type=1 AND z_type=1 AND status =1  THEN 1 ELSE 0 END) as taxtype_1_ztype";
        $case_when[] = "sum(case when tax_type=1 AND c_type=1 AND status =1 AND z_type=1  THEN 1 ELSE 0 END) as taxtype_1_ctype_ztype";
        $case_when[] = "sum(case when tax_type=2 AND c_type=1 AND status =1  THEN 1 ELSE 0 END) as taxtype_2_ctype";
        $case_when[] = "sum(case when tax_type=2 AND z_type=1 AND status =1  THEN 1 ELSE 0 END) as taxtype_2_ztype";
        $case_when[] = "sum(case when tax_type=2 AND c_type=1 AND status =1 AND z_type=1  THEN 1 ELSE 0 END) as taxtype_2_ctype_ztype";
        $sql .= implode(',',$case_when)." FROM ".$this->_table;
        if($cid){
            $sql.=" WHERE cid=".$cid." AND create_at>=".$start_time." AND create_at<".$end_time;
        }
        $res = $this->db->query($sql);
        $res = $res->result_array();
        return $res;
    }
}
