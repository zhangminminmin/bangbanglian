<?php

namespace app\api\controller;

use app\api\model\Banner;
use app\api\model\Message;
use app\api\model\Goods;
use app\api\model\News;
use app\api\model\Page;
use app\api\model\Policy;
use app\common\controller\Api;
use think\Validate;

/**
 * 基础接口
 */
class Base extends Api
{
    protected $noNeedLogin = ['company','policy', 'policyInfo', 'newsInfo', 'news', 'linkUsInfo','message','homePage'];
    protected $noNeedRight = [];

    /**
     * 首页
     */
    public function homePage()
    {
        $banner = $this->bannerList();
        $news = $this->newsList();
        $goodsList = $this->goodsList();

        $params = [
            'banner' => $banner,
            'news' => $news,
            'goodsList' => $goodsList,
        ];
        return ajaxReturn(200, '信息获取成功！',$params);
    }
    /**
     * banner 首页辅助
     */
    public function bannerList()
    {
        $list = Banner::where('id', '>', 0)->select();
        $items = [];
        foreach($list as $k => $val) {
            $items[] = [
                'id' => $val->id,
                'image' => cdnurl($val->image, true),
                'type' => $val->type,
                'path' => $val->path,
            ];
        }
        return $items;
    }

    /**
     * 新闻公告  首页辅助
     */
    public function newsList()
    {
        $newsList = News::where('id', '>', 0)->order('id','desc')->paginate(5);
        $items = [];
        foreach($newsList as $k => $val) {
            $items[] = [
                'id' => $val->id,
                'title' => $val->title,
                'format_time' => formatTime($val->created_at),
            ];
        }
        return $items;
    }

    /**
     * 推荐商品  首页推荐展示的
     */
    public function goodsList()
    {
        $list = Goods::where('position', 1)->where('status', 1)->where('id', ">", 0)->whereNull('deleted_at')->paginate(20);
        $items = [];
        foreach ($list as $k => $val) {
            $images = $val->images ? explode(',',$val->images) : [];
            $items[] = [
                'id' => $val->id,
                'title' => $val->title,
                'image' => isset($images[0]) ? cdnurl($images[0],true) : '',
                'buy_num' => $val->buy_num,
                'false_buy_num' => $val->false_buy_num,
                'price' => $val->price,
                'virtual_price' => $val->virtual_price,
            ];
        }
        return $items;
    }
    /**
     * 公司简介
     */
    public function  company()
    {
        $info = Page::where('key', 'company')->find();
        $items = [];
        if ($info) {
            $items = [
                'content' => $info->content ? getImgThumbUrl($info->content,\think\Config::get('upload.cdnurl')) :'',
                'created_at' => date('Y-m-d H:i', $info['created_at']),
            ];
        }
        $data = [
            'info' => $items,
        ];
        return ajaxReturn(200, '公司简介获取成功！', $data);
    }

    /**
     * 商城政策列表
     * page
     * limit
     */
    public function policy()
    {
        $page = $this->request->request('page') ? :1;
        $limit = $this->request->request('limit') ? :15;

        $list = Policy::where('id', '>', 0)
                ->order('id','desc')
                ->paginate($limit);

        $items = [];
        foreach($list as $k => $item) {
            $items[] = [
                'id' => $item->id,
                'title' => $item->title,
                'created_at' => date('Y-m-d', $item->created_at),
            ];
        }

        $param = [
            'total' => $list->total(),
            'list' => $items,
        ];

        return ajaxReturn(200, '商城政策列表获取成功', $param);
    }

    /**
     * 商城政策详情信息
     * id 商城政策id
     */
    public function policyInfo()
    {
        $id = $this->request->request('id');
        if (!$id) {
            return ajaxReturn(202, '参数错误 请刷新重试！');
        }

        $info = Policy::get($id);
        $items = [];
        if ($info) {
            $items = [
                'id' => $info->id,
                'title' => $info->title,
                'content' => $info->content ? getImgThumbUrl($info->content,\think\Config::get('upload.cdnurl')) :'',
                'created_at' => date('Y-m-d', $info->created_at),
            ];
        }
        $param = [
            'info' => $items,
        ];
        return ajaxReturn(200, '商城政策详情获取成功！', $param);
    }

    /**
     * 商城政策列表
     * page
     * limit
     */
    public function news()
    {
        $page = $this->request->request('page') ? :1;
        $limit = $this->request->request('limit') ? :15;

        $list = News::where('id', '>', 0)
            ->order('id','desc')
            ->paginate($limit);

        $items = [];
        foreach($list as $k => $item) {
            $items[] = [
                'id' => $item->id,
                'title' => $item->title,
                'created_at' => date('Y-m-d', $item->created_at),
            ];
        }

        $param = [
            'total' => $list->total(),
            'list' => $items,
        ];

        return ajaxReturn(200, '新闻列表列表获取成功', $param);
    }


    /**
     * 新闻详情信息
     * id 新闻详情id
     */
    public function newsInfo()
    {
        $id = $this->request->request('id');
        if (!$id) {
            return ajaxReturn(202, '参数错误 请刷新重试！');
        }

        $info = News::get($id);
        $items = [];
        if ($info) {
            $items = [
                'id' => $info->id,
                'title' => $info->title,
                'content' => $info->content ? getImgThumbUrl($info->content,\think\Config::get('upload.cdnurl')) :'',
                'created_at' => date('Y-m-d', $info->created_at),
            ];
        }
        $param = [
            'info' => $items,
        ];
        return ajaxReturn(200, '新闻详情获取成功！', $param);
    }

    /**
     * 联系我们信息
     */
    public function linkUsInfo()
    {
        $params = [
            'company_map' => cdnurl(config('site.company_map'), true),
            'link_mobile' => config('site.link_mobile'),
            'company_address' => config('site.company_address'),
            'kfwechat_code' => cdnurl(config('site.kfwechat_code'), true),
            'kfwechat' => config('site.kfwechat'),
        ];
        return ajaxReturn(200, '联系我们页面信息获取成功！', $params);
    }

    /**
     * 提交留言信息
     * realname  姓名
     * mobile  手机号
     * content  留言内容
     */
    public function message()
    {
        $realname = $this->request->request('realname');
        $mobile = $this->request->request('mobile');
        $content = $this->request->request('content');

        if (!$realname) {
            return ajaxReturn(202, '姓名不能为空');
        }

        if (!Validate::regex($mobile, "^1\d{10}$")) {
            return ajaxReturn(202, '手机号格式不正确');
        }

        if (!$content) {
            return ajaxReturn(202, '留言内容不能为空');
        }

        $data = [
            'realname' => $realname,
            'mobile' => $mobile,
            'content' => $content,
        ];

        $message = new Message();
        $m = $message->save($data);
        if ($m) {
            return ajaxReturn(200, '留言成功！');
        }else{
            return ajaxReturn(202, '网络出错 请稍后重试！');
        }

    }
}
