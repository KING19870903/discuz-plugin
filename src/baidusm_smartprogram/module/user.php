<?php

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

include DISCUZ_ROOT . './config/config_ucenter.php';

//统一处理模块参数
$filter =   array(
    'username'  => 's',
    'password'  => 's',
    'type'      => 's',
    'uid'       => 's',
    'email'     => 's',
);
$param = request_params::filterInput($filter, $_GET);
$params = array_merge($params, $param);

try {
    switch ($params['action']) {
        case 'login':           //登录
            $arrResonse['data'] = login($params, $_G);
            break;
        case 'register':        //注册
            $arrResonse['data'] = register($params, $_G);
            break;
        case 'userinfo':        //用户信息
            $arrResonse['data'] = getUserInfo($params, $_G);
            break;
        case 'mythread':        //我的帖子
            $arrResonse['data'] = getMyThread($params, $_G);
            break;
        case 'verify':          //二维码(验证信息放在header)
            imgVerify($_G);
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
 * login
 * @description :   登录
 *
 * @param $params
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 11/06/2019
 */
function login($params, $_G) {
    $arrRet = array(
        "token"         =>  '',
        "expiretime"    => EXPIRETIME,
        "userInfo"      =>  array(),
    );

    $account = addslashes($params['username']);
    $pw      = addslashes($params['password']);

    if(empty($account)) {
        throw new discuz_exception(error_plugin::ERROR_LOGIN_USERNAME_EMPTY);
    }
    if (empty($pw)) {
        throw new discuz_exception(error_plugin::ERROR_LOGIN_PASSWORD_EMPTY);
    }

    $sql = "select uid, username, password, email, salt from ".UC_DBTABLEPRE."members where username = '" . $account . "'";
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        throw new discuz_exception(error_plugin::ERROR_LOGIN_USERNAME_INVAILD);
    }
    $ret = $res[0];

    $password = md5(md5($pw).$ret['salt']);         //check密码
    if ($ret['password'] != $password) {
        throw new discuz_exception(error_plugin::ERROR_LOGIN_PASSWORD_INVAILD);
    }

    $userInfo = UserInfo($params, $ret['uid']);     //获取用户信息
    $arrRet['userInfo'] = array(
        "uid"        =>  $userInfo['uid'],
        "userName"   =>  $userInfo['username'],
        "groupid"    =>  $userInfo['groupid'],
        "avatar"     =>  $userInfo['avatar'],
    );

    $_G['uid']      = $userInfo['uid'];
    $_G['username'] = $userInfo['username'];
    $_G['adminid']  = $userInfo['adminid'];
    $_G['groupid']  = $userInfo['groupid'];
    $_G['avatar']   = $userInfo['avatar'];


    $arrRet['token'] = makeToken($_G);              //生成token

    return $arrRet;
}

/**
 * register
 * @description :   注册
 *
 * @param $params
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 11/06/2019
 */
function register($params, $_G) {
    $arrRet = array(
        "token"     =>  '',
        "expiretime"    => EXPIRETIME,
        "userInfo"  =>  array(),
    );

    $account = addslashes($params['username']);
    $pw      = addslashes($params['password']);
    $email   = addslashes($params['email']);

    if(empty($account)) {
        throw new discuz_exception(error_plugin::ERROR_LOGIN_USERNAME_EMPTY);
    }
    if (empty($pw)) {
        throw new discuz_exception(error_plugin::ERROR_LOGIN_PASSWORD_EMPTY);
    }
    if (empty($email)) {
        throw new discuz_exception(error_plugin::ERROR_REGISTER_EMAIL_EMPTY);
    }

    //用户名重复检查
    $sql = "select uid, username, password, email, salt from ".UC_DBTABLEPRE."members where username = '" . $account . "'";
    $res = DB::fetch_all($sql);
    if (!empty($res)) {
        throw new discuz_exception(error_plugin::ERROR_REGISTER_USERNAME_EXITED);
    }

    $salt       = random(6);                  //生成随机6位salt
    $password   = md5(md5($pw).$salt);    //生成新密码
    $regdate    = time();
    //创建新用户
    $data = array(
        'uid'       =>  '',
        'username'  =>  $account,
        'password'  =>  $password,
        'email'     =>  $email,
        'salt'      =>  $salt,
        'regdate'   =>  $regdate,
    );
    $register = DB::insert('ucenter_members', $data);
    if (empty($register)) {
        throw new discuz_exception(error_plugin::ERROR_REGISTER_FAILED);
    }

    $uid = DB::insert_id();
    $groupid = 10;
    $param = array(
        'uid'       =>  $uid,
        'email'     =>  $email,
        'username'  =>  $account,
        'password'  =>  md5($salt),
        'groupid'   =>  $groupid,
        'regdate'   =>  $regdate,
    );
    $register = DB::insert('common_member', $param);
    if (empty($register)) {
        throw new discuz_exception(error_plugin::ERROR_REGISTER_FAILED);
    }

    //获取用户信息
    $userInfo = UserInfo($params, $uid);
    $arrRet['userInfo'] = array(
        "uid"        =>  $userInfo['uid'],
        "userName"   =>  $userInfo['username'],
        "groupid"    =>  $userInfo['groupid'],
        "avatar"     =>  $userInfo['avatar'],
    );

    $_G['userInfo'] = $arrRet['userInfo'];

    //返回数据
    $arrRet['token'] = makeToken();

    return $arrRet;
}



/**
 * getMyThread
 * @description :   我的帖子
 *
 * @param $params
 * @param $_G
 * @return array
 * @author zhaoxichao
 * @date 04/06/2019
 */
function  getMyThread($params, $_G) {
    $arrRet = array(
        'hasMore'   =>  false,
        'threadInfo'=>  array(),
        'postInfo'  =>  array(),
    );

    $arrRet['threadInfo'] = getThreadInfo($params, $_G);     //帖子
    list($arrRet['threadInfo'], $threadMore) = paging($arrRet['threadInfo'], $params['page']);

    $arrRet['postInfo'] = getPostInfo($params, $_G);   //回复
    list($arrRet['postInfo'], $postMore) = paging($arrRet['postInfo'], $params['page']);

    if ('threadInfo' == $params['type']) {
        $arrRet['postInfo'] = array();
    }
    if ('postInfo' == $params['type']) {
        $arrRet['threadInfo'] = array();
    }

    $arrRet['hasMore'] =  ('postInfo' == $params['type']) ? $postMore : $threadMore;

    return  $arrRet;
}

/**
 * getUserInfo
 * @description :   用户信息
 *
 * @param $params
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 11/06/2019
 */
function getUserInfo($params, $_G) {
    $arrRet = array();

    $info = UserInfo($params, $_G['uid']);
    if (empty($info)) {
        throw new discuz_exception(error_plugin::ERROR_LOGIN_MUST);
    }

    $arrRet = array(
        "avatar"	=>	$info['avatar'],    //用户头像
        "uid"		=>	$info['uid'],       //用户id
        "userName"	=>	$info['username'],  //用户名称
        "group"		=>	$info['groupid'],   //用户组
    );

    return  $arrRet;
}

/**
 * getThreadInfo
 * @description :   我的帖子信息
 *
 * @param $params
 * @param $_G
 * @return array
 * @author zhaoxichao
 * @date 12/06/2019
 */
function getThreadInfo($params, $_G) {
    $arrRet = array();

    $uid = $_G['uid'];
    $sql = "select thread.*, forum.name from ".DB::table('forum_thread')." thread left join ".DB::table('forum_forum')." forum on thread.fid = forum.fid where thread.authorid = " .$uid. " and thread.closed = 0";
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        return  $arrRet;
    }
    $arrTids = array();
    foreach ($res as $key => $value) {
        $arrTids[] = $value['tid'];
        $arrTmp[$value['tid']] = array(
            "tid"       =>   $value['tid'],
            "title"     =>   encodToUtf8($value['subject']),
            "imgUrl"    =>   array(),
            "author"    =>   isset($value['author']) ? $value['author'] : '匿名',
            "replyNum"  =>   $value['replies'],
            "typeid"    =>   $value['typeid'],       //主题分类id
            "perm"      =>   $value['readperm'],
            "price"     =>   $value['readperm'],
            "authorId"  =>   $value['authorid'],
            "dateLine"  =>   date("Y-m-d", $value['dateline']),
            "lastPost"  =>   formTimestampNature($value['lastpost']),
            "lastPoster"=>   encodToUtf8($value['lastposter']),
            "views"     =>   $value['views'],
            'forumName' =>   encodToUtf8($value['name']),
        );
    }

    $arrTmp = getImgByTids($arrTids, $arrTmp);  //批量获取帖子图片

    $arrRet = array_values($arrTmp);

    return  $arrRet;
}

