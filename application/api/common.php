<?php
/**
 * @param $code
 * @param $msg
 * @param array $content
 * @return \think\response\Json
 */
function ajaxReturn($code, $msg, $content = [])
{
    $content = (object)$content;
    throw new \think\exception\HttpResponseException(json(compact('code', 'msg', 'content')));
}


/*
 * 随机 自定义位数的字符串
 */
function getRandChar($length){
    $str = '0123456789abcdefghijklmnopqrstuvwxyz';
    $len = strlen($str);
    $return = '';
    for($i=0;$i<$length;$i++){
        $num = mt_rand(0,$len-1);
        $return .= $str{$num};
    }
    return strtoupper($return);
}


// 格式化时间
function formatTime($val)
{
    $x = time();
    $a = $x - $val;

    if ($a < 60) {
        return $a . "秒前";
    } elseif ($a < (60 * 60)) {
        return round($a / 60) . "分钟前";
    } elseif ($a < (24 * 60 * 60)) {
        return round($a / 60 / 60) . "小时前";
    } elseif ($a < (30 * 24 * 60 * 60)){
        return round($a / 24 / 60 / 60) . "天前";
    } else {
        return round($a / 30 / 24 / 60 / 60) . "月前";
    }
}


function getImgThumbUrl($content="",$suffix){
    if (preg_match('/(http:\/\/)|(https:\/\/)/i', $content)) {
        $url = "";
    }else{
        $url = "http://" . $_SERVER['SERVER_NAME'];
    }
    $pregRule = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/";
    $content = preg_replace($pregRule, '<img src="' . $url . '${1}">', $content);

    return $content;
}  