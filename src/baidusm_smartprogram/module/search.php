<?php
/**
 * search.php
 *
 * @description :   搜索接口
 *
 * @author : zhaoxichao
 * @since : 03/06/2019
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once libfile('function/misc');

//统一处理模块参数
$filter =   array(
    'searchid'  =>  'i',
    'orderby'   =>  's',
    'ascdesc'   =>  's',
    'searchsubmit'  =>  's',
    'kw'        =>  's',
);
$param = request_params::filterInput($filter, $_GET);
$params = array_merge($params, $param);

try {
    switch ($params['action']) {
        case 'forum':           //搜索接口
            $arrResonse['data'] = getSearchForum($params, $_G);
            break;
        case 'xxx':             //收藏功能
            $arrResonse['data'] = getFavorite($_G);
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
 * getSearchForum
 * @description :   搜索
 *
 * @param $params
 * @param $_G
 * @return mixed
 * @throws discuz_exception
 * @author zhaoxichao
 * @date 05/06/2019
 */
function    getSearchForum($params, $_G) {
    $arrRet = array(
        'hasMore'   =>  false,
        'forumThreadList'   =>  array(),
    );

    $kw =  addslashes($params['kw']);
    if (CHARSET == 'gbk') {
        $kw = iconv('utf-8', 'gbk',$kw);
    }
    if (empty($kw)) {
        throw new discuz_exception(error_plugin::ERROR_QUERY_EMPTY);
    }

    $sql = "select thread.*, post.message from ".DB::table('forum_thread')." thread left join ".DB::table('forum_post')." post on thread.tid =  post.tid where thread.subject like '%" . $kw . "%'";

    $res = DB::fetch_all($sql);
    if (empty($res)) {
        throw new discuz_exception(error_plugin::ERROR_QUERY_RESULE_EMPTY);
    }

    $arrTids = array();
    foreach ($res as $key => $value) {
        $arrTids[] = $value['tid'];
        $arrTmp[$value['tid']] = array(
            "tid"		=>	$value['tid'],      //板块id
            "perm"	    =>	$value['readperm'], //是否限制阅读
            "price"		=>	$value['price'],
            "author"	=>	$value['author'],   //作者昵称
            "authorId"	=>	$value['authorid'],
            "title"	    =>	encodToUtf8($value['subject']),  //标题
            "message"   =>  encodToUtf8($value['message']),  //message
            "dateLine"	=>	date("Y-m-d", $value['dateline']),
            "lastPost"	=>	formTimestampNature($value['lastpost']), //只回复
            "lastPoster"=>	encodToUtf8($value['lastposter']),
            "views"		=>	$value['views'],    //点击次数
            "replyNum"	=>	$value['replies'],  //回复次数
            "imgUrl"	=>	array(),            //封面图片
        );
    }

    $arrTmp = getImgByTids($arrTids, $arrTmp);  //获取帖子图片

    $arrRet['forumThreadList'] = array_values($arrTmp);

    //分页
    list($arrRet['forumThreadList'], $arrRet['hasMore']) = paging($arrRet['forumThreadList'], $params['page']);

    return  $arrRet;
}

/**
 * bat_highlight
 * @description :
 *
 * @param        $message
 * @param        $words
 * @param string $color
 * @return mixed|string
 * @author zhaoxichao
 * @date 12/06/2019
 */
function bat_highlight($message, $words, $color = '#ff0000') {
    if(!empty($words)) {
        $highlightarray = explode(' ', $words);
        $sppos = strrpos($message, chr(0).chr(0).chr(0));
        if($sppos !== false) {
            $specialextra = substr($message, $sppos + 3);
            $message = substr($message, 0, $sppos);
        }
        bat_highlight_callback_highlight_21($highlightarray, 1);
        $message = preg_replace_callback("/(^|>)([^<]+)(?=<|$)/sU", 'bat_highlight_callback_highlight_21', $message);
        $message = preg_replace("/<highlight>(.*)<\/highlight>/siU", "<strong><font color=\"$color\">\\1</font></strong>", $message);
        if($sppos !== false) {
            $message = $message.chr(0).chr(0).chr(0).$specialextra;
        }
    }
    return $message;
}

/**
 * bat_highlight_callback_highlight_21
 * @description :
 *
 * @param     $matches
 * @param int $action
 * @return string
 * @author zhaoxichao
 * @date 12/06/2019
 */
function bat_highlight_callback_highlight_21($matches, $action = 0) {
    static $highlightarray = array();

    if($action == 1) {
        $highlightarray = $matches;
    } else {
        return highlight($matches[2], $highlightarray, $matches[1]);
    }
}

/**
 * highlight
 * @description :
 *
 * @param $text
 * @param $words
 * @param $prepend
 * @return string
 * @author zhaoxichao
 * @date 12/06/2019
 */
function highlight($text, $words, $prepend) {
    $text = str_replace('\"', '"', $text);
    foreach($words AS $key => $replaceword) {
        $text = str_replace($replaceword, '<highlight>'.$replaceword.'</highlight>', $text);
    }
    return "$prepend$text";
}
