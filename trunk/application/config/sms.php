<?php

$config['appkey'] = ENVIRONMENT != 'production' ? '23461809' : ''; //测试
$config['secretKey'] = 'fa616a7a0729cb97685ba76d9a74b82c';
$config['sms_type'] = 'normal'; //短信类型，传入值请填写normal
$config['sms_free_sign_name'] = '健健家'; //短信签名
$config['sms_template_code_vcode'] = 'SMS_15310002'; //短信模板ID
