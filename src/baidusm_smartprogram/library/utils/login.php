<?php
/**
 * login.php
 *
 * @description :   login + token
 *
 * @author : zhaoxichao
 * @since : 31/05/2019
 */

/**
 * checkToken
 * @description :   校验token
 *
 * @param      $params
 * @param      $_G
 * @param bool $force   强弱校验
 * @return bool
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 17/06/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

function checkToken($params, &$_G, $force = true) {
    if (empty($params['token'])) {
        return  true;
    }

    $token  =   trim($params['token']);

    $sql = "select id, uid,  expire_time from ".DB::table('login_token')." where is_effect = 1 and token = '" . $token . "'";

    $res = DB::fetch_all($sql);
    if (empty($res) || $res[0]['expire_time'] - time() < 1000) {
        if ($force) {
            helper_log::runlog(SWAN, 'params of token = ' .$token);
            throw new discuz_exception(error_plugin::ERROR_TOKEN_ERROR);
        } else {
            return  true;
        }

    }

    //获取用户信息
    $userInfo       = UserInfo($params, $res[0]['uid']);
    $_G['uid']      = $userInfo['uid'];
    $_G['username'] = $userInfo['username'];
    $_G['adminid']  = $userInfo['adminid'];
    $_G['groupid']  = $userInfo['groupid'];
    $_G['avatar']   = $userInfo['avatar'];

    return  true;
}

/**
 * makeToken
 * @description :   生成token
 *
 * @param $_G
 * @return string
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 11/06/2019
 */
function makeToken($_G) {
    //Base64操作
    $str = $_G['username'] . time();
    $prefix_base64 = urlsafeB64encode($str);
    $domain_base64 = urlsafeB64encode(DOMAIN);

    $str_base64 = $prefix_base64 + $domain_base64;

    //HMAC + SHA256 加密
    $sign = hash_hmac('sha256', $str_base64, SECRETKEY);

    //生成token
    $token = $prefix_base64 . $domain_base64 . $sign;
    //有则更新无则插入
    $sql = 'select id from pre_login_token where is_effect = 1 and uid = ' .$_G['uid'];
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        $param = array(
            'id'            =>  '',
            'uid'           =>  $_G['uid'],
            'username'      =>  $_G['username'],
            'token'         =>  $token,
            'expire_time'   =>  time() + EXPIRETIME,
            'update_time'   =>  time(),
            'create_time'   =>  time(),
        );

        $sqlInsert = "INSERT INTO ".DB::table('login_token')." SET `uid`='".$param['uid']."' , `username`='".$param['username']."' , `token`='".$param['token']."' , `expire_time`='".$param['expire_time']."' , `update_time`='".$param['update_time']."' , `create_time`='".$param['create_time']."'";

        $tokenInsert = DB::query($sqlInsert);
        if (empty($tokenInsert)) {
            throw new discuz_exception(error_plugin::ERROR_TOKEN_MAKE_FAILED);
        }
    } else {
        $data = array(
            'token'         =>  $token,
            'expire_time'   =>  time() + EXPIRETIME,
            'update_time'   =>  time(),
        );
        $condition = array(
            'is_effect' =>  1,
            'uid'   =>  $_G['uid'],
        );

        $sqlUpdate = "UPDATE ".DB::table('login_token')." SET `token`='".$data['token']."' , `expire_time`='".$data['expire_time']."' , `update_time`='".$data['update_time']."' WHERE `is_effect`='1' AND `uid`='".$condition['uid']."'";

        $resUpdate = DB::query($sqlUpdate);
        if (empty($resUpdate)) {
            throw new discuz_exception(error_plugin::ERROR_UPDATE_DATA_ERROR);
        }
    }


    return  $token;
}


/**
 * urlsafe_b64decode
 * @description :   URL base64解码
 *
 * 将Base64编码中的"-"，"_"字符串转换成"+"，"/"，
 * 字符串长度余4倍的位补"=",保证两方面传输数据一致
 *
 * @param $string
 * @return string
 * @author zhaoxichao
 * @date 30/05/2019
 */
function urlsafeB64decode($string) {
    $data = str_replace(array('-','_'),array('+','/'),$string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}

/**
 * urlsafe_b64encode
 * @description :   URL base64编码
 *
 * @param $string
 * @return mixed|string
 * @author zhaoxichao
 * @date 30/05/2019
 */
function urlsafeB64encode($string) {
    $data = base64_encode($string);
    $data = str_replace(array('+','/','='),array('-','_',''),$data);
    return $data;
}

