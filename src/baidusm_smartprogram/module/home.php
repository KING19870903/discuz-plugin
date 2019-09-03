<?php

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once DISCUZ_ROOT . 'uc_client/client.php';

//统一处理模块参数
$filter =   array(
    'uid'   =>  'i',
    'filter'=>  's',    //privatepm:私人消息; announcepm:公共消息
    'do'    =>  's',
    'touid' =>  'i',
    'type'  =>  's',
    'fid'   =>  'i',
    'tid'   =>  'i',
    'name'  =>  's',
    'op'    =>  's',
);

$param  = request_params::filterInput($filter, $_GET);
$params = array_merge($params, $param);

try {
    switch ($params['action']) {
        case 'space':           //获取未读消息数量
            checkToken($params, $_G, false);
            $arrResonse['data'] = getMessage($params, $_G);
            break;
        case 'spacelist':           //获取未读消息列表
            $arrResonse['data'] = getMessageList($params, $_G);
            break;
        case 'setread':           //获取未读消息列表
            $arrResonse['data'] = setRead($params, $_G);
            break;
        case 'spacecp':          //添加收藏
            $arrResonse['data'] = addCollected($params, $_G);
            break;
        case 'spacecplist':       //收藏列表
            $arrResonse['data'] = getFavoriteList($params, $_G);
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
 * getMessage
 * @description :   获取新短消息数量
 *
 * @param $params
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 12/06/2019
 */
function getMessage($params, $_G) {
    $arrRet = array(
        'count' =>  0,
    );
    $uid = $_G['uid'];
    if (empty($uid)) {
        return  $arrRet;
    }

    //获取系统消息数量
    $sqla = "select count(*) as num from ".DB::table('common_grouppm')." where authorid = " . $uid;
    $resa = DB::fetch_all($sqla);
    if (empty($resa)) {
        return  $arrRet;
    }
    $announcepm = intval($resa[0]['num']);

    //获取私人消息数量
    $sqlp = "select newpm from ".DB::table('common_member')." where uid =" .$uid;
    $resp = DB::fetch_all($sqlp);
    if (empty($resp)) {
        throw new discuz_exception(error_plugin::ERROR_SELECT_DATA_EMPTY);
    }

    $privatepm = intval($resp[0]['newpm']);

    if ('announcepm' == $params['filter']) {
        $arrRet['count'] = $announcepm;             //获取系统消息数量
    } elseif ('privatepm' == $params['filter']) {
        $arrRet['count'] = $privatepm;              //获取私人消息数量
    } else {
        $arrRet['count'] = $announcepm + $privatepm;//获取系统消息数量+私人消息数量
    }

    return $arrRet;
}

/**
 * getMessageList
 * @description :
 *
 * @param $params
 * @param $_G
 * @return array
 * @author zhaoxichao
 * @date 05/06/2019
 */
function getMessageList($params, $_G) {
    $arrRet = array(
        'hasMore'   =>  false,
        'noteList'  =>  array(),
    );

    $uid = $_G['uid'];

    //公共消息
    if ('announcepm' == $params['filter']) {
        $sql = "select * from ".DB::table('common_grouppm')." where authorid = " . $uid . " order by dateline desc";
        $res = DB::fetch_all($sql);
        if (empty($res)) {
            return  $arrRet;
        }

        foreach ($res as $k => $v) {
            $arrRet['noteList'][] = array(
                "authorId"	=>	$v['authorid'],
                "author"    =>  $v['author'],
                "message"	=>	$v['message'],
                "dateLine"	=>	formTimestampNature($v['dateline']),
                "numbers"	=>	$v['numbers'],
            );
        }
        list($arrRet['noteList'], $arrRet['hasMore']) = paging($arrRet['noteList'], $params['page']);
    }

    //私人消息
    if ('privatepm' == $params['filter']) {
        $sql = "select members.pmnum, members.plid, lists.* from ".DB::table('ucenter_pm_members')." members left join ".DB::table('ucenter_pm_lists')." lists on members.plid = lists.plid where uid = " .$uid . " order by dateline desc";
        $res = DB::fetch_all($sql);
        if (empty($res)) {
            return  $arrRet;
        }

        $accepter = UserInfo($params, $uid);
        foreach ($res as $key => $value) {
            $poster = UserInfo($params, $value['authorid']);
            $lastmessage = unserialize($value['lastmessage']);
            $arrRet['noteList'][] = array(
                "nid"		=>	$value['pmid'],
                "type"		=>	(1 == $value['pmtype']) ? 'private':'public',
                "title"     =>  $value['subject'],
                "message"	=>	isset($lastmessage['lastsummary']) ? $lastmessage['lastsummary'] : '',
                "dateLine"	=>	formTimestampNature($value['dateline']),
                "userName"	=>	$accepter['username'],
                "avatar"	=>	$poster['avatar'],
                "postName"	=>	$poster['username'],
            );
        }

        list($arrRet['noteList'], $arrRet['hasMore']) = paging($arrRet['noteList'], $params['page']);
    }

    return  $arrRet;
}

/**
 * setRead
 * @description :   设置消息已读
 *
 * @param $params
 * @param $_G
 * @return mixed
 * @author zhaoxichao
 * @date 04/06/2019
 */
function    setRead($params, $_G) {
    $arrRet['nid'] = 1;

    return  $arrRet;
}

/**
 * addCollected
 * @description :   添加收藏(收藏板块和帖子)
 *
 * @param $params
 * @param $_G
 * @return bool
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 12/06/2019
 */
function addCollected($params, $_G) {
    $uid    = $_G['uid'];
    $fid    = addslashes($params['fid']);
    $tid    = addslashes($params['tid']);
    $type   = addslashes($params['type']);
    $title  = trim($params['name']);
    $op     = trim($params['op']);      //cancel: 取消收藏; collect: 添加收藏

    if (empty($uid)) {
        throw new discuz_exception(error_plugin::ERROR_LOGIN_MUST);
    }

    if (!empty($fid) && 'forum' == trim($type)) {
        $id     =   $fid;
        $idtype =  'fid';
    }
    if (!empty($tid) && 'thread' == trim($type)) {
        $id     =   $tid;
        $idtype =  'tid';
    }

    if (empty($id) || empty($idtype) ||  !in_array($op, array('collect', 'cancel'))) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID);
    }

    //添加收藏
    if ('collect' == $op) {
        $favor = isCollected($idtype, $id, $uid);      //判断是否已收藏
        if (!empty($favor)) {
            throw new discuz_exception(error_plugin::ERROR_COLLECT_DUMPLICATE);
        }

        $insert = array(
            'favid'     =>  '',
            'uid'       =>  $uid,
            'id'        =>  $id,
            'idtype'    =>  $idtype,
            'title'     =>  $title,
            'dateline'  =>    time(),
        );

        $res = DB::insert('home_favorite', $insert);
        if (!$res) {
            throw new discuz_exception(error_plugin::ERROR_INSERT_DATA_ERROR);
        }
    }

    //取消收藏
    if ('cancel' == $op) {
        $favor = isCollected($idtype, $id, $uid);      //判断是否已取消收藏
        if (empty($favor)) {
            throw new discuz_exception(error_plugin::ERROR_COLLECT_CANCEL);
        }

        $delete = array(
            'uid'       =>  $uid,
            'id'        =>  $id,
            'idtype'    =>  $idtype,
        );

        $del = DB::delete('home_favorite', $delete);
        if (!$del) {
            return  false;
        }
    }

    return  true;
}


