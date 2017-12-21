<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class Service 测试
 */
class Api_test extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('jzm/Jzm_csd_smallscale');
        $this->model = $this->Jzm_csd_smallscale;
    }
    public function test_get(){

        echo '-----';


    }


    public function index_get()
    {
        $request_data = $this->check_param([
        	'status' => ['状态','integer'], 
            'type' => ['类型','integer'],
        ], [], 'get');
        /**
         * 验证成功后的逻辑
         */
        // var_dump($request_data);die;
       $month = date('Ym',time());
       $where = [
            'jzm_csd_smallscale.type' => $request_data['type'],
			'jzm_csd_smallscale.status' => 1,
        ];
        $data = $this->model->f7('*',$where);
        //$data = $this->model->grid("*", $where, $request_data['page'], $request_data['page_size'],'pic desc',TRUE);
        
         
        $this->returnData($data);
    }
//简单编辑
    public function edit_post(){
        $request_data = $this->check_param([
            'id'    => ['主键','integer'],
            'status' => ['状态','integer'], 
            'type' => ['类型','integer'],
        ], [], 'get_post');
        // var_dump($request_data);die;
        $condition = [ 'id' => $request_data['id'] ];
        $res = $this->model->edit($condition, $request_data);
        if ($res === false) {
            $this->returnError('编辑失败');
        }
        $this->returnData([]);
    }
//简单增加 add
    public function add_post(){
        $request_data = $this->check_param([
            'id'    => ['主键','integer'],
            'status' => ['状态','integer'], 
            'type' => ['类型','integer'],
        ], [], 'get_post');
        // var_dump($request_data);die;
        $res = $this->model->add($request_data);
        if ($res === false) {
            $this->returnError('添加失败');
        }
        $this->returnData([]);
    }
//简单删除
    public function del_post(){
        $request_data = $this->check_param([
            'id'    => ['主键','integer'],
            'status' => ['状态','integer'], 
            'type' => ['类型','integer'],
        ], [], 'get_post'); 
       $filter = ['id'=>$request_data['id']];
        $this->model->del($filter);   
        if ($res === false) {
             $this->returnData('删除失败');   
        }
        $this->returnData([]);
    }
//获取所有的字段
    public function getcol_post(){
        $request_data = $this->check_param([
            'id'    => ['主键','integer'],
            'status' => ['状态','integer'], 
            'type' => ['类型','integer'],
        ], [], 'get_post'); 
        //这里可以用 array_merge 合并两个表中的所有字段成为一个新的数组
        $data = $this->getCols('jzm_csd_smallscale');
        $this->returnData($data);
    }
}
