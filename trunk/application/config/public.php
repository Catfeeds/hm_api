<?php

/*
 * 公共配置
 */

defined('BASEPATH') OR exit('No direct script access allowed');


//配置
$config['sex_list'] = [ '1' => '男', '2' => '女'];
$config['oauth2_tables'] = array(
    'client_table' => 'oauth_clients',
    'access_token_table' => 'oauth_access_tokens',
    'refresh_token_table' => 'oauth_refresh_tokens',
    'code_table' => 'oauth_authorization_codes',
    'user_table' => 'oauth_users',
    'jwt_table' => 'oauth_jwt',
    'jti_table' => 'oauth_jti',
    'scope_table' => 'oauth_scopes',
    'public_key_table' => 'oauth_public_keys',
);

