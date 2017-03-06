<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class AliPays
 * 支付宝支付接口验证
 * @author wxy
 */
include('Config.php');

class AliPay
{
    protected $gateWay = 'https://mapi.alipay.com/gateway.do';  //支付宝支付接口
    protected $cacertPath = '';                                 //支付宝证书地址
    protected $partner = '';                     				//合作者id
    protected $seller_id = '';                                  // 收款支付宝账号， 一般和签约账号一致
    protected $secret = '';     								// MD5 秘钥 安全校验码
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
        $this->cacertPath = CACERT_PATH . '\\cacert.pem';
    }

    public function orderPay($params)
    {
        try {
            // 验证必要的参数
            if (empty($params) || !is_array($params)) {
                throw new Exception('参数不合法!');
            }
            if (empty($params['notify_url'])) {
                throw new Exception('请填写通知地址!');
            }
            if (empty($params['return_url'])) {
                throw new Exception('请填写回调地址！');
            }
            if (empty($params['out_trade_no'])) {
                throw new Exception('订单号不能为空!');
            }
            if (empty($params['subject'])) {
                throw new Exception('商品信息不能为空!');
            }
            if (empty($params['total_fee'])) {
                throw new Exception('交易金额不能为空!');
            }

            $data = array();
            //基本参数
            $data['service'] = $this->getService();
            $data['partner'] = $this->partner;
            $data['seller_id'] = $this->seller_id;
            $data['payment_type'] = $this->payment_type;
            $data['notify_url'] = $params['notify_url'];
            $data['return_url'] = $params['return_url'];
            // 业务参数
            $data['out_trade_no'] = $params['out_trade_no'];
            $data['subject'] = $params['subject'];
            $data['total_fee'] = $params['total_fee'];
            $data['body'] = isset($params['body']) ? $params['body'] : '';
            $data['_input_charset'] = $this->inputCharset();

            $para_filter = $this->paraFilter($data);
            $para_sort = $this->argSort($para_filter);
            $para_sort['sign'] = $this->_createSign($data);
            $para_sort['sign_type'] = $this->getSignType();
            return $this->buildRequestForm($para_sort,  "post", "确认支付");
            $url = $this->gateWay . '?' . $this->createLinkString($para_sort);
            //$info = $this->getHttpResponsePOST($url, $this->cacertPath, $para_sort);
            //file_put_contents(LOG_PATH . 'alipay.txt', $info);
            //print_r($info);
            //exit;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 除去数组中的空值和签名参数
     * @param array $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    private function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param array $para 排序前的数组
     * return 排序后的数组
     */
    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }
    /**
     * 生成签名
     * @param $params
     * @return string
     */
    private function _createSign($params)
    {
        if (empty($params)) return '';
        $data = array();
        // 剔除不需要的支付参数,空值、sign、sign_type
        foreach ($params as $key => $item) {
            if ($key != "sign" && $key != "sign_type" && $item != "") {
                $data[$key] = $item;
            }
        }
        ksort($data);
        reset($data);

        $dataJoin = array();
        // 组合字符串
        foreach ($data as $key => $item) {
            $dataJoin[] = "{$key}={$item}";
        }

        // 将字符串组合后连接商户秘钥,然后进行MD5运算
        $signStr = implode('&', $dataJoin) . $this->secret;

        // 如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {

            $signStr = stripslashes($signStr);
        }

        $signStr = md5($signStr);
        return $signStr;
    }

    /**
     * 签名方式 默认MD5
     * @return string
     */
    private function getSignType($sign_type = 'MD5')
    {
        return $this->sign_type = strtoupper($sign_type);
    }

    /**
     * 编码格式 目前支持 gbk 与 utf-8  默认是utf-8
     * @param string $input_charset
     * @return string
     */
    private function inputCharset($input_charset = 'utf-8')
    {
        return $this->input_charset = strtolower($input_charset);
    }

    /**
     * 产品类型
     * @return string
     */
    private function getService()
    {
        return $this->service = "create_direct_pay_by_user";
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param array $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function createLinkString($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @param array $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function createLinkStringUrlencode($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . urlencode($val) . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 远程获取数据，POST模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param string $url 指定URL完整路径地址
     * @param string $cacert_url 指定当前工作目录绝对路径
     * @param array $para 请求的数据
     * @param string $input_charset 编码格式。默认值：空值
     * return 远程输出的数据
     */
    public function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '')
    {

        if (trim($input_charset) != '') {
            $url = $url . "_input_charset=" . $input_charset;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url);//证书地址
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl, CURLOPT_POST, true); // post传输数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $para);// post传输数据
        $responseText = curl_exec($curl);
        var_dump(curl_error($curl));//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

    /**
     * 远程获取数据，GET模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param string $url 指定URL完整路径地址
     * @param string $cacert_url 指定当前工作目录绝对路径
     * return 远程输出的数据
     */
    private function getHttpResponseGET($url, $cacert_url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url);//证书地址
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

    /**
     * 验证签名
     * @param string $prestr 需要签名的字符串
     * @param string $sign 签名结果
     * @param string $key 私钥
     * return 签名结果
     */
    public function md5Verify($prestr, $sign, $key)
    {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);

        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @param array $orderInfo 订单数据
     * @param array $params 支付宝通知的商户后台的数据
     */
    public function _queryVerify($orderInfo, $params)
    {
        try {
            // 判断通知状态，只在支付完成的时候确认支付成功，其他时候返回false不触发异常
            $tradeStatus = isset($params['trade_status']) ? trim($params['trade_status']) : "";
            if (!in_array($tradeStatus, array("TRADE_SUCCESS", "TRADE_FINISHED"))) {
                throw new Exception('支付状态不是支付成功或支付完成', 10001);
            }
            if (!$orderInfo) {
                throw new Exception('订单数据不能为空', 10001);
            }
            if (!$params) {
                throw new Exception('支付商后台通知参数不能为空', 10001);
            }

            //判断商品金额
            $orderTotalFee = isset($orderInfo['total_fee']) ? $orderInfo['total_fee'] : 0;
            $totalFee = isset($params['total_fee']) ? $params['total_fee'] : 0;
            if ($orderTotalFee !== $totalFee) {
                throw new Exception('交易金额不一致!', 10001);
            }

            // 判断订单号
            $orderNo = isset($orderInfo['out_trade_no']) ? $orderInfo['out_trade_no'] : 0;
            $orderNo1 = isset($orderInfo['out_trade_no']) ? $orderInfo['out_trade_no'] : 0;
            if ($orderNo != $orderNo1) {
                throw new Exception('订单号不一致!', 10001);
            }

            //判断商品名称
            $subject = isset($orderInfo['subject']) ? $orderInfo['subject'] : '';
            $subject1 = isset($params['subject']) ? $params['subject'] : '';
            if ($subject != $subject1) {
                throw new Exception('商品名称不一致', 10001);
            }

            // 获取签名字符串，和通知参数的sign签名比较
            $para_filter = $this->paraFilter($params);
            $para_sort = $this->argSort($para_filter);
            $signStr = $this->_createSign($para_sort);
            $sign = isset($param['sign']) ? trim($param['sign']) : "";

            if ($signStr != $sign) {
                throw new Exception('后台通知MD5签名验证失败', 10001);
            }
            $prestr = createLinkstring($para_sort);
            $res = $this->md5Verify($prestr, $params['sign'], $this->secret);
            if (!$res) {
                throw new Exception('签名失败!');
            }
            $notifyId = isset($param['notify_id']) ? trim($param['notify_id']) : "";
            if (!$this->getResponse($notifyId)) {
                throw new Exception('后台通知notify_id验证失败', 10001);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param string    $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    private function getResponse($notify_id)
    {
        $transport = strtolower(trim($this->transport));
        $partner = trim($this->partner);
        $veryfy_url = '';
        if ($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        } else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;
        $responseText = $this->getHttpResponseGET($veryfy_url, $this->cacertPath);
        // 如果返回true字符串，则通知是合法的
        if (preg_match("/true$/i", $responseText)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param array     $para_temp 请求参数数组
     * @param string    $method 提交方式。两个值可选：post、get
     * @param string    $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
    function buildRequestForm($para_temp, $method, $button_name)
    {
        //待请求参数数组
        //$para = $this->buildRequestPara($para_temp);
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->gateWay. '?'. "_input_charset=" . trim($this->inputCharset()) . "' method='" . $method . "'>";
        while (list ($key, $val) = each($para_temp)) {
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit'  value='" . $button_name . "' style='display:none;'></form>";

        $sHtml = $sHtml . "<script>document.forms['alipaysubmit'].submit();</script>";

        return $sHtml;
    }
}