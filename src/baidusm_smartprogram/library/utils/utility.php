<?php

/**
 * formTimestamp
 * @description :   格式化日期
 *
 * 示例: 05-23
 *
 * @param $timestamp
 * @return bool|string
 * @author zhaoxichao
 * @date 30/05/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

function formTimestamp($timestamp) {
    return date('m-d', $timestamp);
}

/**
 * formTimestampNature
 * @description :
 *
 * @param $targetTime
 * @return string
 * @author zhaoxichao
 * @date 30/05/2019
 */
function formTimestampNature($targetTime)
{
    // 今天最大时间
    $todayLast   = strtotime(date('Y-m-d 23:59:59'));
    $agoTimeTrue = time() - $targetTime;
    $agoTime     = $todayLast - $targetTime;
    $agoDay      = floor($agoTime / 86400);

    if ($agoTimeTrue < 60) {
        $result = '刚刚';
    } elseif ($agoTimeTrue < 3600) {
        $result = (ceil($agoTimeTrue / 60)) . '分钟前';
    } elseif ($agoTimeTrue < 3600 * 12) {
        $result = (ceil($agoTimeTrue / 3600)) . '小时前';
    } elseif ($agoDay == 0) {
        $result = '今天 '  ;
    } elseif ($agoDay == 1) {
        $result = '昨天 '  ;
    } elseif ($agoDay == 2) {
        $result = '前天 '  ;
    } elseif ($agoDay > 2 && $agoDay < 16) {
        $result = $agoDay . '天前 '  ;
    } else {
        $format = date('Y') != date('Y', $targetTime) ? "m-d" : "m-d H:i";
        $result = date($format, $targetTime);
    }
    return $result;
}

/**
 * paging
 * @description :   分页
 *
 * @param     $pageInfo
 * @param int $page
 * @param int $pagenum
 * @return array
 * @author zhaoxichao
 * @date 21/06/2019
 */
function   paging($pageInfo, $page = 1, $pagenum = PAGENUM) {
    $intOffset = ($page - 1) * $pagenum;

    $hasMore = (($intOffset + $pagenum) < count($pageInfo)) ? true : false;
    $pageInfo = array_slice($pageInfo, $intOffset, $pagenum);

    return  array($pageInfo, $hasMore);
}

/**
 * insertAdvert
 * @description :   插入广告数据
 *
 * 插入位置和扯落示例:
 * 1. feed  	- 首页信息流			策略: 3+12n
 * 2. forum 	- 版块落地页信息流   	策略: 3+12n
 * 3. thdend 	- 帖子落地页			策略: 正文底部
 * 4. thdreply  - 帖子落地页			策略: 回复区5+10n
 *
 *
 * @param     $arr      待插入数据
 * @param     $type     广告插入标识
 * @param int $postion  插入的广告排序
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 04/06/2019
 */
function insertAdvert($arr, $type, $postion = 1) {
    $sql = 'select ad_config from '.DB::table('swan_app_config').' where is_effect = 1 order by update_time desc limit 1';
    $arrAd = DB::fetch_all($sql);
    if (empty($arrAd)) {
        return  $arr;
    }

    switch ($type) {
        case 'feed':
            $arrRet = insertAdvertFeed($arr, $arrAd, $postion);
            break;
        case 'forum':
            $arrRet = insertAdvertFeed($arr, $arrAd, $postion);
            break;
        case 'thdend':
            $arrRet = insertAdvertThdend($arr, $arrAd, $postion);
            break;
        case 'thdreply':
            $arrRet = insertAdvertThdreply($arr, $arrAd, $postion);
            break;
        default:
            throw new discuz_exception(error_plugin::ERROR_ADVERT_TYPE);
    }


    return $arrRet;
}

/**
 * insertAdvertFeed
 * @description :   插入广告数据
 *
 * @param $arr
 * @param $arrAd
 * @param $postion
 * @return array
 * @author zhaoxichao
 * @date 05/06/2019
 */
