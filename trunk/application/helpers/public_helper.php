<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 *  获取页面完整url
 */
function get_web_url()
{
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? safe_replace($_SERVER['PHP_SELF']) : safe_replace($_SERVER['SCRIPT_NAME']);
    $path_info = isset($_SERVER['PATH_INFO']) ? safe_replace($_SERVER['PATH_INFO']) : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? safe_replace($_SERVER['REQUEST_URI']) : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . safe_replace($_SERVER['QUERY_STRING']) : $path_info);
    return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
}

/**
 * 字符过滤url
 */
function safe_replace($string)
{
    $string = str_replace('%20', '', $string);
    $string = str_replace('%27', '', $string);
    $string = str_replace('%2527', '', $string);
    $string = str_replace('*', '', $string);
    $string = str_replace('"', '&quot;', $string);
    $string = str_replace("'", '', $string);
    $string = str_replace('"', '', $string);
    $string = str_replace(';', '', $string);
    $string = str_replace('<', '&lt;', $string);
    $string = str_replace('>', '&gt;', $string);
    $string = str_replace("{", '', $string);
    $string = str_replace('}', '', $string);
    $string = str_replace('\\', '', $string);
    return $string;
}

/**
 * 是否为手机
 * @return boolean
 */
function _is_mobile()
{
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $mobile_agents = Array("240x320", "acer", "acoon", "acs-", "abacho", "ahong", "airness", "alcatel", "amoi", "android", "anywhereyougo.com", "applewebkit/525", "applewebkit/532", "asus", "audio", "au-mic", "avantogo", "becker", "benq", "bilbo", "bird", "blackberry", "blazer", "bleu", "cdm-", "compal", "coolpad", "danger", "dbtel", "dopod", "elaine", "eric", "etouch", "fly ", "fly_", "fly-", "go.web", "goodaccess", "gradiente", "grundig", "haier", "hedy", "hitachi", "htc", "huawei", "hutchison", "inno", "ipad", "ipaq", "ipod", "jbrowser", "kddi", "kgt", "kwc", "lenovo", "lg ", "lg2", "lg3", "lg4", "lg5", "lg7", "lg8", "lg9", "lg-", "lge-", "lge9", "longcos", "maemo", "mercator", "meridian", "micromax", "midp", "mini", "mitsu", "mmm", "mmp", "mobi", "mot-", "moto", "nec-", "netfront", "newgen", "nexian", "nf-browser", "nintendo", "nitro", "nokia", "nook", "novarra", "obigo", "palm", "panasonic", "pantech", "philips", "phone", "pg-", "playstation", "pocket", "pt-", "qc-", "qtek", "rover", "sagem", "sama", "samu", "sanyo", "samsung", "sch-", "scooter", "sec-", "sendo", "sgh-", "sharp", "siemens", "sie-", "softbank", "sony", "spice", "sprint", "spv", "symbian", "tablet", "talkabout", "tcl-", "teleca", "telit", "tianyu", "tim-", "toshiba", "tsm", "up.browser", "utec", "utstar", "verykool", "virgin", "vk-", "voda", "voxtel", "vx", "wap", "wellco", "wig browser", "wii", "windows ce", "wireless", "xda", "xde", "zte");
    $is_mobile = false;
    foreach ($mobile_agents as $device) {
        if (stristr($user_agent, $device)) {
            $is_mobile = true;
            break;
        }
    }
    return $is_mobile;
}

/**
 * 判断是否是微信客户端
 * @return boolean
 */
function is_weixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return TRUE;
    }
    return FALSE;
}

/**
 * 判断文件是否为图片
 * @param type $filename
 * @return boolean
 */
function isImage($filename)
{
    error_reporting(0);
    ini_set('display_errors', 1);
    $types = '.gif|.jpeg|.png|.bmp'; //定义检查的图片类型
    if (file_exists($filename)) {
        try {
            $info = getimagesize($filename);
            $ext = image_type_to_extension($info['2']);
            return stripos($types, $ext);
        } catch (Exception $e) {
            return FALSE;
        }
    } else {
        return FALSE;
    }
}

