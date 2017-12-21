<?php

/*
 * 描述TODO
 * @author:jacky Version 1.0.0 2016-7-17
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'models/Modelbase.php';

class Cwm_achievements_cate extends Modelbase
{

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    public function addService($cid)
    {
        $data = [
            ['name' => '送单', 'update_at' => time(), 'type' => 1, 'stype' => 1, 'cid' => $cid],
            ['name' => '整单', 'update_at' => time(), 'type' => 1, 'stype' => 1, 'cid' => $cid],
            ['name' => '收单', 'update_at' => time(), 'type' => 1, 'stype' => 1, 'cid' => $cid],
            ['name' => '报税', 'update_at' => time(), 'type' => 1, 'stype' => 1, 'cid' => $cid],
            ['name' => '客服', 'update_at' => time(), 'type' => 1, 'stype' => 1, 'cid' => $cid],
            ['name' => '记账', 'update_at' => time(), 'type' => 1, 'stype' => 1, 'cid' => $cid],

            ['name' => '记账', 'update_at' => time(), 'type' => 2, 'stype' => 1, 'cid' => $cid],
            ['name' => '送单', 'update_at' => time(), 'type' => 2, 'stype' => 1, 'cid' => $cid],
            ['name' => '整单', 'update_at' => time(), 'type' => 2, 'stype' => 1, 'cid' => $cid],
            ['name' => '收单', 'update_at' => time(), 'type' => 2, 'stype' => 1, 'cid' => $cid],
            ['name' => '报税', 'update_at' => time(), 'type' => 2, 'stype' => 1, 'cid' => $cid],
            ['name' => '客服', 'update_at' => time(), 'type' => 2, 'stype' => 1, 'cid' => $cid],
        ];
        $this->add_batch($data);
    }
}