function insertAdvertFeed($arr, $arrAd, $postion) {
    $arrRet = array();

    $adJson = base64_decode($arrAd[0]['ad_config']);
    $asArr  = json_decode($adJson, true);
    foreach ($asArr as $key => $ad) {
        //过滤非法数据
        if (empty($ad['code'])) {
            continue;
        }
        $ad['isAd'] = true;
        $adValid[] = $ad;   //合法的广告数据
    }

    $count  = count($adValid);
    if ($count >= $postion) {
        $pos = $postion - 1;
    } else {
        $remain = $postion % $count;
        $pos    = $remain -1;
    }

    foreach ($arr as $k => $value) {
        //插入广告数据
        if (2 == $k) {
            $arrRet[] = $adValid[$pos];
        }

        if ($k > 2 && ($k - 2)%12 == 0) {
            $pos = $pos + 1;
            if ($count >= $pos) {
                $pos = $pos - 1;
            } else {
                $remain = $pos % $count;
                $pos    = $remain -1;
            }
            $arrRet[] = $adValid[$pos];
        }

        //插入正常数据
        $arrRet[] = $value;
    }

    return  $arrRet;
}

/**
 * insertAdvertThdend
 * @description :   插入广告
 *
 * @param $arr
 * @param $arrAd
 * @param $postion
 * @return array
 * @author zhaoxichao
 * @date 05/06/2019
 */
function  insertAdvertThdend($arr, $arrAd, $postion) {
    $arrRet = array();
    $arrRet[] = $arr;

    $adJson = base64_decode($arrAd[0]['ad_config']);
    $asArr  = json_decode($adJson, true);
    foreach ($asArr as $key => $ad) {
        //过滤非法数据
        if (empty($ad['code'])) {
            continue;
        }
        $ad['isAd'] = true;
        $adValid[] = $ad;   //合法的广告数据
    }
    $count  = count($adValid);
    if ($count >= $postion) {
        $pos = $postion - 1;
    } else {
        $remain = $postion % $count;
        $pos    = $remain -1;
    }

    $arrRet[] = $adValid[$pos];

    return  $arrRet;
}

/**
 * insertAdvertThdreply
 * @description :
 *
 * @param $arr
 * @param $arrAd
 * @param $postion
 * @return array
 * @author zhaoxichao
 * @date 11/06/2019
 */
function    insertAdvertThdreply($arr, $arrAd, $postion) {
    $arrRet = array();

    $adJson = base64_decode($arrAd[0]['ad_config']);
    $asArr  = json_decode($adJson, true);
    foreach ($asArr as $key => $ad) {
        //过滤非法数据
        if (empty($ad['code'])) {
            continue;
        }
        $ad['isAd'] = true;
        $adValid[] = $ad;   //合法的广告数据
    }

    $count  = count($adValid);
    if ($count >= $postion) {
        $pos = $postion - 1;
    } else {
        $remain = $postion % $count;
        $pos    = $remain -1;
    }

    foreach ($arr as $k => $value) {
        //插入广告数据
        if (5 == $k) {
            $arrRet[] = $adValid[$pos];
        }

        if ($k > 5 && ($k - 5)%10 == 0) {
            $pos = $pos + 1;
            if ($count >= $pos) {
                $pos = $pos - 1;
            } else {
                $remain = $pos % $count;
                $pos    = $remain -1;
            }
            $arrRet[] = $adValid[$pos];
        }

        //插入正常数据
        $arrRet[] = $value;
    }

    return  $arrRet;
}


/**
 * getForumIcon
 * @description :   获取板块头像
 *
 * @param $fid
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 03/06/2019
 */
function getForumIcon($fid) {
    $arrRet = array();

    $sql = 'select icon from '.DB::table('forum_forumfield').' where fid = '.$fid;
    $arrRet = DB::fetch_all($sql);

    return  $arrRet;
}


/**
 * getIconsByTids
 * @description :   获取帖子图片
 *
 *
 * @param $arrTids
 * @return array
 * @author zhaoxichao
 * @date 29/05/2019
 */
function getIconsByTids($arrTids) {
    $res = array();
    $strTid = count($arrTids) > 1 ? implode(',', $arrTids) : $arrTids[0];
    if (empty($strTid)) {
        return $res;
    }
    $sqlIcons  =   'select tid, attachment from '.DB::table('forum_threadimage').' where tid in ('. $strTid .')';
    $res = DB::fetch_all($sqlIcons);

    return $res;
}

