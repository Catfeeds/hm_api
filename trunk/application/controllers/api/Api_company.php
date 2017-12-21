<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_company extends Apibase
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('um/um_company', 'model');
    }

    public function edit_post()
    {
        $request_data = $this->check_param([
            'logo' => ['LOGO', 'max_length[200]'],
            'name' => ['企业名称', 'max_length[200]'],
            'address' => ['address', 'max_length[200]'],
            'contact_user' => ['负责人', 'max_length[200]'],
            'tel' => ['电话号码', 'max_length[200]'],
            'phone' => ['手机号码', 'max_length[200]'],
            'email' => ['电子邮箱', 'max_length[200]'],
            'qq' => ['QQ', 'max_length[200]']
        ], [], 'post');
        $cid = $this->loginData['cid'];
        $condition = ['id' => $cid];
        $res = $this->model->edit($condition, $request_data);
        if ($res === false) {
            $this->returnError('编辑失败');
        }
        $this->returnData($request_data);
    }

    public function com_info_get()
    {
        $cid = $this->loginData['cid'];
        $condition = ['id' => $cid];
        $res = $this->model->get_one('*', $condition);
        $this->returnData($res);
    }
}
