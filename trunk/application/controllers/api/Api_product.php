<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_product extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ckm/ckm_product');
        $this->model = $this->ckm_product;
    }

    public function index_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],//带上is_del=0
            'order' => ['排序'],
        ], [], 'post');
        $grid = $this->model->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $this->returnData($grid);
    }

    public function add_post()
    {
        $request_data = $this->check_param([
            'name' => ['产品名称', 'required'],
            'type' => ['分类', 'required', 'integer'],//1-记账报税 2-工商服务 3-知识产权 4-财税服务 5-网站建设
            'process' => ['流程ID', 'required', 'integer'],
            'price' => ['产品价格', 'required', 'numeric'],
        ], [], 'post');
        $info = $this->model->get_one('*', ['name' => $request_data['name']]);
        if ($info) {
            $this->returnError('名称重复');
        }
        $request_data['create_user'] = $this->loginData['id'];
        $request_data['create_time'] = time();
        $request_data['company_id'] = $this->loginData['company_id'];
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        //添加到绩效分类表
        $this->load->model('cwm/cwm_achievements_cate');
        $cid = $this->loginData['cid'];
        if ($request_data['type'] == 1) {
            $ll = $this->cwm_achievements_cate->get_one('*', ['stype' => 1, 'cid' => $cid]);
            if (empty($ll)) {
                $this->cwm_achievements_cate->addService($cid);
            }
        } else {
            $saveData = [
                'name' => $request_data['name'],
                'product_id' => $id,
                'update_at' => time(),
                'stype' => 2,
                'cid' => $cid
            ];
            $this->cwm_achievements_cate->add($saveData);
        }
        $this->returnData($id);
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
        $this->load->model('cwm/cwm_achievements_cate');
        $this->cwm_achievements_cate->del(['product_id' => $request_data['id']]);
        $this->returnData([]);
    }
}
