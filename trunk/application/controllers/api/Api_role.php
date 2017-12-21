<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_role extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('am/am_role');
        $this->model = $this->am_role;
    }

    /**
     * 新增编辑角色
     */
    public function role_add_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['角色ID', 'integer'],
            'name' => ['名称', 'required', 'max_length[200]'],
            'nodes' => ['权限节点', 'required', 'max_length[200]'],//1,2,3,4
        ], [], 'post');
        if (empty($request_data['id'])) {
            unset($request_data['id']);
            $request_data['cid'] = $this->get_login_info()['cid'];
            $request_data['create_at'] = time();
            $id = $this->model->add($request_data);
        } else {
            $condition = ['id' => $request_data['id']];
            $info = $this->model->get_one('*', $condition);
            if (!$info) {
                $this->returnError('未查询到信息');
            }
            $request_data['update_at'] = time();
            $id = $this->model->edit($condition, $request_data);
        }
        if ($id === false) {
            $this->returnError('提交失败');
        }
        $this->returnData();
    }

    /**
     * 新增编辑分组
     */
    public function cate_add_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'integer'],
            'name' => ['分组名称', 'required', 'max_length[200]'],
        ], [], 'post');
        $this->load->model('am/am_nodes_cate');
        if (empty($request_data['id'])) {
            $request_data['cid'] = $this->get_login_info()['cid'];
            $id = $this->am_nodes_cate->add($request_data);
        } else {
            $condition = ['id' => $request_data['id']];
            $info = $this->am_nodes_cate->get_one('*', $condition);
            if (!$info) {
                $this->returnError('未查询到信息');
            }
            $id = $this->am_nodes_cate->edit($condition, $request_data);
        }
        if ($id === false) {
            $this->returnError('提交失败');
        }
        $this->returnData();
    }

    /**
     * 删除分组
     */
    public function del_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('am/am_nodes');
        $this->load->model('am/am_nodes_cate');
        $condition = ['id' => $request_data['id']];
        $info = $this->am_nodes_cate->get_one('*', $condition);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $this->am_nodes->edit(['cate_id' => $request_data['id']], ['cate_id' => 0]);
        $this->am_nodes_cate->edit($condition, ['is_del' => 1]);
        $this->returnData();
    }

    /**
     * 角色权限分组列表
     */
    public function list_cate_post()
    {
        $request_data = $this->check_param([
            'role' => ['角色ID', 'required', 'integer'],
        ], [], 'post');
        $roleInfo = $this->model->get_one('*', ['id' => $request_data['role']]);
        if (!$roleInfo) {
            $this->returnError('未查询到信息');
        }
        $nodes = $roleInfo['nodes'];
        if ($nodes == '*') {
            $this->load->model('am/am_nodes');
            $nodesInfo = $this->am_nodes->get_all('*');
            foreach ($nodesInfo as $item) {
                $lis[] = $item['id'];
            }
            $nodes = implode(',', $lis);
        }
        $this->returnData($nodes);
    }

    /**
     * 角色列表
     */
    public function list_role_get()
    {
        $cid = $this->get_login_info()['cid'];
        $list = $this->model->get_all('*', ['cid' => $cid]);
        $this->returnData($list);
    }

    /**
     * 获取登录用户的权限
     */
    public function get_user_nodes_get()
    {
        $info = $this->get_login_info();
        $nodes = $this->model->get_nodes($info);
        if (!$nodes) {
            $this->returnError('登录信息角色有误');
        }
        $list = $this->getRoleCate($nodes);
        $this->returnData(['nodes' => $nodes, 'list' => $list]);
    }

    /**
     * 修改权限所属分组
     */
    public function change_nodes_cate_post()
    {
        $request_data = $this->check_param([
            'role' => ['权限ID', 'required', 'integer'],//权限parent=0的权限，子集会一起移动
            'cate_id' => ['分组ID', 'required', 'integer'],//不分组的时候传0
        ], [], 'post');
        $this->load->model('am/am_nodes');
        $this->am_nodes->edit(['id' => $request_data['role']], ['cate_id' => $request_data['cate_id']]);
        $this->am_nodes->edit(['parent' => $request_data['role']], ['cate_id' => $request_data['cate_id']]);
        $this->returnData();
    }

    /**
     * @param $nodes 权限节点 格式 1,2,3,4
     * @return mixed 返回节点分组
     */
    private function getRoleCate($nodes)
    {
        $this->load->model('am/am_nodes');
        $this->load->model('am/am_nodes_cate');
        $cateList = $this->am_nodes_cate->get_all('*', ['is_del' => 0]);
        $nodesLists = $this->am_nodes->get_all('*', "id in ({$nodes})");
        foreach ($nodesLists as $item) {
            if ($item['parent'] == 0) {
                $list[] = $item;
            } else {
                foreach ($list as $j => $jtem) {
                    if ($item['parent'] == $jtem['id']) {
                        $list[$j]['child'][] = $item;
                        continue;
                    }
                }
            }
        }
        foreach ($list as $i => $item) {
            if ($item['cate_id'] == 0) {
                $cateList['other'][] = $item;
                continue;
            }
            foreach ($cateList as $key => $cate) {
                if ($cate['id'] == $item['cate_id']) {
                    $cateList[$key]['child'][] = $item;
                    continue;
                }
            }
        }
        return $cateList;
    }
}