/**
 * getPostInfo
 * @description :
 *
 * @param $params
 * @param $_G
 * @return array
 * @author zhaoxichao
 * @date 05/06/2019
 */
function getPostInfo($params, $_G) {
    $arrRet = array();

    $uid = $_G['uid'];
    $sql = "select * from ".DB::table('forum_post')." where authorid = " . $uid;
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        return  $arrRet;
    }

    //获取当前用户信息
    $space  = UserInfo($params, $_G['uid']);
    foreach ($res as $key => $value) {
        $arrRet[] = array(
            "pid"       =>  $value['pid'],
            "tid"       =>  $value['tid'],
            "author"    =>  $value['author'],
            "authorId"  =>  $value['authorid'],
            "imageUrl"  =>  array(),
            "dateLine"  =>  formTimestamp($value['dateline']),
            "message"   =>  encodToUtf8($value['message']),
            "anonymous" =>  $value['anonymous'],//是否匿名
            "position"  =>  $value['position'], //位置包含帖子
            "userName"  =>  isset($space['username']) ? $space['username'] : '匿名',
            "avatar"    =>  $space['avatar'],
            "groupId"   =>  $space['groupid'],  //用户组id
            "replayPost"    =>  array(
                "userName"  =>  isset($space['username']) ? $space['username'] : '匿名',//回复帖子用户
                "dateLine"  =>  formTimestampNature($value['dateline']),
                "message"   =>  encodToUtf8($value['message']),
                "position"  =>  $value['position'], //位置包含帖子
            ),
        );
    }

    return  $arrRet;
}

