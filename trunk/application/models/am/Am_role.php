<?php

require_once APPPATH . '/models/Modelbase.php';

class Am_role extends Modelbase
{

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    public function get_nodes($info)
    {
        $role_id = $info['role_id'];
        $roleInfo = $this->get_one('*', ['id' => $role_id]);
        if (!$roleInfo) {
            return false;
        }
        $nodes = $roleInfo['nodes'];
        if ($nodes == '*') {
            $this->load->model('am/am_nodes');
            $nodesInfo = $this->am_nodes->get_all('*');
            foreach ($nodesInfo as $item) {
                $lis[] = $item['id'];
            }
            $nodes = implode(',', $lis);
        }
        return $nodes;
    }
}