/**
 * getFavoriteList
 * @description :   获取收藏列表
 *
 * @param $params
 * @param $_G
 * @return array
 * @author zhaoxichao
 * @date 05/06/2019
 */
function getFavoriteList($params, $_G) {
    $arrRet = array(
        "hasMore"    => false,
        "plateList"  =>  array(),
        "postList"   =>  array(),
    );

    $uid = $_G['uid'];

    $arrSql = array(
        'fid'   =>  "select forum.fid, forum.name, forum.threads, forum.posts, forum.todayposts from ".DB::table('home_favorite')." favorite left join ".DB::table('forum_forum')." forum on favorite.id = forum.fid where favorite.uid = ".$uid." and favorite.idtype = 'fid'",
        'tid'   =>  "select thread.tid, thread.author, thread.subject, thread.replies, thread.lastpost, thread.stickreply from ".DB::table('home_favorite')." favorite left join ".DB::table('forum_thread')." thread on favorite.id =  thread.tid where favorite.uid = ".$uid." and favorite.idtype = 'tid'",
    );
    //收藏板块
    $resForum = DB::fetch_all($arrSql['fid']);
    foreach ($resForum as $key => $forum) {
        $ret = getForumIcon($forum['fid']);
        $arrRet['plateList'][] = array(
            "fid"	=>	$forum['fid'],
            "name"	=>	encodToUtf8($forum['name']),
            "icon"	=>	empty($ret[0]['icon']) ? '' : DOMAIN . ATTACHCOMMON . $ret[0]['icon'], // 板块头像
            "threads"	=>	$forum['threads'],
            "posts"	    =>	$forum['posts'],
            "todayposts"=>	$forum['todayposts'],
        );
    }

    //收藏帖子
    $arrTids = array();
    $resThread = DB::fetch_all($arrSql['tid']);
    foreach ($resThread as $k =>  $thread) {
        $arrTids[] = $thread['tid'];
        $arrTmp[$thread['tid']] = array(
            "tid"	    =>	 $thread['tid'],
            "authorName"=>	 $thread['author'],
            "imgUrl"	=>	 '',
            "title"	    =>	 encodToUtf8($thread['subject']),
            "replyNum"	=>	 $thread['replies'],    // 回复数量
            "lastpost"	=>	 formTimestampNature($thread['lastpost']),   // 发帖时间
            "isTop"	    =>	 $thread['stickreply'], // 是否为置顶，"0"为否， "1"为是
        );
    }

    $arrTmp = getImgByTids($arrTids, $arrTmp);  //获取帖子图片

    $arrRet['postList'] = array_values($arrTmp);

    list($arrRet['postList'] , $arrRet['hasMore']) = paging($arrRet['postList'] , $params['page']);

    if ('forum' == $params['type']) {
        $arrRet['postList'] = array();
    }

    if ('thread' == $params['type']) {
        $arrRet['plateList'] = array();
    }

    return  $arrRet;
}

