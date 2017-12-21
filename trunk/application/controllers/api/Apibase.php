<?php

/*
 * 基类
 * @author:jacky Version 1.0.0 2016-9-18
 */

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/libraries/REST_Controller.php';

/**
 * 所有API的基类
 */
class Apibase extends REST_Controller {

    public $model;
    public $server = NULL;
    public $loginData = [];
    public $inputData = [];

    function __construct()
    {

        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
        $rest_auth = strtolower($this->config->item('rest_auth')); //获取验证配置
        switch ($rest_auth) {
            case 'auth2':
                //$this->_prepare_oauth2_auth();
                break;
        }
        //$this->check_user_id();       
        $this->loginData = $this->get_login_info();


        //模拟登录
        //$this->loginData = ['cid' => 1, 'id' => 1, 'role_id' => 1];
    }

    /**
     * 校验用户有效性
     */
    public function check_user_id()
    {
        $request_data = $this->check_param([
            'uid' => ['用户ID', 'integer'],
                ], [], 'get_post');
        if (!empty($request_data['uid'])) {
            $this->load->model('um/um_user');
            $res = $this->um_user->get_one('uid', ['uid' => $request_data['uid']]);
            if (!$res) {
                $this->returnError('用户不存在,请检查');
            }
        }
    }

    /**
     * 限制域名+ip白名单
     */
    protected function early_checks()
    {
        /**
         * 1.获取h5请求api的内网ip地址
         * 2.获取访问的REMOTE_ADDR
         * 3.判断是否是内网访问api
         * 4.如果是内网访问api就将rest_auth 设置为basic auth
         */
        $allow_ips = $this->config->item('allow_ips');
        $allow_domain = $this->config->item('allow_domain');
        $access_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $access_domain = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        if (in_array($access_ip, $allow_ips) || in_array($access_domain, $allow_domain) || 'production' != ENVIRONMENT) {
            //$this->config->set_item('rest_auth', 'basic');
        }
    }

    /**
     * 具体执行oauth2验证的动作
     *
     * @access protected
     * @return void
     */
    protected function _prepare_oauth2_auth()
    {
        $this->server();
        if ('token' == $this->uri->segment(3)) {//获取token不走这个验证            
            return TRUE;
        }

        if (!$this->server->verifyResourceRequest(OAuth2\Request::createFromGlobals())) {
            $this->server->getResponse()->send();
            $response = new OAuth2\Response();
            exit;
        }
    }

