<?php

/**
 * request_params.php
 *
 * @description :
 *
 * @author : zhaoxichao
 * @since : 01/06/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class request_params {
    /**
     * @var array
     *
     * 参数值处理方式
     * a -> array_merge 数组
     * s -> string      字符串
     * d -> double      双精度浮点数
     * i -> integer     整型数值
     * b -> boolean     布尔值
     * hs -> htmlspecialchars  转义或删除不安全的字符
     */
    protected static $arrMap = array(
        'a' => array(
            'func'    => 'array_merge',
            'default' => array(),
        ),
        'i' => array(
            'func'    => 'intval',
            'default' => 0,
        ),
        's' => array(
            'func'    => 'strval',
            'default' => '',
        ),
        'd' => array(
            'func'    => 'doubleval',
            'default' => 0,
        ),
        'b' => array(
            'func'    => 'boolval',
            'default' => false,
        ),
        'hs'=> array(
            'func'    => 'htmlspecialchars',
            'default' => '',
        )
    );

    /**
     * filterInput
     * @description :   格式化请求参数
     *
     * eg: $filter = array(
     *          'board_name' => 's',
     *          'board_id'   => 'hs',
     *     );
     *
     *
     * @param       $filter     参数类型数组
     * @param array $arrInput
     * @return array
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public static function filterInput($filter, array $arrInput) {
        $arrResult = array();

        foreach ($filter as $key => $val) {
            if (isset($arrInput[$key])) {
                $strFuncName = self::$arrMap[$val]['func'];
                $arrResult[$key] = $strFuncName($arrInput[$key]);
            } else {
                $arrResult[$key] = self::$arrMap[$val]['default'];   //初始化默认值
            }
        }

        return $arrResult;
    }

    /**
     * validate
     * @description :   校验类，支持无限嵌套的复杂请求数组
     *
     * eg:
     * 校验规则
     * $validateRules = array(
     *      'orderId'    => array('notEmpty', 'numeric'),//请求字段名为orderId,必填，数字类型
     *      'dealId'     => array('numeric'),            //请求字段名为dealId,选填（如果第1条规则不是notEmpty，则该字段为选填)，数字类型
     *      'dealOption' =>                              //请求字段名为dealOption,选填
     *          array(
     *              'jsonToArray',                       //dealOption的第1条规则，表明该字段对应的值为json串，需要转换为array数组，
     *              array(                               //dealOption的第2条规则，表明array内的校验规则
     *                  'dealOptionId'    => array('notEmpty', 'numeric'),
     *                  'dealOptionCount' => array('notEmpty', 'numeric'),
     *              )
     *          ),
     *  );
     * 对应的请求数组：
     * $input = array(
     *      'orderId'    => 10098710,
     *      'dealId'     => 14005,
     *      'dealOption' => ‘{{"dealOptionId":1,"dealOptionCount":10}, {"dealOptionId":2,"dealOptionCount":15}’
     * );
     *
     *
     * @param $arrValidate  校验规则
     * @param $input        输入参数
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public static function validate($arrValidate, $input) {
        if (empty($arrValidate)) {
            return;
        }

        foreach ($arrValidate as $key => $validateRules) {
            $entry = new check_kv($key, $input[$key]);      //初始化key, value

            foreach ($validateRules as $validateRule) {
                if (is_array($validateRule)) {
                    $entry->isArray();              //判断value值是否是数组
                    $value = $entry->getValue();    //获取value值
                    if (!empty($value)) {
                        foreach ($value as $entryValue) {
                            self::validate($validateRule, $entryValue); //递归校验
                        }
                    }
                } else {
                    $entry->{$validateRule}();      //指定类型校验
                }
            }
        }
    }


}