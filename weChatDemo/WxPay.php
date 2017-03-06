<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class WxPay
 * 微信支付逻辑类
 * @author wxy
 */
class WxPay
{
    private $CI = NULL;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * @param array $params
     */
    public function gatPrepare($params)
    {
        try {
            //print_r($params);exit;
            $url = "http://wx.wsche.com/front/pay/scanpay?" . http_build_query($params);
            $payUrl = $this->httpGet($url);
            $result = json_decode($payUrl, true);
            if (is_array($result) && $result['result_code'] == 'FAIL' && $result['return_code'] == 'SUCCESS') {
                throw new Exception($result['err_code_des']);
            }
            return $payUrl;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    // 1.查看回调是否返回成功的状态
    // 2.如果成功 则去核实订单的真实性
    // 3.如果失败,根据失败原因作出相应的处理
    public function NotifyProcess($params)
    {
        if (empty($params)) {
            return false;
        }
        if ($params['result_code'] == 'SUCCESS' && $params['return_code'] == 'SUCCESS') {
            if (!array_key_exists('transaction_id', $params)) {
                return false;
            }
            $queryParams = array('transaction_id' => $params['transaction_id'], 'out_trade_no' => $params['out_trade_no']);
            $url = "http://wx.wsche.com/front/pay/queryOrder?" . http_build_query($queryParams);
            $res = $this->httpGet($url);
            file_put_contents(LOG_PATH . 'XML.txt', $res);  //记录日记
            if (!is_array($res)) {
                $queryResult = json_decode($res, true);
            } else {
                $queryResult = $res;
            }
            try {
                if (array_key_exists("return_code", $queryResult) && array_key_exists("result_code", $queryResult) && $queryResult["return_code"] == "SUCCESS" && $queryResult["result_code"] == "SUCCESS") {
                    $info = $this->successPay($queryResult);
                    self::sendSuccess();
                    return true;
                } else {
                    //记录错误码
                    return false;
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }


        } else {
            //支付异常  记录错误码
            return false;
        }
    }


    public function successPay($queryResult)
    {
        //开启事物处理
        try {
            $this->CI->load->library('MY_Common');
            $this->CI->my_common->write_log('alipayNotify', $queryResult);
            $this->CI->db->trans_begin();
            $this->CI->load->model('Pay_log_m');
            $this->CI->load->model('Direct_order_m');
            $this->CI->load->model('User_m');
            $orderId = $this->CI->Pay_log_m->getRow(array('id' => $queryResult['out_trade_no'], 'status' => 0, 'fields' => 'id, order_id, amount'));
            if (empty($orderId)) {
                throw new Exception('订单号不存在!');
            }
            $directInfo = $this->CI->db->select('id,user_id')->from('direct_order')->where(array('order_id' => $orderId['order_id'], 'is_pay' => 0))->get()->row_array();
            if (empty($directInfo)) {
                throw new Exception('该订单已支付!');
            }

            $this->CI->my_common->write_log('alipayNotify', $queryResult);
            $directRes = $this->CI->Direct_order_m->updateInfo(array('id' => $directInfo['id'], 'is_pay' => 1, 'pay_time' => date('Y-m-d H:i:s'), 'pay_amount' => $orderId['amount']));
            $payLogParams = array(
                'id' => $orderId['id'],
                'transaction_id' => $queryResult['transaction_id'],
                'buyer_email' => !empty($queryResult['buyer_email']) ? $queryResult['buyer_email'] : '',
                'open_id' => !empty($queryResult['openid']) ? $queryResult['openid'] : '',
                'status' => 1
            );
            $payLogRes = $this->CI->Pay_log_m->updateInfo($payLogParams);
            //$userRes = $this->CI->User_m->updateMember(array('id' => $directInfo['user_id'], 'open_id' => !empty($queryResult['openid']) ? $queryResult['openid'] : ''));
            if (empty($directRes) || empty($payLogRes)) {
                throw new Exception('支付异常!');
            }
            $this->CI->db->trans_commit();
            return true;
        } catch (Exception $e) {
            $this->CI->db->trans_rollback();
            throw new Exception($e->getMessage());
        }
    }

    public function aLiPayNotify()
    {

    }

    private function sendSuccess($param = array())
    {
        $ret = array(
            'return_code' => 'SUCCESS',
            'return_msg' => 'OK'
        );
        echo self::array2xml($ret);
    }

    /**
     * 数组转换为xml
     * @param array $params
     * @return bool|string
     */
    private function array2xml($params = array())
    {
        if (empty($params) || count($params) <= 0) {
            return false;
        }
        $xml = '<xml>';
        foreach ($params as $key => $val) {
            if (is_numeric($key)) {
                $xml .= '<' . $key . '>' . $val . '</' . $key . '>';
            } else {
                $xml .= '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
            }
        }
        $xml .= '</xml>';
        return $xml;
    }

    public function xml2array($xml)
    {
        if (!$xml) {
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $array = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array;
    }

    private function httpGet($url)
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
}