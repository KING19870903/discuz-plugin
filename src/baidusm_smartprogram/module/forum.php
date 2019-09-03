<?php

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

//统一处理模块参数
$filter =   array(
    'fid' => 'i',
    'tid' => 'i',
    'pid' => 'i',
    'type'=> 's',
    'uid' =>  'i',
    'filter' => 's',
    'isanonymous'   =>  's',
);
$param  = request_params::filterInput($filter, $_GET);
$params = array_merge($params, $param);

try {
    switch ($params['action']) {
        case 'feed':            //首页帖子流
            $arrResonse['data'] = getFeed($params, $_G);
            break;
        case 'list':            //discuz板块接口
            $arrResonse['data'] = getForum($params, $_G);
            break;
        case 'display':         //帖子列表
            $arrResonse['data'] = getDisplay($params, $_G);
            break;
        case 'detail':          //discuz板块详情接口
            checkToken($params, $_G, false);
            $arrResonse['data'] = getForumDetail($params, $_G);
            break;
        case 'detaillist':      //获取子版块列表
            $arrResonse['data'] = getSubForumList($fid);
            break;
        case 'viewthread':      //帖子信息
            checkToken($params, $_G, false);
            $arrResonse['data'] = getiVewthread($params, $_G);
            break;
        case 'viewpost':        //查看回复列表(查看全部|查看作者)
            $arrResonse['data'] = getiVewPost($params, $_G);
            break;
        case 'post':            //发表回复(发表帖子回复|发表评论回复)
            $arrResonse['data'] = replayPost($params, $_G);
            break;
        case 'delpost':         //删除帖子(tpid:删除帖子|ppid:删除评论)
            $arrResonse['data'] = delPost($params, $_G);
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
 * getFeed
 * @description :   首页帖子流
 *
 * @param $params
 * @param $_G
 * @return array
 * @author zhaoxichao
 * @date 05/06/2019
 */
function getFeed($params, $_G) {
    $arrRet = array(
        'hasMore'   =>  false,
        "threads"   =>  array(),
    );

    //获取发帖最多的板块fid
    $sql = 'select thread.fid,forum.name, count(thread.fid) as count  from '.DB::table('forum_thread').' thread left join '.DB::table('forum_forum').' forum on thread.fid = forum.fid  group by thread.fid order by count desc limit 8';

    $res = DB::fetch_all($sql);
    if (empty($res)) {
        return  $arrRet;
    }
    foreach ($res as $fk => $threadInfo) {
        $threadName =  isset($threadInfo['name']) ? $threadInfo['name'] : '默认板块';
        $fid    =   addslashes($threadInfo['fid']);     //获取帖子流数据(2条最热;1条最新;1条精华)
        $arrSql = array(
            "hot"   =>   'select thread.tid, thread.subject, thread.replies, thread.icon, image.message from '.DB::table('forum_thread').' thread left join '.DB::table('forum_post').' image on thread.tid =  image.tid where thread.fid = '. $fid .' order by thread.heats desc limit 2',
            'new'   =>   'select thread.tid, thread.subject, thread.replies, thread.icon, image.message from '.DB::table('forum_thread').' thread left join '.DB::table('forum_post').' image on thread.tid =  image.tid where thread.fid = '. $fid .' order by thread.lastpost desc limit 1',
            'digest' =>  'select thread.tid, thread.subject, thread.replies, thread.icon, image.message from '.DB::table('forum_thread').' thread left join '.DB::table('forum_post').' image on thread.tid =  image.tid where thread.fid = '. $fid .' order by thread.digest desc limit 1',
        );

        $perm = forumAccess($fid, $_G);
        foreach ($arrSql as $s => $sql) {
            $arrTmp = array();
            $arrTids = array();

            $res = DB::fetch_all($sql);
            if (empty($res)) {
                continue;
            }
            foreach ($res as $key => $value) {
                $arrTids[] = $value['tid'];
                $arrTmp[$value['tid']] = array(
                    'tid'       =>  $value['tid'],
                    'title'     =>  encodToUtf8($value['subject']),
                    'plate'     =>  encodToUtf8($threadName),
                    'replyNum'  =>  encodToUtf8($value['replies']),
                    'imgUrl'    =>  array(),
                    'perm'      =>  $perm,
                );
            }

            $arrTmp = getImgByTids($arrTids, $arrTmp);  //批量获取帖子图片

            $rest = getThreadDetailByTids($arrTids);
            if (!empty($rest)) {
                foreach ($rest as $k => $v) {
                    $arrTmp[$v['tid']]['author']  = encodToUtf8($v['author']);
                }
            }

            foreach ($arrTmp as $vs) {
                $arrRet['threads'][] = $vs;
            }
        }
    }

    // 插入广告数据
    $arrRet['threads'] = insertAdvert($arrRet['threads'], 'feed', 1);

    //分页
    list($arrRet['threads'], $arrRet['hasMore']) = paging($arrRet['threads'], $params['page']);

    return  $arrRet;
}

/**
 * getForum
 * @description :   discuz板块接口
 *
 * @param $params
 * @param $_G
 * @return array
 * @author zhaoxichao
 * @date 21/06/2019
 */
function getForum($params, $_G) {
    $data = array(
        'hasMore'   =>  false,
        'forumList' =>  array(),
    );

    $forums = $GLOBALS['forums'] ? $GLOBALS['forums'] : C::t('forum_forum')->fetch_all_by_status(1);

    $forumlist = array();
    //只需遍历二级板块
    foreach ($forums as $forum) {
        $fid = $forum["fid"];
        $arrField = C::t('forum_forumfield')->fetch($fid);
        //获取论坛icon
        if (!empty($arrField["icon"]) && !preg_match('/^http:\//', $arrField["icon"])) {
            $icon = $_G['siteurl'] . "data/attachment/common/" . $arrField["icon"];
            $forum['icon'] = $icon;
        }

        if ($forum['fup'] && !empty($forumlist[$forum['fup']])) {
            //是二级板块
            $forumlist[$forum['fup']]['sublist'][] = smart_core::getvalues($forum, array('fid', 'icon', 'name', 'threads', 'posts', 'redirect', 'todayposts', 'description'));
        } else if ($forum['fup'] == 0) {
            //是一级板块
            $forumlist[$forum['fid']] = smart_core::getvalues($forum, array('fid', 'name', 'threads', 'posts', 'redirect', 'todayposts', 'description'));
        }
    }

    $data = array(
        'forumList' => array_values(smart_core::getvalues($forumlist, array('/^\d+$/'), array('fid', 'name', 'threads', 'posts', 'redirect', 'todayposts', 'description', 'sublist', 'icon'))),
    );

    foreach ($data['forumList'] as $key => $value) {
        if (isset($value['sublist'])) {
            $data['forumList'][$key]['subList'] = $value['sublist'];
            unset($data['forumList'][$key]['sublist']);
        } else {
            unset($data['forumList'][$key]);
        }

    }

    //添加板块读取权限和登录权限
    foreach ($data['forumList'] as $k => $value) {
        $data['forumList'][$k]['perm'] = forumAccess($value['fid']);                    //板块权限
        $data['forumList'][$k]['name'] = encodToUtf8($value['name']);
        foreach ($data['forumList'][$k]['subList'] as $kk => $kv) {
            $data['forumList'][$k]['subList'][$kk]['perm']      = forumAccess($kv['fid']);   //板块权限
            $data['forumList'][$k]['subList'][$kk]['isLogin']   = false;
            $data['forumList'][$k]['subList'][$kk]['name']      = encodToUtf8($kv['name']);
        }
    }



    //添加分页
    list($data['forumList'], $data['hasMore']) = paging($data['forumList'], $params['page'], 4);

    return $data;
}


/**
 * getDisplay
 * @description :   帖子列表
 *
 * @param $params
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 13/06/2019
 */
function getDisplay($params, $_G) {
    $arrRet = array(
        'hasMore'   =>  false,
        'sortList'  =>  array(
            array(
                "name"      => "最新",
                "filter"    =>  "lastpost",
            ),
            array(
                "name"      => "热门",
                "filter"    =>  "heats",
            ),
            array(
                "name"      => "精华",
                "filter"    =>  "digest",
            ),
        ),
        'forumThreadList'   =>  array(),
    );
    $arrTmp = array();

    $fid    =   addslashes($params['fid']);
    if (empty($fid)) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID, 'fid');
    }

    $order = (in_array($params['filter'], array('heats', 'lastpost', 'digest'))) ?  trim($params['filter']) : 'lastpost';
    $sqls = array(
        'lastpost' =>  'select * from '.DB::table('forum_thread').' where fid = ' .$fid. ' and displayorder >= 0 order by lastpost desc',
        'heats'    =>  'select * from '.DB::table('forum_thread').' where fid = ' .$fid. ' and displayorder >= 0 order by heats desc',
        'digest'   =>  'select * from '.DB::table('forum_thread').' where fid = ' .$fid. ' and displayorder >= 0 and digest = 1 order by dateline desc',
    );

    $res = DB::fetch_all($sqls[$order]);
    if (empty($res)) {
        return  $arrRet;
    }

    //板块权限
    foreach ($res as $key => $value) {
        $arrTids[] = $value['tid'];
        $arrTmp[$value['tid']] = array(
            "tid"       =>   $value['tid'],
            "title"     =>   encodToUtf8($value['subject']),
            "imgUrl"    =>   array(),
            "author"    =>   encodToUtf8($value['author']),
            "replyNum"  =>   encodToUtf8($value['replies']),
            "typeid"    =>   $value['typeid'],       //主题分类id
            "price"     =>   $value['price'],
            "authorId"  =>   $value['authorid'],
            "dateLine"  =>   date("Y-m-d", $value['dateline']),
            "lastPost"  =>   date("Y-m-d", $value['lastpost']),
            "lastPoster"=>   $value['lastposter'],
            "views"     =>   $value['views'],
        );
    }

    $arrTmp = getImgByTids($arrTids, $arrTmp);  //获取帖子图片

    $forumThreadlist = array_values($arrTmp);

    $forumThreadlist = insertAdvert($forumThreadlist, 'feed', 2);

    list($arrRet['forumThreadList'], $arrRet['hasMore']) = paging($forumThreadlist, $params['page']);

    return  $arrRet;
}

