<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class Api_Finance 财务管理
 */
class Api_finance extends Apibase
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     *  欠款列表
     */
    public function qklist_post()
    {
        $request_data = $this->check_param([
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'month1' => ['查询月份-头'], //格式 201701 不传默认查询今年1月份
            'month2' => ['查询月份-尾'], //格式 201701 不传默认查询今年12月份
            'name' => ['客户名称'],
            'where' => ['自定义查询'], //高级搜索  xxx字段=1 and xxx字段=2 or xxx字段=3
        ], [], 'get_post');
        $filter = "htm_contract.status = 1 ";
        $year = date('Y', time());
        if (empty($request_data['month1'])) {
            $request_data['month1'] = $year . '01';
        }
        if (empty($request_data['month2'])) {
            $request_data['month2'] = $year . '12';
        }
        $filter .= "and jzm_service_info.time >= {$request_data['month1']} and jzm_service_info.time <= {$request_data['month2']} ";
        if (!empty($request_data['name'])) {
            $filter .= "and (khm_customer.name like '%{$request_data['name']}%' or khm_customer.id like '%{$request_data['name']}%')";
        }
        if (!empty($request_data['where'])) {
            $filter .= ' and ' . $request_data['where'];
        }
        /**
         * 验证成功后的逻辑
         */
        $select = '';
        $this->load->model('jzm/jzm_service_info');
        $this->load->model('ckm/ckm_position_num');
        $data = $this->jzm_service_info->grid("jzm_service_info.contract_id", $filter, $request_data['page'], $request_data['page_size'], 'time desc', TRUE);
        $list = $data['items'];
        if ($list) {
            foreach ($list as $i => $v) {
                $this->load->model('htm/htm_contract');
                $this->load->model('htm/htm_task');
                $info = $this->htm_contract->info('*', "htm_contract.id = {$v['contract_id']}");
                $taxInfo = $this->htm_task->info('*', "htm_task.contract_id = {$v['contract_id']}");
                $ser_type = $this->jzm_service_info->get_one('type', ['contract_id' => $v['contract_id']]);
                $where = "jzm_service_info.time >= {$request_data['month1']} and jzm_service_info.time <= {$request_data['month2']} and jzm_service_info.contract_id = {$v['contract_id']} and type = {$ser_type['type']}";
                $li = $this->jzm_service_info->get_all("*", $where, 'time asc');
                $num = 0;
                $money = 0;
                if ($info['htm_contract.total_monry'] > $info['htm_contract.get_money']) {
                    $this_month = date('Ym', time());
                    $get_num = 0;
                    foreach ($li as $ii => $vv) {
                        if ($vv['time'] < $this_month) {
                            if ($vv['get_money'] == 0) {
                                $num += 1;
                            } else {
                                $get_num += 1;
                            }

                        }
                    }
                    $money = ($num + $get_num) * $taxInfo['htm_task.discount'] + $info['htm_contract.account_book'] - $info['htm_contract.get_money'];
                }
                $list[$i]['overdue'] = $num;
                $list[$i]['yq_money'] = $money;
                $list[$i]['info'] = $taxInfo;
                $list[$i]['list'] = $li;
                $list[$i]['position'] = $this->ckm_position_num->get_one('*', ['customer_id' => $info['htm_contract.customer_id']]);
            }
        }
        $data['items'] = $list;
        $this->returnData($data);
    }

    /**
     *  其他退款列表
     */
    public function qk_other_list_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'page_size' => ['每页显示多少条', 'required'],
            'page' => ['第几页'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
            'market' => ['营销员']
        ], [], 'post');
        $this->load->model('htm/htm_contract');
        $cid = $this->loginData['cid'];
        $filter = "htm_contract.cid = {$cid} and htm_contract.total_monry > htm_contract.get_money and htm_contract.contract_type = 2";
        $this->load->model('bmm/bmm_employees');
        if (!empty($request_data['market'])) {
            $all_ids = $this->bmm_employees->getEmps($request_data['market'], $cid);
            if (!empty($all_ids)) {
                $this->load->model('htm/htm_contract_marketing');
                $con_ids = $this->htm_contract_marketing->getContractId($all_ids);
                if (!empty($con_ids)) {
                    $conId = implode(',', $con_ids);
                    $filter .= " and htm_contract.id in ({$conId})";
                }
            }
        }
        if (!empty($request_data['filter'])) {
            $filter .= " and {$request_data['filter']}";
        }
        $grid = $this->htm_contract->grid($request_data['select'], $filter, $request_data['page'], $request_data['page_size'], '', '', $request_data['order']);
        //获取员工信息
        $emploInfo = $this->bmm_employees->getEmployees($cid);
        //获取文件信息
        $items = $grid['items'];
        foreach ($items as $i => $item) {
            $items[$i] = $this->contractChange($item, $emploInfo);
        }
        $grid['items'] = $items;
        $this->returnData($grid);
    }

    /**
     * @param $item
     * @param $emploInfo
     * @return mixed 补充合同信息
     */
    private function contractChange($item, $emploInfo)
    {
        $this->load->model('ckm/ckm_product');
        $this->load->model('ckm/ckm_position_num');
        $this->load->model('htm/htm_contract_file');
        $this->load->model('htm/htm_contract_marketing');
        $this->load->model('htm/htm_task');
        $this->load->model('htm/htm_task_staff');
        if ($item['htm_contract.hang_time'] > 0) {
            $da = (time() - $item['htm_contract.hang_time']) / (24 * 60 * 60);
            $item['htm_contract.hang'] = $item['htm_contract.hang'] + ceil($da);
        }
        $item['HT_create_info'] = $emploInfo[1][$item['htm_contract.create_by']];
        $item = $this->htm_contract_file->getFile($item);
        $market = $this->htm_contract_marketing->getMarket($item['htm_contract.id']);
        foreach ($market as $val) {
            $_market[] = ['employees_id' => $val, 'info' => $emploInfo[1][$val]];
        }
        $item['market'] = $_market;
        $taskList = $this->htm_task->getTask($item['htm_contract.id']);
        foreach ($taskList as $k => $v) {
            $ii = $this->htm_task_staff->get_all('*', ['task_id' => $v['id']]);
            foreach ($ii as $kk => $vv) {
                $ii[$kk]['user'] = !empty($emploInfo[1][$vv['staff_id']]) ? $emploInfo[1][$vv['staff_id']] : '';
            }
            $productInfo = $this->ckm_product->get_one('id,name', ['id' => $v['product_id']]);
            $taskList[$k]['productName'] = $productInfo['name'];
            $taskList[$k]['staff'] = $ii;
        }
        $item['task'] = $taskList;
        $item['position'] = $this->ckm_position_num->get_one('*', ['customer_id' => $item['htm_contract.customer_id']]);
        return $item;
    }

    /**
     * 收款添加
     */
    public function receivables_add_post()
    {
        $request_data = $this->check_param([
            'contract_id' => ['合同ID', 'required', 'integer'],
            'get_to_term' => ['收款期限', 'required'],
            'cound_money' => ['应收金额', 'required', 'numeric'],
            'discount_money' => ['优惠金额', 'numeric'],
            'get_money' => ['收款金额', 'required', 'numeric'],
            'get_time' => ['收款日期', 'required', 'integer'],
            'get_way' => ['收款方式', 'required', 'integer'], //1-转账 2-现金
            'remark' => ['备注'],
            'jb_id' => ['经办人id', 'integer'],
            'account' => ['收款账户'],
            'receiver' => ['收款人'],
        ], [], 'post');
        $this->load->model('htm/htm_contract');
        $info = $this->htm_contract->get_one('*', ['id' => $request_data['contract_id']]);
        if (!$info) {
            $this->returnError('未查到信息');
        }
//        $monarr = array();
//        $time1 = $request_data['month1'];
//        $time2 = $request_data['month2'];
//        $monarr[] = date('Ym', $time1); // 当前月;
//        while (($time1 = strtotime('+1 month', $time1)) <= $time2) {
//            $monarr[] = date('Ym', $time1); // 取得递增月;
//        }
//        $monarr[] = date('Ym', $time2);
//        $monarr = array_unique($monarr);
//        $request_data['get_to_term'] = json_encode($monarr);
//        unset($request_data['month1']);
//        unset($request_data['month2']);
        $request_data['customer_id'] = $info['customer_id'];
        $request_data['num'] = $this->get_num('SK');
        $request_data['order_id'] = $info['contract_code'];
        $request_data['create_at'] = time();
        $request_data['create'] = $this->get_login_info()['id'];
        $this->load->model('cwm/cwm_receivables');
        $id = $this->cwm_receivables->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData($id);
    }

    /**
     * 收款审批
     */
    public function receivables_sh_post()
    {
        $request_data = $this->check_param([
            'id' => ['收款ID', 'required', 'integer'],
            'status' => ['状态', 'required', 'integer'],//状态1-审核中 2- 通过 3-未通过
            'confirm_remark' => ['审批描述', 'max_length[1000]'],
        ], [], 'post');
        $this->load->model('cwm/cwm_receivables');
        $info = $this->cwm_receivables->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $this->load->model('htm/htm_contract');
        $htInfo = $this->htm_contract->get_one('*', ['id' => $info['contract_id']]);
        if ($request_data['status'] == 2) {
            if ($htInfo['contract_type'] == 1) {
                $months = implode(',', json_decode($info['get_to_term'], true));
                if ($months) {
                    $this->load->model('jzm/jzm_service_info');
                    $this->jzm_service_info->edit("contract_id = {$info['contract_id']} and time in ({$months})", ['get_money' => $request_data['id']]);
                }
            }
            $htSave = [
                'amount_beceived' => $htInfo['amount_beceived'] + $info['get_money'],
                'outstanding_amount' => $info['cound_money'] - $info['get_money'] - $info['discount_money']
            ];
            $this->htm_contract->edit(['id' => $info['contract_id']], $htSave);
        }
        $request_data['confirm'] = $this->get_login_info()['id'];
        $request_data['confirm_at'] = time();
        $request_data['cid'] = $this->get_login_info()['cid'];
        $info = $this->cwm_receivables->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 收款批量审批
     */
    public function all_recei_sh_post()
    {
        $request_data = $this->check_param([
            'coldata' => ['数据源', 'required']//[{"id":"111","status":"123"},{"id":"111","status":"123","confirm_remark":"dsadasdsa"}...]
        ], [], 'post');
        $data = json_decode($request_data['coldata'], true);
        if (empty($data)) {
            $this->returnError('数据格式错误，不是Json格式');
        }
        $this->load->model('cwm/cwm_receivables');
        $this->load->model('htm/htm_contract');
        $this->load->model('jzm/jzm_service_info');
        $error = [];
        $user_id = $this->get_login_info()['id'];
        $cid = $this->get_login_info()['cid'];
        foreach ($data as $item) {
            if (empty($item['id']) || !in_array($item['status'], [0, 1, 2, 3])) {
                continue;
            }
            $info = $this->cwm_receivables->get_one('*', ['id' => $item['id']]);
            if (!$info) {
                $error[] = $item['id'];
                continue;
            }
            $htInfo = $this->htm_contract->get_one('*', ['id' => $info['contract_id']]);
            if ($item['status'] == 2) {
                if ($htInfo['contract_type'] == 1) {
                    $months = implode(',', json_decode($info['get_to_term'], true));
                    if ($months) {
                        $this->jzm_service_info->edit("contract_id = {$info['contract_id']} and time in ({$months})", ['get_money' => $item['id']]);
                    }
                }
                $htSave = [
                    'amount_beceived' => $htInfo['amount_beceived'] + $info['get_money'],
                    'outstanding_amount' => $info['cound_money'] - $info['get_money'] - $info['discount_money']
                ];
                $this->htm_contract->edit(['id' => $info['contract_id']], $htSave);
            }
            $item['confirm'] = $user_id;
            $item['confirm_at'] = time();
            $item['cid'] = $cid;
            $info = $this->cwm_receivables->edit(['id' => $item['id']], $item);
        }
        $this->returnData(['error_id' => $error]);
    }


    /**
     * 收款列表
     */
    public function receivables_list_post()
    {
        $select = $this->input->get_post('select'); //查询字段
        $page_size = $this->input->get_post('limit'); //每页显示多少条
        $page = $this->input->get_post('page'); //第几页
        $sidx = $this->input->get_post('sidx');
        $sord = $this->input->get_post('sort');
        $filter = $this->input->get_post('filter'); //查询条件
        $order = $this->input->get_post('order'); //排序
        $this->load->model('cwm/cwm_receivables');
        $grid = $this->cwm_receivables->grid($select, $filter, $page, $page_size, $sidx, $sord, $order);
        $this->returnData($grid);
    }

    /**
     * 绩效分类列表
     */
    public function get_cate_get()
    {
        $request_data = $this->check_param([
            'stype' => ['类型', 'integer'], //1-记账报税 2-短期业务
        ], [], 'get');
        if (!$request_data['stype']) {
            $request_data['stype'] = 1;
        }
        $this->load->model('cwm/cwm_achievements_cate');
        $list = $this->cwm_achievements_cate->get_all('*', ['stype' => $request_data['stype'], 'cid' => $this->loginData['cid']]);
        $this->returnData($list);
    }

    /**
     * 绩效分类编辑
     */
    public function edit_cate_post()
    {
        $request_data = $this->check_param([
            'update' => ['编辑数据', 'required'],//"[[1,200],[2,100]]"  1-一般纳税人 2-小规模
        ], [], 'post');
        $request_data['update'] = json_decode($request_data['update'], true);
        $this->load->model('cwm/cwm_achievements_cate');
        foreach ($request_data['update'] as $i => $v) {
            $this->cwm_achievements_cate->edit(['id' => $v[0]], ['money' => $v[1]]);
        }
        $this->returnData();
    }

    /**
     * 员工绩效
     */
    public function achievements_list_post()
    {
        $request_data = $this->check_param([
            'stype' => ['查询类型', 'required', 'integer'],//1-记账报税 2-短期业务
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'name' => ['员工姓名'],
            'department' => ['部门ID', 'integer'],//下拉选择部门
            'time' => ['查询月份'],//201710
        ], [], 'post');
        //获取查询类型
        $cid = $this->loginData['cid'];
        $this->load->model('cwm/cwm_achievements_cate');
        $cate_ids = $this->cwm_achievements_cate->get_all('id', ['stype' => $request_data['stype'], 'cid' => $cid]);
        foreach ($cate_ids as $item) {
            $cate_id[] = $item['id'];
        }
        $cate_id = implode(',', $cate_id);
        //获取部门下所有的员工
        if ($request_data['department']) {
            $this->load->model('bmm/bmm_employees');
            $emp_ids = $this->bmm_employees->get_all('id', ['department' => $request_data['department']]);
            if (empty($emp_ids)) {
                $this->returnData2();
            }
            foreach ($emp_ids as $item) {
                $emp_id[] = $item['id'];
            }
            $emp_id = implode(',', $emp_id);
        }
        //搜索用户名
        if ($request_data['name']) {
            $name_filter = "name like '%{$request_data['name']}%'";
            if (!empty($emp_id)) {
                $name_filter = $name_filter . " and department in ({$emp_id})";
            }
            $emp_id = [];
            $this->load->model('bmm/bmm_employees');
            $emp_ids = $this->bmm_employees->get_all('id', $name_filter);
            if (empty($emp_ids)) {
                $this->returnData2();
            }
            foreach ($emp_ids as $item) {
                $emp_id[] = $item['id'];
            }
            $emp_id = implode(',', $emp_id);
        }
        if ($request_data['time']) {
            $sel_time = strtotime($request_data['time'] . '01');
            $BeginDate = date('Y-m-01', $sel_time);
            $EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day")) . ' 23:59:59';
            $begin = strtotime($BeginDate);
            $end = strtotime($EndDate);
        } else {
            $BeginDate = date('Y-m-01', strtotime(date("Y-m-d")));
            $EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day")) . ' 23:59:59';
            $begin = strtotime($BeginDate);
            $end = strtotime($EndDate);
            $request_data['time'] = date('Ym');
        }
        $time_where = "cwm_achievements_info.complete_time <={$end} and cwm_achievements_info.complete_time >={$begin} and cwm_achievements_info.cate_id in ({$cate_id})";
        $ach_where = $time_where;
        if ($emp_id) {
            $ach_where = $time_where . " and cwm_achievements_info.complete_id in ({$emp_id})";
        }
        $this->load->model('cwm/cwm_achievements_info');
        $grid = $this->cwm_achievements_info->grid('cwm_achievements_info.complete_id', $ach_where, $request_data['page'], $request_data['limit'], '', '', 'cwm_achievements_info.complete_id desc');
        $items = $grid['items'];
        if ($items) {
            foreach ($items as $i => $v) {
                $this->load->model('bmm/bmm_employees');
                $info = $this->bmm_employees->get_one('id,name', ['id' => $v['complete_id']]);
                $items[$i]['userinfo'] = $info;
                $where3 = $time_where . " and complete_id = {$v['complete_id']}";
                $list = $this->cwm_achievements_info->get_all('count(id) as count,cate_id', $where3, '', 'cate_id');
                foreach ($list as $j => $jtem) {
                    $list[$j]['cateInfo'] = $this->cwm_achievements_cate->get_one('id,name,type,money', ['id' => $jtem['cate_id']]);
                }
                $items[$i]['list'] = $list;
                $this->load->model('cwm/cwm_wages');
                $is_sta = $this->cwm_wages->get_one('standard_1,standard_2', ['employee_id' => $v['complete_id'], 'month' => $request_data['time']]);
                $items[$i]['is_sta'] = $is_sta;
            }
        }
        $grid['items'] = $items;
        $grid['time'] = $request_data['time'];
        $this->returnData($grid);
    }

    /**
     * 绩效复核
     */
    public function ac_status_post()
    {
        $request_data = $this->check_param([
            'stype' => ['查询类型', 'required', 'integer'],//1-记账报税 2-短期业务
            'complete_id' => ['员工ID', 'required', 'integer'],
            'total' => ['总额', 'required', 'numeric'],
            'month' => ['查询月份', 'required', 'integer'],//201710
        ], [], 'post');
        if ($request_data['stype'] == 1) {
            $request_data['standard_1'] = $request_data['total'];
        } else {
            $request_data['standard_2'] = $request_data['total'];
        }
        unset($request_data['stype']);
        unset($request_data['total']);
        $this->load->model('cwm/cwm_wages');
        $info = $this->cwm_wages->get_one('*', ['employee_id' => $request_data['complete_id'], 'month' => $request_data['month']]);
        $request_data['employee_id'] = $request_data['complete_id'];
        unset($request_data['complete_id']);
        if ($info) {
            $id = $this->cwm_wages->edit(['id' => $info['id']], $request_data);
        } else {
            $id = $this->cwm_wages->add($request_data);
        }
        if (!$id) {
            $this->returnError('复核失败');
        }
        $this->returnData();
    }

    /**
     * 绩效记账报税详情
     */
    public function ac_info_post()
    {
        $request_data = $this->check_param([
            'complete_id' => ['员工ID', 'required', 'integer'],
            'month' => ['查询月份', 'required', 'integer'],//201710
        ], [], 'post');
        if ($request_data['month']) {
            $sel_time = strtotime($request_data['month'] . '01');
            $BeginDate = date('Y-m-01', $sel_time);
            $EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day")) . ' 23:59:59';
            $begin = strtotime($BeginDate);
            $end = strtotime($EndDate);
        }
        $time_where = "cwm_achievements_info.complete_time <={$end} and cwm_achievements_info.complete_time >={$begin} and cwm_achievements_info.complete_id = {$request_data['complete_id']}";
        $this->load->model('cwm/cwm_achievements_info');
        $ac_datas = $this->cwm_achievements_info->get_all('*', $time_where);
        $this->load->model('jzm/jzm_service_info');
        $select = 'khm_customer.name,jzm_service_info.time,jzm_service_info.get_money,jzm_service_info.type';
        foreach ($ac_datas as $i => $item) {
            $serviceInfo = $this->jzm_service_info->info($select, ['jzm_service_info.id' => $item['service_id']]);
            $ac_datas[$i]['serviceInfo'] = $serviceInfo;
            if (in_array($serviceInfo['type'], [1, 2, 3, 4, 5, 6])) {
                $list[$serviceInfo['type']][] = $ac_datas[$i];
            }
        }
        $this->returnData($list);
    }

    /**
     * 支出新增
     */
    public function expenditure_add_post()
    {
        $request_data = $this->check_param([
            'customer_id' => ['客户id', 'required', 'integer'],
            'status' => ['状态', 'required', 'integer'],
            'give_time' => ['支出日期', 'required', 'integer'],
            'give_money' => ['支出金额', 'required', 'numeric'],
            'gice_way' => ['支出方式', 'required', 'integer'],
            'duty_id' => ['责任人id', 'required', 'integer'],
            'duty_name' => ['责任人', 'required'],
            'accounts' => ['支付帐号', 'required'],
            'remark' => ['备注'],
        ], [], 'post');
        $request_data['create_id'] = $this->get_login_info()['id'];
        $request_data['create'] = $this->get_login_info()['name'];
        $request_data['create_at'] = time();
        $request_data['order_id'] = $this->get_order_num();
        $this->load->model('cwm/cwm_expenditure');
        $id = $this->cwm_expenditure->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData();
    }

    /**
     * 支出编辑
     */
    public function expenditure_change_post()
    {
        $request_data = $this->check_param([
            'id' => ['id', 'required', 'integer'],
            'customer_id' => ['客户id', 'required', 'integer'],
            'status' => ['状态', 'required', 'integer'],
            'give_time' => ['支出日期', 'required', 'integer'],
            'give_money' => ['支出金额', 'required', 'numeric'],
            'gice_way' => ['支出方式', 'required', 'integer'],
            'duty_id' => ['责任人id', 'required', 'integer'],
            'accounts' => ['支付帐号', 'required'],
            'remark' => ['备注'],
        ], [], 'post');
        $this->load->model('cwm/cwm_expenditure');
        $info = $this->cwm_expenditure->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $id = $this->cwm_expenditure->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('审核失败');
        }
        $this->returnData();
    }

    /**
     * 支出列表
     */
    public function expenditure_list_post()
    {
        $page_size = $this->input->get_post('limit'); //每页显示多少条
        $page = $this->input->get_post('page'); //第几页
        $sidx = $this->input->get_post('sidx');
        $sord = $this->input->get_post('sort');
        $filter = $this->input->get_post('filter'); //查询条件
        $order = $this->input->get_post('order'); //排序
        $this->load->model('cwm/cwm_expenditure');
        $grid = $this->cwm_expenditure->grid('*', $filter, $page, $page_size, $sidx, $sord, $order);
        $this->returnData($grid);
    }

    /**
     * 支出审批
     */
    public function expenditure_statu_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'status' => ['审核状态', 'required', 'integer'],//0-审核中 1-未通过 2- 通过
            'auth_remark' => ['审批备注']
        ], [], 'post');
        $this->load->model('cwm/cwm_expenditure');
        $info = $this->cwm_expenditure->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $request_data['auth_id'] = $this->get_login_info()['id'];
        $request_data['auth_time'] = time();
        $request_data['cid'] = $this->get_login_info()['cid'];
        $id = $this->cwm_expenditure->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('审核失败');
        }
        $this->returnData();
    }

    /**
     * 支出批量审批
     */
    public function all_expenditure_sh_post()
    {
        $request_data = $this->check_param([
            'coldata' => ['数据源', 'required']//[{"id":"111","status":"123"},{"id":"111","status":"123","auth_remark":"dsadasdsa"}...]
        ], [], 'post');
        $data = json_decode($request_data['coldata'], true);
        if (empty($data)) {
            $this->returnError('数据格式错误，不是Json格式');
        }
        $this->load->model('cwm/cwm_expenditure');
        $error = [];
        $user_id = $this->get_login_info()['id'];
        $cid = $this->get_login_info()['cid'];
        foreach ($data as $item) {
            if (empty($item['id']) || !in_array($item['status'], [0, 1, 2, 3])) {
                continue;
            }
            $info = $this->cwm_expenditure->get_one('*', ['id' => $item['id']]);
            if (!$info) {
                $error[] = $item['id'];
                continue;
            }
            $item['auth_id'] = $user_id;
            $item['auth_time'] = time();
            $item['cid'] = $cid;
            $id = $this->cwm_expenditure->edit(['id' => $item['id']], $item);
            if ($id === false) {
                $error[] = $item['id'];
                continue;
            }
        }
        $this->returnData(['error_id' => $error]);
    }

    /**
     * 员工工资列表
     */
    public function weges_list_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
            'month' => ['查询月份', 'integer'],//201710
        ], [], 'post');
        if (empty($request_data['month'])) {
            $request_data['month'] = date('Ym');
        }
        $thisMonth = date('Ym');
        $nextMonth = date('Ym', strtotime('+1 month'));
        $this->addMonthWages($thisMonth);
        $this->addMonthWages($nextMonth);
        $cid = $this->get_login_info()['cid'];
        $where = "cwm_wages.cid = {$cid} and cwm_wages.month = {$request_data['month']}";
        if (!empty($request_data['filter'])) {
            $where = $where . " and {$request_data['filter']}";
        }
        $this->load->model('cwm/cwm_wages');
        $grid = $this->cwm_wages->grid($request_data['select'], $where, $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $this->returnData($grid);
    }

    /**
     * 新增月份工资
     */
    private function addMonthWages($month)
    {
        $cid = $this->loginData['cid'];
        $this->load->model('cwm/cwm_wages');
        $this->load->model('bmm/bmm_employees');
        $allEmplo = $this->bmm_employees->get_all('id', ['is_del' => 0, 'cid' => $cid]);
        $saveData = [];
        foreach ($allEmplo as $k => $v) {
            $info = $this->cwm_wages->get_one('id', ['month' => $month, 'employee_id' => $v['id']]);
            if (empty($info)) {
                $saveData[] = ['month' => $month, 'employee_id' => $v['id'], 'cid' => $cid];
            }
        }
        $this->cwm_wages->add_batch($saveData);
    }

    /**
     * 员工工资添加
     */
    public function wagesAdd_post()
    {
        $request_data = $this->check_param([
            'month' => ['查询月份', 'required', 'integer'],//201710
            'employee_id' => ['员工ID', 'required', 'integer'],
            'base' => ['月基本工资', 'required', 'numeric'],
            'post' => ['岗位工资', 'numeric'],
            'assessment' => ['考核工资', 'numeric'],
            'attendance' => ['全勤奖', 'numeric'],
            'communication' => ['通讯补贴', 'numeric'],
            'post_subsidy' => ['岗位补贴', 'numeric'],
            'overtime' => ['加班补贴', 'numeric'],
            'other' => ['应发其他', 'numeric'],
            'percent' => ['比例 %', 'integer'],
            'late' => ['迟到早退'],
            'leave' => ['请假'],
            'absenteeism' => ['旷工'],
            'social' => ['社保'],
            'tax' => ['个人税'],
            'other2' => ['扣除金额其他']
        ], [], 'post');
        /**
         * 验证成功后的逻辑
         */
        $request_data['create_at'] = time();
        $this->load->model('cwm/cwm_wages');
        $this->cwm_wages;
        $id = $this->cwm_wages->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData();
    }

    /**
     * 员工工资编辑
     */
    public function wagesEdit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'employee_id' => ['员工ID', 'required', 'integer'],
            'base' => ['月基本工资', 'required', 'numeric'],
            'post' => ['岗位工资', 'numeric'],
            'assessment' => ['考核工资', 'numeric'],
            'attendance' => ['全勤奖', 'numeric'],
            'communication' => ['通讯补贴', 'numeric'],
            'post_subsidy' => ['岗位补贴', 'numeric'],
            'overtime' => ['加班补贴', 'numeric'],
            'other' => ['应发其他', 'numeric'],
            'percent' => ['比例 %', 'integer'],
            'late' => ['迟到早退'],
            'leave' => ['请假'],
            'absenteeism' => ['旷工'],
            'social' => ['社保'],
            'tax' => ['个人税'],
            'other2' => ['扣除金额其他']
        ], [], 'post');
        /**
         * 验证成功后的逻辑
         * .检查是否合同存在
         * .判断是否加入记账报税管理
         */
        $this->load->model('cwm/cwm_wages');
        $this->cwm_wages;
        //检查是否合同存在
        $info = $this->cwm_wages->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到记录');
        }
        //编辑数据库
        $this->cwm_wages->edit(['id' => $request_data['id']], $request_data);
        $this->returnData();
    }

    /**
     * 员工签字
     */
    public function weges_sh_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'is_sign' => ['是否签字', 'required', 'integer']//0-未 1-是
        ], [], 'post');
        $this->load->model('cwm/cwm_wages');
        $info = $this->cwm_wages->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到记录');
        }
        //编辑数据库
        $id = $this->cwm_wages->edit(['id' => $request_data['id']], $request_data);
        if (!$id) {
            $this->returnError('编辑失败');
        }
        $this->returnData();
    }

    /**
     * 借贷列表
     */
    public function loan_list_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'filter' => ['查询条件'],
            'order' => ['排序'],
        ], [], 'post');
        $this->load->model('cwm/cwm_loan');
        $grid = $this->cwm_loan->grid($request_data['select'], $request_data['filter'], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $this->returnData($grid);
    }

    /**
     * 借贷新增编辑
     */
    public function loan_add_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'integer'],
            'time' => ['时间', 'required', 'integer'],
            'money' => ['金额', 'required', 'numeric'],
            'account' => ['账户', 'required', 'integer'],
            'loan_type' => ['借款类别', 'required', 'integer'],//1-借出 2-借入 3-收款 4-还款
            'object_type' => ['对象类别', 'required', 'integer'],//1-客户公司 2-员工 3-部门
            'object' => ['对象ID', 'required', 'integer'],
            'get_way' => ['支付方式', 'required', 'integer'],//1-现金 2-支付宝 3-微信 4-银行卡
            'remark' => ['备注', 'max_length[1000]'],
            'img' => ['图片', 'max_length[1000]']//多张用逗号分割
        ], [], 'post');
        $this->load->model('cwm/cwm_loan');
        if (empty($request_data['id'])) {
            unset($request_data['id']);
            $request_data['create_id'] = $this->get_login_info()['id'];
            $request_data['create_at'] = time();
            $request_data['cid'] = $this->get_login_info()['cid'];
            $id = $this->cwm_loan->add($request_data);
        } else {
            $info = $this->cwm_loan->get_one('*', ['id' => $request_data['id']]);
            if (!$info) {
                $this->returnError('未查询到信息');
            }
            $id = $this->cwm_loan->edit(['id' => $request_data['id']], $request_data);
        }
        if ($id === false) {
            $this->returnError('提交失败');
        }
        $this->returnData();
    }

    /**
     * 借贷审批
     */
    public function loan_sh_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'required', 'integer'],
            'status' => ['审批状态', 'required', 'integer'],//0-未审核 1-未通过 2-已审核
            'auth_remark' => ['审批回复', 'max_length[1000]']
        ], [], 'post');
        $this->load->model('cwm/cwm_loan');
        $info = $this->cwm_loan->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $request_data['auth_id'] = $this->get_login_info()['id'];
        $request_data['auth_time'] = time();
        $id = $this->cwm_loan->edit(['id' => $request_data['id']], $request_data);
        if ($id === false) {
            $this->returnError('提交失败');
        }
        $this->returnData();
    }

    /**
     * 借贷批量审批
     */
    public function all_loan_sh_post()
    {
        $request_data = $this->check_param([
            'coldata' => ['数据源', 'required']//[{"id":"111","status":"123"},{"id":"111","status":"123","auth_remark":"dsadasdsa"}...]
        ], [], 'post');
        $data = json_decode($request_data['coldata'], true);
        if (empty($data)) {
            $this->returnError('数据格式错误，不是Json格式');
        }
        $this->load->model('cwm/cwm_loan');
        $error = [];
        $user_id = $this->get_login_info()['id'];
        $cid = $this->get_login_info()['cid'];
        foreach ($data as $item) {
            if (empty($item['id']) || !in_array($item['status'], [0, 1, 2, 3])) {
                continue;
            }
            $info = $this->cwm_loan->get_one('*', ['id' => $item['id']]);
            if (!$info) {
                $error[] = $item['id'];
                continue;
            }
            $item['auth_id'] = $user_id;
            $item['auth_time'] = time();
            $id = $this->cwm_loan->edit(['id' => $item['id']], $item);
            if ($id === false) {
                $error[] = $item['id'];
                continue;
            }
        }
        $this->returnData(['error_id' => $error]);
    }
}
