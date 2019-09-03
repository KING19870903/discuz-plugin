<?php
/**
 * check_kv.php
 *
 * @description :   校验请求的键值对
 *
 * @author : zhaoxichao
 * @since : 01/06/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class check_kv {
    /**
     * @var KEY
     */
    private $_key;
    /**
     * @var VALUE
     */
    private $_value;
    /**
     * @var bool    是否必填, 默认FALSE
     */
    private $_required;

    /**
     * check_kv constructor.
     *
     * @param $key
     * @param $value
     */
    public function __construct($key, $value) {
        $this->_key     =   $key;
        $this->_value   =   $value;
        $this->_required=   false;
    }

    /**
     * getValue
     * @description :   获取值
     *
     * @return VALUE
     * @author zhaoxichao
     * @date king
     */
    public function getValue() {
        return $this->_value;
    }

    /**
     * notEmpty
     * @description :   判断非空
     *
     * @throws Exception
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public function notEmpty() {
        if (!isset($this->_value)) {
            throw new Exception('empty ' . $this->_key, error::ERROR_PARAMS_EMPTY);
        }
        $this->_required    =   true;
    }

    /**
     * hasValue
     * @description :   判断必填字段
     *
     * eg:字段必须设置 且必须有值 不能为 ''
     *
     * @throws Exception
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public function hasValue() {
        if (!isset($this->_value) || is_null($this->_value) || ($this->_value === '')) {
            throw new Exception('must have value ' . $this->_key, error::ERROR_PARAMS_INVALID);
        }
        $this->_required = true;
    }

    /**
     * numeric
     * @description :   判断数字类型
     *
     * @throws Exception
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public function numeric() {
        if ($this->_required || !empty($this->_value)) {
            if ((!is_numeric($this->_value))) {
                throw new Exception('invalid ' . $this->_key, error::ERROR_PARAMS_INVALID);
            }
        }
    }

    /**
     * isNumAndLetter
     * @description :
     *
     * @throws Exception
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public function isNumAndLetter() {
        //检查是否为合法的合同号。
        $matchStr = '/^([0-9a-zA-Z]{1,})$/';
        if ($this->_required || !empty($this->_value)) {
            if (!preg_match($matchStr, $this->_value)) {
                throw new Exception('invalid ' . $this->_key, error::ERROR_PARAMS_INVALID);
            }
        }
    }

    /**
     * positiveNumber
     * @description :
     *
     * @throws Exception
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public function positiveNumber() {
        if ($this->_required || !empty($this->_value)) {
            if ((!is_numeric($this->_value)) || ($this->_value <= 0)) {
                throw new Exception('invalid ' . $this->_key, error::ERROR_PARAMS_INVALID);
            }
        }
    }

    /**
     * nonNegativeNumber
     * @description :
     *
     * @throws Exception
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public function nonNegativeNumber() {
        if ($this->_required || !empty ($this->_value)) {
            if ((!is_numeric($this->_value)) || ($this->_value < 0)) {
                throw new Exception('invalid ' . $this->_key, error::ERROR_PARAMS_INVALID);
            }
        }
    }

    /**
     * isMultiId
     * @description :
     *
     * @throws Exception
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public function isMultiId() {
        //检查是否为多个id中间用逗号隔开。
        $matchStr = '/^[0-9]{1,}(,[0-9]{1,}){0,}$/';
        if ($this->_required || !empty($this->_value)) {
            if (!preg_match($matchStr, $this->_value)) {
                throw new Exception('invalid ' . $this->_key, error::ERROR_PARAMS_INVALID);
            }
        }
    }

    /**
     * isArray
     * @description :
     *
     * @throws Exception
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public function isArray() {
        if ($this->_required || !empty($this->_value)) {
            if ((!is_array($this->_value))) {
                throw new Exception('invalid ' . $this->_key, error::ERROR_PARAMS_INVALID);
            }
        }
    }

    /**
     * jsonToArray
     * @description :
     *
     * @throws Exception
     * @author zhaoxichao
     * @date 01/06/2019
     */
    public function jsonToArray() {
        if ($this->_required || !empty($this->_value)) {
            $this->_value = json_decode($this->_value, true);
            if (!is_array($this->_value) || is_null($this->_value)) {
                throw new Exception('invalid json ' . $this->_key, error::ERROR_PARAMS_INVALID);
            }
        }
    }
}