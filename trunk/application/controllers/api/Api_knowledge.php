<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_knowledge extends Apibase
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 分组列表
     */
    public function cate_list_get()
    {
        $this->load->model('zsm/zsm_category');
        $cid = $this->loginData['cid'];
        $list = $this->zsm_category->get_all('*', ['cid' => $cid, 'is_del' => 0]);
        $sList = makeTree($list);
        $this->returnData($sList);
    }

    /**
     * 分组添加编辑
     */
    public function cate_add_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'integer'],
            'name' => ['分组名称', 'required'],
            'parent' => ['上级ID', 'required', 'integer'],//顶级分组0
        ], [], 'post');
        $this->load->model('zsm/zsm_category');
        if (empty($request_data['id'])) {
            unset($request_data['id']);
            $request_data['user'] = $this->loginData['id'];
            $request_data['cid'] = $this->loginData['cid'];
            $request_data['create_at'] = time();
            $this->zsm_category->add($request_data);
        } else {
            $this->zsm_category->edit(['id' => $request_data['id']], $request_data);
        }
        $this->returnData();
    }

    /**
     * 分组删除
     */
    public function cate_del_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'required', 'integer'],
            'is_del' => ['是够删除', 'required', 'integer'],// 1-删除 0-不删除
        ], [], 'post');
        $this->load->model('zsm/zsm_category');
        $this->zsm_category->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 问题新增编辑
     */
    public function question_add_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'integer'],
            'cate_id' => ['分组ID', 'required', 'integer'],
            'question' => ['问题', 'required', 'max_length[1000]'],
            'answer' => ['答案', 'max_length[2000]'],
            'status' => ['状态', 'required', 'integer'],//0-未解决 1-解决中 2-已解决
        ], [], 'post');
        $this->load->model('zsm/zsm_question');
        if (empty($request_data['id'])) {
            unset($request_data['id']);
            $request_data['cid'] = $this->loginData['cid'];
            $request_data['create_id'] = $this->loginData['id'];
            $request_data['create_at'] = time();
            $request_data['update_id'] = $this->loginData['id'];
            $request_data['update_at'] = time();
            $this->zsm_question->add($request_data);
        } else {
            $info = $this->zsm_question->get_one('*', ['id' => $request_data['id']]);
            if (!$info) {
                $this->returnError('未查询到信息');
            }
            $request_data['update_id'] = $this->loginData['id'];
            $request_data['update_at'] = time();
            $this->zsm_question->edit(['id' => $request_data['id']], $request_data);
        }
        $this->returnData();
    }

    /**
     * 问题删除
     */
    public function question_del_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'required', 'integer'],
            'is_del' => ['是够删除', 'required', 'integer'],// 1-删除 0-不删除
        ], [], 'post');
        $this->load->model('zsm/zsm_question');
        $this->zsm_question->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 问题列表
     */
    public function question_list_post()
    {
        $request_data = $this->check_param([
            'cate_id' => ['分组ID', 'integer'],
            'question' => ['问题', 'max_length[1000]'],
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
        ], [], 'post');
        $cid = $this->loginData['cid'];
        $filter = "zsm_question.is_del = 0 and zsm_question.cid = {$cid}";
        if (!empty($request_data['cate_id'])) {
            $filter .= " and zsm_question.cate_id = {$request_data['cate_id']}";
        }
        if (!empty($request_data['question'])) {
            $filter .= " and zsm_question.question like '%{$request_data['question']}%'";
        }
        $this->load->model('zsm/zsm_question');
        $grid = $this->zsm_question->grid("*", $filter, $request_data['page'], $request_data['page_size'], 'zsm_question.create_at desc', TRUE);
        $this->returnData($grid);
    }

    /**
     * 问题详情
     */
    public function question_info_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'required', 'integer']
        ], [], 'post');
        $this->load->model('zsm/zsm_question');
        $info = $this->zsm_question->info('*', ['zsm_question.id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $this->returnData($info);
    }
}
