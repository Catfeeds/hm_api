<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class Api_accountancies 仓库管理
 */
class Api_accountancies extends Apibase
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 入库出库页面
     */
    public function index_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'], //1-入库 2-出库
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
        ], [], 'post');
        if ($request_data['type'] == 1) {
            $this->load->model('ckm/ckm_in_warehouse');
            $grid = $this->ckm_in_warehouse->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        } else {
            $this->load->model('ckm/ckm_out_warehouse');
            $grid = $this->ckm_out_warehouse->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        }
        $this->returnData($grid);
    }

    /**
     * 出入库新增
     */
    public function add_io_post()
    {
        $request_data = $this->check_param([
            'stype' => ['类型', 'required', 'integer'],//1-入库 2-出库
            'type' => ['类型', 'required', 'integer'],//类型1-凭证 2-单据 3-证件
            'month' => ['月份', 'required', 'integer'],
            'customer_id' => ['客户id', 'required', 'integer'],
            'goods' => ['物品', 'required'],
            'number' => ['数量', 'required', 'integer'],
            'time' => ['时间', 'required', 'integer'],
            'jb_id' => ['经办人id', 'integer'],
            'remark' => ['备注'],
        ], [], 'post');
        if ($request_data['stype'] == 1) {
            $this->load->model('ckm/ckm_in_warehouse');
            $this->model = $this->ckm_in_warehouse;
            $request_data['num'] = $this->get_num('RK');
        } else {
            $this->load->model('ckm/ckm_out_warehouse');
            $this->model = $this->ckm_out_warehouse;
            $request_data['num'] = $this->get_num('CK');
        }
        unset($request_data['stype']);
        $login = $this->get_login_info();
        $request_data['create_id'] = $login['id'];
        $request_data['create_time'] = time();
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData();
    }

    /**
     * 出库入库详情
     */
    public function info_io_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],//1-入库 2-出库
            'id' => ['主键ID', 'required', 'integer'],
        ], [], 'post');
        if ($request_data['type'] == 1) {
            $this->load->model('ckm/ckm_in_warehouse');
            $this->model = $this->ckm_in_warehouse;
        } else {
            $this->load->model('ckm/ckm_out_warehouse');
            $this->model = $this->ckm_out_warehouse;
        }
        unset($request_data['type']);
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到数据');
        }
        $this->returnData($info);

    }

    /**
     * 出库入库编辑
     */
    public function edit_io_post()
    {
        $request_data = $this->check_param([
            'stype' => ['类型', 'required', 'integer'],//1-入库 2-出库
            'id' => ['主键ID', 'required', 'integer'],
            'type' => ['类型', 'required', 'integer'],//类型1-凭证 2-单据 3-证件
            'month' => ['月份', 'required', 'integer'],
            'customer_id' => ['客户id', 'required', 'integer'],
            'goods' => ['物品', 'required'],
            'number' => ['数量', 'required', 'integer'],
            'time' => ['时间', 'required', 'integer'],
            'jb_id' => ['经办人id', 'integer'],
            'remark' => ['备注'],
        ], [], 'post');
        if ($request_data['stype'] == 1) {
            $this->load->model('ckm/ckm_in_warehouse');
            $this->model = $this->ckm_in_warehouse;
        } else {
            $this->load->model('ckm/ckm_out_warehouse');
            $this->model = $this->ckm_out_warehouse;
        }
        unset($request_data['stype']);
        $id = $this->model->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('编辑失败');
        }
        $this->returnData();
    }

    /**
     * 出库入库审核
     */
    public function sh_io_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],//1-入库 2-出库
            'id' => ['主键ID', 'required', 'integer'],
            'status' => ['审核状态', 'required', 'integer'],//0-未审核 1-未通过 2-已审核
            'qr_remark' => ['审批回复'],
        ], [], 'post');
        $login = $this->get_login_info();
        if ($request_data['type'] == 1) {
            $this->load->model('ckm/ckm_in_warehouse');
            $this->model = $this->ckm_in_warehouse;
        } else {
            $this->load->model('ckm/ckm_out_warehouse');
            $this->model = $this->ckm_out_warehouse;
        }
        unset($request_data['type']);
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到信息');
        }
        if ($request_data['status'] != 0) {
            $request_data['qr_id'] = $login['id'];
            $request_data['qr_time'] = time();
        }
        $this->model->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 出库入库批量审核
     */
    public function batch_sh_io_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],//1-入库 2-出库
            'data' => ['数据', 'required'],//格式[{'id':'1','status':'2','qr_remark':'123'},{}]
        ], [], 'post');
        if ($request_data['type'] == 1) {
            $this->load->model('ckm/ckm_in_warehouse');
            $this->model = $this->ckm_in_warehouse;
        } else {
            $this->load->model('ckm/ckm_out_warehouse');
            $this->model = $this->ckm_out_warehouse;
        }
        unset($request_data['type']);
        $batch = json_decode($request_data['data'], true);
        if (!$batch) {
            $this->returnError('数据格式错误，请检查！');
        }
        foreach ($batch as $item) {
            $this->sh_io($item);
        }
        $this->returnData();
    }


    /**
     * 出库入库审核方法
     */
    private function sh_io($request_data)
    {
        $login = $this->get_login_info();
        $info = $this->model->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到信息');
        }
        if ($request_data['status'] != 0) {
            $request_data['qr_id'] = $login['id'];
            $request_data['qr_time'] = time();
        }
        $this->model->edit(['id' => $request_data['id']], $request_data);
    }


    /**
     * 退单列表
     */
    public function retreat_grid_post()
    {
        $select = $this->input->get_post('select');//查询字段
        $page_size = $this->input->get_post('limit'); //每页显示多少条
        $page = $this->input->get_post('page');//第几页
        $sidx = $this->input->get_post('sidx');
        $sord = $this->input->get_post('sort');
        $filter = $this->input->get_post('filter');//查询条件
        $order = $this->input->get_post('order');//排序
        $this->load->model('ckm/ckm_retreat');
        $grid = $this->ckm_retreat->grid($select, $filter, $page, $page_size, $sidx, $sord, $order);
        $this->returnData($grid);
    }

    /**
     * 退单编辑
     */
    public function edit_retreat_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
            'creata_at' => ['未配送时间', 'required', 'integer'],
            'status' => ['配送状态'],
            'reason' => ['具体原因'],
            'remark' => ['备注'],
        ], [], 'post');
        $this->load->model('ckm/ckm_retreat');
        $info = $this->ckm_retreat->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $request_data['num'] = $info['num'] + 1;
        $request_data['status'] = 1;
        $request_data['auth_status'] = 0;
        $id = $this->ckm_retreat->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('提交失败');
        }
        $this->returnData();
    }

    /**
     * 退单审批
     */
    public function sh_retreat_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键ID', 'required', 'integer'],
            'auth_status' => ['主键ID', 'required', 'integer']//审批状态 1-未通过 2-已审核
        ], [], 'post');
        $this->load->model('ckm/ckm_retreat');
        $info = $this->ckm_retreat->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $this->db->trans_start();
        if ($request_data['auth_status'] == 1) {
            $request_data['status'] = 2;
            $id = $this->ckm_retreat->edit(['id' => $request_data['id']], $request_data);
        } else {
            $this->load->model('jzm/jzm_service_info');
            $this->jzm_service_info->edit(['id' => $info['service_id']], ['status' => 2]);
            $id = $this->ckm_retreat->edit(['id' => $request_data['id']], $request_data);
            /*
             * 审批通过
             * 1.查询送单列表
             * 2.查询出库记录
             * 3.将出库未送单的入库
             */
            $this->load->model('ckm/ckm_in_warehouse');
            $this->load->model('ckm/ckm_out_warehouse');
            $this->load->model('jzm/jzm_acquiring_details');
            //1.查询送单列表
            $list = $this->jzm_acquiring_details->f7('*', ['service_id' => $info['service_id']]);
            //2.查询出库记录
            $out_list = $this->ckm_out_warehouse->get_all('*', [
                'customer_id' => $aData['service_info']['htm_contract.customer_id'],
                'month' => $aData['service_info']['jzm_service_info.time'],
                'status' => 2
            ]);
            //计算是否出库
            $in_liat = [];
            $send_out_ids = [];
            foreach ($list as $item) {
                $send_out_ids[] = $item['jzm_acquiring_details.ck_id'];
            }
            foreach ($out_list as $val) {
                if (in_array($val['id'], $send_out_ids)) {
                    foreach ($list as $item) {
                        if ($item['jzm_acquiring_details.ck_id'] == $val['id']) {
                            $in_liat[] = [
                                'out_id' => $val['id'],
                                'num' => $val['number'] - $item['jzm_acquiring_details.num'],
                                'info' => $val
                            ];
                        }
                    }
                } else {
                    $in_liat[] = [
                        'out_id' => $val['id'],
                        'num' => $val['number'],
                        'info' => $val
                    ];
                }
            }
            foreach ($in_liat as $item) {
                //保存入库记录
                if ($item['num'] > 0) {
                    $saveData = [
                        'num' => $this->get_num('RK'),
                        'type' => $item['info']['type'],
                        'customer_id' => $aData['service_info']['htm_contract.customer_id'],
                        'number' => $item['num'],
                        'goods' => $item['info']['goods'],
                        'month' => $aData['service_info']['jzm_service_info.time'],
                        'time' => time(),
                        'jb_id' => $this->get_login_info()['id'],
                        'create_id' => $info['submit_employee_id'],
                        'create_time' => $info['submit_time'],
                        'qr_id' => $info['submit_employee_id'],
                        'qr_time' => time(),
                        'status' => 2
                    ];
                    $this->ckm_in_warehouse->add($saveData);
                }
            }
        }
        $this->db->trans_complete();
        if (!$id) {
            $this->returnError('提交失败');
        }
        $this->returnData();
    }

    /**
     * 盘点列表
     */
    public function inventory_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
        ], [], 'post');
        $this->load->model('ckm/ckm_inventory');
        $grid = $this->ckm_inventory->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $items = $grid['items'];
        $this->load->model('ckm/ckm_inventory_info');
        foreach ($items as $i => $item) {
            $f7 = $this->ckm_inventory_info->get_all('*', ['inventory' => $item['ckm_inventory.id']]);
            $items[$i]['list'] = $f7;
        }
        $grid['items'] = $items;
        $this->returnData($grid);
    }

    /**
     * 新增盘点
     */
    public function add_inventory_post()
    {
        $request_data = $this->check_param([
            'type' => ['仓库名称', 'required', 'integer'],//1-凭证 2-单据 3-证件
            'customer_id' => ['客户ID', 'required', 'integer'],
            'pd_time' => ['盘点时间', 'required', 'integer'],
            'pd_goods' => ['盘点物品', 'required'],//多个物品用逗号分割
            'pd_num' => ['盘点数量', 'required', 'integer'],
            'kc_goods' => ['库存物品', 'required'],//多个物品用逗号分割
            'kc_num' => ['库存数量', 'required', 'integer'],
            'pd_kc' => ['盘点差额', 'required', 'integer'],
            'remark' => ['备注'],
            'pd_infos' => ['盘点明细', 'required'],//[{"goods":"凭证","kc_num":123,"pa_num":122,"pd_kc":-1},{}...]
        ], [], 'post');
        $this->load->model('ckm/ckm_position_num');
        $info = $this->ckm_position_num->get_one('*', ['id' => $request_data['customer_id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $pd_infos = json_decode($request_data['pd_infos'], true);
        unset($request_data['pd_infos']);
        $loginData = $this->get_login_info();
        $request_data['num'] = $this->get_num('PD');
        $request_data['create_id'] = $loginData['id'];
        $request_data['create_at'] = time();
        $this->load->model('ckm/ckm_inventory');
        $this->db->trans_start();
        $id = $this->ckm_inventory->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->load->model('ckm/ckm_inventory_info');
        foreach ($pd_infos as $item) {
            $item['inventory'] = $id;
            $this->ckm_inventory_info->add($item);
        }
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 客户仓位列表
     */
    public function get_cus_post()
    {
        $this->load->model('ckm/ckm_position_num');
        $f7 = $this->ckm_position_num->f7('*', ['customer_id !=' => 0]);
        $this->returnData($f7);
    }

    /**
     * 根据仓位客户ID获取数量和物品
     */
    public function get_num_info_post()
    {
        $request_data = $this->check_param([
            'type' => ['仓库名称', 'required', 'integer'],//1-凭证 2-单据 3-证件
            'customer_id' => ['客户ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('ckm/ckm_in_warehouse');
        $this->load->model('ckm/ckm_out_warehouse');
        $goods = $this->ckm_in_warehouse->f7('ckm_in_warehouse.goods', ['ckm_in_warehouse.customer_id' => $request_data['customer_id'], 'ckm_in_warehouse.type' => $request_data['type']]);
        if (!$goods) {
            $this->returnError('未查询到信息');
        }
        $data = [];
        foreach ($goods as $good) {
            $in_count = $this->ckm_in_warehouse->get_one('sum(number) as number', ['goods' => $good['goods']]);
            $out_count = $this->ckm_out_warehouse->get_one('sum(number) as number', ['goods' => $good['goods']]);
            $count = $in_count['number'] - $out_count['number'];
            $data[] = ['goods' => $good['goods'], 'count' => $count];
        }
        $this->returnData($data);
    }

    /**
     * 盘点审核
     */
    public function sh_inventory_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'required', 'integer'],
            'status' => ['审核状态', 'required', 'integer'],//0-未审核 1-未通过 2-已审核
        ], [], 'post');
        $this->load->model('ckm/ckm_inventory');
        $info = $this->ckm_inventory->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $loginData = $this->get_login_info();
        $request_data['auth_id'] = $loginData['id'];
        $request_data['auth_time'] = time();
        $this->ckm_inventory->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 批量盘点审核
     */
    public function batch_sh_inventory_post()
    {
        $request_data = $this->check_param([
            'data' => ['数据', 'required'],//格式[{'id':'1','status':'2'},{}]
        ], [], 'post');
        $this->load->model('ckm/ckm_inventory');
        $batch = json_decode($request_data['data'], true);
        if (!$batch) {
            $this->returnError('数据格式错误，请检查！');
        }
        foreach ($batch as $item) {
            $info = $this->ckm_inventory->get_one('*', ['id' => $item['id']]);
            if (!$info) {
                $this->returnError('未查询到信息');
            }
            $loginData = $this->get_login_info();
            $item['auth_id'] = $loginData['id'];
            $item['auth_time'] = time();
            $this->ckm_inventory->edit(['id' => $item['id']], $item);
        }
        $this->returnData();
    }

    /**
     * 系统设置
     * 新增仓库物品
     */
    public function add_goods_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],//所属仓库 1-凭证 2-单据 3-证件
            'name' => ['物品名称', 'required'],
            'unit' => ['单位', 'required'],
        ], [], 'post');
        $this->load->model('ckm/ckm_warehouse_goods');
        $info = $this->ckm_warehouse_goods->get_one('*', ['name' => $request_data['name'], 'type' => $request_data['type']]);
        if ($info) {
            $this->returnError('物品名称已使用');
        }
        $request_data['create_at'] = time();
        $id = $this->ckm_warehouse_goods->add($request_data);
        $info = $this->ckm_warehouse_goods->get_one('id', ['id' => $id]);
        switch ($request_data['type']) {
            case 1:
                $pre = 'ZJ';
                break;
            case 2:
                $pre = 'PZ';
                break;
            case 3:
                $pre = 'PJ';
                break;
        }
        $num = $pre . $info['id'];
        $this->ckm_warehouse_goods->edit(['id' => $id], ['num' => $num]);
        $this->returnData();
    }

    /**
     * 系统设置
     * 仓库物品编辑
     */
    public function edit_goods_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'type' => ['类型', 'required', 'integer'],//所属仓库 1-凭证 2-单据 3-证件
            'name' => ['物品名称', 'required'],
            'unit' => ['单位', 'required'],
        ], [], 'post');
        $this->load->model('ckm/ckm_warehouse_goods');
        $ii = $this->ckm_warehouse_goods->get_one('*', ['name' => $request_data['name'], 'type' => $request_data['type'], 'id !=' => $request_data['id']]);
        if ($ii) {
            $this->returnError('物品名称已使用');
        }
        $info = $this->ckm_warehouse_goods->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        switch ($request_data['type']) {
            case 1:
                $pre = 'ZJ';
                break;
            case 2:
                $pre = 'PZ';
                break;
            case 3:
                $pre = 'PJ';
                break;
        }
        $request_data['num'] = $pre . $info['id'];
        $this->ckm_warehouse_goods->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 系统设置
     * 仓库物品详情
     */
    public function info_goods_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('ckm/ckm_warehouse_goods');
        $info = $this->ckm_warehouse_goods->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $this->returnData($info);
    }

    /**
     *  仓库提交盘点审批
     */
    public function edit_warehouse_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],//1-凭证 2-单据 3-证件
            'pd' => ['盘点人', 'required'],
            'sp' => ['审批人', 'required']
        ], [], 'post');
        $this->load->model('ckm/ckm_warehouse');
        $id = $this->ckm_warehouse->edit(['type' => $request_data['type']], $request_data);
        if (!$id) {
            $this->returnError('编辑失败');
        }
        $this->returnData();
    }

    /**
     * 系统设置
     * 仓库物品列表
     */
    public function list_goods_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'integer'],//不传参数时查询所有的列表。 所属仓库 1-凭证 2-单据 3-证件
        ], [], 'post');
        $this->load->model('ckm/ckm_warehouse');
        $this->load->model('ckm/ckm_warehouse_goods');
        $l1 = [
            'info' => $this->ckm_warehouse->get_one('*', ['type' => 1]),
            'list' => $this->ckm_warehouse_goods->f7('*', ['type' => 1])
        ];
        $l2 = [
            'info' => $this->ckm_warehouse->get_one('*', ['type' => 2]),
            'list' => $this->ckm_warehouse_goods->f7('*', ['type' => 2])
        ];
        $l3 = [
            'info' => $this->ckm_warehouse->get_one('*', ['type' => 3]),
            'list' => $this->ckm_warehouse_goods->f7('*', ['type' => 3])
        ];
        switch ($request_data['type']) {
            case 1:
                $data = $l1;
                break;
            case 2:
                $data = $l2;
                break;
            case 3:
                $data = $l3;
                break;
            default:
                $data = [
                    'PZ' => $l1,
                    'PJ' => $l2,
                    'ZJ' => $l3,
                ];

        }
        $this->returnData($data);
    }

    /**
     * 仓位添加
     */
    public function add_edit_position_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'integer'],
            'name' => ['仓位名称', 'required'],
            'province' => ['省', 'required'],
            'city' => ['市', 'required'],
            'sd' => ['收单负责人', 'required'],//多选 参数格式1,2,3
            'zd' => ['整单负责人', 'required'],//多选 参数格式1,2,3
            'zz' => ['做账负责人', 'required'],//多选 参数格式1,2,3
            'bs' => ['报税负责人', 'required'],//多选 参数格式1,2,3
            'kf' => ['客服负责人', 'required'],//多选 参数格式1,2,3
            'sod' => ['送单负责人', 'required'],//多选 参数格式1,2,3
        ], [], 'post');
        $this->load->model('ckm/ckm_position');
        $this->load->model('ckm/ckm_position_num');
        $this->db->trans_start();
        if (empty($request_data['id'])) {
            $request_data['create_at'] = time();
            $id = $this->ckm_position->add($request_data);
            for ($i = 1; $i <= 999; $i++) {
                $this->ckm_position_num->add(['pos_id' => $id, 'pos_name' => $request_data['name'], 'pos_num' => $i]);
            }
        } else {
            $info = $this->ckm_position->get_one('*', ['id' => $request_data['id']]);
            if (!$info) {
                $this->returnError('未查询到信息');
            }
            $this->ckm_position->edit(['id' => $request_data['id']], $request_data);
            $this->ckm_position_num->edit(['pos_id' => $request_data['id']], ['pos_name' => $request_data['name']]);
        }
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     * 根据省市获取仓位
     */
    public function get_position_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],//1-省  2-市
            'name' => ['省市名称', 'required'],
        ], [], 'post');
        if ($request_data['type'] == 1) {
            $where = "province like '%{$request_data['name']}%'";
        } else {
            $where = "city like '%{$request_data['name']}%'";
        }
        $cid = $this->get_login_info()['cid'];
        $where .= " and cid = {$cid}";
        $this->load->model('ckm/ckm_position');
        $f7 = $this->ckm_position->get_all('id,name,province,city', $where);
        $this->returnData($f7);
    }

    /**
     * 仓位列表
     */
    public function list_position_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
        ], [], 'post');
        $this->load->model('ckm/ckm_position');
        $grid = $this->ckm_position->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $items = $grid['items'];
        $this->load->model('ckm/ckm_position_num');
        foreach ($items as $i => $item) {
            $f7 = $this->ckm_position_num->get_one('count(id) as total', ['pos_id' => $item['ckm_position.id'], 'customer_id !=' => 0]);
            $items[$i]['number'] = $f7['total'];
        }
        $grid['items'] = $items;
        $this->returnData($grid);
    }


    /**
     * 行政添加
     */
    public function add_administration_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'integer'],
            'type' => ['区域级别', 'required', 'integer'],
            'name' => ['区域名称', 'required'],
            'state_tax' => ['国税网站', 'required'],
            'local_tax' => ['地税网站', 'required'],
            'gs_web' => ['工商网站', 'required'],
        ], [], 'post');
        $this->load->model('ckm/ckm_administration');
        if (empty($request_data['id'])) {
            $info = $this->ckm_administration->get_one('*', ['name' => $request_data['name']]);
            if ($info) {
                $this->returnError('请勿重复添加');
            }
            $id = $this->ckm_administration->add($request_data);
        } else {
            $info = $this->ckm_administration->get_one('*', ['id' => $request_data['id']]);
            if (!$info) {
                $this->returnError('未查询到信息');
            }
            $id = $this->ckm_administration->edit(['id' => $request_data['id']], $request_data);
        }
        if (!$id) {
            $this->returnError('提交失败');
        }
        $this->returnData();
    }

    /**
     * 行政删除
     */
    public function del_administration_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('ckm/ckm_administration');
        $info = $this->ckm_administration->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $this->ckm_administration->edit(['id' => $request_data['id']], ['is_del' => 1]);
        $this->returnData();
    }

    /**
     * 行政列表
     */
    public function list_administration_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
        ], [], 'post');
        $this->load->model('ckm/ckm_administration');
        $grid = $this->ckm_administration->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $items = $grid['items'];
        $this->load->model('ckm/ckm_position');
        $this->load->model('ckm/ckm_position_num');
        foreach ($items as $i => $item) {
            if ($item['ckm_administration.type'] == 1) {
                $ids = $this->ckm_position->get_all('id', ['province' => $item['ckm_administration.name']]);
            } else {
                $ids = $this->ckm_position->get_all('id', ['city' => $item['ckm_administration.name']]);
            }
            $ck_num = count($ids);
            $items[$i]['ck_num'] = $ck_num;
            $id_li = [];
            foreach ($ids as $id) {
                $id_li[] = $id['id'];
            }
            $ids_li_s = implode(',', $id_li);
            if ($ids_li_s) {
                $f7 = $this->ckm_position_num->get_one('count(id) as total', "pos_id in ({$ids_li_s}) and customer_id != 0");
            }
            $items[$i]['kh_num'] = $f7['total'];
        }
        $grid['items'] = $items;
        $this->returnData($grid);
    }
}
