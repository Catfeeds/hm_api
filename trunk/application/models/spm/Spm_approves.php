<?php

require_once APPPATH . '/models/Modelbase.php';

class Spm_approves extends Modelbase
{

    public $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = strtolower(__CLASS__);
    }

    public function add_approves($type, $userinfo, $datainfo = [], $customer_name = '', $id = '')
    {
        $data = [
            'approve_type' => $type,
            'submit_employee_id' => $userinfo['id'],
            'submit_employee_name' => $userinfo['name'],
            'submit_time' => time(),
            'submitted_data' => json_encode($datainfo),
            'approve_code' => get_randdata('SP', '', true),
            'customer_name' => $customer_name,
            'customer_num' => $id
        ];
        $this->add($data);
    }
}
