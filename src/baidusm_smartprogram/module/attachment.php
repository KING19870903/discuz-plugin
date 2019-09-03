<?php
/**
 * attachment.php
 *
 * @description :   附件上传
 *
 * @author : zhaoxichao
 * @since : 30/05/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

//统一处理模块参数
$filter =   array(
    'attachment' => 's',
);
$param = request_params::filterInput($filter, $_GET);
$params = array_merge($params, $param);

//初始化上传文件
if (!isset($_FILES['file']) || empty($_FILES['file'])) {
    throw new discuz_exception(error_plugin::ERROR_UPLOAD_FILE_ERROR);
}
$file = $_FILES['file'];

try {
    $arrResonse['data'] = uploadFile($file, $params);

}catch (discuz_exception $e) {
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
 * uploadImage
 * @description :   上传图片(备用)
 *
 * @param $file
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 01/06/2019
 */
function uploadImage($file) {
    $arrRet = array(
        'image' => array(),
    );

    $dirType = isset($_GET['attament']) ? $_GET['attament'] : 'album';  //存储目录
    $pic = pic_upload($file, $dirType);
    if (empty($pic)) {
        throw new discuz_exception(error_plugin::ERROR_UPLOAD_IMAG_FAILED);
    }
    $arrRet['image'][] = DOMAIN .'data/attachment/' . $dirType . '/' .$pic['pic'];

    return $arrRet;
}

/**
 * uploadFile
 * @description :   上传附件
 *
 * @param $file
 * @param $params
 * @return array
 * @author zhaoxichao
 * @date 06/06/2019
 */
function uploadFile($file, $params) {
    $arrRet = array(
        'attachment' => array(),
    );

    $upload = new discuz_upload();
    $upload->init($file, 'forum');
    $attach = $upload->attach;
    if($attach['isimage']) {
        $upload->save();    //上传图片
    } else {
        $upload->save();    //上传文件
    }

    //文件存储目录
    $dirTmp = isset($params['attachment']) ? trim($params['attachment']) : 'album';

    //文件存储路径
    $arrRet['attachment'][] = DOMAIN . ATTACHMENT . $dirTmp . '/' .$attach['attachment'];

    return $arrRet;
}