/**
 * isCollected
 * @description :   判断是否收藏
 *
 * @param $type
 * @param $id
 * @param $uid
 * @return bool
 * @author zhaoxichao
 * @date 12/06/2019
 */
function isCollected($type, $id, $uid) {
    if (empty($uid) || empty($id) || empty($type)) {
        return  false;
    }

    $sql =  "select favid from ".DB::table('home_favorite')." where idtype = '" .$type. "' and uid = " .$uid. " and  id = " . $id;

    $res = DB::fetch_all($sql);
    if(empty($res)) {
        return  false;
    }
    return true;
}

/**
 * UserInfo
 * @description :   获取用户信息
 *
 * @param $params
 * @param $uid
 * @return mixed
 * @author zhaoxichao
 * @date 09/06/2019
 */
function UserInfo($params, $uid) {
    //获取用户信息
    $space = getuserbyuid($uid);

    //获取用户头像
    $avatar = getUserAvatar($uid, $params);

    $space['avatar'] =  $avatar['avatar'];

    return  $space;
}

/**
 * getUserAvatar
 * @description :   根据uid获取用户头像
 *
 * @param $uid
 * @param $params
 * @return array|void
 * @author zhaoxichao
 * @date 09/06/2019
 */
function getUserAvatar($uid, $params) {
    $arrRet = array(
        'avatar'  => '',
    );

    if (empty($uid)) {
        return  $arrRet;
    }

    $size   = isset($params['size']) ? $params['size'] : '';
    $random = isset($params['random']) ? $params['random'] : '';
    $type   = isset($params['type']) ? $params['type'] : '';
    $check  = isset($params['check_file_exists']) ? $params['check_file_exists'] : '';

    $avatar = './data/avatar/'.get_avatar($uid, $size, $type);
    if(file_exists(dirname(__FILE__).'/'.$avatar)) {
        //检查是否存在
        if($check) {
            echo 1;
            return;
        }
        $random = !empty($random) ? rand(1000, 9999) : '';
        $avatar_url = empty($random) ? $avatar : $avatar.'?random='.$random;
    } else {
        if($check) {
            echo 0;
            return;
        }
        $size = in_array($size, array('big', 'middle', 'small')) ? $size : 'middle';
        $avatar_url = 'images/noavatar_'.$size.'.gif';
    }

    $arrRet['avatar'] = UC_API . '/' . $avatar_url;

    return  $arrRet;
}


/**
 * get_avatar
 * @description :   获取头像目录
 *
 * @param        $uid
 * @param string $size
 * @param string $type
 * @return string
 * @author zhaoxichao
 * @date 09/06/2019
 */
function get_avatar($uid, $size = 'middle', $type = '') {
    $size = in_array($size, array('big', 'middle', 'small')) ? $size : 'middle';
    $uid = abs(intval($uid));
    $uid = sprintf("%09d", $uid);
    $dir1 = substr($uid, 0, 3);
    $dir2 = substr($uid, 3, 2);
    $dir3 = substr($uid, 5, 2);
    $typeadd = $type == 'real' ? '_real' : '';
    return $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
}

/**
 * _get_script_url
 * @description :   获取头像PHP_SELF
 *
 * @return bool|mixed|string
 * @author zhaoxichao
 * @date 09/06/2019
 */
function _get_script_url() {
    $scriptName = basename($_SERVER['SCRIPT_FILENAME']);

    if(basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    } else if(basename($_SERVER['PHP_SELF']) === $scriptName) {
        $_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'];
    } else if(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
        $_SERVER['PHP_SELF'] = $_SERVER['ORIG_SCRIPT_NAME'];
    } else if(($pos = strpos($_SERVER['PHP_SELF'],'/'.$scriptName)) !== false) {
        $_SERVER['PHP_SELF'] = substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
    } else if(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT']) === 0) {
        $_SERVER['PHP_SELF'] = str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
        $_SERVER['PHP_SELF'][0] != '/' && $_SERVER['PHP_SELF'] = '/'.$_SERVER['PHP_SELF'];
    } else {
        return false;
    }

    return $_SERVER['PHP_SELF'];
}

