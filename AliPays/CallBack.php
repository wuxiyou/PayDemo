
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CallBack
{
    private $CI = NULL;


    /**
     * 备注
     * 支付宝同步回调是GET方式并以页面通知  异步回调是POST方式  例子可参考 demo
     */
    /**
     * 验证回调信息
     * @param array $orderInfo             订单信息
     * @param array $callBackParams        回调信息
     * @return mixed
     */
    function verifyResult($orderInfo, $callBackParams)
    {
        try {
            $this->CI = &get_instance();
            $this->CI->load->library('Pay/AliPays/AliPay');
            $verify_result = $this->CI->alipay->_queryVerify($orderInfo, $callBackParams);
            return $verify_result;
        } catch (Exception $e) {
            //此处可以记录日志
            throw new Exception($e->getMessage());
        }
    }

    // 通知支付宝订单处理成功
    public function notifySuccess()
    {
        echo 'success';
    }

    // 通知支付宝订单处理失败
    public function notifyFail()
    {
        echo 'fail';
    }

    //异步回调通知
    public function notifyDemo()
    {
        $out_trade_no = $_GET['out_trade_no'];  //得到订单号 查询订单信息
        $orderInfo = array();
        $callBackParams = $_GET;
        $verify_result = $this->verifyResult($orderInfo, $callBackParams);

        if ($verify_result) {
            $out_trade_no = $_GET['out_trade_no'];
            //支付宝交易号
            $trade_no = $_GET['trade_no'];
            //交易状态
            $trade_status = $_GET['trade_status'];
            if ($_POST['trade_status'] == 'TRADE_FINISHED') {
                //交易已完成 说明已通知支付宝  此处可以查询是否已确认交易完成
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //需要处理对应的订单逻辑
            }
            $this->notifySuccess();
        } else {
            $this->notifyFail();
        }
    }

}
?>


<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?php
    defined('BASEPATH') OR exit('No direct script access allowed');
    header('Content-Type:text/html;charset=utf-8');//woo 2016-06-01
    //计算得出通知验证结果
    class returnDemo extends  CallBack
    {
        function getReturn()
        {
            $out_trade_no = $_GET['out_trade_no'];  //得到订单号 查询订单信息
            $orderInfo = array();
            $callBackParams = $_GET;
            $verify_result = $this->verifyResult($orderInfo, $callBackParams);
            if ($verify_result) {//验证成功
                $out_trade_no = $_GET['out_trade_no'];
                $trade_no = $_GET['trade_no'];
                $trade_status = $_GET['trade_status'];
                //file_put_contents(LOG_PATH . 'alipay.txt', $_GET);
                if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {

                    header("Location: http://neice.wsche.com/tuangou/aLiPay_returnUrl?n={$out_trade_no}");  //跳转到支付成功页面

                } else {
                    echo "trade_status=" . $_GET['trade_status'];
                }

                echo "验证成功<br />";

            } else {
                //验证失败
                //如要调试，请看alipay_notify.php页面的verifyReturn函数
                echo "验证失败";
            }
        }
    }

    ?>
    <title>支付宝即时到账交易接口</title>
</head>
<body>
</body>
</html>



