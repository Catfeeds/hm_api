<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_login extends Apibase {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('hm/login/hm_login', 'model');
    }

    public function login_post()
    {
        $request_data = $this->check_param([
            'uname' => ['用户名', 'required'],
            'pwd' => ['密码', 'required'],
                ], [], 'post');
        $userid = $this->model->get_one('id', ['uname' => $request_data['uname']]);
        if (!$userid) {
            $this->returnError('用户名不存在');
        } else {
            $data['uname'] = $request_data['uname'];
            $data['pwd'] = $request_data['pwd'];
            $result = $this->model->get_one('id', $data);
            if (!$result) {
                $this->returnError('原始密码不正确');
            } else {
                $data['logintime'] = time();
                $data['loginip'] = _get_ip();
                $condition = ['id' => $result['id']];
                $res = $this->model->edit($condition, $data);
                if ($res) {
                    $result = $this->model->get_one('*', $condition = ['id' => $result['id']]);
                    unset($result['pwd']);
                }
            }
        }
        $this->returnData($result);
    }

    //获取用户信息
    public function userInfo_post()
    {
        $request_data = $this->check_param([
            'id' => ['用户id', 'required'],
                ], [], 'post');
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if ($info) {
            unset($info['pwd']);
            $this->returnData($info);
        } else {
            $this->returnError('用户不存在，请输入正确的id');
        }
    }

    public function change_pwd_post()
    {
        $request_data = $this->check_param([
            'id' => ['用户id', 'required'],
            'pwd' => ['旧密码', 'required'],
            'newPwd' => ['新密码', 'required'],
                ], [], 'post');
        $id = $this->model->get_one('id', ['id' => $request_data['id']]);
        if (!$id) {
            $this->returnError('用户名不存在');
        } else {
            $data['id'] = $request_data['id'];
            $data['pwd'] = $request_data['pwd'];
            $result = $this->model->get_one('id', $data);
            if ($result) {
                $condition = ['id' => $request_data['id']];
                $data['pwd'] = $request_data['newPwd'];
                $res = $this->model->edit($condition, $data);
                $this->returnData($res);
            } else {
                $this->returnError('密码不正确');
            }
        }
    }

    public function edit_info_post()
    {
        $request_data = $this->check_param([
            'id' => ['用户id', 'required'],
            'email' => ['邮箱'],
            'tname' => ['用户名', 'required'],
            'sex' => ['性别'],
                ], [], 'post');
        $condition = ['id' => $request_data['id']];
        $res = $this->model->edit($condition, $request_data);
        $this->returnData($request_data);
    }

}
