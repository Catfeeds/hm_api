<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_customer extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('khm/khm_customer', 'model');
    }

    /**
     * 客户列表
     */
    public function customer_post()
    {
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],//1-客户公海 2-合作客户 3-客户服务 4-回收站客户
            'page' => ['当前页', 'integer'],
            'limit' => ['每页多少内容', 'integer'],
            'filter' => ['自定义查询'], //高级搜索  xxx字段=1 and xxx字段=2 or xxx字段=3
            'order' => ['排序']
        ], [], 'post');
        switch ($request_data['type']) {
            case 1:
                $filter = "khm_customer.status = 0";
                break;
            case 2:
                $filter = "khm_customer.status = 1 and khm_customer.c_type = 1";
                break;
            case 3:
                $filter = "khm_customer.status = 1 and khm_customer.z_type = 1";
                break;
            case 4:
                $filter = "khm_customer.status = 2";
                break;
            default:
                $filter = "1 = 1";
        }
        if (!empty($request_data['filter'])) {
            $filter .= ' and ' . $request_data['filter'];
        }
        $grid = $this->model->grid("*", $filter, $request_data['page'], $request_data['limit'], '', '', $request_data['order'], TRUE);
        $this->returnData($grid);
    }

    /**
     * 客户公海导入
     */
    public function upload_batch_post()
    {
        $excel = $_FILES['customer'];
        if (!isset($excel['name'])) {
            $this->returnError('上传错误');
        }
        if ($excel["error"] > 0) {
            $this->returnError($excel["error"]);
        }
        $fileExplode = explode('.', $excel['name']);
        if (!in_array($fileExplode[1], ['xls', 'xlsx'])) {
            $this->returnError('文件格式错误');
        }
        $excelRes = readExcel($excel['tmp_name'], $fileExplode[1]);
        if (!$excelRes) {
            $this->returnError('读取表格失败');
        }
        $dataKey = [
            'A' => ['key' => 'name', 'name' => '公司名称', 'required' => true],
            'B' => ['key' => 'corporation', 'name' => '法人', 'required' => true],
            'C' => ['key' => 'capital', 'name' => '注册资本', 'required' => true],
            'D' => ['key' => 'stablish_time', 'name' => '成立日期', 'required' => true, 'key_type' => 'timestamp'],
            'E' => ['key' => 'address', 'name' => '地址', 'required' => true],
            'F' => ['key' => 'tel', 'name' => '电话号码', 'required' => true, 'key_type' => 'tel'],
            'G' => ['key' => 'range', 'name' => '经营范围', 'required' => true],
            'H' => ['key' => 'url', 'name' => '网址', 'required' => true],
            'I' => ['key' => 'phone', 'name' => '手机号码', 'required' => true],
            'J' => ['key' => 'fax', 'name' => '传真', 'required' => true],
            'K' => ['key' => 'email', 'name' => '邮箱', 'required' => true],
            'L' => ['key' => 'source', 'name' => '客户来源', 'required' => true],
            'M' => ['key' => 'industry', 'name' => '所属行业', 'required' => true],
            'N' => ['key' => 'scale', 'name' => '人员规模', 'required' => true],
            'O' => ['key' => 'contacts', 'name' => '联系人', 'required' => true],
            'P' => ['key' => 'tax_type', 'name' => '税务类型', 'required' => true, 'key_type' => 'obj', 'key_obj' => ['一般纳税人' => 1, '小规模' => 2]],
            'Q' => ['key' => 'tax_remark', 'name' => '备注'],
        ];
        $dataF = [];
        unset($excelRes[1]);
        foreach ($excelRes as $line => $row) {
            if (!$row['A']) {
                continue;   //  防止表格空白行
            }
            $tem = [];
            foreach ($dataKey as $dk => $dv) {
                $key = $dv['key'];
                if (isset($dv['required']) && $dv['required'] && (!isset($row[$dk]) || !$row[$dk])) {
                    $this->returnError("{$dv['name']}不能为空，第{$dk}{$line}单元格");
                }
                $value = isset($row[$dk]) ? $row[$dk] : '';
                if (isset($dv['key_type'])) {
                    switch ($dv['key_type']) {
                        case 'timestamp':
                            $value = strtotime($value);
                            break;
                        case 'obj':
                            if (!isset($dv['key_obj'][$value])) {
                                $this->returnError("{$dv['name']}错误，第{$dk}{$line}单元格");
                            }
                            $value = $dv['key_obj'][$value];
                            break;
                    }
                }
                $tem[$key] = $value;
            }
            $tem['create_id'] = $this->get_login_info()['id'];
            $tem['create_at'] = time();
            $dataF[] = $tem;
        }
        $res = $this->model->add_batch($dataF);
        if (!$res) {
            $this->returnError('添加失败');
        }
        $this->returnData();
    }

    public function index_post()
    {
        $request_data = $this->check_param([
            'name' => ['公司名称', 'max_length[200]'],
            'corporation' => ['法人', 'max_length[200]'],
            'capital' => ['注册资本', 'max_length[200]'],
            'address' => ['地址', 'max_length[200]'],
            'email' => ['邮箱', 'max_length[200]'],
            'tel' => ['电话号码', 'max_length[200]'],
            'range' => ['经营范围', 'max_length[200]'],
            'url' => ['网址', 'max_length[200]'],
            'phone' => ['手机号码', 'max_length[200]'],
            'fax' => ['传真', 'max_length[200]'],
            'source' => ['客户来源', 'max_length[200]'],
            'industry' => ['所属行业', 'max_length[200]'],
            'scale' => ['人员规模', 'max_length[200]'],
            'remark' => ['备注', 'max_length[200]'],
            'contacts' => ['联系人', 'max_length[200]'],
            'register_address' => ['注册地址', 'max_length[200]'],
            'id' => ['客户编号', 'max_length[200]'],
            'area' => ['所在区域', 'max_length[200]'],
            'corporation_card' => ['法人证件号码', 'max_length[200]'],
            'license_no' => ['营业执照号', 'max_length[200]'],
            'ratepaying_no' => ['纳税识别号', 'max_length[200]'],
            'state_tax_no' => ['国税编码', 'max_length[200]'],
            'state_tax_pass' => ['国税密码', 'max_length[200]'],
            'local_tax_no' => ['地税编码', 'max_length[200]'],
            'local_tax_pass' => ['地税密码', 'max_length[200]'],
            'tax_remark' => ['税务备注', 'max_length[200]'],
            'introduce' => ['介绍人', 'max_length[200]'],
            'social_credit_code' => ['社会信用代码', 'max_length[200]'],
            'stablish_time' => ['创建时间', 'integer'],
            'type' => ['客户类型', 'integer'],  // 1-一般纳税人 2-小规模
            'rank' => ['等级', 'integer'],    // 1-普通 2-一般 3-重要
            'tax_type' => ['税务类型', 'integer'],
            'billing_type' => ['开票类型', 'integer'],
            'c_type' => ['客户类型【公海/合作客户】', 'integer'],   //  1：公海客户  2：合作客户
            'page' => ['当前页', 'integer'],
            'page_size' => ['每页多少内容', 'integer'],
            'rows' => ['每页多少内容', 'integer'],
            'sort_rule' => ['排序规则'],
            'order' => ['排序规则'],   //  新增参数， 当sort_rule不为空时，以sort_rule为准
            'filter' => ['查询条件'],   //  与上面的多个条件会自动拼接起来
        ]);
        if (!$request_data['page_size'] && $request_data['rows']) {
            $request_data['page_size'] = $request_data['rows'];
        }
        $condition = [];
        $condition['is_del'] = 0;
        foreach ($request_data as $k => $v) {
            if (!$v || in_array($k, ['filter', 'order', 'sort_rule', 'page', 'page_size'])) {
                continue;
            }
            if (is_int($v)) {
                $condition[$k] = $v;
            } elseif (is_string($v)) {
                $condition[$k] = ['like', $v];
            }
        }
        $condition['company_id'] = $this->loginData['company_id'];
        if (!$request_data['sort_rule']) {
            $request_data['sort_rule'] = $request_data['order'];
        }
        if (!$request_data['sort_rule']) {
            $request_data['sort_rule'] = 'id desc';
        }
        if ($request_data['filter']) {
            $this->model->db->where($request_data['filter']);
        }
        $data = $this->model->grid("*", $condition, $request_data['page'], $request_data['page_size'], $request_data['sort_rule'], TRUE);
        $this->returnData($data);
    }

    /**
     * 客户公海新增
     */
    public function add_post()
    {
        $request_data = $this->check_param([
            'name' => ['公司名称', 'required', 'max_length[200]'],
            'corporation' => ['法人', 'required', 'max_length[200]'],
            'capital' => ['注册资本', 'required', 'max_length[200]'],
            'address' => ['地址', 'required', 'max_length[200]'],
            'email' => ['邮箱', 'required', 'max_length[200]'],
            'tel' => ['电话号码', 'required', 'max_length[200]'],
            'range' => ['经营范围', 'required', 'max_length[200]'],
            'url' => ['网址', 'required', 'max_length[200]'],
            'phone' => ['手机号码', 'max_length[200]'],
            'fax' => ['传真', 'required', 'max_length[200]'],
            'source' => ['客户来源', 'required', 'max_length[200]'],
            'industry' => ['所属行业', 'required', 'max_length[200]'],
            'scale' => ['人员规模', 'required', 'max_length[200]'],
            'remark' => ['备注', 'max_length[200]'],
            'contacts' => ['联系人', 'required', 'max_length[200]'],
//            'register_address' => ['注册地址', 'required', 'max_length[200]'],
            'id' => ['客户编号', 'max_length[200]'],
            'area' => ['所在区域', 'required', 'max_length[200]'],
            'corporation_card' => ['法人证件号码', 'max_length[200]'],
            'license_no' => ['营业执照号', 'max_length[200]'],
            'ratepaying_no' => ['纳税识别号', 'max_length[200]'],
            'state_tax_no' => ['国税编码', 'max_length[200]'],
            'state_tax_pass' => ['国税密码', 'max_length[200]'],
            'local_tax_no' => ['地税编码', 'max_length[200]'],
            'local_tax_pass' => ['地税密码', 'max_length[200]'],
            'tax_remark' => ['税务备注', 'max_length[200]'],
            'introduce' => ['介绍人', 'max_length[200]'],
            'social_credit_code' => ['社会信用代码', 'max_length[200]'],
            'stablish_time' => ['创建时间', 'integer'],
            'rank' => ['等级', 'integer'],    // 1-普通 2-一般 3-重要
            'tax_type' => ['税务类型', 'integer'],//1-一般纳税人 2-小规模
            'billing_type' => ['开票类型', 'integer'],
            'register_capital' => ['注册资金', 'numeric'],
            'paid_in_capital' => ['实收资金', 'numeric'],
            'c_type' => ['客户类型【公海/合作客户】', 'integer']
        ], [], 'post');
        if (!$request_data['c_type']) {
            unset($request_data['c_type']);
        }
        $request_data['cid'] = $this->loginData['cid'];
        $request_data['create_id'] = $this->get_login_info()['id'];
        $request_data['create_at'] = time();
        $id = $this->model->add($request_data);
        if (!$id) {
            $this->returnError('添加失败');
        }
        $this->returnData(['id' => $id]);
    }

    /**
     * 客户公海编辑
     */
    public function edit_post()
    {
        // 判断权限
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'name' => ['公司名称', 'max_length[200]'],
            'corporation' => ['法人', 'max_length[200]'],
            'capital' => ['注册资本', 'max_length[200]'],
            'address' => ['地址', 'max_length[200]'],
            'email' => ['邮箱', 'max_length[200]'],
            'tel' => ['电话号码', 'max_length[200]'],
            'range' => ['经营范围', 'max_length[200]'],
            'url' => ['网址', 'max_length[200]'],
            'phone' => ['手机号码', 'max_length[200]'],
            'fax' => ['传真', 'max_length[200]'],
            'source' => ['客户来源', 'max_length[200]'],
            'industry' => ['所属行业', 'max_length[200]'],
            'scale' => ['人员规模', 'max_length[200]'],
            'remark' => ['备注', 'max_length[200]'],
            'contacts' => ['联系人', 'max_length[200]'],
//            'register_address' => ['注册地址', 'max_length[200]'],
            'id' => ['客户编号', 'max_length[200]'],
            'area' => ['所在区域', 'max_length[200]'],
            'corporation_card' => ['法人证件号码', 'max_length[200]'],
            'license_no' => ['营业执照号', 'max_length[200]'],
            'ratepaying_no' => ['纳税识别号', 'max_length[200]'],
            'state_tax_no' => ['国税编码', 'max_length[200]'],
            'state_tax_pass' => ['国税密码', 'max_length[200]'],
            'local_tax_no' => ['地税编码', 'max_length[200]'],
            'local_tax_pass' => ['地税密码', 'max_length[200]'],
            'tax_remark' => ['税务备注', 'max_length[200]'],
            'introduce' => ['介绍人', 'max_length[200]'],
            'social_credit_code' => ['社会信用代码', 'max_length[200]'],
            'stablish_time' => ['创建时间', 'integer'],
            'rank' => ['等级', 'integer'],    // 1-普通 2-一般 3-重要
            'tax_type' => ['税务类型', 'integer'],// 1-一般纳税人 2-小规模
            'billing_type' => ['开票类型', 'integer'],
            'register_capital' => ['注册资金', 'numeric'],
            'paid_in_capital' => ['实收资金', 'numeric'],
        ], [], 'post');
        $condition = ['id' => $request_data['id']];
        $res = $this->model->edit($condition, $request_data);
        if ($res === false) {
            $this->returnError('编辑失败');
        }
        $this->returnData([]);
    }

    /**
     * 合作客户编辑
     */
    public function cooperation_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'name' => ['公司名称', 'max_length[200]'],
            'source' => ['客户来源', 'required', 'max_length[200]'],
            'introduce' => ['介绍人', 'max_length[200]'],
            'billing_type' => ['开票类型', 'integer'],//开票类型 1：增值税专用发票 2：增值税普通发票
            'social_credit_code' => ['社会信用代码', 'max_length[200]'],
            'corporation' => ['法人', 'max_length[200]'],
            'corporation_card' => ['法人证件号码', 'max_length[20]'],
            'register_capital' => ['注册资金', 'numeric'],
            'area' => ['所在区域', 'max_length[200]'],
            'stablish_time' => ['创建时间', 'integer'],
            'address' => ['详细地址', 'max_length[200]'],
            'industry' => ['所属行业', 'max_length[200]'],
            'range' => ['经营范围', 'max_length[1000]'],
            'scale' => ['人员规模', 'max_length[200]'],
            'remark' => ['备注', 'max_length[200]'],
        ], [], 'post');
        $condition = ['id' => $request_data['id']];
        $res = $this->model->edit($condition, $request_data);
        if ($res === false) {
            $this->returnError('编辑失败');
        }
        $this->returnData([]);
    }

    /**
     * 判断客户资料是否在审核中
     */
    public function customer_oo_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],//客户ID
        ], [], 'post');
        $this->load->model('spm/spm_approves');
        $info = $this->spm_approves->get_one('*', ['customer_num' => $request_data['id'], 'approve_type' => 1, 'approve_result' => 1, 'is_del' => 0]);
        if ($info) {
            $this->returnError('客户资料还在审核中');
        }
        $this->returnData();
    }

    /**
     * 客户服务编辑
     */
    public function service_edit_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'required', 'integer'],
            'name' => ['公司名称', 'max_length[200]'],
            'source' => ['客户来源', 'required', 'max_length[200]'],
            'introduce' => ['介绍人', 'max_length[200]'],
            'billing_type' => ['开票类型', 'integer'],//开票类型 1：增值税专用发票 2：增值税普通发票
            'social_credit_code' => ['社会信用代码', 'max_length[200]'],
            'corporation' => ['法人', 'max_length[200]'],
            'corporation_card' => ['法人证件号码', 'max_length[20]'],
            'register_capital' => ['注册资金', 'numeric'],
            'area' => ['所在区域', 'max_length[200]'],
            'stablish_time' => ['创建时间', 'integer'],
            'address' => ['详细地址', 'max_length[200]'],
            'industry' => ['所属行业', 'max_length[200]'],
            'range' => ['经营范围', 'max_length[1000]'],
            'scale' => ['人员规模', 'max_length[200]'],
            'remark' => ['备注', 'max_length[200]'],

            'tax_type' => ['税务类型', 'integer'],//1-一般纳税人 2-小规模
            'basic' => ['有无基本户', 'integer'],  //1.有 2.无
            'ratepaying_no' => ['纳税识别号', 'max_length[200]'],
            'state_tax_no' => ['国税编码', 'max_length[200]'],
            'state_tax_pass' => ['国税密码', 'max_length[200]'],
            'local_tax_no' => ['地税编码', 'max_length[200]'],
            'local_tax_pass' => ['地税密码', 'max_length[200]'],
            'dca' => ['地税ca证书', 'max_length[200]'],
            'gca' => ['国税ca证书', 'max_length[200]'],
        ], [], 'post');
        $condition = ['id' => $request_data['id']];
        $res = $this->model->edit($condition, $request_data);
        if ($res === false) {
            $this->returnError('编辑失败');
        }
        //编辑成功提交审批
        $info = $this->model->get_one('*', $condition);
        $this->load->model('spm/spm_approves');
        $this->spm_approves->add_approves(1, $this->get_login_info(), $info, $request_data['name'], $request_data['id']);
        $this->returnData([]);
    }

    public function del_post()
    {
        $request_data = $this->check_param([
            'id' => ['ID', 'integer'],
        ], [], 'post');
        $res = $this->model->edit($request_data, ['is_del' => 1]);
        if ($res === false) {
            $this->returnError('删除失败');
        }
        $this->returnData([]);
    }

    /**
     * 批量删除
     */
    public function batch_del_post()
    {
        $request_data = $this->check_param([
            'ids' => ['ID', 'required'],//1,2,3,4
        ], [], 'post');
        $where = "id in ({$request_data['ids']})";
        $res = $this->model->edit($where, ['is_del' => 1]);
        if ($res === false) {
            $this->returnError('删除失败');
        }
        $this->returnData();
    }

    /**
     * 获取没有线索的客户
     */
    public function get_customer_post()
    {
        $this->load->model('khm/khm_customer_clue');
        $list = $this->khm_customer_clue->get_all('customer_id');
        if ($list) {
            foreach ($list as $item) {
                $id[] = $item['customer_id'];
            }
            $id_s = implode(',', $id);
            $filter = "khm_customer.id not in ({$id_s})";
            $this->model->f7('*', "khm_customer.id not in ({$id_s})");
        } else {
            $filter = "1=1";
        }
        $f7 = $this->model->f7('*', $filter);
        $this->returnData($f7);
    }

    /**
     * 获取线索的信息
     */
    public function get_clue_info_post()
    {
        $request_data = $this->check_param([
            'id' => ['线索ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('khm/khm_customer_clue');
        $info = $this->khm_customer_clue->get_one('*', ['khm_customer_clue.id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查询到信息');
        }
        $this->returnData($info);
    }

    /**
     * 添加编辑线索
     */
    public function add_clue_post()
    {
        $request_data = $this->check_param([
            'customer_id' => ['客户ID', 'required', 'integer'],
            'cate_id' => ['分组ID', 'required', 'integer'],
            'label_id' => ['标签ID', 'required', 'integer'],
            'rank' => ['阶段', 'required', 'integer'],//阶段 1-新人 2-初步沟通 3-判断分析 4-上门面谈 5-合同签订
//            'last_time' => ['最后跟进时间', 'required', 'integer'],
            'user_id' => ['负责人', 'required', 'integer'],
//            'decision' => ['决策人', 'required'],
//            'is_main' => ['是否主要联系人', 'required', 'integer'],// 1-是 0-否
        ], [], 'post');
        $this->load->model('khm/khm_customer_clue');
        $info = $this->khm_customer_clue->get_one('*', ['customer_id' => $request_data['customer_id']]);
        if (!$info) {
            $request_data['create_time'] = time();
            $request_data['cid'] = $this->loginData['cid'];
            $id = $this->khm_customer_clue->add($request_data);
        } else {
            $id = $this->khm_customer_clue->edit(['customer_id' => $request_data['customer_id']], $request_data);
        }
        $this->returnData();
    }

    /**
     * 线索
     */
    public function index_clue_post()
    {
        $select = $this->input->get_post('select'); //查询字段
        $page_size = $this->input->get_post('limit'); //每页显示多少条
        $page = $this->input->get_post('page'); //第几页
        $filter = $this->input->get_post('filter'); //查询条件
        $order = $this->input->get_post('order'); //排序
        $this->load->model('khm/khm_customer_clue');
        $grid = $this->khm_customer_clue->grid($select, $filter, $page, $page_size, '', '', $order);
        $this->returnData($grid);
    }

    /**
     * 客户工资单列表
     */
    public function hk_wages_list_post()
    {
        $request_data = $this->check_param([
            'select' => ['查询字段'],
            'limit' => ['每页显示多少条', 'required', 'integer'],
            'page' => ['第几页', 'integer'],
            'order' => ['排序'],
            'kh_id' => ['客户ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('khm/khm_wages');
        $grid = $this->khm_wages->grid($request_data['select'], ['kh_id' => $request_data['kh_id']], $request_data['page'], $request_data['limit'], '', '', $request_data['order']);
        $this->returnData($grid);
    }

    /**
     * 客户工资单新增编辑
     */
    public function hk_wages_add_edit_post()
    {
        $request_data = $this->check_param([
            'coldata' => ['每行数据', 'required'],//json字符串 [{},{}] 参照khm_wages表，编辑带主键 新增不带主键
        ], [], 'post');
        $colData = json_decode($request_data['coldata'], true);
        if (empty($colData)) {
            $this->returnError('数据格式错误');
        }
        $this->load->model('khm/khm_wages');
        $this->db->trans_start();
        foreach ($colData as $item) {
            if (empty($item['id'])) {
                $id = $this->khm_wages->add($item);
            } else {
                $id = $this->khm_wages->edit(['id' => $item['id']], $item);
            }
            if ($id === false) {
                $this->returnError('提交失败');
            }
        }
        $this->db->trans_complete();
        $this->returnData();
    }

    /**
     *  客户工资删除
     */
    public function hk_wages_del_post()
    {
        $request_data = $this->check_param([
            'id' => ['主键', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('khm/khm_wages');
        $info = $this->khm_wages->get_one('*', ['id' => $request_data['id']]);
        if (!$info) {
            $this->returnError('未查到记录');
        }
        $this->khm_wages->del(['id' => $request_data['id']]);
        $this->returnData();
    }
}