    /**
     * 构造Ouath2 server
     */
    private function server()
    {
        $oauth2_tables_config = $this->config->item('oauth2_tables'); //获取配置表        
        $connection = ['dsn' => $this->db->dsn, 'username' => $this->db->username, 'password' => $this->db->password]; //获取数据库连接信息       
        OAuth2\Autoloader::register();
        //正式环境启用redis作为验证的存储
        if ('production' === ENVIRONMENT) {
            $this->load->driver('cache');
            $redis = $this->cache->redis->get_normal_redis();
            $storage = new OAuth2\Storage\Redis($redis);
        } else {
            $storage = new OAuth2\Storage\Pdo($connection, $oauth2_tables_config);
        }
        // 初始化server
        $this->server = new OAuth2\Server($storage);

        // 添加 "Client Credentials" 授权类型 (最简单的授权类型)
        $this->server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));
        // 添加 "RefreshToken" 授权类型 (最简单的授权类型)
        $this->server->addGrantType(new OAuth2\GrantType\RefreshToken($storage));
        // 添加 "Authorization Code" 授权类型 (this is where the oauth magic happens)
        $this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));
    }

    /**
     * 校验用户有效性
     * db grant_type: client_credentials
     */
    public function token_post()
    {
        $this->server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();
    }

    /**
     * CODE授权认证
     * db grant_type: authorization_code
     */
    public function authorize_post()
    {
        $request = OAuth2\Request::createFromGlobals();
        $response = new OAuth2\Response();

        // validate the authorize request
        if (!$this->server->validateAuthorizeRequest($request, $response)) {
            $response->send();
            die;
        }

        // print the authorization code if the user has authorized your client
        $is_authorized = ($_POST['authorized'] === 'yes');

        $this->server->handleAuthorizeRequest($request, $response, $is_authorized);
        if ($is_authorized) {
            // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
            $code = substr($response->getHttpHeader('Location'), strpos($response->getHttpHeader('Location'), 'code=') + 5, 40);
            exit("SUCCESS! Authorization Code: $code");
        }
        $response->send();
    }

    /**
     * 统一API参数检验方法
     * 调用示例 check_param(array('money' => array('required', 'integer', 'greater_than_equal_to[1]', 'less_than_equal_to[200]')));
     * @param   array $arr
     * @return  boolean
     */
    public function check_param($arr, $data = array(), $method = 'get')
    {
        /**
         * 设置要验证的请求数据
         */
        if (!empty($arr)) {
            $key_arr = array();
            $rule_arr = array();
            $tmp_arr = [];
            foreach ($arr as $key => $value) {
                $tmp_arr[$key] = array_shift($value);
                $temp_arr = explode(",", $key);
                if (!is_array($value)) {
                    $value = explode("|", $value);
                }
                $key_arr = array_merge($key_arr, $temp_arr);
                if (!empty($temp_arr)) {
                    foreach ($temp_arr as $temp_value) {
                        if (!empty($rule_arr[$temp_value])) {
                            $rule_arr[$temp_value] = array_merge($rule_arr[$temp_value], $value);
                        } else {
                            $rule_arr[$temp_value] = $value;
                        }
                    }
                }
            }
            $key_arr = array_unique($key_arr);
            if (!empty($rule_arr)) {
                foreach ($rule_arr as $rule_key => $rule_value) {
                    $rule_arr[$rule_key] = array_unique($rule_value);
                }
            }
        }
        if ($method === 'post' || $method === 'POST') {
            $request_data = $this->input->post($key_arr);
        } else {
            $request_data = $this->input->get($key_arr);
        }
        if ('get_post' == $method) {
            $request_data = [];
            foreach ($key_arr as $one_key) {
                $request_data[$one_key] = $this->input->get_post($one_key);
            }
        }
        if ('input' == $method) {//兼容新写法
            $request_data = $this->inputData;
        }
        //也可以直接传需要验证的数据过来
        if (is_array($method) && !empty($method))
            $request_data = $method;

        $this->form_validation->set_data($request_data);
        /**
         * 设置验证规则
         */
        if (!empty($rule_arr)) {
            foreach ($rule_arr as $rule_key => $rule_value) {
                $this->form_validation->set_rules($rule_key, '', $rule_value, array('required' => '%s 不能为空;'
                    , 'numeric' => '%s 必须是数字;'
                    , 'integer' => '%s 必须是数字;'
                    , 'regex_match' => '%s 格式有误;'
                    , 'greater_than' => '%s 有误;'
                    , 'max_length' => '%s 超过长度;'
                    , 'min_length' => '%s 长度不够;'
                ));
            }
        }
        /**
         * 开始验证
         */
        if (!$this->form_validation->run()) {
            //验证失败处理逻辑
            $errmsg = validation_errors(' ', ' ');
            if (!empty($tmp_arr)) {
                foreach ($tmp_arr as $arr_key => $arr_value) {
                    if ($arr_value) {
                        $errmsg = str_replace($arr_key, $tmp_arr[$arr_key], $errmsg);
                    }
                }
            }
            $this->returnError($errmsg . "请检查是否正确", '400');
            return FALSE;
        }
        return $request_data;
    }

    public function returnDataCache($data, $cache_time = 60, $msg = '', $code = self::HTTP_OK)
    {
        $method = $this->input->method();
        $key = md5(uri_string() . current_url() . implode('', $this->input->$method()));

        $cache_data = [
            'data' => $data,
            'msg' => $msg,
            'code' => $code,
            'return_fun' => 'returnData',
        ];
        $this->saveCache($key, serialize($cache_data), $cache_time);
        $this->returnData($data, $msg, $code);
    }

    protected function saveCache($key, $value, $cache_time = null)
    {
        if (is_null($cache_time))
            $cache_time = 3600 * 24 * 30; //默认30天
        return $this->getCacheOB()->save($key, $value, $cache_time);
    }

    protected function getCache($key)
    {
        return $this->getCacheOB()->get($key);
    }

    protected function getCacheOB()
    {
        $this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
        if ('testing' === ENVIRONMENT) {
            $this->load->driver('cache', array('adapter' => 'redis', 'backup' => 'file'));
        }

        return $this->cache;
    }

    /**
     * api返回数据格式
     * @param type $data
     * @param type $msg
     * @param type $code
     */
    public function returnData($data = [], $msg = '', $code = self::HTTP_OK)
    {
        $ret_data = array(
            'code' => $code . '',
            'msg' => $msg,
            'data' => $data,
        );
        $http_code = $code; //新的api让code跟http_code保持一致
        set_data_string($ret_data);
        $this->response($ret_data, $http_code, TRUE);
    }

    /**
     * api返回数据格式
     * @param type $data
     * @param type $msg
     * @param type $code
     */
    public function outData($data = [], $msg = '', $code = self::HTTP_OK)
    {
        $ret_data = array(
            'code' => 0,
            'msg' => $msg,
            'data' => $data,
            "count" => 0,
            'total' => 0,
            'page' => 1
        );

        $http_code = $code; //新的api让code跟http_code保持一致
        if (count($data) == 0) {
            $this->response($ret_data, $http_code, TRUE);
            return;
        }
        set_data_string($ret_data);
        $data['data'] = $data['rows'];
        $data['count'] = $data['records'];
        unset($data['rows']);
        unset($data['records']);
        $this->response($data, $http_code, TRUE);
    }

    /**
     * api返回数据格式
     * @param type $data
     * @param type $msg
     * @param type $code
     */
    public function returnData2($data = [], $msg = '', $code = self::HTTP_OK)
    {
        echo json_encode(['code' => $code . '', 'msg' => $msg, 'data' => $data]);
        exit;
    }

    /**
     * 获取数据失败提示2(上一个方法提供数据给Android有时候会返回异常数据)
     * @param type $msg
     * @param type $code
     */
    public function returnError($msg = '', $code = self::HTTP_BAD_REQUEST)
    {
        echo json_encode(['code' => $code . '', 'msg' => $msg, 'data' => []]);
        exit;
    }

    /**
     * 移动端分页列表数据接口 ,供子类参照或复写，不建议API直接调用此方法
     * 调用可参照Api_software/softwares_get方法
     */
    public function list_get()
    {
        /**
         * 获取要查询的SQL，复杂SQL通过复写或参照模型层getListSql方法
         */
        $sql = $this->model->getListSql("*", NULL);
        /**
         * 根据$sql, $order获取分页数据
         */
        $grid = $this->grid($sql, NULL);
        $this->returnData($grid);
    }

    /**
     * 移动端分页列表数据接口
     * @param type $sql
     * @return type
     */
    public function list_post($sql)
    {
        $request_data = $this->check_param(array("page" => array("integer", "greater_than_equal_to[1]"),
            "rows" => array("integer", "greater_than_equal_to[1]", "less_than_equal_to[1000]"),
            "filter" => array("max_length[100]"),
            "order" => array("max_length[100]"),
                ), [], $_SERVER['REQUEST_METHOD']);
        $request_data['filter'] = htmlspecialchars_decode($request_data['filter']);
        $request_data['order'] = htmlspecialchars_decode($request_data['order']);
        $grid = $this->model->list_do($sql, $request_data['filter'], $request_data['order'], $request_data['page'], $request_data['rows'], TRUE);
        return $grid;
    }

    /**
     * 获取列表数据
     * @param type $postData
     * @param type $isCount
     * @return type
     */
    public function getGrid($postData, $isCount)
    {
        $select = empty($postData['select']) ? NULL : $postData['select'];
        $page_size = empty($postData['limit']) ? 10000 : $postData['limit'];
        $page = empty($postData['page']) ? 1 : $postData['page'];
        $sidx = empty($postData['sidx']) ? NULL : $postData['sidx'];
        $sord = empty($postData['sord']) ? NULL : $postData['sord'];
        $filter = empty($postData['filter']) ? NULL : $postData['filter'];
        $order = empty($postData['order']) ? NULL : $postData['order'];
        $grid = $this->model->grid($select, $filter, $page, $page_size, $sidx, $sord, $order, $isCount);
        return $grid;
    }

    /**
     * 获取随机数
     */
    public function get_num($pre)
    {
        return $pre . date('ymds') . rand(100, 999);
    }

    /**
     * 获取订单编号
     */
    public function get_order_num()
    {
        return date('ymdHis') . rand(100, 999);
    }

    /**
     * 将图片编码写入图片文件
     * @param type $base64_string
     * @param type $output_file
     * @return type
     */
    function base64_to_img($base64_string, $output_file)
    {
        $ifp = fopen($output_file, "wb");
        fwrite($ifp, base64_decode($base64_string));
        fclose($ifp);
        return ($output_file);
    }

    /**
     * 生成随机数 （数字和字母）
     * @prams string $prefix 前缀
     * @prams string $suffix 后缀
     */
    function make_rand_code($pre = '', $suffix = '')
    {
        return $pre . base_convert(time() . substr(microtime(), 2, 6) . rand(10, 99), 10, 16) . $suffix;
    }

    public function _export($objPHPExcel, $colModel, $grid)
    {
        $table = $this->model->_table;
        for ($i = 0, $t = 0; $i < count($colModel); $i++) {
            $label = $colModel[$i]['label'];
            $name = $colModel[$i]['name'];
            $hidden = empty($colModel[$i]['hidden']) ? FALSE : $colModel[$i]['hidden'];
            $key = empty($colModel[$i]['key']) ? FALSE : $colModel[$i]['key'];
            $sorttype = empty($colModel[$i]['sorttype']) ? 'string' : $colModel[$i]['sorttype'];
            $width = empty($colModel[$i]['width']) ? 15 : $colModel[$i]['width'] / 8;
            $isEdit = empty($colModel[$i]['editable']) ? FALSE : $colModel[$i]['editable'];
            if ($isEdit && !empty($name) && strstr($name, $table . ".") == $name) {
                $isEdit = TRUE;
            } else {
                $isEdit = FALSE;
            }
            if ($hidden && !$key) {
                continue;
            }
            if ($key) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($t, 1, $label)->getStyleByColumnAndRow($t, 1)->getFont()->setBold(true)->getColor()->setRGB('FF0000');
            } else if ($isEdit) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($t, 1, $label)->getStyleByColumnAndRow($t, 1)->getFont()->setBold(true)->getColor()->setRGB('00FF00');
            } else {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($t, 1, $label)->getStyleByColumnAndRow($t, 1)->getFont()->setBold(true);
            }
            $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(20);
            $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($t)->setWidth($width);
            for ($j = 0; $j < count($grid); $j++) {
                $item = $grid[$j];
                $value = $item[$name];
                if ($sorttype === 'image' && !empty($value)) {
                    $url = $this->saveImage("statics/uploads/export_excel_images/", $value);
                    if (!$url || !isImage($url)) {
                        $objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($t, $j + 2, $value, PHPExcel_Cell_DataType::TYPE_STRING);
                        continue;
                    }
                    $img = new PHPExcel_Worksheet_Drawing();
                    $img->setName($name);
                    $img->setDescription($name);
                    $img->setPath($url);
                    $img->setWidth(40); //写入图片宽度
                    $img->setHeight(40); //写入图片高度
                    $img->setOffsetX(1); //写入图片在指定格中的X坐标值
                    $img->setOffsetY(1); //写入图片在指定格中的Y坐标值
                    $img->setRotation(1); //设置旋转角度
                    $img->getShadow()->setVisible(true);
                    $img->getShadow()->setDirection(50);
                    $columnLetter = PHPExcel_Cell::stringFromColumnIndex($t);
                    $coordinate = $columnLetter . ($j + 2);
                    $img->setCoordinates($coordinate); //设置图片所在表格位置
                    $img->setWorksheet($objPHPExcel->getActiveSheet()); //把图片写到当前的表格中
                } else if ($sorttype === 'datetime' && !empty($value)) {
                    if (strtotime($value)) {
                        $value = strtotime($value);
                    }
                    $value = date("Y-m-d H:m:s", $value);
                    $objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($t, $j + 2, $value, PHPExcel_Cell_DataType::TYPE_STRING);
                } else if ($sorttype === 'number') {
                    $objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($t, $j + 2, $value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                } else if ($sorttype === 'checkbox') {
                    $value = $colModel[$i]['checkbox'][$value];
                    $objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($t, $j + 2, $value, PHPExcel_Cell_DataType::TYPE_STRING);
                } else {
                    $objPHPExcel->getActiveSheet()->setCellValueExplicitByColumnAndRow($t, $j + 2, $value, PHPExcel_Cell_DataType::TYPE_STRING);
                }
            }
            $t++;
        }
    }

    /**
     * 获取登录信息
     */
    public function get_login_info()
    {
        $login_token = $this->input->get_post('login_token');
        $return_data = [];
        //var_dump($login_token);
        if (isset($login_token)) {
            $id = $this->getCache($login_token);
            //var_dump($id);
            $this->load->model('bmm/bmm_employees');
            $res = $this->bmm_employees->get_one('*', ['id' => $id]);
            array_filts($res, ['password'], false);
            $return_data = $res;
        }
        return $return_data;
    }

    /* -------------------------------------- 后台管理系统所用的方法 -------------------------------------- */

    /**
     * 统一编辑、新增，子类不要复写，直接供js api调用
     */
    public function update_or_add_post()
    {
        //加载缓存
        $this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
        //获取表单信息
        $modelInfo = $this->input->get_post('modelInfo');
        $modelInfo = urldecode($modelInfo);
        $modelInfo = json_decode($modelInfo, TRUE);
        //获取表单配置
        $colModel = $this->input->get_post('colModel');
        $colModel = urldecode($colModel);
        $colModel = json_decode($colModel, TRUE);
        $res = $this->update_or_add_do($modelInfo, $colModel);
        //writelog(['$modelInfo' => $modelInfo, '$colModel' => $colModel]);
        $this->returnData($res);
    }

    /**
     * 统一编辑、新增，子类不要复写
     */
    public function update_or_add_do($modelInfo, $colModel)
    {
        //加载缓存
        $this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));
        //获取更新主表
        $table = $this->model->_table;
        $key = $table . ".id";
        for ($i = 0; $i < count($colModel); $i++) {
            if (isset($colModel[$i]['key']) && $colModel[$i]['key'] == true) {
                $key = $colModel[$i]['name'];
            }
            if (!empty($colModel[$i]['name']) && !strstr($colModel[$i]['name'], $table . ".")) {
                unset($modelInfo[$colModel[$i]['name']]);
            }
        }
        $id = empty($modelInfo[$key]) ? "" : $modelInfo[$key];
        //提供接口处理表单信息，供子类复写
        $modelInfo = $this->before_update($modelInfo);
        //提供接口处理表单校验，供子类复写
        $is_ok = $this->update_check($modelInfo);
        //校验失败，返回统一错误码
        if ($is_ok !== TRUE) {
            $this->returnError($is_ok);
        }
        if (empty($id)) {
            //新增，处理
            unset($modelInfo[$key]);
            //$modelInfo[$table . '.create_at'] = date("Y-m-d H:i:s");
            $this->db->insert($table, $modelInfo);
            $id = $this->db->insert_id();
            $sql = $this->db->last_query();
        } else {
            //编辑，处理
            $this->db->where($key, $id);
            $this->db->update($table, $modelInfo);
            $num = $this->db->affected_rows();
            $sql = $this->db->last_query();
        }
        $res = array("sql" => $sql, "id" => $id);
        //删除缓存
        $this->cache->delete('f7_get_' . $table);
        $this->cache->delete('Home.index.line_data');
        $modelInfo[$key] = $id;
        $modelInfo = $this->after_update($modelInfo);
        return $res;
    }

    /**
     * 编辑或新增前操作，供子类复写
     * @param type $modelInfo
     * @return type
     */
    public function before_update($modelInfo)
    {
        return $modelInfo;
    }

    /**
     * 编辑或新增后操作，供子类复写
     * @param type $modelInfo
     * @return type
     */
    public function after_update($modelInfo)
    {
        return $modelInfo;
    }

    /**
     * 编辑或新增前数据校验，供子类复写
     * @param type $modelInfo
     * @return boolean
     */
    public function update_check($modelInfo)
    {
        return TRUE;
    }

    /**
     * 删除数据
     */
    public function delete_post()
    {
        $filter = $this->input->get_post('id', FALSE);
        $table = $this->model->_table;
        $ret = $this->db->delete($table, $filter);
        $this->returnData($ret);
    }

    /**
     * 获取附件，子类不要复写
     */
    public function file_get()
    {
        $relation_table = $this->input->get_post('relation_table');
        $relation_id = $this->input->get_post('relation_id');
        $type = $this->input->get_post('type');
        $this->db->select("*");
        $this->db->from('am_attachment');
        if (!empty($relation_table)) {
            $this->db->where("am_attachment.relation_table", $relation_table);
        }
        if (!empty($relation_id)) {
            $this->db->where("am_attachment.relation_id", $relation_id);
        }
        if (!empty($type)) {
            $this->db->where("am_attachment.type", $type);
        }
        $this->db->order_by("am_attachment.create_at desc");
        $query = $this->db->get();
        $rows = (!$query->num_rows()) ? NULL : $query->result_array();
        $this->returnData($rows);
    }

    /**
     * 获取附件链接，子类不要复写
     * @return boolean
     */
    public function attachment_get()
    {
//        $this->check_admin_id();
        /**
         * 文件名不要用中文
         * $path = "/uploads/20151109/a.doc";
         * echo base64_encode($path);
         * http://william.ejiuzhen.net/api/user/attachment?f=L3VwbG9hZHMvMjAxNTExMDkvYS5kb2M=
         *                                                   Qzovd2FtcC93d3cvZWppdXpoZW4vYXBwbGljYXRpb24vdXBsb2Fkcy8xNTExMTAvOWY1YzM3YWFkODc5ZjkxOTcxYWU0NjkyOGRhMTczMWIucG5n
         */
        $f = $this->input->get('f');
        $f = str_replace(' ', '+', $f);
        $f = base64_decode($f);

        if (!$f) {
            return FALSE;
        }
        $PE_imgpath = APPPATH . $f;
        if (!file_exists($PE_imgpath)) {
            return FALSE;
        }
        $PE_imgarray = pathinfo($PE_imgpath);
        $PE_imgarray["extension"] = strtolower($PE_imgarray["extension"]);
        switch ($PE_imgarray["extension"]) {
            case 'jpeg':
            case 'jpg':
            case 'png':
            case 'gif':
                $iconcontent = file_get_contents($PE_imgpath);
                header("Content-type: image/" . $PE_imgarray["extension"]);
                header('Content-length: ' . strlen($iconcontent));
                echo $iconcontent;
                break;
            case 'doc';
            case 'docx';
            case 'pdf';
                $this->downloadFile($PE_imgpath);
            default:
                break;
        }
    }

    /**
     * 下载文件
     * @param type $fullPath
     */
    function downloadFile($fullPath)
    {
        // Must be fresh start
        if (headers_sent()) {
            die('Headers Sent');
        }
        // Required for some browsers
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        // File Exists?
        if (file_exists($fullPath)) {
            // Parse Info / Get Extension
            $fsize = filesize($fullPath);
            $path_parts = pathinfo($fullPath);
            $ext = strtolower($path_parts["extension"]);
            // Determine Content Type
            switch ($ext) {
                case "pdf": $ctype = "application/pdf";
                    break;
                case "exe": $ctype = "application/octet-stream";
                    break;
                case "zip": $ctype = "application/zip";
                    break;
                case "doc": $ctype = "application/msword";
                    break;
                case "xls": $ctype = "application/vnd.ms-excel";
                    break;
                case "ppt": $ctype = "application/vnd.ms-powerpoint";
                    break;
                case "gif": $ctype = "image/gif";
                    break;
                case "png": $ctype = "image/png";
                    break;
                case "jpeg":
                case "jpg": $ctype = "image/jpg";
                    break;
                default: $ctype = "application/force-download";
            }
            $full_name = $path_parts['filename'] . '.' . $path_parts['extension'];
            header("Content-type: application/octet-stream");
            header("Accept-Ranges: bytes");
            header("Accept-Length: " . $fsize);
            header('Content-Disposition: attachment; filename=' . $full_name);
            header("Pragma:no-cache");
            header("Expires:0");
            ob_clean();
            flush();
            readfile($fullPath);
        } else
            die('File Not Found');
    }

    public function attachment_post()
    {
        $dir = $this->input->get_post('dir');
        $files = $this->input->get_post('files_base64_string');
        $receive_file_name = $this->input->get_post('files_name');
        //writelog(array("attachment_______________dir" => $dir, "files" => $files));
        if (!empty($receive_file_name)) {
            $file_ext = strrchr($receive_file_name, '.');
            $temp_arr = strstr($receive_file_name, $file_ext, true);
            $cur_path = $dir . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR;
            //$savePath = 'statics' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $cur_path;
            $savePath = 'resource' . DIRECTORY_SEPARATOR . 'front' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $cur_path;
            if (!is_dir($savePath)) {
                $mkres = @mkdir($savePath, 0777, TRUE);
                writelog(array("attachment__________mkres" => $mkres, "savePath" => $savePath));
            }
            $file_name = $this->make_rand_code() . $file_ext;
            $file_path = FCPATH . $savePath . $file_name;
            if (!$this->base64_to_img($files, $file_path)) {
                writelog(["上传失败" => $file_path]);
            }
            $http_url = base_url() . $savePath . $file_name;
            $http_url = str_replace("\\", "/", $http_url);
            $is_image = isImage($file_path);
            $res = array("url" => $http_url, "real_path" => $file_path, "is_image" => $is_image, "file_ext" => $file_ext, "files" => '');
            $this->returnData($res);
        }
    }

    /**
     * 输入表名 获取所有字段
     */
    public function getCols($table)
    {
        $rescolumns = $this->db->query("SHOW FULL COLUMNS FROM {$table}");
        $row = $rescolumns->result_array();
        $select = [];
        foreach ($row as $i => $v) {
            $select[] = "{$table}.{$v['Field']} as '{$table}.{$v['Field']}'";
        }
        return $select;
    }

    /**
     * 统一获取数据列表，子类不要复写
     */
    public function info_post()
    {
        $select = $this->input->get_post('select');
        $filter = $this->input->get_post('filter');
        $order = $this->input->get_post('order');
        $info = $this->model->info($select, $filter, $order);
        $info = $this->afterInfo($info);
        $this->returnData($info);
    }

    public function afterInfo($info)
    {
        return $info;
    }

    public function grid_post()
    {
        $select = $this->input->get_post('select'); //查询字段
        $page_size = $this->input->get_post('limit'); //每页显示多少条
        $page = $this->input->get_post('page'); //第几页
        $sidx = $this->input->get_post('sidx');
        $sord = $this->input->get_post('sort');
        $filter = $this->input->get_post('filter'); //查询条件
        $order = $this->input->get_post('order'); //排序
        $grid = $this->model->grid($select, $filter, $page, $page_size, $sidx, $sord, $order);
        //$this->response($grid, self::HTTP_OK);        
        $this->returnData($grid);
    }

    public function f7_post()
    {
        $select = $this->input->get_post('select'); //查询字段
        $filter = $this->input->get_post('filter'); //查询条件
        $order = $this->input->get_post('order'); //排序
        $grid = $this->model->f7($select, $filter, $order);
        //$this->response($grid, self::HTTP_OK);
        $this->returnData($grid);
    }

}
