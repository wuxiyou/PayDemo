<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>支付宝即时到账交易接口接口</title>
</head>
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PayPage
{
    private  $CI = NULL;
    function getParams($params)
    {
        $this->CI = &get_instance();
        $this->CI->load->library('Pay/AliPays/AliPay');
        $page = $this->CI->alipay->orderPay($params);
        echo $page;
    }
}
?>
</body>
</html>