/**
 * getForumDetail
 * @description :   discuz板块详情接口
 *
 * @param $params
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 12/06/2019
 */
function getForumDetail($params, $_G) {
    $arrRet = array(
        'hasMore'	=>	false,
        'plateInfo'	=>	array(),        //板块信息
        'subPlateList'	=>	array(),    //子板块信息
    );

    $fid    =   addslashes($params['fid']);
    $page   =   addslashes($params['page']);
    if (empty($fid)) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID, 'fid');
    }

    //获取板块详情
    $arrRet['plateInfo'] = getForumDetailByFid($fid, $_G);

    //获取子板块详情
    $subForumList = getSubForumList($fid);
    if (empty($subForumList)) {
        return  $arrRet;
    }

    list($arrRet['subPlateList'], $arrRet['hasMore']) = paging($subForumList['subPlateList'], $page);

    return  $arrRet;
}

/**
 * getSubForumList
 * @description :   获取子版块列表
 *
 * @param $fid
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 03/06/2019
 */
function getSubForumList($fid) {
    if (empty($fid)) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID, 'fid');
    }

    $arrRet = array(
        "subPlateList"  =>  array(),
    );

    $sql = 'select forum.name,forum.threads,forum.posts,forum.todayposts,forum.fid,field.icon from '.DB::table('forum_forum').' forum left join '.DB::table('forum_forumfield').' field on forum.fid = field.fid where forum.fup = '. $fid;
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        return   $arrRet;
    }

    foreach ($res as $key => $value) {
        $perm = forumAccess($value['fid']);                  //板块权限
        $arrRet['subPlateList'][] = array(
            "fid"	    =>	$value['fid'],
            "name"	    =>	$value['name'],
            "icon"	    =>	empty($value['icon']) ? '' : DOMAIN . ATTACHCOMMON . $value['icon'], // 板块头像
            "threads"	=>	$value['threads'],
            "posts"		=>	$value['posts'],
            "todayposts"=>	$value['todayposts'],
            "isLogin"   =>  false,      //需用户登录
            'perm'      =>  $perm,
        );
    }

    return $arrRet;
}

