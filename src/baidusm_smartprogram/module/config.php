<?php
/**
 * config.php
 *
 * @description :   商业端接口
 *
 * @author : zhaoxichao
 * @since : 28/05/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

//统一处理模块参数
$filter =   array(
    'fid' => 'i',
    'tid' => 'i',
    'adConfig'=>'s',
    'createTime'=>'i',
);

$param  = request_params::filterInput($filter, $_GET);
$params = array_merge($params, $param);


try {

    signIsInvaild($params);     //sign校验
    switch ($params['action']) {
        case 'selt':            //查询
            $arrResonse['data'] = getConfig();
            break;
        case 'upsert':          //插入
            $arrResonse['data'] = upsertConfig($params);
            break;
        default:
            throw new discuz_exception(error_plugin::ERROR_ACTION_INVALID, $params['action']);
    }
} catch (discuz_exception $e) {
    //统一报错处理
    $arrResonse = array(
        "errNo"     =>  $e->getCode(),
        "errMsg"    =>  $e->getMessage(),
        "data"      =>  array(),
    );
}

//统一数据输出
smart_core::outputJson($arrResonse);

/**
 * getConfig
 * @description :   查询
 *
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 03/06/2019
 */
function getConfig() {
    $arrRet = array(
      "adConfig"=>  "selt success",
    );

    $sql = 'select * from '.DB::table('swan_app_config').' where is_effect = 1 order by update_time desc limit 1';
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        return  $arrRet;
    }

    $adConfig = $res[0]['ad_config'];

    $arrRet['adConfig'] = $adConfig;
    return $arrRet;
}

/**
 * upsertConfig
 * @description :   更新插入数据
 *
 * @param $params
 * @return bool
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 03/06/2019
 */
function upsertConfig($params) {
    $adConfig = isset($params['adConfig']) ? $params['adConfig'] : "";
    if (empty($adConfig)) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID, 'fid');
    }

    $data = array(
        "ad_config"     =>  $adConfig,
        "update_time"   =>  time(),
    );

    //查询数据
    $sql = 'select * from '.DB::table('swan_app_config').' where is_effect = 1 order by update_time desc limit 1';
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        //插入数据
        $sqlInsert = "INSERT INTO ".DB::table('swan_app_config')." SET `ad_config`='".$data['ad_config']."' , `update_time`='".$data['update_time']."' , `create_time`='".$params['createTime']."'";

        $resInsert = DB::query($sqlInsert);
        if (empty($resInsert)) {
            throw new discuz_exception(error_plugin::ERROR_INSERT_DATA_ERROR);
        }
    } else {
        //更新数据
        $sqlUpdate = "UPDATE ".DB::table('swan_app_config')." SET `ad_config`='".$data['ad_config']."' , `update_time`='".$data['update_time']."' WHERE `id`='".$res[0]['id']."'";

        $resUpdate = DB::query($sqlUpdate);
        if (empty($resUpdate)) {
            throw new discuz_exception(error_plugin::ERROR_UPDATE_DATA_ERROR);
        }
    }

    return  true;
}
