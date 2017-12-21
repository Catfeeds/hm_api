<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Bmm_employees extends Modelbase
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
            $select[] = "bmm_department.id as 'bmm_department.id'";
            $select[] = "bmm_department.name as 'bmm_department.name'";
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("bmm_department", "bmm_department.id = bmm_employees.department", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

    public function getEmployees($cid)
    {
        $cols = $this->f7('*', ['bmm_employees.cid' => $cid]);
        foreach ($cols as $i => $item) {
            $allIds[] = $item['bmm_employees.id'];
            $s = rand(1, 3);
            if ($s == 1) {
                $avatar = 'http://finance.yunkepai.net/resource/adimin/images/logo1.png';
            } else {
                $avatar = 'http://finance.yunkepai.net/resource/adimin/assets/avatars/user.jpg';
            }
            $data[$item['bmm_employees.id']] = [
                'id' => $item['bmm_employees.id'],
                'name' => $item['bmm_employees.name'],
                'role_id' => $item['bmm_employees.role_id'],
                'department_id' => $item['bmm_employees.department'],
                'department_name' => $item['bmm_department.name'],
                'avatar' => $avatar
            ];
        }
        return [$allIds, $data];
    }

    public function getEmps($name, $cid, $field = 'id')
    {
        $cols = $this->get_all($field, "cid = {$cid} and name like '%{$name}%'");
        foreach ($cols as $i => $item) {
            $allIds[] = $item['id'];
        }
        return $allIds;
    }
}