/**
 * getiVewthread
 * @description :   帖子信息
 *
 * @param $params
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 06/06/2019
 */
function getiVewthread($params, $_G) {
    $arrRet = array(
        "allow_view"    =>  0,
        "threadInfo"    =>  array(),
        "forumThreadlist"   =>  array(),
    );

    $tid = addslashes($params['tid']);

    //获取帖子详情
    $sqlThread = "select forum.name, forum.fid, thread.tid, thread.subject, thread.dateline, thread.author, thread.authorid, thread.replies from ".DB::table('forum_thread')." thread left join ".DB::table('forum_forum')." forum on thread.fid = forum.fid where thread.tid = " . $tid;
    $thread = DB::fetch_all($sqlThread);
    if (empty($thread)) {
        return  $arrRet;
    }
    $thread = $thread[0];

    //获取帖子内容
    $sqlPost = "select message,pid from ".DB::table('forum_post')." where first = 1 and tid = " . $tid;
    $res = DB::fetch_all($sqlPost);
    if (empty($res)) {
        return  $arrRet;
    }
    $post = $res[0];

    $favor = isCollected('tid', $tid, $_G['uid']);          //是否收藏

    $userInfo = UserInfo($params, $_G['uid']);
    $perm = forumAccess($thread['fid']);                    //板块权限

    $arrTids = array();
    $threadInfo = array(
        "source"    =>    encodToUtf8($thread['name']),
        "pid"       =>    $post['pid'],
        "fid"       =>    $thread['fid'],
        "tid"       =>    $thread['tid'],
        "author"    =>    isset($thread['author']) ? $thread['author'] : '',
        "authorId"  =>    $thread['authorid'],
        "title"     =>    encodToUtf8($thread['subject']),
        "dateLine"  =>    formTimestamp($thread['dateline']),
        "message"   =>    encodToUtf8($post['message']),
        "imageUrl"  =>    array(),
        "avatar"    =>    $userInfo['avatar'],
        "replyNums" =>    $thread['replies'], //回复次数
        "isFavorite"=>    $favor,   //是否收藏
        "isLogin"   =>    false,
        "perm"      =>    $perm,
    );

    $arrTids[] = $thread['tid'];
    $icons = getIconsByTids($arrTids);
    $imageUrl = array();
    if (!empty($icons)) {
        foreach ($icons as $km => $vm) {
            if (false !== strpos($vm['attachment'], 'http')
                || false !== strpos($vm['attachment'], 'https')) {
                $img = parseImg($vm['attachment']);
            } else {
                $img = '';
                $img =  DOMAIN . ATTACHMENTFORM . $vm['attachment'];
            }
            $imageUrl  = empty($img) ? array() : array($img);
        }
    }

    $threadInfo['imageUrl'] = $imageUrl;

    //处理PC端发布图片帖子
    if (!empty($imageUrl) && false === strpos($post['message'], 'jpg')
        && false === strpos($post['message'], 'png')
        && false === strpos($post['message'], 'gif')
        && false === strpos($post['message'], 'jpeg')) {
        $threadInfo['message'] = '[img]'.$img.'[/img]' . $threadInfo['message'];
    }

    //插入广告
    $threadInfo = insertAdvert($threadInfo, 'thdend', 3);

    $arrRet['threadInfo'] = $threadInfo;

    //获取评论信息
    $postList = getiVewPost($params, $_G);

    $arrRet = array_merge($arrRet, $postList);

    return $arrRet;
}

