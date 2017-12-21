<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2017-10-20
 * Time: 15:58
 */
require_once APPPATH . '/models/Modelbase.php';

class Nm_notice extends ModelBase
{
    function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    public function setQuery($it, $select = '*', $filter = NULL)
    {
        if ($select == '*') {
            $select = $this->getCols($this->_table);
            $select[] = "bmm_employees.name as 'bmm_employees.name'";
        }
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join('bmm_employees', 'bmm_employees.id = nm_notice.user', 'left');
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }
}