/**
 * getLastPid
 * @description :   获取当前最大pid
 *
 * @return int
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 11/06/2019
 */
function getLastPid() {
    $sql = 'select pid from '.DB::table('forum_post').' order by pid desc limit 1';
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        throw new discuz_exception(error_plugin::ERROR_SELECT_DATA_EMPTY, 'getMaxPid');
    }
    $lastPid = intval($res[0]['pid']);

    return  $lastPid;
}

/**
 * getDetailByPid
 * @description :   根据pid获取帖子详情
 *
 * @param $pid
 * @return array
 * @author zhaoxichao
 * @date 12/06/2019
 */
function getDetailByPid($pid) {
    $arrRet = array();

    $sql  = 'select author, dateline, message, position, pid  from '.DB::table('forum_post').' where first = 0 and pid = ' . $pid;
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        return $arrRet;
    }

    $arrRet = $res[0];

    return $arrRet;
}

/**
 * getDetailByPosition
 * @description :   根据Position获取帖子详情
 *
 * @param $pos
 * @return mixed
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 11/06/2019
 */
function getDetailByPosition($pos) {
    $sql  = 'select author, dateline, message, position, pid  from '.DB::table('forum_post').' where position = ' . $pos;
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        throw new discuz_exception(error_plugin::ERROR_SELECT_DATA_EMPTY, 'getDetailByPosition');
    }

    return $res[0];
}

/**
 * repliesPlus
 * @description :   帖子回复数加1
 *
 * @param $tid
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 12/06/2019
 */


function repliesPlus($tid) {
    $sql = "select replies from ".DB::table('forum_thread')." where tid = " . $tid;
    $res = DB::fetch_all($sql);

    $replies = $res[0]['replies'];

    $update = array(
        'replies'   =>  $replies + 1,
    );
    //更新条件
    $condition = array('tid'=> $tid);
    //更新数据
    $resUpdate = DB::update('forum_thread', $update, $condition);

    if (empty($resUpdate)) {
        throw new discuz_exception(error_plugin::ERROR_UPDATE_DATA_ERROR);
    }
}

/**
 * getForumPerm
 * @description :   获取板块权限(old)
 *
 * @param $_G
 * @param $fid
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 13/06/2019
 */
function getForumPerm($_G, $fid) {
    if (empty($_G['uid']) || empty($fid)) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID);
    }

    $sql = 'select allowview, allowpost from '.DB::table('forum_access').' where uid = '.$_G['uid'].' and fid = ' . $fid;
    $res = DB::fetch_all($sql);

    return $res;
}

/**
 * groupAccess
 * @description :   用户组权限
 *
 * 0: 无权限;
 * 1: 读权限;
 * 2: 发帖权限;
 * 3: 回复权限;
 * @param $_G
 * @return int
 * @author zhaoxichao
 * @date 13/06/2019
 */
function groupAccess($_G) {
    $arrRet = 1;    //默认读权限

    if (empty($_G['groupid'])) {
        return $arrRet;
    }

    $sql = "select readaccess, allowpost, allowreply from ".DB::table('common_usergroup_field')." where groupid = " . $_G['groupid'];
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        $arrRet = 1;
        return  $arrRet;
    }

    $ret = $res[0];
    if (!empty($ret['allowreply'])) {
        $arrRet = 3;        //回复权限
    } elseif (!empty($ret['allowpost'])) {
        $arrRet = 2;        //发帖权限
    } elseif (!empty($ret['readaccess'])) {
        $arrRet = 1;        //读权限
    } else {
        $arrRet = 0;        //无权限
    }

    return  $arrRet;
}

/**
 * forumAccess
 * @description :   板块权限
 *
 * @param $fid
 * @param $G
 * @return int
 * @author zhaoxichao
 * @date 13/06/2019
 */