/**
 * getiVewPost
 * @description :   回复列表
 *
 * @param $params
 * @param $_G
 * @return array
 * @author zhaoxichao
 * @date 06/06/2019
 */
function getiVewPost($params, $_G) {
    $arrRet = array(
        'hasMore'           =>   false,
        'forumThreadlist'   =>  array(
            "allCommentList"          =>  array(),
            "onlyLandlordCommentList" =>  array(),
        ),
    );

    $tid    =   addslashes($params['tid']);
    if (empty($tid)) {
        return  $arrRet;
    }

    $postInfo = getPostByTid($tid);     //根据tid获取帖子回复信息
    if (empty($postInfo)) {
        return  $arrRet;
    }

    $postUser = getPostUserByTid($tid);     //根据tid获取帖子版主信息
    $author   = isset($postUser[0]['author']) ? $postUser[0]['author'] : '';

    //获取当前用户信息
    $space = UserInfo($params, $_G['uid']);

    foreach ($postInfo as $key => $value) {
        if (!empty($author) && $author == $value['author']) {
            $onlyLandlordCommentList[] =  formatPostInfo($value, $space);   //楼主回复
        }
        $allCommentList[] = formatPostInfo($value, $space);                 //全部回复
    }

    //插入广告数据
    $allCommentList = insertAdvert($allCommentList, 'thdreply', 4);
    $onlyLandlordCommentList = insertAdvert($onlyLandlordCommentList, 'thdreply', 4);

    //格式化分页结果
    list($allCommentList, $pageAll) = paging($allCommentList, $params['page']);
    list($onlyLandlordCommentList, $pageAuthor) = paging($onlyLandlordCommentList,$params['page']);

    if ('all' == $params['type']) {              //仅楼主回复
        $arrRet['forumThreadlist']['allCommentList'] = $allCommentList;
    } elseif ('author' == $params['type']) {     //仅全部回复
        $arrRet['forumThreadlist']['onlyLandlordCommentList'] = $onlyLandlordCommentList;
    } else {                                     //楼主回复+全部回复
        $arrRet['forumThreadlist'] = array(
            'allCommentList'            =>  $allCommentList,
            'onlyLandlordCommentList'   =>  $onlyLandlordCommentList,
        );
    }

    $arrRet['hasMore'] = ('author' ==  $params['type']) ? $pageAuthor : $pageAll;

    return $arrRet;
}

