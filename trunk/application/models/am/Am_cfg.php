<?php

/*
 * 任务模型
 * @author:jacky Version 1.0.0 2016-9-19
 */
require_once APPPATH . '/models/Modelbase.php';

class Am_cfg extends Modelbase {

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    public function setQuery($it, $select = "*", $filter = NULL)
    {
        $it->db->select($select);
        $it->db->distinct(TRUE);
        $it->db->from($this->_table);
        $it->db->join("am_cfg_type", "am_cfg_type.key = am_cfg.key", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

}