/**
 * 写日志
 * 调用方式   writelog(['msg'=>'说明文字','order_info'=>[]]);
 * @param type $data
 */
function writelog($data = [])
{
    $dir = APPPATH . "logs/";
    if (!is_dir($dir)) {
        @mkdir($dir);
    }
    $logname = $dir . date("Ymd") . ".log";
    $date = date("Y-m-d H:i:s") . "=>";
    $debug_backtrace = debug_backtrace();
    $res = [
        'file' => $debug_backtrace[0]['file'],
        'line' => $debug_backtrace[0]['line'],
        'class' => isset($debug_backtrace[1]['class']) ? $debug_backtrace[1]['class'] : '',
        'func' => isset($debug_backtrace[1]['function']) ? $debug_backtrace[1]['function'] : '',
        //'args' => isset($debug_backtrace[0]['args']) ? $debug_backtrace[0]['args'] : '',
    ];
    $res = array_merge($res, $data);
    file_put_contents($logname, $date . var_export($res, TRUE) . "\r\n", FILE_APPEND);
}

/**
 * 格式化日期
 * @param type $time
 */
function formatDateStr($time)
{
    $time = (int)$time;
    if ($time >= strtotime(date('Y-m-d'))) {
        return '今天 ' . date('H:i:s', $time);
    } else if (($time >= (strtotime(date('Y-m-d') . '00:00:00') - 86400)) && ($time < (strtotime(date('Y-m-d') . '23:59:59') - 86400))) {
        return '昨天 ' . date('H:i:s', $time);
    } else if (($time >= (strtotime(date('Y-m-d') . '00:00:00') - 86400 * 2)) && ($time < (strtotime(date('Y-m-d') . '23:59:59') - 86400 * 2))) {
        return '前天 ' . date('H:i:s', $time);
    } else {
        return date("Y-m-d H:i:s", $time);
    }
}

/**
 * 生成guid
 * @return string
 */
function guid()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((double)microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = chr(123)// "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125); // "}"
        return $uuid;
    }
}

// 将手机号中间位用“*”号代替，用作昵称
function get_mobile_nickname($mobile)
{
    return substr($mobile, 0, 4) . '****' . substr($mobile, -2);
}