/**
 * getPostByTid
 * @description :   获取回复信息
 *
 * @param $tid
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 03/06/2019
 */
function getPostByTid($tid) {
    $arrRet = array();

    if (empty($tid)) {
        throw new discuz_excption(error_plugin::ERROR_PARAMS_INVALID);
    }
    $sql = "select * from ".DB::table('forum_post')." where first = 0 and tid = " . $tid . " order by position asc";
    $arrRet = DB::fetch_all($sql);
    if (empty($arrRet)) {
        return  $arrRet;
    }

    return  $arrRet;
}

/**
 * getPostUserByTid
 * @description :   获取帖子发布者名称
 *
 * @param $tid
 * @return array
 * @author zhaoxichao
 * @date 20/06/2019
 */
function getPostUserByTid($tid) {
    $arrRet = array();

    if (empty($tid)) {
        throw new discuz_excption(error_plugin::ERROR_PARAMS_INVALID);
    }
    $sql = "select author from ".DB::table('forum_post')." where first = 1 and tid = " . $tid;
    $arrRet = DB::fetch_all($sql);
    if (empty($arrRet)) {
        return  $arrRet;
    }

    return  $arrRet;
}


/**
 * replayPost
 * @description :   发表帖子type=thread|发表回复type=post
 *
 * @param $params
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 10/06/2019
 */
