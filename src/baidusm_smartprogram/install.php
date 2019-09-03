<?php
/**
 * install.php
 *
 * @description : 新建数据库
 *
 * @author : zhaoxichao
 * @since : 07/08/2019
 */
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
    exit('Access Denied');
}

// 新建数据库
$sql = <<<EOF

CREATE TABLE IF NOT EXISTS `pre_swan_app_config` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `ad_config` text NOT NULL,
  `is_effect` tinyint(1) NOT NULL DEFAULT '1',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
);


CREATE TABLE IF NOT EXISTS `pre_login_token` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `uid` mediumint(8) unsigned NOT NULL,
  `username` char(15)  NOT NULL,
  `token` varchar(150)  NOT NULL,
  `is_effect` tinyint(1) NOT NULL DEFAULT '1',
  `expire_time` int(10) unsigned NOT NULL,
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
);

EOF;

runquery($sql);