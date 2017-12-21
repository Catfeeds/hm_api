<?php 

defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . '/controllers/api/Apibase.php';

/**
 * Class 验证登陆信息
 */
class Api_checklog extends Apibase
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('yz/logm_local');
        $this->model = $this->logm_local;
    }

    //验证用户名和密码
    function check_post(){
        $request_data = $this->check_param([ 
        	'LoginName' => ['用户名','required'], 
            'Password' => ['密码','required'],
        ], [], 'post');
        $user = [
        	'LoginName' =>$request_data['LoginName'],
        	'Password'=>$request_data['Password']
        ];
		@$local_data[] = $this->model->f7("*");

        var_dump($local_data['LoginName'] === $inputData['LoginName']);

die;
		$url = "http://test.smnf.cc/Api/Account/Login";
		$content = $this->request($url,false,'post',$user);
        if( $content ) {
        	 $res = $this->model->add($request_data);
        	 $this->session->set_userdata('NickName', $request_data['LoginName']);
        	 $this->session->set_userdata('pwd', $request_data['Password']);
        	$this->returnData($content);
        }else{
        	$this->returnData([]);
        }
    }
	function check_local($inputData){
		$local_data = $this->model->f7("*");
		if( $local_data['LoginName'] === $inputData['LoginName']   ){
			return true;
		}else{
			return false;
		}

	/*	if(){

		}*/
	}

//更新本地数据
	// $tt = ['LoginName' =>"171172198@qq.com",'Password'=>'171172198'];
	// $url = "http://test.smnf.cc/Api/Account/Login";
    //通过curl来获取数据
    /**  
	* 函数的含义说明 
	*  等会添加
	* @access public 
	* @param mixed $arg1 参数一的说明 
	* @param mixed $arg2 参数二的说明 
	* @param mixed $mixed 这是一个混合类型 
	* @return array 返回类型
	*/  
	function request($url,$https=false,$post='get',$data='null') 
	{
		//初始化curl
		$ch = curl_init();
		//设置基本参数
		//设置返回值不直接输出
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_URL,$url);
		
		if ($post == 'post') {
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		if ($https) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		}
		$content = curl_exec($ch);
		curl_close($ch);
		return $content;
	} 




}












 ?>