function replayPost($params, $_G) {
    $arrRet = array();

    //获取请求参数
    $filter =   array(
        'message'   =>  's',
        'subject'   =>  's',
        'imgUrl'    =>  's',
    );
    $param  = request_params::filterInput($filter, $_GET);
    $params = array_merge($params, $param);

    $fid        = addslashes($params['fid']);           //必填
    $tid        = addslashes($params['tid']);
    $oldPid     = addslashes($params['pid']);           //被回复的pid
    $message    = addslashes($params['message']);       //评论内容
    $subject    = addslashes($params['subject']);
    $user       = UserInfo($params, $_G['uid']);
    $imgUrl     = $params['imgUrl'];

    if (CHARSET == 'gbk') {
        $message = iconv('utf-8', 'gbk',$message);
        $subject = iconv('utf-8', 'gbk',$subject);
    }

    //生成新pid
    $newId = C::t('forum_post_tableid')->insert(array('pid' => null), true);

    //message拼装
    if (!empty($imgUrl) && '[]' !== $imgUrl) {
        $imgUrl = '[img]' . $imgUrl . '[/img]' . PHP_EOL;
        $message = $imgUrl .'   '. $message;
    }

    $newPost = array(
        'pid'       =>  $newId,
        'tid'       =>  $tid,
        'fid'       =>  $fid,
        'author'    =>  $user['username'],
        'authorid'  =>  $user['uid'],
        'subject'   =>  $subject,
        'message'   =>  $message,
        'dateline'  =>  time(),
        'position'  =>  '',     //帖子位置信息(自增)
    );

    $imgNew = '';
    $type  = addslashes($params['type']);
    if (!in_array($type, array('post', 'thread'))) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID);
    }
    //发表回复
    if ('post' == $type) {
        if(empty($fid) || empty($tid) || empty($oldPid)
           || empty($message) || empty($subject)) {
            throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID);
        }

        $resP = DB::insert('forum_post', $newPost);
        if (!$resP) {
            throw new discuz_exception(error_plugin::ERROR_INSERT_DATA_ERROR);
        }

        $newDetail = getDetailByPid($newId);

        $oldDetail = getDetailByPid($oldPid);
        if (empty($oldDetail)) {
            $replaypost = '';
        } else {
            $replaypost = array(
                'author'    =>  isset($oldDetail['author']) ? $oldDetail['author'] : '匿名',
                'dateLine'  =>  formTimestampNature($oldDetail['dateline']),
                'message'   =>  encodToUtf8($oldDetail['message']),
                'position'  =>  $oldDetail['position'],
            );
        }

        //回复次数加1
        repliesPlus($tid);

        $arrRet =   array(
            "position"		=>	$newDetail['position'],  //最新回帖的信息
            "message"		=>	encodToUtf8($newDetail['message']),
            "pid"			=>	$newDetail['pid'],
            "dateLine"      =>  formTimestampNature($newDetail['dateline']),
            "author"        =>  isset($newDetail['author']) ? $newDetail['author'] : '匿名',
            "authorId"      =>  $user['uid'],
            "avatar"        =>  $user['avatar'],
            "replayPost"	=>	$replaypost,        //被回复帖子信息
        );
    }

    //发表帖子
    if ('thread' == $type) {
        if(empty($fid) || empty($message) || empty($subject)) {
            throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID);
        }

        #本版有待处理的管理事项
        C::t('forum_forum')->update($fid, array('modworks' => '1'));

        //insert thread
        $newThread = array(
            'tid'       =>  '',     //自增ID
            'fid'       =>  $fid,   //必填参数
            'posttableid' => 0,
            'author'    =>  $user['username'],
            'authorid'  =>  $user['uid'],
            'subject'   =>  $subject,
            'dateline'  =>  time(),
            'lastpost'  =>  time(),
            'lastposter' => $user['username'],
            'attachment' => 0,

        );

        $insertTid = C::t('forum_thread')->insert($newThread, true);
        if (!$insertTid) {
            throw new discuz_exception(error_plugin::ERROR_INSERT_DATA_ERROR);
        }

        #最新主题表
        C::t('forum_newthread')->insert(array(
            'tid' => $insertTid,
            'fid' => $fid,
            'dateline' => time(),
        ));

        #用户家园字段表 - 更新:最近一次行为记录
        if(!$params['isanonymous']) {
            C::t('common_member_field_home')->update($user['uid'], array('recentnote'=>$subject));
        }

        //插入threadimage
        if (!empty($imgUrl) && $insertTid) {
            $newImg = array(
                'tid'        => $insertTid,
                'attachment' => $imgUrl,
            );
            $resP = DB::insert('forum_threadimage', $newImg);
            if (!$resP) {
                throw new discuz_exception(error_plugin::ERROR_INSERT_DATA_ERROR);
            }
        }

        $arr = array(
            'tid'   =>  $insertTid,
            'first' => 1,           //代表首帖
            'attachment' => '0',
            'replycredit' => 0,
        );
        $newPost = array_merge($newPost, $arr);
        $resP = DB::insert('forum_post', $newPost);
        if (!$resP) {
            throw new discuz_exception(error_plugin::ERROR_INSERT_DATA_ERROR);
        }

        $arrRet = array(
            'tid'   =>  $insertTid,
        );
    }

    return $arrRet;
}

/**
 * delPost
 * @description :   删除帖子
 *
 * @param $params
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 12/06/2019
 */