/**
 * getstr
 * @description :
 *
 * @param     $string
 * @param int $length
 * @param int $in_slashes
 * @param int $out_slashes
 * @param int $bbcode
 * @param int $html
 * @return string
 * @author zhaoxichao
 * @date 29/05/2019
 */
function getstr($string, $length = 0, $in_slashes=0, $out_slashes=0, $bbcode=0, $html=0) {
    global $_G;

    $string = trim($string);
    $sppos = strpos($string, chr(0).chr(0).chr(0));
    if($sppos !== false) {
        $string = substr($string, 0, $sppos);
    }
    if($in_slashes) {
        $string = dstripslashes($string);
    }
    $string = preg_replace("/\[hide=?\d*\](.*?)\[\/hide\]/is", '', $string);
    if($html < 0) {
        $string = preg_replace("/(\<[^\<]*\>|\r|\n|\s|\[.+?\])/is", ' ', $string);
    } elseif ($html == 0) {
        $string = dhtmlspecialchars($string);
    }

    if($length) {
        $string = cutstr($string, $length);
    }

    if($bbcode) {
        require_once DISCUZ_ROOT.'./source/class/class_bbcode.php';
        $bb = & bbcode::instance();
        $string = $bb->bbcode2html($string, $bbcode);
    }
    if($out_slashes) {
        $string = daddslashes($string);
    }
    return trim($string);
}