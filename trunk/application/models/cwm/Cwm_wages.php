<?php

/*
 * 描述TODO
 * @author:jacky Version 1.0.0 2016-7-17
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Cwm_wages extends Modelbase
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
//            $select = array_merge($this->getCols($this->_table),$this->getCols('bmm_employees'),$this->getCols('bmm_department'));
            $select[] = "bmm_employees.id as 'bmm_employees.id'";
            $select[] = "bmm_employees.name as 'bmm_employees.name'";
            $select[] = "bmm_department.name as 'bmm_department.name'";
            $select[] = "bmm_department.id as 'bmm_department.id'";
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("bmm_employees", "bmm_employees.id = cwm_wages.employee_id", "left");
        $it->db->join("bmm_department", "bmm_department.id = bmm_employees.department", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }
}
