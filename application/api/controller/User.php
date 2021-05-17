<?php

namespace app\api\controller;

use app\api\model\Page;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;
use app\common\model\User as UserModel;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login','registerStepOne', 'registerStepTwo','userAgreement'];
    protected $noNeedRight = [];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->auth->getEncryptPassword();
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /**
     * 会员登录
     *
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->request('mobile');
        $password = $this->request->request('password');
        if (!$account || !$password) {
//            $this->error(__('Invalid parameters'));
            return ajaxReturn(202, '参数不正确');
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
//            $this->success(__('Logged in successful'), $data);
            return ajaxReturn(200, '登录成功！',$data);
        } else {
//            $this->error($this->auth->getError());
            return ajaxReturn(202, $this->auth->getError());
        }
    }


    /**
     * 注册会员
     *
     * @param string $mobile   手机号
     * @param string $code   验证码
     */
    public function registerStepOne()
    {
        $mobile = $this->request->request('mobile');
        $code = $this->request->request('code');

        if (!Validate::regex($mobile, "^1\d{10}$")) {
            return ajaxReturn(202, '手机号格式不正确');
        }

        $info = UserModel::where('mobile', $mobile)->find();
        if ($info) {
            return ajaxReturn(202, '手机号已被注册');
        }

        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            return ajaxReturn(202, '验证码输入不正确');
        }

        return ajaxReturn(200, '手机号验证成功！请设置密码');
    }

    /*
     * 注册第二步骤
     * mobile 手机号
     * code  验证码
     * password 密码
     * repassword 重复密码
     */
    public function registerStepTwo()
    {
        $mobile = $this->request->request('mobile');
        $code = $this->request->request('code');
        $password = $this->request->request('password');
        $repassword = $this->request->request('repassword');

        if (!Validate::regex($mobile, "^1\d{10}$")) {
            return ajaxReturn(202, '手机号格式不正确');
        }

        $info = UserModel::where('mobile', $mobile)->find();
        if ($info) {
            return ajaxReturn(202, '手机号已被注册');
        }

        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            return ajaxReturn(202, '验证码输入不正确');
        }

        if (strlen($password) < 6) {
            return ajaxReturn(202, '密码不能低于六位');
        }

        if ($password != $repassword) {
            return ajaxReturn(202, '两次密码输入的不一致！');
        }

        $data = [
            'mobile' => $mobile,
        ];
        $ret = $this->auth->register($password, $data);
        if (!$ret) {
            return ajaxReturn(202, $this->auth->getError());
        }

        $userinfo = $this->auth->getUserinfo();
        $data = [
            'userinfo' => $userinfo,
        ];
        return ajaxReturn(200, '注册成功！', $data);
    }

    /**
     * 用户协议
     */
    public function userAgreement()
    {
        $info = Page::where('key', 'user_agreement')->find();
        $items = [];
        if ($info) {
            $items = [
                'content' => $info->content ? :"",
                'created_at' => date('Y-m-d H:i', $info->created_at),
            ];
        }

        $data = [
            'info' => $items,
        ];
        return ajaxReturn(200, '信息获取成功！',$items);
    }
}
