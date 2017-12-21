<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class Service 测试
 */
class Api_user extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('bmm/bmm_employees');
        $this->model = $this->bmm_employees;
    }

    //这里没有加入md5和盐值 只是为了方便测试
    public function check_post()
    {
        $request_data = $this->check_param([
            'LoginName' => ['用户名', 'required'],
            'Password' => ['密码', 'required'],
            'status' => ['是否免登陆', 'integer'],
            //0一次性session 1 设置生存周期 
        ], [], 'post');
        $filter = ['username' => $request_data['LoginName']];
        $userData = $this->model->get_one('*', $filter);
        if (!$userData) {
            $this->returnError('用户名不存在');
        }
        if ($userData['password'] != $request_data['Password']) {
            $this->returnError('请检查用户名和密码是否正确');
        }
        $_SESSION['logininfo'] = $userData;
        unset($userData['bmm_employees.password']);
        $this->returnData($userData);
    }

    public function test_get()
    {
        $request_data = $this->check_param([
            'time1' => ['时间1', 'required'],
            'time2' => ['时间2', 'required'],
        ], [], 'get');
        $time1 = $request_data['time1'];
        $time2 = $request_data['time2'];
        $monarr = array();
        $monarr[] = date('Ym', $time1); // 当前月;
        while (($time1 = strtotime('+1 month', $time1)) <= $time2) {
            $monarr[] = date('Ym', $time1); // 取得递增月;
        }
        $monarr[] = date('Ym', $time2);
        $monarr = array_unique($monarr);
        $this->returnData($monarr);
    }
}