function delPost($params, $_G) {
    $arrRet = array();

    //获取请求参数
    $filter =   array(
        'ppid' => 'i',
        'tpid' => 'i',
    );
    $param = request_params::filterInput($filter, $_GET);
    $params = array_merge($params, $param);

    $tpid   =   addslashes($params['tpid']);
    $ppid   =   addslashes($params['ppid']);

    $pid = !empty($tpid) ? $tpid : $ppid;
    if (empty($pid)) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID);
    }

    //删除评论
    if ($ppid) {
        $condition = array(
            "pid"       =>  $pid,
            "authorid"  =>  $_G['uid'],
        );

        $result = DB::delete('forum_post', $condition);
        if (empty($result)) {
            throw new discuz_exception(error_plugin::ERROR_DELETE_DATA_ERROR);
        }

        $arrRet['pid'] = $pid;  //删除成功失败标识
    }

    //删除帖子
    if ($tpid) {
        $condition = array(
            "tid"   =>  $pid,
            "authorid"  =>  $_G['uid'],
        );

        $result = DB::delete('forum_thread', $condition);
        if (empty($result)) {
            throw new discuz_exception(error_plugin::ERROR_DELETE_DATA_ERROR);
        }

        $arrRet['tid'] = $pid;  //删除成功失败标识
    }

    return $arrRet;
}

/**
 * getForumDetailByFid
 * @description :   获取板块详情
 *
 * @param $fid
 * @param $_G
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 12/06/2019
 */
function getForumDetailByFid($fid, $_G) {
    $arrRet = array();

    if (empty($fid)) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID, 'fid');
    }

    $sql = 'select forum.name,forum.favtimes,forum.posts,forum.rank,forum.fid,field.icon from '.DB::table('forum_forum').' forum left join '.DB::table('forum_forumfield').' field on forum.fid = field.fid where forum.fid = '. $fid;
    $res = DB::fetch_all($sql);
    if (empty($res)) {
        throw new discuz_exception(error_plugin::ERROR_SELECT_DATA_EMPTY);
    }

    $ret = $res[0];
    $perm = forumAccess($fid, $_G);                  //板块权限
    $collected = isCollected('fid', $fid, $_G['uid']);      //是否收藏
    $arrRet = array(
        "name"	        =>	encodToUtf8($ret['name']),
        "author"	    =>	encodToUtf8("admin"),                // 站长
        "favoriteNum"	=>	$ret['favtimes'],       // 收藏数量
        "postsNum"	    =>	$ret['posts'],          // 帖子总数
        "rank"	        =>	$ret['rank'],           // 排名
        "fid"	        =>	$ret['fid'],            // 板块id
        "isCollected"	=>	$collected,             // 是否收藏
        "icon"	        =>	empty($ret['icon']) ? '' : DOMAIN . ATTACHCOMMON . $ret['icon'], // 板块头像
        "perm"	        =>	$perm,                  //用户权限 1:可读，2:可写 0:无权限
    );

    return $arrRet;
}

/**
 * getThreadDetailByTids
 * @description :   根据tid获取帖子详情
 *
 * @param $arrTids
 * @return array
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 01/06/2019
 */
function getThreadDetailByTids($arrTids) {
    if (empty($arrTids)) {
        throw new discuz_exception(error_plugin::ERROR_PARAMS_INVALID, 'fid');
    }

    $strTid = count($arrTids) > 1 ? implode(',', $arrTids) : $arrTids[0];
    $sqlThreadDetail = 'select * from '.DB::table('forum_post').' where tid in ('. $strTid .') and first = 1';

    $res = DB::fetch_all($sqlThreadDetail);

    return $res;
}

/**
 * formatPostInfo
 * @description :   格式化帖子回复数据
 *
 * @param $value
 * @param $space
 * @return array
 * @author zhaoxichao
 * @date 06/06/2019
 */
function formatPostInfo($value, $space) {
    $arrRet = array(
        "pid"       =>  $value['pid'],
        "tid"       =>  $value['tid'],
        "author"    =>  encodToUtf8($value['author']),
        "authorId"  =>  $value['authorid'],
        "dateLine"  =>  formTimestamp($value['dateline']),
        "message"   =>  encodToUtf8($value['message']),
        "anonymous" =>  encodToUtf8($value['anonymous']),    //是否匿名
        "position"  =>  $value['position'],     //位置包含帖子
        "userName"  =>  isset($space['username']) ? $space['username'] : '匿名',
        "avatar"    =>  $space['avatar'],
        "groupId"   =>  $space['groupid'],      //用户组id
    );

    return  $arrRet;
}