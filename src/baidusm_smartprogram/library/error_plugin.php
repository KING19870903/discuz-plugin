<?php

/**
 * error_plugin.php
 *
 * @description :   公共错误类
 *
 * @author : zhaoxichao
 * @since : 01/06/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class error_plugin {
    /**
     * 入参错误码
     */
    const ERROR_PARAMS_INVALID              =     10001;  //输入参数非法
    const ERROR_PARAMS_EMPTY                =     10002;  //输入参数为空

    /**
     *  返回结果错误码
     */
    const ERROR_RESULT_EMPTY                =     20003;  //返回结果为空

    /**
     * 数据库操作错误码
     */
    const ERROR_SELECT_DATA_EMPTY           =     30001;  //查询数据为空
    const ERROR_INSERT_DATA_ERROR           =     30002;  //插入数据错误
    const ERROR_UPDATE_DATA_ERROR           =     30003;  //更新数据错误
    const ERROR_DELETE_DATA_ERROR           =     30004;  //删除数据错误
    const ERROR_INSERT_DATA_DUPLICATE       =     30005;  //插入数据重复

    /**
     * 业务逻辑错误码
     */
    const ERROR_SIGN_ERROR                  =     40001;  //SIGN校验失败
    const ERROR_TOKEN_ERROR                 =     40002;  //TOKEN校验失败
    const ERROR_LOGIN_FAILED                =     40003;  //用户登录失败
    const ERROR_LOGIN_USERNAME_EMPTY        =     40004;  //请输入用户账号
    const ERROR_LOGIN_PASSWORD_EMPTY        =     40005;  //请输入用户密码
    const ERROR_LOGIN_USERNAME_INVAILD      =     40006;  //用户账号不存在
    const ERROR_LOGIN_PASSWORD_INVAILD      =     40007;  //用户密码错误
    const ERROR_REGISTER_USERNAME_EXITED    =     40008;  //用户名已存在
    const ERROR_REGISTER_FAILED             =     40009;  //用户注册失败
    const ERROR_ACTION_INVALID              =     40010;  //ACTION非法操作
    const ERROR_MOD_INVALID                 =     40011;  //MOD非法操作
    const ERROR_UPLOAD_FILE_ERROR           =     40012;  //上传文件非法
    const ERROR_UPLOAD_IMAG_FAILED          =     40013;  //上传文件失败
    const ERROR_ILLEGAL_ACCESS_ERROR        =     40014;  //访问非法
    const ERROR_ADVERT_TYPE                 =     40015;  //广告插入标识非法
    const ERROR_QUERY_EMPTY                 =     40016;  //检索词为空
    const ERROR_QUERY_RESULE_EMPTY          =     40017;  //检索结果为空
    const ERROR_REGISTER_EMAIL_EMPTY        =     40018;  //请输入用户邮箱
    const ERROR_LOGIN_MUST                  =     40019;  //请登录
    const ERROR_SECRETKEY_EMPTY             =     40020;  //请在后台配置secretkey
    const ERROR_TOKEN_MAKE_FAILED           =     40021;  //生成token失败
    const ERROR_TOKEN_EMPTY                 =     40022;  //token参数为空
    const ERROR_COLLECT_DUMPLICATE          =     40023;  //收藏重复
    const ERROR_COLLECT_CANCEL              =     40024;  //已取消收藏
    const ERROR_DOMAIN_EMPTY                =     40025;  //DOMAIN常量初始化错误
    const ERROR_CHARSET_EMPTY               =     40026;  //discuz编码常量初始化失败


    /**
     * @var array   异常消息提示
     */
    public static $ERROR_MSG = array(
        self::ERROR_PARAMS_INVALID              =>	"输入参数非法",
        self::ERROR_PARAMS_EMPTY                =>	"输入参数为空",
        self::ERROR_RESULT_EMPTY                =>	"返回结果为空",
        self::ERROR_SELECT_DATA_EMPTY           =>	"查询数据为空",
        self::ERROR_INSERT_DATA_ERROR           =>	"插入数据错误",
        self::ERROR_UPDATE_DATA_ERROR           =>	"更新数据错误",
        self::ERROR_DELETE_DATA_ERROR           =>	"删除数据错误",
        self::ERROR_INSERT_DATA_DUPLICATE       =>	"插入数据重复",
        self::ERROR_SIGN_ERROR                  =>	"sign校验失败",
        self::ERROR_TOKEN_ERROR                 =>	"token校验失败",
        self::ERROR_LOGIN_FAILED                =>	"用户登录失败",
        self::ERROR_REGISTER_USERNAME_EXITED    =>	"用户名已存在",
        self::ERROR_ACTION_INVALID              =>	"ACTION非法操作",
        self::ERROR_UPLOAD_FILE_ERROR           =>	"上传文件非法",
        self::ERROR_UPLOAD_IMAG_FAILED          =>	"上传文件失败",
        self::ERROR_ILLEGAL_ACCESS_ERROR        =>	"访问非法",
        self::ERROR_MOD_INVALID                 =>	"MOD非法操作",
        self::ERROR_ADVERT_TYPE                 =>	"广告插入标识非法",
        self::ERROR_QUERY_EMPTY                 =>	"检索词为空",
        self::ERROR_QUERY_RESULE_EMPTY          =>	"检索结果为空",
        self::ERROR_LOGIN_USERNAME_EMPTY        =>	"请输入用户账号",
        self::ERROR_LOGIN_PASSWORD_EMPTY        =>	"请输入用户密码",
        self::ERROR_LOGIN_USERNAME_INVAILD      =>	"用户账号不存在",
        self::ERROR_LOGIN_PASSWORD_INVAILD      =>	"用户密码错误",
        self::ERROR_REGISTER_FAILED             =>	"用户注册失败",
        self::ERROR_REGISTER_EMAIL_EMPTY        =>	"请输入用户邮箱",
        self::ERROR_LOGIN_MUST                  =>	"请登录",
        self::ERROR_SECRETKEY_EMPTY             =>	"未设置管理后台 论坛完整域名 参数",
        self::ERROR_TOKEN_MAKE_FAILED           =>	"生成token失败",
        self::ERROR_TOKEN_EMPTY                 =>	"token参数为空",
        self::ERROR_COLLECT_DUMPLICATE          =>	"已收藏",
        self::ERROR_COLLECT_CANCEL              =>	"已取消收藏",
        self::ERROR_DOMAIN_EMPTY                =>	"未设置管理后台 APP Secret 参数",
        self::ERROR_CHARSET_EMPTY                =>	"discuz编码常量初始化失败",
    );
}