/**
 * imgVerify
 * @description :   二位验证码
 *
 * @param $_G
 * @author zhaoxichao
 * @date 05/06/2019
 */
function imgVerify(&$_G) {
    $image = imagecreatetruecolor(100, 30); //创建一个100×30的画布
    $white = imagecolorallocate($image,255,255,255);//白色
    imagefill($image,0,0,$white);//覆盖黑色画布

    $session = ""; //空变量 ，存放验证码
    for($i=0;$i<4;$i++){
        $size = 6;
        $x = $i*25+mt_rand(5,10);
        $y = mt_rand(5,10);
        $sizi_color = imagecolorallocate($image,mt_rand(80,220),mt_rand(80,220),mt_rand(80,220));
        $char = join("",array_merge(range('a','z'),range('A','Z'),range(0,9)));
        $char = str_shuffle($char);
        $char = substr($char,0,1);
        imagestring($image,$size,$x,$y,$char,$sizi_color);
        $session .= $char ; //把验证码的每一个值赋值给变量
    }

    $_G['verify']   = $session;

    for($k=0;$k<200;$k++){
        $rand_color = imagecolorallocate($image,mt_rand(50,200),mt_rand(50,200),mt_rand(50,200));
        imagesetpixel($image,mt_rand(1,99),mt_rand(1,29),$rand_color);
    }

    for($n=0;$n<5;$n++){
        $line_color = imagecolorallocate($image,mt_rand(80,220),mt_rand(80,220),mt_rand(80,220));
        imageline($image,mt_rand(1,99),mt_rand(1,29),mt_rand(1,99),mt_rand(1,29),$line_color);
    }

    header('content-type:image/png');//设置文件输出格式
    imagepng( $image ); //以png格式输出$image图像

    $_G['verifyimg'] = $image;

   //设置header变量
    setcookie("verify", $session, time()+60);

    imagedestroy( $image ); //销毁图像
}