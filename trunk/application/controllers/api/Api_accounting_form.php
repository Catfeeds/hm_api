<?php
// +----------------------------------------------------------------------
// | Title   : 记账报表
// +----------------------------------------------------------------------
// | Created : Henrick (me@hejinmin.cn)
// +----------------------------------------------------------------------
// | From    : Shenzhen wepartner network Ltd
// +----------------------------------------------------------------------
// | Date    : 2017/10/23 19:41
// +----------------------------------------------------------------------
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class Api_approves 审批管理
 */
class Api_accounting_form extends Apibase
{
    protected $cid = 1;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('khm/khm_customer');
        $this->model = $this->khm_customer;
        $user_info = $this->get_login_info();
        $this->cid = $user_info['cid'];
    }

    public function index_get(){  //客户管理
        $date = $this->input->get_post('date'); //开始日期
        $date = explode(' - ',$date);
        $start_date = strtotime($date[0].'-01');
        $end_date = strtotime($date[1].'-01');
        if($date[0]==$date[1]){
            $end_date = strtotime($date[0].'-01 +1 month');
        }

        if($start_date>$end_date){
            $this->returnError('日期格式不正确');
        }

        $res = $this->model->getDoYearCount($this->cid);

        $data['all_count'] = $res;

        $month = $this->_sum_month($start_date,$end_date);
        if($month>12){
            $this->returnError('只能查询一年内的之间数据');
        }

        if($date[0]==$date[1]){
            $month = 0;
        }else{
            $month = $month ? $month:0;
        }

        $line_data = $this->model->getList($start_date,$month,$this->cid);
        if($month){
            $month_key  = [];
            $month_data =[];
            $month_count= 0;
            for ($i=0; $i<=$month; $i++){
                $month_key[] = date("Y年m月",strtotime(date("Y-m-01",$start_date)." +$i month"));
                $month_data[] = $line_data[0]['count'.$i];
                $month_count+= $line_data[0]['count'.$i];
                $month_title = date("Y年m月",$start_date).'-'.date("Y年m月",$end_date).'客户统计图(总计：'.$month_count.'个)';
            }
        }else{
            $month_key = [date("Y年m月",strtotime(date("Y-m-01",$start_date)))];
            $month_data=[$line_data[0]['count0']];
            $month_count = $line_data[0]['count0'];
            $end_date = $start_date;
            $month_title = date("Y年m月",$start_date).'客户统计图(总计：'.$month_count.'个)';
        }
        $data['month_key'] = $month_key;
        $data['month_data']= $month_data;
        $data['month_count']=$month_count;
        $data['month_title']= $month_title;

        $data['series'] = [
            'taxtype_1_ctype' => $line_data[0]['taxtype_1_ctype'],
            'taxtype_1_ztype' => $line_data[0]['taxtype_1_ztype'],
            'taxtype_1_ctype_ztype' => $line_data[0]['taxtype_1_ctype_ztype'],
            'taxtype_2_ctype' => $line_data[0]['taxtype_2_ctype'],
            'taxtype_2_ztype' => $line_data[0]['taxtype_2_ztype'],
            'taxtype_1_ctype' => $line_data[0]['taxtype_1_ctype'],
            'taxtype_2_ctype_ztype' => $line_data[0]['taxtype_2_ctype_ztype'],
        ];

        $this->returnData($data);

    }

    public function get_other_table_get(){
        $request_data = $this->check_param([
            'type' => ['类型', 'required', 'integer'], //类型1-收单 2-整单 3-记账 4-客服 5-报税 6-送单
        ], [], 'get');
        $date = $this->input->get_post('date'); //开始日期
        $date = explode(' - ',$date);
        $start_date = strtotime($date[0].'-01');
        $end_date = strtotime($date[1].'-01');
        if($date[0]==$date[1]){
            $end_date = strtotime($date[0].'-01 +1 month');
        }

        if($start_date>$end_date){
            $this->returnError('日期格式不正确');
        }

        $this->load->model('jzm/jzm_service_info');
        $this->load->model('ckm/ckm_position');
        $this->load->model('ckm/ckm_position_num');
        $position = $this->ckm_position->get_all('id,name',[
            'cid' => $this->cid
        ]);

        $day = strtotime(date("Y-m-d"));
        $month = strtotime(date("Y-m-01"));
        $year  = strtotime(date("Y-01-01"));

        $count_where = [
            "sum(case when com_time>=$day THEN 1 ELSE 0 END) as day_count",
            "sum(case when com_time>=$month THEN 1 ELSE 0 END) as month_count",
            "sum(case when com_time>=$year THEN 1 ELSE 0 END) as year_count"
        ];

        $count_res = $this->jzm_service_info->get_all(implode(',',$count_where),[
            'cid' => $this->cid,
            'type'=> $request_data['type'],
            'status' => 2,
            'com_time>='=>date("Y-01-01"),
            'com_time<='=>date("Y-m-01 23:59:59"),
        ]);

        $month = $this->_sum_month($start_date,$end_date);
        if($month>12){
            $this->returnError('只能查询一年内的之间数据');
        }

        if($date[0]==$date[1]){
            $month = 0;
        }else{
            $month = $month ? $month:0;
        }

        $case_when=[];
        if($month>=1){
            for ($i=0; $i<=$month; $i++){
                $start_time = strtotime(date("Y-m-01",$start_date)." +$i month");
                $end_time   = strtotime(date("Y-m-01",$start_date)." + ".($i+1)." month");
                $case_when[] = "sum(case when com_time>=$start_time AND  com_time<$end_time THEN 1 ELSE 0 END) as count".$i;
            }
        }else{
            $end_time   = strtotime(date("Y-m-01",$start_date)." + 1 month");
            $case_when[] = "sum(case when com_time>=$start_date AND com_time<$end_time THEN 1 ELSE 0 END) as count0";
        }


        $line_data = $this->jzm_service_info->get_all(implode(',',$case_when),[
            'cid' => $this->cid,
            'type'=> $request_data['type'],
            'status' => 2,
            'com_time>='=>$start_date,
            'com_time<'=>$end_date,
        ]);

        $all_count = [];
        foreach ($count_res[0] as $key=>$val){
            $all_count[$key] = intval($val);
        }

        $data = [
            'all_count' => $all_count
        ];


        $title = '';
        switch ($request_data['type']){
            case 1:
                $title = '收单';
                break;
            case 2:
                $title = '整单';
                break;
            case 3:
                $title = '记账';
                break;
            case 4:
                $title = '客服';
                break;
            case 5:
                $title = '报税';
                break;
            case 6:
                $title = '送单';
                break;
        }

        if($month){
            $month_key  = [];
            $month_data =[];
            $month_count= 0;
            for ($i=0; $i<=$month; $i++){
                $month_key[] = date("Y年m月",strtotime(date("Y-m-01",$start_date)." +$i month"));
                $month_data[] = $line_data[0]['count'.$i];
                $month_count+= $line_data[0]['count'.$i];
                $month_title = date("Y年m月",$start_date).'-'.date("Y年m月",$end_date).$title.'统计图(总计：'.$month_count.'个)';
            }
        }else{
            $month_key = [date("Y年m月",strtotime(date("Y-m-01",$start_date)))];
            $month_data=[$line_data[0]['count0']];
            $month_count = $line_data[0]['count0'];
            $end_date = $start_date;
            $month_title = date("Y年m月",$start_date).$title.'统计图(总计：'.$month_count.'个)';
        }

        $data['month_key'] = $month_key;
        $data['month_data']= $month_data;
        $data['month_count']=$month_count;
        $data['month_title']= $month_title;

        //饼图数据
        $ckm_position_num = $this->ckm_position_num->getSeriesData($position,[
            'start_date' => $start_date,
            'end_date'   => $end_date
        ],$this->cid,$request_data['type']);
        $legend_data = [];
        $i=0;
        foreach ($ckm_position_num['legend_data'] as $val){
            $legend_data[] = [
                'value' => intval($val),
                'name'  => $ckm_position_num['legend_title'][$i]
            ];
            $i++;
        }

        $data['series'] = [
            'title'        => $title.'统计',
            'legend_title' => $ckm_position_num['legend_title'],
            'legend_data'  => $legend_data
        ];



        $this->returnData($data);
    }

    protected function _sum_month($start_date,$end_date){
        //计算月份
        $year = date("Y",$end_date)-date("Y",$start_date);
        if($year==0){
            $month_num = intval(date("m",$end_date))-intval(date("m",$start_date));
        }else{
            $month_num = (($year-1)*12)+(12-intval(date("m",$start_date)))+intval(date("m",$end_date));
        }

        return $month_num;
    }
}