<?php
/**
 * exception.php
 *
 * @description :
 *
 * @author : zhaoxichao
 * @since : 29/05/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class discuz_exception extends Exception {

    /**
     * discuz_exception constructor.
     *
     * @param string $code
     * @param string $note
     */
    public function __construct($code, $note = '') {
        $message = isset(error_plugin::$ERROR_MSG[$code]) ? error_plugin::$ERROR_MSG[$code] : 'undefined message';
        $this->line = $this->getLine();
        $this->file = $this->getFile();

        $this->code = $code;
        $this->message = $message;

        $log = $note ? $message .': '.$note : $message;
        helper_log::runlog('swan_notice', $log);    //打印日志

        parent::__construct($this->message, $this->code);
    }
}