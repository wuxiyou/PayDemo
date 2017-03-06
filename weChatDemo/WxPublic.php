<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class WxPublic extends WxTools
{
    /**
     * 统一下单入口
     * @param $params
     */
    public function unifiedOrder($params)
    {
        try {
            //检查必填的参数
            if (empty($params['body'])) {
                throw new Exception('缺少必填的参数 body！');
            }
            if (empty($params['out_trade_no'])) {
                throw new Exception('缺少必填的参数 out_trade_no！');
            }
            if (empty($params['total_fee'])) {
                throw new Exception('缺少必填的参数 total_fee！');
            }
            if (empty($params['trade_type'])) {
                throw new Exception('缺少必填参数 trade_type！');
            }
            if ($params['trade_type'] == 'JSAPI' && empty($params['openid'])) {
                throw new Exception('缺少openid参数!');
            }
            if ($params['trade_type'] == 'NATIVE' && empty($params['product_id'])) {
                throw new Exception('缺少 product_id 参数!');
            }
            if (empty($params['notify_url'])) {
                throw new Exception('缺少必要的参数 notify_url');
            }
            $data['appid'] = self::APPID;
            $data['mch_id'] = self::MCHID;
            $data['device_info'] = isset($params['device_info']) ? $params['device_info'] : '';
            $data['nonce_str'] = $this->createNonceStr();
            $data['body'] = $params['body'];
            $data['trade_type'] = $params['trade_type'];
            $data['out_trade_no'] = $params['out_trade_no'];
            $data['detail'] = isset($params['detail']) ? $params['detail'] : '';
            $data['attach'] = isset($params['attach']) ? $params['attach'] : '';
            $data['fee_type'] = 'CNY';
            $data['total_fee'] = $params['total_fee'];
            $data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
            $data['time_start'] = isset($params['time_start']) ? $params['time_start'] : '';
            $data['time_expire'] = isset($params['time_expire']) ? $params['time_expire'] : '';
            $data['goods_tag'] = isset($params['goods_tag']) ? $params['goods_tag'] : '';
            $data['product_id'] = isset($params['product_id']) ? $params['product_id'] : '';
            $data['openid'] = isset($params['openid']) ? $params['openid'] : '';
            $data['sign'] = $this->makeSign($data);
            $xml = $this->array2xml($data);
            $curlRes = $this->postXmlCurl($xml, self::gateWay);
            return $this->xml2array($curlRes);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 订单查询
     * @param $params
     */
    public function queryOrder($params)
    {

    }


    /**
     * 退款
     * @param $params
     */
    public function refund($params)
    {
        try {

        } catch (Exception $e) {

        }
    }

    public function rufundQuery($params)
    {

    }
}