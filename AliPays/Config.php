<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Config
{
    protected $gateWay = 'https://mapi.alipay.com/gateway.do';  //支付宝支付接口
    protected $cacertPath = '';                                 //支付宝证书地址
    protected $partner = '2088911954971419';                     //合作者id
    protected $seller_id = '';                                  // 收款支付宝账号， 一般和签约账号一致
    protected $secret = 'ry1v8f14gd5k5a4hw36kf152g7mxj1nk';     // MD5 秘钥 安全校验码
    protected $notify_url = '';                                 // 服务器异步回调地址
    protected $return_url = '';                                 // 同步通知地址
    protected $sign_type = 'MD5';                               //签名方式
    protected $input_charset = 'utf-8';                         //字符编码格式
    protected $transport = 'http';                              //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
    protected $payment_type = '1';                              // 支付类型 ，无需修改
    protected $service = "create_direct_pay_by_user";           // 产品类型，无需修改
    protected $anti_phishing_key = "";                          // 防钓鱼时间戳  若要使用请调用类文件submit中的query_timestamp函数
    protected $exter_invoke_ip = "";                            //  客户端的IP地址
    /**
     * HTTPS形式消息验证地址
     */
    protected $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    /**
     * HTTP形式消息验证地址
     */
    protected $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

    function __construct()
    {
        $this->seller_id = $this->partner;
        $this->cacertPath = getcwd() . '\\cacert.pem';
    }
}
