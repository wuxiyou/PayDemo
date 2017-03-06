<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 微信常用用工具类
 * @author wxy
 */

class WxTools
{
    //可以在配置信息里配置
    const APPID = 'wx426b3015555a46be';
    const MCHID = '1900009851';
    const KEY = '8934e7d15453e97507ef794cf7b0519d';
    const APPSECRET = '7813490da6f1265e4901ffb80afaa36f';


    /**
     * TODO：设置商户证书路径
     * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
     * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
     * @var path
     */
    const SSLCERT_PATH = '../cert/apiclient_cert.pem';
    const SSLKEY_PATH = '../cert/apiclient_key.pem';

    //=======【curl代理设置】===================================
    /**
     * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
     * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
     * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
     * @var unknown_type
     */
    const CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
    const CURL_PROXY_PORT = 0;//8080;


    // 统一下单网关
    const gateWay = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    // 退款网关
    const gateWayRefund = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

    //订单查询

    // 支付默认配置
    protected $defaultConfig = array(
        // 货币类型
        'fee_type' => 'CNY',
        // 交易类型
        "trade_type" => "JSAPI",
        // 交易类型-扫码支付
        "trade_type_scan" => "NATIVE"
    );

    /**
     * 生成32位的随机字符串
     * @param int $length  字符串长度
     * @return string
     */
    public function createNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = '';
        for ( $i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 格式化参数
     * @param array $params
     * @return bool|string
     */
    public function formatUrlParams($params = array())
    {
        if (empty($params) || count($params) <= 0) {
            return false;
        }
        $buff = "";
        foreach ($params as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 数组转换为xml
     * @param array $params
     * @return bool|string
     */
    public function array2xml($params = array())
    {
        if (empty($params) || count($params) <= 0) {
            return false;
        }
        $xml  = '<xml>';
        foreach ($params as $key => $val) {
            if (is_numeric($key)) {
                $xml .= '<' .$key. '>' .$val. '</'. $key. '>';
            } else {
                $xml .= '<' .$key. '><![CDATA[' .$val. ']]></' .$key. '>';
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * xml 转换为 array
     * @param $xml
     * @return bool|mixed
     */
    public function xml2array($xml)
    {
        if(!$xml){
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $array = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array;
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     */
    public static function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        //如果有配置代理这里就设置代理
        if(self::CURL_PROXY_HOST != "0.0.0.0" && self::CURL_PROXY_PORT != 0)
        {
            curl_setopt($ch,CURLOPT_PROXY, self::CURL_PROXY_HOST);
            curl_setopt($ch,CURLOPT_PROXYPORT, self::CURL_PROXY_PORT);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, self::SSLCERT_PATH);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, self::SSLKEY_PATH);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return $error;
        }
    }

    /**
     * @param $url
     * @return mixed
     */
    public function curlGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    /*
   * 写日志方法
   */
    public function write_log($title, $content, $file='',$directory='')
    {
        if($directory==''){
            $path = LOG_PATH .$_SERVER['SERVER_NAME'].'/'.date('Ym').'/';
        }else{
            $path = LOG_PATH.$directory.'/'.date('Ym').'/';
        }
        if(!file_exists($path)) {
            mkdir($path,0777,true);
        }
        $log = "==[".$title."]-[".date("Y-m-d H:i:s").' '.floor(microtime()*1000)."]==\r\n";
        $log.= var_export($content,true)."\r\n\r\n";
        if($file=='') {
            $logFile = $path.date("Y-m-d").'.log';
        } else {
            $logFile = $path.$file.date("Y-m-d").'.log';
        }
        if(file_exists($logFile)) {
            $filesize = filesize($logFile);
            if($filesize>10485760) {//10M
                rename($logFile,substr($logFile,0,strlen($logFile)-4).date("Ymd").'.log');
            }
        }
        error_log($log, 3, $logFile);
    }

    /**
     * 接受微信回调的信息
     * @return bool|mixed
     */
    public function callBackNotifyData()
    {
        $data = file_get_contents('php://input','r');  //接受微信返回的xml数据
        // $data = $GLOBALS["HTTP_RAW_POST_DATA"];
        return $this->xml2array($data);
    }

    /**
     * 处理完相关逻辑之后 通知微信接收成功
     * @param array $param
     */
    private function sendSuccess($param = array())
    {
        $ret = array(
            'return_code' => 'SUCCESS',
            'return_msg' => 'OK'
        );
        echo $this->array2xml($ret);
    }

    /**
     * 生成签名验证
     * @param array $data
     * @param string $key
     */
    public function makeSign($data)
    {
        try {
            if (empty($data) || !is_array($data)) {
                throw new Exception('参数不正确!');
            }
            // 1. 排序
            ksort($data);
            // 2. 格式化参数
            $sign = $this->formatUrlParams($data);
            if (empty($sign)) {
                throw new Exception('参数错误!');
            }
            $sign = $sign . '&key=' . self::KEY;
            // 3. 转化为小写 并MD5加密
            return strtolower(MD5($sign));
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}