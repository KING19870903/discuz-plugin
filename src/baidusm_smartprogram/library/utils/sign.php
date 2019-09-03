<?php

/**
 * sign
 * @description :   生成sign值
 *
 * @param $arrParams
 * @return string
 * @author zhaoxichao
 * @date 03/06/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

function sign($arrParams) {
    //参数校验字段
    $signFilter = array('id', 'mod', 'action');
    ksort($arrParams);
    $arr = array();
    foreach ($arrParams as $key => $value) {
        if (in_array($key, $signFilter)) {
            $arr[] = "{$key}={$value}";
        }
        continue;
    }
    $gather = implode('&', $arr);
    $gather .= '&' . SECRETKEY . DOMAIN;
    helper_log::runlog(SWAN, 'make sign SECRETKEY =' . SECRETKEY . 'DOMAIN = ' . DOMAIN);
    $sign = md5($gather);

    return $sign;
}

/**
 * signIsInvaild
 * @description :   校验sign值
 *
 * @param $params
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 03/06/2019
 */
function signIsInvaild($params) {
    if (empty($params['sign'])) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID, 'sign');
    }

    $signOrg = sign($params);
    if ($params['sign'] != $signOrg) {
        helper_log::runlog(SWAN, 'param of sign = '. $params['sign'] . 'origin of sign = '. $signOrg);
        throw new discuz_exception(error_plugin::ERROR_SIGN_ERROR);
    }
}
