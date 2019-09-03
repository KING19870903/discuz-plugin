<?php
/**
 * smart.class.php
 *
 * @description :
 *
 * @author : zhaoxichao
 * @since : 23/08/2019
 */
if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class smart_core{

    const AES_METHOD = "AES-128-ECB";
    const AES_IV = "";
    const AES_OPTIONS = 0;

    public static function outputJson($response){
        echo json_encode($response);
        exit();
    }

    //获取指定字段值
    public static function getvalues($variables, $keys, $subkeys = array()){
        $return = array();
        foreach ($variables as $key => $value) {
            foreach ($keys as $k) {
                if ($k{0} == '/' && preg_match($k, $key) || $key == $k) {
                    if ($subkeys) {
                        $return[$key] = smart_core::getvalues($value, $subkeys);
                    } else {
                        if (!empty($value) || (is_numeric($value) && intval($value) === 0)) {
                            $return[$key] = is_array($value) ? smart_core::arraystring($value) : (string)$value;
                        }
                    }
                }
            }
        }
        return $return;
    }

    public static function arraystring($array){
        foreach ($array as $k => $v) {
            $array[$k] = is_array($v) ? smart_core::arraystring($v) : (string)$v;
        }
        return $array;
    }

    public static function makeSign($arrContent, $appkey){
        ksort($arrContent);
        $arr = array();
        foreach ($arrContent as $key => $value) {
            $arr[] = "{$key}={$value}";
        }
        $gather = implode('&', $arr);
        $gather .= '&' . $appkey;
        $sign = md5($gather);
        return $sign;
    }

    /**
     * @desc aes加密
     * @param $data
     * @param $secret_key
     * @return string
     */
    public static function aesEncrypt($data,$secret_key){
        return openssl_encrypt($data, self::AES_METHOD, $secret_key, self::AES_OPTIONS, self::AES_IV);
    }

    /**
     * @desc aes解密
     * @param $data
     * @param $secret_key
     * @return string
     */
    public static function aesDecrypt($data,$secret_key){
        return openssl_decrypt($data, self::AES_METHOD, $secret_key, self::AES_OPTIONS, self::AES_IV);
    }

}