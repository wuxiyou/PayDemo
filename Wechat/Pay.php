<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pay extends User {
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');

    $wxconfig['appid']=$this->config->item('appid');
    $wxconfig['mch_id']=$this->config->item('mch_id');
    $wxconfig['apikey']=$this->config->item('apikey');
    $wxconfig['appsecret']=$this->config->item('appsecret');
    $wxconfig['sslcertPath']=$this->config->item('sslcertPath');
    $wxconfig['sslkeyPath']=$this->config->item('sslkeyPath');
    $this->load->library('Wechatpay',$wxconfig);
	}

  public $step2And3Url   = 'front/pay/commonpay?'; 	//设置第一步获取code后的对接程序


  public function commonpay()
  {
    $redirect_uri = urlencode(base_url() . $this->step2And3Url . $_SERVER['QUERY_STRING']);
    if ( ! (isset($_SESSION['openid']))){
      if (! ($code = $this->input->get('code'))){
        header('Location:'.$this->wechatpay->jsapiPayStep1Url($redirect_uri)); 
      } else {
        $_SESSION['openid'] = $this->wechatpay->getOpenId($code);
      }
    }

//    $_SESSION['id'] = 15648;
    $res = $this->db->get_where('tuangou_baoming', array('id'=> $this->session->userdata('id')))->row();
    $data['order_no'] = $res->order_no;
    $arr = array('206'=> '起亚限时抢购会-湛江站', '207'=> '比亚迪限时抢购会-佛山站', '209'=> '江淮千人直销会', '210'=> '长安汽车兰州直销会', '211'=>'长安淘车季!', '212'=>'长安淘车季!', '213'=>'长安淘车季!'
      ,'214'=>'比亚迪西北五省大联动-西安站','215'=>'比亚迪西北五省大联动-咸阳站','216'=>'比亚迪西北五省大联动-渭南站','217'=>'比亚迪西北五省大联动-榆林站','218'=>'比亚迪西北五省大联动-安康站','219'=>'比亚迪西北五省大联动-兰州站','220'=>'比亚迪西北五省大联动-乌鲁木齐站'
      ,'221'=>'广物汽贸集团特卖会茂名站'
      ,'222'=>'长安汽车大型联动直销会-太原站'
	    ,'223'=>'东风风神限时抢购会中山站'
      ,'224'=>'双12五省联动现车抢购惠西安站','225'=>'双12五省联动现车抢购惠新疆站','226'=>'双12五省联动现车抢购惠西宁站','227'=>'双12五省联动现车抢购惠宁夏站','228'=>'双12五省联动现车抢购惠咸阳站','229'=>'双12五省联动现车抢购惠渭南站','230'=>'双12五省联动现车抢购惠安康站','231'=>'双12五省联动现车抢购惠汉中站','232'=>'双12五省联动现车抢购惠榆林站','233'=>'双12五省联动现车抢购惠伊宁站'
    );
    $data['name'] = isset($arr[$res->tuanid]) ? $arr[$res->tuanid] : '新春欢乐宋 车主对对购';

    $param['body']             = '【100元门票】__'.$data['name'];
    // $param['attach']           = 520;
    $param['detail']           = $this->session->userdata('id');
    $param['out_trade_no']     = $data['order_no'];
    $param['total_fee']        = 10000;
    $param["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];
    // $param["time_start"]       = date("YmdHis");  // 订单生成时间
    // $param["time_expire"]      = date("YmdHis", time() + 600); // 订单失效时间
//    $param["goods_tag"]        = "黑人牙膏";
    $param["notify_url"]       = "http://expo.wsche.com/gw/ca/wxorderUp";
    $param["trade_type"]       = "JSAPI";
    $param["openid"]           = $_SESSION['openid'];
    
    //统一下单，获取结果，结果是为了构造jsapi调用微信支付组件所需参数
    $result=$this->wechatpay->unifiedOrder($param);
    // var_dump($result);

    if (isset($result["prepay_id"]) && !empty($result["prepay_id"])) {
      //调用支付类里的get_package方法，得到构造的参数
      $data['parameters']=json_encode($this->wechatpay->get_package($result['prepay_id']));

      $this->load->view('front/commonpay', $data);
    } else {
      echo '支付出错, 截图并联系管理员';
      echo '<hr>result如下:<hr><pre>';
      print_r($result);
      echo '</pre><hr>param如下:<hr><pre>';
      print_r($param);
      echo '</pre>';
      echo $res->tuanid . '-' . $data['name'];
    }


  }

  public function newpay(){
    $redirect_uri = urlencode(base_url() . 'front/pay/newpay?' . $_SERVER['QUERY_STRING']);
    if ( ! (isset($_SESSION['openid']))){
      if (! ($code = $this->input->get('code'))){
        header('Location:'.$this->wechatpay->jsapiPayStep1Url($redirect_uri)); 
      } else {
        $_SESSION['openid'] = $this->wechatpay->getOpenId($code);
      }
    }

    $gets = $this->input->get();
    $data['order_no'] = $gets['order_no'];
    $data['name'] = $gets['body'];
    
    $param['body']             = $gets['body'];
    $param['detail']           = $gets['detail'];
    $param['out_trade_no']     = $gets['order_no'];
    $param['total_fee']        = $gets['total_fee'];
    $param["notify_url"]       = $gets['notify_url'];
    $param["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];
    $param["trade_type"]       = "JSAPI";
    $param["openid"]           = $_SESSION['openid'];
    
    //统一下单，获取结果，结果是为了构造jsapi调用微信支付组件所需参数
    $result=$this->wechatpay->unifiedOrder($param);
    // var_dump($result);

    if (isset($result["prepay_id"]) && !empty($result["prepay_id"])) {
      //调用支付类里的get_package方法，得到构造的参数
      $data['parameters']=json_encode($this->wechatpay->get_package($result['prepay_id']));

      $this->load->view('front/'.$gets['view'], $data);
    } else {
      echo '支付出错, 截图并联系管理员';
      echo '<hr>param:<hr><pre>';
      print_r($param);
      echo '</pre><hr>result:<hr><pre>';
      print_r($result);
      echo '</pre>';
    }
  }

    /**
     * 微信扫码支付
     */
    public function scanPay()
    {
        $getParams = $this->input->get();
        $info = $this->wechatpay->getCodeUrl($getParams['body'],$getParams['out_trade_no'], $getParams['total_fee'], $getParams['notify_url'], $getParams['product_id']);
        if (is_array($info)) {
            $info = json_encode($info);
        }
        echo $info;exit;
    }

    /**
     * 回调地址通知处理
     */
    public function notifyTest()
    {
        $data = file_get_contents('php://input','r');
        $info = $this->xml2array($data);
        file_put_contents(APPPATH.'logs/'.'xml1.txt',$data);
        $this->write_log('data', $info);
        if ($info['result_code'] == 'SUCCESS' && $info['return_code'] == 'SUCCESS') {
            self::sendSuccess();
        }
        return false;
    }


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

    /*
  * 写日志方法
  */
    public function write_log($title, $content, $file='',$directory='')
    {
        if($directory==''){
            $path = APPPATH.'logs/' .$_SERVER['SERVER_NAME'].'/'.date('Ym').'/';
        }else{
            $path = APPPATH.'logs/'.$directory.'/'.date('Ym').'/';
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

    public function queryOrder()
    {
        $getParams = $this->input->get();
        $info = $this->wechatpay->orderQuery($getParams['transaction_id'], $getParams['out_trade_no']);
        if (is_array($info)) {
            echo json_encode($info);
            return false;
        } else {
            echo $info;
            return false;
        }
    }
}
?>