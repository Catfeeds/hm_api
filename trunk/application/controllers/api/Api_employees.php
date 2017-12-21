<?php

/*
 * 文章管理
 * @author:jacky Version 1.0.0 2016-6-12
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * test
 */
class Api_employees extends Apibase
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('bmm/bmm_employees');
        $this->model = $this->bmm_employees;
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'name' => ['姓名', 'required', 'max_length[200]'],
            'username' => ['用户名', 'required', 'max_length[200]'],
            'password' => ['密码', 'required', 'max_length[200]'],
            'department' => ['部门', 'integer'],
            'sex' => ['性别', 'integer'],//性别 1-男 2-女 0-未知
            'birthday' => ['生日', 'integer'],
            'maritalStatus' => ['任职状态', 'integer'],
            'domicile' => ['住所', 'max_length[200]'],
            'degreeLevel' => ['教育等级', 'integer'],
            'school' => ['学校', 'max_length[200]'],
            'empno' => ['工号', 'max_length[200]'],
            'employedDate' => ['入职时间', 'integer'],
            'officePhone' => ['办公室电话', 'max_length[200]'],
            'mobilePhone' => ['手机号', 'max_length[200]'],
            'email' => ['邮箱', 'max_length[200]'],
            'emergencyContactName' => ['紧急联系人姓名', 'max_length[200]'],
            'emergencyPhone' => ['紧急联系人电话', 'max_length[200]'],
            'identityCode' => ['身份证', 'max_length[18]'],
            'workStatus' => ['工作状态', 'max_length[200]'],
            'currentAddress' => ['当前住所', 'max_length[200]'],
            'siteUser_Id' => ['个人网站', 'max_length[200]'],
            'role_id' => ['角色', 'integer'],
        ], [], 'post');
        if (empty($request_data['employedDate'])) {
            $request_data['employedDate'] = time();
        }
        $request_data['cid'] = $this->loginData['cid'];
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData(['id' => $id]);
    }

    public function edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'name' => ['姓名', 'required', 'max_length[200]'],
            'username' => ['用户名', 'required', 'max_length[200]'],
            'password' => ['密码', 'required', 'max_length[200]'],
            'department' => ['部门', 'integer'],
            'sex' => ['性别', 'integer'],//性别 1-男 2-女 0-未知
            'birthday' => ['生日', 'integer'],
            'maritalStatus' => ['任职状态', 'integer'],
            'domicile' => ['住所', 'max_length[200]'],
            'degreeLevel' => ['教育等级', 'integer'],
            'school' => ['学校', 'max_length[200]'],
            'empno' => ['工号', 'max_length[200]'],
            'employedDate' => ['入职时间', 'integer'],
            'officePhone' => ['办公室电话', 'max_length[200]'],
            'mobilePhone' => ['手机号', 'max_length[200]'],
            'email' => ['邮箱', 'max_length[200]'],
            'emergencyContactName' => ['紧急联系人姓名', 'max_length[200]'],
            'emergencyPhone' => ['紧急联系人电话', 'max_length[200]'],
            'identityCode' => ['身份证', 'max_length[18]'],
            'workStatus' => ['工作状态', 'max_length[200]'],
            'currentAddress' => ['当前住所', 'max_length[200]'],
            'siteUser_Id' => ['个人网站', 'max_length[200]'],
            'role_id' => ['角色', 'integer'],
        ], [], 'post');
        $condition = ['id' => $request_data['id']];
        $res = $this->model->edit($condition, $request_data);
        if ($res === false) {
            $this->returnError('编辑失败');
        }
        $this->returnData([]);
    }

    public function del_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
        ], [], 'post');
        $res = $this->model->edit($request_data, ['is_del' => 1]);
        if ($res === false) {
            $this->returnError('删除失败');
        }
        $this->returnData([]);
    }
	
	public function edit_info_post() {
		$request_data = $this->check_param([
			'id' => ['用户id','required', 'integer'],
            'marriage' => ['婚姻状态', 'integer'],
            'origin' => ['户籍', 'max_length[200]'],
            'emergencyContactName' => ['紧急联系人', 'max_length[200]'],
            'emergencyPhone' => ['紧急联系人电话', 'max_length[200]'],
            'identityCode' => ['身份证号码', 'integer'],
            'mail_address' => ['通讯地址'],//性别 1-男 2-女 0-未知
            'img_card' => ['身份证照片', 'max_length[200]'],
            'img_school' => ['毕业证照片', 'max_length[200]'],
            'img_qualifications' => ['资格证照片', 'max_length[200]'],
            'img_labour' => ['劳动照片', 'max_length[200]'],
            'img_other' => ['其他照片', 'max_length[200]'],
        ], [], 'post');
		
		$condition = ['id' => $request_data['id']];
        $res = $this->model->edit($condition, $request_data);
        if ($res === false) {
            $this->returnError('编辑失败');
        }
		$this->returnData([]);
	}
	
	//修改密码
	public function change_pwd_post() {
		$request_data = $this->check_param([
			'id' => ['用户id','required', 'integer'],
            'password' => ['旧密码','required' ,'integer'],
            'newPassword' => ['新密码','required', 'integer'],
        ], [], 'post');
		
		$info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('没有用户信息');
        }
		$data['bmm_employees.id'] = $request_data['id'];
		$data['bmm_employees.password'] =  $request_data['password'];
		
		$result = $this->model->info('*', $data);
		if(!$result) {
			$this->returnError('原始密码不正确');
		}else{
			$condition = ['id' => $request_data['id']];
			$data = ['password' => $request_data['newPassword']];
        	$res = $this->model->edit($condition,$data);
		}
		$this->returnData([]);
	}

}
