<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

class Api_home extends Apibase
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * (个人)折线图
     */
    public function home_chart_post()
    {
        //类型1-收单 2-整单 3-记账 4-客服 5-报税 6-送单 7-任务 8-客户 9-联系人 10-商机 11-拜访客户 12-销售记录 13-合同数量 14-回款数量 15-合同金额
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],
            'time' => ['时间类型', 'required', 'integer'],//1-今日 2-昨日 3-近7日 4-近30日 5-本月 6-上个月
            'user' => ['员工ID', 'required', 'integer'],
        ], [], 'post');
        $filter = '';
        $countCol = '';
        $range = '';
        $isR = false;
        switch ($request_data['type']) {
            case 7:
                $this->load->model('htm/htm_task_staff');
                $contractIds = $this->htm_task_staff->get_all('distinct (contract_id)', ['staff_id' => 1]);
                foreach ($contractIds as $k => $v) {
                    $_contract[] = $v['contract_id'];
                }
                $_contract = implode(',', $_contract);
                $isR = true;
                $range = "contract_id in ({$_contract})";
                $tableData = ['table' => 'htm_task', 'time' => 'done_time', 'user' => 'staff_id'];
                $filter = "is_del = 0";
                break;
            case 8:
                $tableData = ['table' => 'khm_customer', 'time' => 'create_at', 'user' => 'create_id'];
                $filter = "is_del = 0";
                break;
            case 9:
                $tableData = ['table' => 'khm_contact_book', 'time' => 'create_time', 'user' => 'create_user'];
                $filter = "is_del = 0";
                break;
            case 10:
                $tableData = ['table' => 'cwm_opportunity', 'time' => 'create_time', 'user' => 'master_user'];
                $filter = "is_del = 0";
                break;
            case 11:
                $tableData = ['table' => 'cwm_visit', 'time' => 'create_time', 'user' => 'visit_id'];
                $filter = "is_del = 0";
                break;
            case 12:
                $tableData = ['table' => 'khm_customer_clue', 'time' => 'create_time', 'user' => 'user_id'];
                break;
            case 13:
                $this->load->model('htm/htm_task_staff');
                $contractIds = $this->htm_task_staff->get_all('distinct (contract_id)', ['staff_id' => 1]);
                foreach ($contractIds as $k => $v) {
                    $_contract[] = $v['contract_id'];
                }
                $_contract = implode(',', $_contract);
                $isR = true;
                $range = "id in ({$_contract})";
                $tableData = ['table' => 'htm_contract', 'time' => 'signed_time', 'user' => 'assign_staff_id'];
                break;
            case 14:
                $tableData = ['table' => 'cwm_receivables', 'time' => 'get_time', 'user' => 'receiver'];
                $filter = "status = 2";
                break;
            case 15:
                $this->load->model('htm/htm_task_staff');
                $contractIds = $this->htm_task_staff->get_all('distinct (contract_id)', ['staff_id' => 1]);
                foreach ($contractIds as $k => $v) {
                    $_contract[] = $v['contract_id'];
                }
                $_contract = implode(',', $_contract);
                $isR = true;
                $range = "id in ({$_contract})";
                $tableData = ['table' => 'htm_contract', 'time' => 'signed_time', 'user' => 'assign_staff_id'];
                $countCol = "(sum(discount_total)+sum(account_book)) as 'num'";
                break;
            default:
                $this->load->model('htm/htm_task_staff');
                $contractIds = $this->htm_task_staff->get_all('distinct (contract_id)', ['staff_id' => 1]);
                foreach ($contractIds as $k => $v) {
                    $_contract[] = $v['contract_id'];
                }
                $_contract = implode(',', $_contract);
                $isR = true;
                $range = "contract_id in ({$_contract})";
                $tableData = ['table' => 'jzm_service_info', 'time' => 'com_time', 'user' => 'user_id'];
                $filter = "type = {$request_data['type']}";
                break;
        }
        $res = $this->personSql($request_data['time'], $request_data['user'], $tableData, $filter, $countCol, $range, $isR);
        $this->returnData($res);
    }

    /**
     * (个人)折线图封装方法
     * @param $time 时间类型
     * @param $userId 用户ID
     * @param $tableData ['table' => 'jzm_service_info', 'time' => 'com_time', 'user' => 'user_id']
     * @param string $filter 添加的查询条件  例子： is_del = 0 and status = 1
     * @param string $countCol 求和字段 例子：$countCol = "(sum(contract_amount)+sum(accountbook_cost)) as 'num'";
     * @return array
     */
    private function personSql($time, $userId, $tableData, $filter = '', $countCol = '', $range = '', $isR = false)
    {
        if (empty($tableData)) {
            return fasle;
        }
        $timeArr = $this->getTime($time);
        $start_time = $timeArr[0];
        $last_time = $timeArr[1];
        $one_day = 24 * 60 * 60;
        $day = ceil(($last_time - $start_time) / $one_day);
        $where = "{$tableData['time']} >= {$start_time} and {$tableData['time']} <= {$last_time}";
        if ($isR) {
            if (!empty($range)) {
                $where .= " and {$range}";
            }
        } else {
            $where .= " and {$tableData['user']} = {$userId}";
        }
        if (!empty($filter)) {
            $where .= " and {$filter}";
        }
        if (empty($countCol)) {
            $countCol = "count(*) as 'num'";
        }
        if ($day <= 1) {
            $sql = "select {$countCol} from {$tableData['table']} where {$where}";
        } else {
            $group = '';
            for ($i = 1; $i <= $day; $i++) {
                $l = $last_time - $one_day * ($i - 1);
                $r = $last_time - $one_day * ($i);
                $group .= "when {$tableData['time']} > {$r} and {$tableData['time']} <= {$l} then {$i} ";
            }
            $sql = "select nday,{$countCol} from (select case {$group} end as nday,{$tableData['table']}.* from {$tableData['table']}) {$tableData['table']} where {$where} group by nday";
        }
        $query = $this->db->query($sql);
        $res = $query->result_array();
        $res = $this->toList($res, $day, $last_time);
        $data = [
            'chart' => !empty($res[0]) ? $res[0] : [],
            'complete' => !empty($res[1]) ? $res[1] : 0,
            'user' => $this->getUserMu($time, $userId),
            'monthDay' => cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')),
            'thisWorkDay' => $this->getThisWork(),
            'lastWorkDay' => $this->getLastWork(),
        ];
        return $data;
    }

