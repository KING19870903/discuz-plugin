<?php
/**
 * uninstall.php
 *
 * @description :   卸载
 *
 * @author : zhaoxichao
 * @since : 23/08/2019
 */
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

$sql = <<<EOF

DROP TABLE pre_swan_app_config;

DROP TABLE pre_login_token;



EOF;

runquery($sql);