<?php

/*
 * 广告模型
 * @author:jacky Version 1.0.0 2016-9-19
 */
require_once APPPATH . '/models/Modelbase.php';

class Am_ads extends Modelbase {

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
        $it->db->join("am_ads_type", "am_ads_type.id = am_ads.type", "left");
        if (!empty($filter)) {
            $it->db->where($filter);
        }
        return $it;
    }

}
