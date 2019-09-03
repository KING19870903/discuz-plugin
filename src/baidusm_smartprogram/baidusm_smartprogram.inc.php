<?php
define('IN_ADMINCP', true);

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

define('DISCUZ_LIBRARY', DISCUZ_ROOT.'source/plugin/baidusm_smartprogram/library/');
define('DISCUZ_UTILS',  DISCUZ_LIBRARY.'utils/');

require_once DISCUZ_LIBRARY . 'require_library.php';    //引入共有函数

//定义全局变量
global $_G;

//允许访问的模块
$allMod = array(
    "forum",
    "forumfeed",
    "home",
    "space",
    "index",
    "user",
    "config",
    "attachment",
    "search",
);

//统一处理公参
$filter =   array(
    'id'        => 's',
    'mod'       => 's',
    'action'    => 's',
    'token'     => 's',
    'page'      => 'i',
    't'         => 'i',
    'sign'      => 's',
);

$params = request_params::filterInput($filter, $_GET);
$params['page'] = max(1, intval($params['page']));

//获取插件配置变量
$smartConfig    = $_G['cache']['plugin']['baidusm_smartprogram'];

initSysConst($smartConfig);                 //设置常量

require_once DISCUZ_LIBRARY . 'common.php';

try {
    if (!in_array($params['mod'], $allMod)) {
        throw new discuz_exception(error_plugin::ERROR_MOD_INVALID, $params['mod']);
    }

    $verifyToken = array('post', 'viewpost', 'delpost','mythread','userinfo','spacecplist','spacelist', 'spacecp', 'mythread', 'userinfo', 'attach');  //强制统一token校验
    if (in_array($params['action'], $verifyToken)) {
        checkToken($params, $_G);
    }

} catch (discuz_exception $e) {
    //统一报错处理
    $arrResonse = array(
        "errNo"     =>  $e->getCode(),
        "errMsg"    =>  $e->getMessage(),
        "data"      =>  array(),
    );

    //统一数据输出
    smart_core::outputJson($arrResonse);
}


include DISCUZ_ROOT . "./source/plugin/baidusm_smartprogram/module/{$params['mod']}.php";