// 获取客户端ip
function _get_ip()
{
    if (isset($_SERVER['HTTP_CLIENT_IP']) && strcasecmp($_SERVER['HTTP_CLIENT_IP'], "unknown")) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], "unknown")) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        return $ip;
    } else if (isset($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else if (isset($_SERVER['REMOTE_ADDR']) && isset($_SERVER['REMOTE_ADDR']) && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = "";
    }
    return ($ip);
}

// 获取ip + 地址
function _get_ip_dizhi()
{
    /**
     * 百度查询结果
     */
    $baidu_ret = _get_ip_dizhi_by_baidu();
    if (!empty($baidu_ret)) {
        return $baidu_ret;
    }
    $opts = array(
        'http' => array(
            'method' => "GET",
            'timeout' => 5,)
    );
    $context = stream_context_create($opts);
    $ipmac = _get_ip();
    if (strpos($ipmac, "127.0.0.") === true)
        return '';
    $url_ip = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ipmac;
    $str = @file_get_contents($url_ip, false, $context);
    if (!$str)
        return "";
    $json = json_decode($str, true);
    if ($json['code'] == 0) {
        $ipcity = $json['data']['region'] . $json['data']['city'];
        $ip = $ipcity . ',' . $ipmac;
    } else {
        $ip = "";
    }
    return $ip;
}

// 通过百度接口获取ip + 地址
function _get_ip_dizhi_by_baidu($ipmac = NULL)
{
    if (NULL === $ipmac) {
        $ipmac = _get_ip();
    }

    //百度api的key
    $ak_array = ['4U7sWTgMZfXl371U2hvmnEO4', 'ytLeyXLISVu4VTGn7ExnI3zL', '8h0FthsqlebfLU4wQAKvVpMR', 'kSKxAm0cbiRtX3uZDBCxg9Rp', 'ERP1cTpdbwrlzVOuo6LOxCQN'];
    shuffle($ak_array);

    $opts = array(
        'http' => array(
            'method' => "GET",
            'timeout' => 5,)
    );
    $context = stream_context_create($opts);
    if (strpos($ipmac, "127.0.0.") === true) {
        return '';
    }
    $url_ip = 'http://api.map.baidu.com/location/ip?ak=' . $ak_array[0] . '&ip=' . $ipmac . '&coor=bd09ll';
    $str = @file_get_contents($url_ip, false, $context);
    if (!$str) {
        return "";
    }
    $json = json_decode($str, true);
    if ($json['status'] == 0) {
        $ipcity = $json['content']['address'];
        $ip = $ipcity . ',' . $ipmac;
    } else {
        $ip = "";
    }
    return $ip;
}

/**
 * 将一个数组的元素强制转为string类型
 * @param array $data
 */
function set_data_string(&$data)
{
    foreach ($data as &$v) {
        if (!is_array($v)) {
            settype($v, 'string');
        } else {
            set_data_string($v);
        }
    }
}

/**
 * 生成随机数 （数字英文字母组合）
 * @prams string $prefix 前缀
 * @prams string $suffix 后缀
 */
function get_randdata($pre = '', $suffix = '', $upper = false)
{
    $rand = substr(microtime(), 2, 6);
    $str = $pre . base_convert(time() . $rand . rand(10, 99), 10, 16) . $suffix;
    if ($upper == true) {
        return strtoupper($str);
    } else {
        return $str;
    }
}

/**
 * 生成优惠券规则 (32位)
 * @param type $pre
 * @param type $suffix
 * @param type $upper
 * @return string
 */
function get_coupon_code($pre = '', $suffix = '', $upper = false)
{
    return md5(get_randdata($pre, $suffix, $upper));
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
 * 过滤数组通用方法
 * @param type $org_arr
 * @param type $filter_key
 * @param type $keep
 * @return type mixed
 */
function array_filts(&$org_arr, $filter_key, $keep = true)
{
    if (empty($org_arr)) {
        return false;
    }
    if (empty($filter_key) || !is_array($filter_key)) {
        return $org_arr;
    }
    foreach ($org_arr as $k => &$v) {
        if (!is_array($v)) {
            if ($keep == true) {
                if (in_array($k, $filter_key, true)) {
                    continue;
                } else {
                    unset($org_arr[$k]);
                }
            } else {
                if (in_array($k, $filter_key, true)) {
                    unset($org_arr[$k]);
                }
            }
        } else {
            array_filts($v, $filter_key, $keep);
        }
    }
    return $org_arr;
}

/**
 * 检测数组是几维数组
 * @param type $array
 * @return type
 */
function array_depth($array)
{
    $max_depth = 1;
    foreach ($array as $value) {
        if (is_array($value)) {
            $depth = array_depth($value) + 1;
            if ($depth > $max_depth) {
                $max_depth = $depth;
            }
        }
    }
    return $max_depth;
}

function objectToArray($obj)
{
    $arr = is_object($obj) ? get_object_vars($obj) : $obj;
    if (is_array($arr)) {
        return array_map(__FUNCTION__, $arr);
    } else {
        return $arr;
    }
}

/**
 * curl 获取数据
 * @param type $url
 * @param type $data
 * @param type $method
 * @param type $author
 * @return type
 */
function curl_postdata($url, $data = NULL, $method = "GET", $author = NULL)
{
    $timeout = 60 * 5;
    ini_set('max_execution_time', $timeout);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => $timeout, //设置超时时间为5分钟
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => empty($author) ? array() : $author,
        CURLOPT_USERAGENT => '', //代理
    ));
    $result = curl_exec($curl);
    $error = curl_error($curl);
    $curlinfo = curl_getinfo($curl);
    curl_close($curl);
    return array("result" => $result, "error" => $error, "code" => $curlinfo['http_code'], "curlinfo" => $curlinfo);
}