//    /**
//     * (个人)折线图
//     */
//    public function home_chart_post()
//    {
//        //类型1-收单 2-整单 3-记账 4-客服 5-报税 6-送单 7-任务 8-客户 9-联系人 10-商机 11-拜访客户 12-销售记录 13-合同数量 14-回款数量 15-合同金额
//        $request_data = $this->check_param([
//            'type' => ['类型', 'required', 'integer'],
//            'time' => ['时间类型', 'required', 'integer'],//1-今日 2-昨日 3-近7日 4-近30日 5-本月 6-上个月
//            'user' => ['员工ID', 'required', 'integer'],
//        ], [], 'post');
//        $filter = '';
//        $countCol = '';
//        switch ($request_data['type']) {
//            case 7:
//                $tableData = ['table' => 'htm_contract', 'time' => 'task_co_time', 'user' => 'assign_staff_id'];
//                $filter = "task_co_status = 1";
//                break;
//            case 8:
//                $tableData = ['table' => 'khm_customer', 'time' => 'create_at', 'user' => 'create_id'];
//                $filter = "is_del = 0";
//                break;
//            case 9:
//                $tableData = ['table' => 'khm_contact_book', 'time' => 'create_time', 'user' => 'create_user'];
//                $filter = "is_del = 0";
//                break;
//            case 10:
//                $tableData = ['table' => 'cwm_opportunity', 'time' => 'create_time', 'user' => 'master_user'];
//                $filter = "is_del = 0";
//                break;
//            case 11:
//                $tableData = ['table' => 'cwm_visit', 'time' => 'create_time', 'user' => 'visit_id'];
//                $filter = "is_del = 0";
//                break;
//            case 12:
//                $tableData = ['table' => 'khm_customer_clue', 'time' => 'create_time', 'user' => 'user_id'];
//                break;
//            case 13:
//                $tableData = ['table' => 'htm_contract', 'time' => 'signed_time', 'user' => 'assign_staff_id'];
//                break;
//            case 14:
//                $tableData = ['table' => 'cwm_receivables', 'time' => 'get_time', 'user' => 'receiver'];
//                $filter = "status = 2";
//                break;
//            case 15:
//                $tableData = ['table' => 'htm_contract', 'time' => 'signed_time', 'user' => 'assign_staff_id'];
//                $countCol = "(sum(contract_amount)+sum(accountbook_cost)) as 'num'";
//                break;
//            default:
//                $tableData = ['table' => 'jzm_service_info', 'time' => 'com_time', 'user' => 'user_id'];
//                $filter = "type = {$request_data['type']}";
//                break;
//        }
//        $res = $this->personSql($request_data['time'], $request_data['user'], $tableData, $filter, $countCol);
//        $this->returnData($res);
//    }