function forumAccess($fid, $G = array()) {
    $arrRet = 3;    //默认读权限

    if (empty($fid)) {
        return $arrRet;
    }
    $sql = "select allowview, allowpost, allowreply from ".DB::table('forum_access')." where fid = " . $fid;
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        $arrRet = 3;
        return  $arrRet;
    }

    $ret = $res[0];
    if (!empty($ret['allowreply'])) {
        $arrRet = 3;        //回复权限
    } elseif (!empty($ret['allowpost'])) {
        $arrRet = 2;        //发帖权限
    } elseif (!empty($ret['allowview'])) {
        $arrRet = 1;        //读权限
    } else {
        $arrRet = 0;        //无权限
    }

    return  $arrRet;
}

/**
 * checkLogin
 * @description :   登录判断
 *
 * @param $_G
 * @return bool
 * @author zhaoxichao
 * @date 13/06/2019
 */
function checkLogin($_G) {
    if (empty($_G['uid'])) {
        return false;
    }

    return  true;
}

/**
 * parseImg
 * @description :   解析图片
 *
 * @param $message
 * @return string
 * @author zhaoxichao
 * @date 20/06/2019
 */
function parseImg($message) {
    $img = '';
    if (false !== strpos($message, 'jpg')
        || false !== strpos($message, 'png')
        || false !== strpos($message, 'gif')
        || false !== strpos($message, 'jpeg')) {

        $http   = strstr($message, 'http');
        $endJpg = strpos($http, 'jpg');
        $endPng = strpos($http, 'png');
        $endGif = strpos($http, 'gif');
        $endJpeg= strpos($http, 'jpeg');
        $end    = max($endJpg, $endPng, $endGif, $endJpeg);
        $end    += !empty($endJpeg) ? 4 : 3;
        $img    = substr($http, 0, $end);
    }

    $img    = empty($img) ? '' : $img;

    return   $img;
}

/**
 * initSysConst
 * @description :   初始化系统常量
 *
 * @param $smartConfig
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 21/06/2019
 */
function initSysConst($smartConfig) {
    if (!defined('SECRETKEY')) {
        $secretkey = trim($smartConfig['secretkey']);
        if (empty($secretkey)) {
            throw new discuz_exception(error_plugin::ERROR_SECRETKEY_EMPTY);
        }
        define('SECRETKEY', $secretkey);        //定义SECRETKEY常量
        helper_log::runlog('swan', 'set SECRETKEY = ' . SECRETKEY);
    }

    if (!defined('DOMAIN')) {
        $domain = trim($smartConfig['domain']);
        if (empty($domain)) {
            throw new discuz_exception(error_plugin::ERROR_DOMAIN_EMPTY);
        }
        define('DOMAIN', $domain);              //定义论坛域名常量
        helper_log::runlog('swan', 'set DOMAIN = ' . DOMAIN);
    }
}

/**
 * getImgByTids
 * @description :   批量获取帖子图片byTid
 *
 * @param $arrTids  帖子ID数组
 * @param $arrTmp   待处理数据
 * @return mixed
 * @author zhaoxichao
 * @date 28/06/2019
 */
function getImgByTids($arrTids, $arrTmp) {
    $icons = getIconsByTids($arrTids);
    if (!empty($icons)) {
        foreach ($icons as $km => $vm) {
            if (false !== strpos($vm['attachment'], 'http')
                || false !== strpos($vm['attachment'], 'https')) {
                $img = parseImg($vm['attachment']);
            } else {
                $img = '';
                $img =  DOMAIN . ATTACHMENTFORM . $vm['attachment'];
            }

            $arrTmp[$vm['tid']]['imgUrl']  = empty($img) ? array() : array($img);
        }
    }

    return  $arrTmp;
}

/**
 * encodToUtf8
 * @description : json化输出utf-8编码
 *
 * @param $data
 * @return string
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 07/08/2019
 */
function  encodToUtf8($data) {
    if (!defined('CHARSET')) {
        throw new discuz_exception(error_plugin::ERROR_CHARSET_EMPTY);
    }

    $encode = mb_detect_encoding($data, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
    if($encode == 'UTF-8'){
        return $data;
    }else{
        return mb_convert_encoding($data, 'UTF-8', $encode);
    }
}