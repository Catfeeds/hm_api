<?php

require_once APPPATH . '/models/Modelbase.php';

class Spm_manage extends Modelbase
{

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    public function getUserID($name, $cid = 1)
    {
        $data = $this->get_all('name,user', ['name' => $name, 'cid' => $cid]);
        foreach ($data as $v) {
            $ids[] = $v['user'];
        }
        return $ids;
    }
}