/**
 * xml转数组
 */
function xmlToArray($xml)
{
    libxml_disable_entity_loader(true);
    $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    $arr = json_decode(json_encode($xmlstring), true);
    return $arr;
}

/**
 * 无线及分类 子集
 * @param $list 数据库数据
 * @param string $pk 主键字段
 * @param string $pid 关联字段
 * @param string $child 子集名称
 * @param int $init 初始id
 * @return array
 */
function makeTree($list, $pk = 'id', $pid = 'parent', $child = '_child', $init = 0)
{
    $tree = array();
    foreach ($list as $key => $val) {
        if ($val[$pid] == $init) {
            //获取当前$pid所有子类
            unset($list[$key]);
            if (!empty($list)) {
                $child = makeTree($list, $pk, $pid, $child, $val[$pk]);
                if (!empty($child)) {
                    $val['_child'] = $child;
                }
            }
            $tree[] = $val;
        }
    }
    return $tree;
}

/**
 * 无线及分类 同级集
 * @param $list 数据库数据
 * @param string $pk 主键字段
 * @param string $pid 关联字段
 * @param string $rank 等级名称
 * @param int $init 初始id
 * @param int $initRank 初始等级
 * @return array
 */
function getTree($list, $pk = 'id', $pid = 'parent', $rank = 'rank', $init = 0, $initRank = 1)
{
    static $tree;
    foreach ($list as $key => $val) {
        if ($val[$pid] == $init) {
            $val[$rank] = $initRank;
            $tree[] = $val;
            unset($list[$key]);
            getTree($list, $pk, $pid, $rank, $val[$pk], $initRank + 1);
        }
    }
    return $tree;
}

/**
 * 生成订单号（数字）
 * @prams string $prefix 前缀
 * @prams string $suffix 后缀
 */
function make_order_code($pre = 'C', $suffix = '')
{
    return $pre . time() . substr(microtime(), 2, 6) . rand(0, 9) . $suffix;
}

/**
 * 生成固定的数字号码
 * @param type $str
 * @param type $length
 * @return type
 */
function get_number_code($str, $length = 8, $fill_str = '0')
{
    $code = preg_replace('/[^\d]/', '', md5($str));
    if (strlen($code) >= $length) {
        $code = substr($code, 0, $length);
    } else {
        $code = str_pad($code, $length, $fill_str, STR_PAD_RIGHT);
    }
    return $code;
}

/**
 * 读取excel
 */
function readExcel($file, $ext = '')
{
    require_once APPPATH . '/third_party/PHPExcel.php';

    if (!file_exists($file)) {
        exit("not found " . $file . ".\n");
    }
    if (!$ext) {
        $fileExplode = explode('.', $file);
        $ext = $fileExplode[1];
    }
    if ($ext == 'xls') {
        $reader = PHPExcel_IOFactory::createReader('Excel5'); //设置以Excel5格式(Excel97-2003工作簿)
    } elseif ($ext == 'xlsx') {
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
    } else {
        return false;
    }
    $PHPExcel = $reader->load($file); // 载入excel文件
    $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
    $highestRow = $sheet->getHighestRow(); // 取得总行数
    $colsNum = $sheet->getHighestColumn(); // 取得总列数
    $highestColumm = PHPExcel_Cell::columnIndexFromString($colsNum); //字母列转换为数字列 如:AA变为27
    $result = array();
    /** 循环读取每个单元格的数据 */
    for ($row = 1; $row <= $highestRow; $row++) {//行数是以第1行开始
        for ($column = 0; $column < $highestColumm; $column++) {//列数是以第0列开始
            $columnName = PHPExcel_Cell::stringFromColumnIndex($column);
            $cell = $sheet->getCellByColumnAndRow($column, $row)->getCalculatedValue();
            if ($cell instanceof PHPExcel_RichText) {
                $cell = $cell->__toString();
            }
            $result[$row][$columnName] = $cell;
        }
    }
    return $result;
}
