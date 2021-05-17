<?php

namespace addons\clsms\library;

/**
 * 创蓝SMS短信发送
 * 如有问题，请加微信  andiff424  QQ:165607361
 */
class Clsms
{

    private $_params = [];
    protected $error = '';
    protected $config = [];

    public function __construct($options = [])
    {
        if ($config = get_addon_config('clsms'))
        {
            $this->config = array_merge($this->config, $config);
        }
        $this->config = array_merge($this->config, is_array($options) ? $options : []);
    }

    /**
     * 单例
     * @param array $options 参数
     * @return Clsms
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance))
        {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 立即发送短信
     *
     * @return boolean
     */
    public function send()
    {
        $flag = 0;
        $params=''; //要post的数据
        $param = $this->_params();
        $argv = array(
            'accesskey'=>"QUG8vY7OCfk0ebnE",     //平台分配给用户的accesskey，登录系统首页可点击"我的秘钥"查看
            'secret'=>"ciKkzSeqqKtyNs0XEMXDwnOgFdqn3dfq",     //平台分配给用户的secret，登录系统首页可点击"我的秘钥"查看
            'sign'=>'141125',   //平台上申请的接口短信签名或者签名ID（须审核通过），采用utf8编码
            'templateId'=>'171823',   //平台上申请的接口短信模板Id（须审核通过）
            'mobile'=>$param['mobile'],   //接收短信的手机号码(只支持单个手机号)
            'content'=> $param['msg']  ,   //发送的短信内容是模板变量内容，多个变量中间用##或者$$隔开，采用utf8编码
        );

        //构造要post的字符串
        foreach ($argv as $key=>$value) {
            if ($flag!=0) {
                $params .= "&";
                $flag = 1;
            }
            $params.= $key."="; $params.= urlencode($value);// urlencode($value);
            $flag = 1;
        }
        $url = "http://api.1cloudsp.com/api/v2/single_send?".$params; //提交的url地址
        $con= substr( file_get_contents($url), 0,100 );  //获取信息发送后的状态
        $con_code = json_decode($con, true);
        if($con_code['code'] == '0'){
            return true;
        }else{
            return false;
        }
    }

    private function _params()
    {
        $smstype = isset($this->_params['smstype']) ? $this->_params['smstype'] : 0;
        return array_merge([
            'smstype'  => $smstype,
            'account'  => ($smstype ? $this->config['key1'] : $this->config['key']),
            'password' => ($smstype ? $this->config['secret1'] : $this->config['secret']),
            'sign'     => $this->config['sign'],
            'report'   => true,
        ], $this->_params);
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 短信类型
     * @param   string    $st       0验证码1会员营销短信（会员营销短信不能测试）
     * @return Clsms
     */
    public function smstype($st = 0)
    {
        $this->_params['smstype'] = $st;
        return $this;
    }

    /**
     * 接收手机
     * @param   string  $mobile     手机号码
     * @return Clsms
     */
    public function mobile($mobile = '')
    {
        $this->_params['mobile'] = $mobile;
        return $this;
    }

    /**
     * 短信内容
     * @param   string  $msg        短信内容
     * @return Clsms
     */
    public function msg($msg = '')
    {
        $this->_params['msg'] = $msg;
        return $this;
    }

}
