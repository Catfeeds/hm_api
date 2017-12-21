<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_goods extends Apibase {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('hm/good/hm_goods', 'model');
    }

    public function index_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
                ], [], 'post');
        $grid = $this->model->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $this->returnData($grid);
    }

    public function add_goods_post()
    {
        $request_data = $this->check_param([
            'id' => ['商品id', 'required', 'integer'],
            'typeid' => ['栏目id', 'required', 'integer'],
            'typeid2' => ['副栏目id', 'required', 'integer'],
            'writer' => ['作者', 'required',],
            'pubdate' => ['发布日期'],
            'litpic' => ['缩略图'],
            'mid' => ['管理员id'],
            'notpost' => ['是否允许回复'],
            'body' => ['商品简介'],
            'price' => ['市场价', 'required'],
            'trueprice' => ['优惠价', 'required'],
            'brand' => ['品牌', 'required', 'integer'],
            'stock' => ['库存', 'required', 'integer'],
            'putStatus' => ['上架状态', 'required', 'integer'],
                ], [], 'post');
        $this->returnData();
    }

//    回收站
    public function del_post()
    {
        $request_data = $this->check_param([
            'id' => ['商品id', 'required'], //1,2,3,4
                ], [], 'post');
        $where = "id in ({$request_data['id']})";
        $res = $this->model->edit($where, ['is_del' => 1]);
        if ($res === false) {
            $this->returnError('删除失败');
        }
        $this->returnData($res);
    }

    //回收站
    public function get_back_post()
    {
        $request_data = $this->check_param([
            'id' => ['商品id', 'required'], //1,2,3,4
                ], [], 'post');
        $where = "id in ({$request_data['id']})";
        $res = $this->model->edit($where, ['is_del' => 0]);
        if ($res === false) {
            $this->returnError('还原失败');
        }
        $this->returnData($res);
    }

//    public function delete_post()
//    {
//        $request_data = $this->check_param([
//            'id' => ['商品id', 'required'], //1,2,3,4
//                ], [], 'post');
//        $where = "id in ({$request_data['id']})";
//        $res = $this->model->del($where);
//        if ($res === false) {
//            $this->returnError('删除成功');
//        }
//        $this->returnData($res);
//    }
}
