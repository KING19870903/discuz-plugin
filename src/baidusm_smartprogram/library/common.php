<?php
/**
 * common.php
 *
 * @description :   定义通用常量
 *
 * @author : zhaoxichao
 * @since : 28/05/2019
 */


if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$arrResonse = array(
    "errNo" => "0",
    "errMsg" => "",
    "data" => array()
);

//分页数量
const PAGENUM    = 20;

//附件目录
const ATTACHMENT = 'data/attachment/';

//common附件目录
const ATTACHCOMMON = 'data/attachment/common/';

//form附件目录
const ATTACHMENTFORM = 'data/attachment/forum/';

//token缓存时间7天
const EXPIRETIME = 604800;

//默认头像地址
const HEADIMG   = DOMAIN .'uc_server/images/noavatar_small.gif';

//默认UC_API
const UC_API    = DOMAIN . 'uc_server';

//小程序日志标识
const SWAN      = 'swan';