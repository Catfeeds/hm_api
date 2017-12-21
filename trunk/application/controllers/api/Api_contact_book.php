<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * test
 */
class Api_contact_book extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('khm/Khm_contact_book', 'model');
    }

    public function index_get()
    {
        $condition = $this->check_param([
            'username' => ['姓名', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'contact_user' => ['客户名称', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'main_user' => ['关键决策人', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'phone' => ['手机号码', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'telephone' => ['电话号码', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'qq' => ['QQ', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'sex' => ['性别', 'integer'],  // 1:男 2：女 3：未知
            'is_main' => ['是否主要联系人', 'integer'],
            'filter' => ['查询条件'],   //  与上面的多个条件会自动拼接起来
        ]);
        $request_data = $this->check_param([
            'page' => ['当前页', 'integer'],
            'rows'  =>   ['每页多少内容', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'order' =>  ['排序规则'],
        ]);
        if(!$request_data['page_size'] && $request_data['rows']){
            $request_data['page_size'] = $request_data['rows'];
        }
        $condition['is_del'] = 0;
        $condition['company_id'] = $this->loginData['company_id'];
        if(!$request_data['order']){
            $request_data['order'] = 'id desc';
        }
        $data = $this->model->grid("*", $condition, $request_data['page'], $request_data['page_size'], 'id desc', TRUE);
        $this->returnData($data);
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'username' => ['姓名', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'customer_id' => ['客户ID', 'required', 'integer'],
            'phone' => ['手机号码', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'qq' => ['QQ', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'telephone' => ['电话号码', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'sex' => ['性别', 'required', 'integer'],  // 1:男 2：女 3：未知
            'main_user' => ['是否关键决策人', 'required', 'integer'],//0-否 1-是
            'is_main' => ['是否主要联系人', 'required', 'integer'],//0-否 1-是
        ], [], 'post');
        $request_data['create_user'] = $this->loginData['id'];
        $request_data['last_modify_user'] = $this->loginData['id'];
        $request_data['create_time'] = time();
        $request_data['cid'] = $this->loginData['cid'];
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData([]);
    }

    public function edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'username' => ['姓名', 'required', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'customer_id' => ['客户ID', 'required', 'integer'],
            'phone' => ['手机号码',  'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'qq' => ['QQ',  'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'telephone' => ['电话号码', 'regex_match[/[\x{4e00}-\x{9fa5}\w]+/u]'],
            'sex' => ['性别', 'required', 'integer'],  // 1:男 2：女 3：未知
            'main_user' => ['是否关键决策人', 'required', 'integer'],//0-否 1-是
            'is_main' => ['是否主要联系人', 'required', 'integer'],//0-否 1-是
        ], [], 'post');
        $condition = ['id' => $request_data['id']];
        $request_data['last_modify_user'] = $this->loginData['id'];
        $request_data['last_modify_time'] = time();
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

}