//    /**
//     * (个人)折线图封装方法
//     * @param $time 时间类型
//     * @param $userId 用户ID
//     * @param $tableData ['table' => 'jzm_service_info', 'time' => 'com_time', 'user' => 'user_id']
//     * @param string $filter 添加的查询条件  例子： is_del = 0 and status = 1
//     * @param string $countCol 求和字段 例子：$countCol = "(sum(contract_amount)+sum(accountbook_cost)) as 'num'";
//     * @return array
//     */
//    private function personSql($time, $userId, $tableData, $filter = '', $countCol = '')
//    {
//        if (empty($tableData)) {
//            return fasle;
//        }
//        $timeArr = $this->getTime($time);
//        $start_time = $timeArr[0];
//        $last_time = $timeArr[1];
//        $one_day = 24 * 60 * 60;
//        $day = ceil(($last_time - $start_time) / $one_day);
//        $where = "{$tableData['time']} >= {$start_time} and {$tableData['time']} <= {$last_time} and {$tableData['user']} = {$userId}";
//        if (!empty($filter)) {
//            $where .= " and {$filter}";
//        }
//        if (empty($countCol)) {
//            $countCol = "count(*) as 'num'";
//        }
//        if ($day <= 1) {
//            $sql = "select {$countCol} from {$tableData['table']} where {$where}";
//        } else {
//            $group = '';
//            for ($i = 1; $i <= $day; $i++) {
//                $l = $last_time - $one_day * ($i - 1);
//                $r = $last_time - $one_day * ($i);
//                $group .= "when {$tableData['time']} > {$r} and {$tableData['time']} <= {$l} then {$i} ";
//            }
//            $sql = "select nday,{$countCol} from (select case {$group} end as nday,{$tableData['table']}.* from {$tableData['table']}) {$tableData['table']} where {$where} group by nday";
//        }
//        $query = $this->db->query($sql);
//        $res = $query->result_array();
//        $res = $this->toList($res, $day, $last_time);
//        $data = [
//            'chart' => !empty($res[0]) ? $res[0] : [],
//            'complete' => !empty($res[1]) ? $res[1] : 0,
//            'user' => $this->getUserMu($time, $userId),
//            'monthDay' => cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')),
//            'workDay' => $this->getWorkDay($time)
//        ];
//        return $data;
//    }

    /**
     * (公司)折线图
     */
    public function home_total_chart_post()
    {
        //类型1-收单 2-整单 3-记账 4-客服 5-报税 6-送单 7-任务 8-客户 9-联系人 10-商机 11-拜访客户 12-销售记录 13-合同数量 14-回款数量 15-合同金额
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'],
            'time' => ['时间类型', 'required', 'integer'],//1-今日 2-昨日 3-近7日 4-近30日 5-本月 6-上个月
        ], [], 'post');
        $filter = '';
        $countCol = '';
        switch ($request_data['type']) {
            case 7:
                $tableData = ['table' => 'htm_contract', 'time' => 'task_co_time', 'user' => 'assign_staff_id'];
                $filter = "task_co_status = 1";
                break;
            case 8:
                $tableData = ['table' => 'khm_customer', 'time' => 'create_at', 'user' => 'create_id'];
                $filter = "is_del = 0";
                break;
            case 9:
                $tableData = ['table' => 'khm_contact_book', 'time' => 'create_time', 'user' => 'create_user'];
                $filter = "is_del = 0";
                break;
            case 10:
                $tableData = ['table' => 'cwm_opportunity', 'time' => 'create_time', 'user' => 'master_user'];
                $filter = "is_del = 0";
                break;
            case 11:
                $tableData = ['table' => 'cwm_visit', 'time' => 'create_time', 'user' => 'visit_id'];
                $filter = "is_del = 0";
                break;
            case 12:
                $tableData = ['table' => 'khm_customer_clue', 'time' => 'create_time', 'user' => 'user_id'];
                break;
            case 13:
                $tableData = ['table' => 'htm_contract', 'time' => 'signed_time', 'user' => 'assign_staff_id'];
                break;
            case 14:
                $tableData = ['table' => 'cwm_receivables', 'time' => 'get_time', 'user' => 'receiver'];
                $filter = "status = 2";
                break;
            case 15:
                $tableData = ['table' => 'htm_contract', 'time' => 'signed_time', 'user' => 'assign_staff_id'];
                $countCol = "(sum(contract_amount)+sum(accountbook_cost)) as 'num'";
                break;
            default:
                $tableData = ['table' => 'jzm_service_info', 'time' => 'com_time', 'user' => 'user_id'];
                $filter = "type = {$request_data['type']}";
                break;
        }
        $res = $this->totalSql($request_data['time'], $tableData, $filter, $countCol);
        $this->returnData(['chart' => $res]);
    }

    /**
     * (公司)折线图封装方法
     * @param $time 时间类型
     * @param $userId 用户ID
     * @param $tableData ['table' => 'jzm_service_info', 'time' => 'com_time', 'user' => 'user_id']
     * @param string $filter 添加的查询条件  例子： is_del = 0 and status = 1
     * @param string $countCol 求和字段 例子：$countCol = "(sum(contract_amount)+sum(accountbook_cost)) as 'num'";
     * @return array
     */
    private function totalSql($time, $tableData, $filter = '', $countCol = '')
    {
        if (empty($tableData)) {
            return fasle;
        }
        $cid = $this->get_login_info()['cid'];
        $timeArr = $this->getTime($time);
        $start_time = $timeArr[0];
        $last_time = $timeArr[1];
        $where = "{$tableData['time']}>={$start_time} and {$tableData['time']}<= {$last_time} and cid = {$cid}";
//        $where = "{$tableData['time']}>={$start_time} and {$tableData['time']}<= {$last_time}";
        if (!empty($filter)) {
            $where .= " and {$filter}";
        }
        if (empty($countCol)) {
            $countCol = "count(*) as 'num'";
        }
        $sql = "select {$tableData['user']} as user_id,{$countCol} from {$tableData['table']} where {$where} group by {$tableData['user']}";
        $query = $this->db->query($sql);
        $res = $query->result_array();
        $this->load->model('bmm/bmm_employees');
        if (!empty($res)) {
            foreach ($res as $i => $item) {
                $userInfo = $this->bmm_employees->get_one('id,name', ['id' => $item['user_id']]);
                $res[$i]['name'] = $userInfo['name'];
//                $res[$i]['user'] = array_filts($userInfo, ['username', 'password'], false);
            }
        }
        return $res;
    }

    /**
     * 新增编辑下个月目标
     */
    public function change_assistant_post()
    {
        $request_data = $this->check_param([
            'user_id' => ['用户ID', 'required', 'integer'],
            'customer' => ['客户目标数', 'integer'],
            'contact' => ['联系人目标数', 'integer'],
            'business' => ['商机目标数', 'integer'],
            'visit' => ['拜访客户数量', 'integer'],
            'sales' => ['销售记录', 'integer'],
            'contract' => ['合同数', 'integer'],
            'contract_money' => ['合同金额', 'numeric'],
            'payment' => ['回款', 'integer'],
            'task' => ['任务', 'integer'],
            'zd' => ['整单', 'integer'],
            'jz' => ['记账', 'integer'],
            'zf' => ['客服', 'integer'],
            'bs' => ['报税', 'integer'],
            'sd' => ['送单', 'integer'],
            'shd' => ['收单', 'integer'],
        ], [], 'post');
        $this->load->model('mbm/mbm_assistant');
        $request_data['month'] = date('Ym', strtotime('+1 month'));
        $where = ['user_id' => $request_data['user_id'], 'month' => $request_data['month']];
        $info = $this->mbm_assistant->get_one('*', $where);
        if ($info) {
            $this->mbm_assistant->edit($where, $request_data);
        } else {
            $this->mbm_assistant->add($request_data);
        }
        $this->returnData($info);
    }

    /**
     * 获取当月目标
     */
    public function info_assistant_post()
    {
        $request_data = $this->check_param([
            'user_id' => ['用户ID', 'required', 'integer'],
        ], [], 'post');
        $this->load->model('mbm/mbm_assistant');
        $where = ['user_id' => $request_data['user_id'], 'month' => date('Ym')];
        $info = $this->mbm_assistant->get_one('*', $where);
        $this->returnData($info);
    }

    private function getUserMu($time, $user_id)
    {
        $this->load->model('mbm/mbm_assistant');
        $userInfo['last'] = $this->mbm_assistant->get_one('*', ['user_id' => $user_id, 'month' => date('Ym', strtotime('-1 month'))]);
        $userInfo['this'] = $this->mbm_assistant->get_one('*', ['user_id' => $user_id, 'month' => date('Ym')]);;
        return $userInfo;
    }

    /**
     * 获取时间范围内的工作日
     */
    private function getWorkDay($time)
    {
        switch ($time) {
            case 4:
                $start_time = strtotime(date('Y-m-d', strtotime('-29 day')));
                $last_time = strtotime(date('Y-m-d') . ' 23:59:59');
                break;
            case 6:
                $start_time = strtotime(date('Y-m-d', strtotime(date('Y-m-1') . '-1 month')));
                $last_time = strtotime(date('Y-m-d', strtotime(date('Y-m-1') . '-1 day')) . ' 23:59:59');
                break;
            default:
                $start_time = strtotime(date('Y-m-1'));
                $last_time = strtotime(date('Y-m-d', strtotime(date('Y-m-1') . '+1 month -1 day')) . ' 23:59:59');
                break;
        }
        return $this->getDays($start_time, $last_time);
    }

    private function getLastWork()
    {
        $start_time = strtotime(date('Y-m-d', strtotime(date('Y-m-1') . '-1 month')));
        $last_time = strtotime(date('Y-m-d', strtotime(date('Y-m-1') . '-1 day')) . ' 23:59:59');
        return $this->getDays($start_time, $last_time);
    }

    private function getThisWork()
    {
        $start_time = strtotime(date('Y-m-1'));
        $last_time = strtotime(date('Y-m-d', strtotime(date('Y-m-1') . '+1 month -1 day')) . ' 23:59:59');
        return $this->getDays($start_time, $last_time);
    }

    /**
     * @param $start_time
     * @param $last_time
     * @return int
     */
    private function getDays($start_time, $last_time)
    {
        $days = [];
        $i = 1;
        $tr = true;
        while ($tr) {
            $timer = $start_time + 3600 * 24 * $i;
            if ($timer > $last_time) {
                break;
            }
            $num = date('N', $timer) - 2;
            if ($num >= -1 and $num <= 3) {
                $days[] = date('Y-m-d', $timer);
            }
            $i++;
        }
        return [count($days), $days];
    }

    /**
     * @param $list 根据时间分组数据
     * @param $day  天数
     * @return array  填充数据为空的数据
     */
    private function toList($list, $day, $lastTime = '')
    {
        $total = 0;
        if (count($list) == $day || empty($list)) {
            return array_reverse($list);
        }
        for ($i = 1; $i <= $day; $i++) {
            $dayList[] = $i;
        }
        foreach ($list as $v) {
            $haDay[] = $v['nday'];
            if (!empty($v['num'])) {
                $total += $v['num'];
            }
        }
        $dayList = array_diff($dayList, $haDay);
        foreach ($dayList as $v) {
            $lll[] = ['nday' => $v];
        }
        $list = array_merge($list, $lll);
        for ($i = $day; $i >= 1; $i--) {
            foreach ($list as $v) {
                if ($v['nday'] == $i) {
                    $sList[] = $v;
                    continue;
                }
            }
        }
        if (!empty($lastTime)) {
            foreach ($sList as $i => $item) {
                $l = $lastTime - 24 * 60 * 60 * ($item['nday'] - 1);
                $sList[$i]['time'] = date('Y-m-d', $l);
                if (empty($item['num'])) {
                    $sList[$i]['num'] = 0;
                }
            }
        }
        return [$sList, $total];
    }

    /**
     * @param $time time type //1-今日 2-昨日 3-近7日 4-近30日 5-本月 6-上个月
     * @return array start and end Time
     */
    private function getTime($time)
    {
        switch ($time) {
            case 1:
                $start_time = strtotime(date('Y-m-d'));
                $last_time = strtotime(date('Y-m-d') . ' 23:59:59');
                break;
            case 2:
                $start_time = strtotime(date('Y-m-d', strtotime('-1 day')));
                $last_time = strtotime(date('Y-m-d', strtotime('-1 day')) . ' 23:59:59');
                break;
            case 3:
                $start_time = strtotime(date('Y-m-d', strtotime('-6 day')));
                $last_time = strtotime(date('Y-m-d') . ' 23:59:59');
                break;
            case 4:
                $start_time = strtotime(date('Y-m-d', strtotime('-29 day')));
                $last_time = strtotime(date('Y-m-d') . ' 23:59:59');
                break;
            case 5:
                $start_time = strtotime(date('Y-m-1'));
                $last_time = strtotime(date('Y-m-d', strtotime(date('Y-m-1') . '+1 month -1 day')) . ' 23:59:59');
                break;
            case 6:
                $start_time = strtotime(date('Y-m-d', strtotime(date('Y-m-1') . '-1 month')));
                $last_time = strtotime(date('Y-m-d', strtotime(date('Y-m-1') . '-1 day')) . ' 23:59:59');
                break;
        }
        return [$start_time, $last_time];
    }

}
