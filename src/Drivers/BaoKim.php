<?php
/**
 * Created by Phong Bui.
 * Date: 27/11/2017
 * Time: 10:07
 */

namespace PhongBui\Payment\Drivers;


use PhongBui\Payment\Interfaces\PaymentInterface;

class BaoKim implements PaymentInterface
{
    protected static $BAOKIM_API_SELLER_INFO = '/payment/rest/payment_pro_api/get_seller_info';
    protected static $BAOKIM_API_PAY_BY_CARD = '/payment/rest/payment_pro_api/pay_by_card';
    protected static $BAOKIM_API_PAYMENT     = '/payment/order/version11';
    protected static $BAOKIM_URL             = 'https://www.baokim.vn';
    protected static $BAOKIM_SANDBOX_URL     = 'https://sandbox.baokim.vn';

    protected $merchant_id;
    protected $email_business;
    protected $api_user;
    protected $api_pwd;
    protected $private_key;
    protected $secure_pass;

    public function __construct($merchant_id, $email_business, $secure_pass, $api_user, $api_pwd, $private_key)
    {
        $this->merchant_id = $merchant_id;
        $this->email_business = $email_business;
        $this->api_user = $api_user;
        $this->api_pwd = $api_pwd;
        $this->private_key = $private_key;
        $this->secure_pass = $secure_pass;
    }

    public function getPaymentUrl($orderId, $amount, $successUrl, $failedUrl, $cancelUrl)
    {
        // TODO: Implement getPaymentUrl() method.
        return $this->createRequestPayUrl($orderId, $amount, $successUrl, $cancelUrl);
    }

    /**
     * @param $orderId
     * @param $amount
     * @param $paymentInfo
     * @param $successUrl
     * @param $cancelUrl
     * @return string
     */
    public function createRequestPayUrl($orderId, $amount, $successUrl, $cancelUrl, $paymentInfo = [])
    {
        $server = config('payment.baokim.env') == 'sandbox' ? self::$BAOKIM_SANDBOX_URL : self::$BAOKIM_URL;

        $total_amount = str_replace('.','', $amount);
        $base_url = "http://" . $_SERVER['SERVER_NAME'];
        $currency = 'VND'; // USD
        // Mảng các tham số chuyển tới baokim.vn
        $params = array(
            'merchant_id'		=>	strval($this->merchant_id),
            'order_id'			=>	$orderId,
            'business'			=>	strval($this->email_business),
            'total_amount'		=>	$total_amount,
            'shipping_fee'		=>  strval('0'),
            'tax_fee'			=>  strval('0'),
            'order_description'	=>	strval('Thanh toán đơn hàng từ Website '. $base_url . ' với mã đơn hàng ' . $orderId),
            'url_success'		=>	$successUrl,
            'url_cancel'		=>	$cancelUrl,
            'url_detail'		=>	strtolower(''),
            'payer_name'		=>  isset($paymentInfo['name']) ? $paymentInfo['name'] : '',
            'payer_email'		=> 	isset($paymentInfo['email']) ? $paymentInfo['email'] : '',
            'payer_phone_no'	=> 	isset($paymentInfo['phone']) ? $paymentInfo['phone'] : '',
            'shipping_address'  =>  isset($paymentInfo['address']) ? $paymentInfo['address'] : '',
            'currency' => strval($currency),

        );
        ksort($params);

        $params['checksum'] = hash_hmac('SHA1',implode('',$params), $this->secure_pass);

        //Kiểm tra  biến $redirect_url xem có '?' không, nếu không có thì bổ sung vào
        $redirect_url = $server . self::$BAOKIM_API_PAYMENT;
        if (strpos($redirect_url, '?') === false)
        {
            $redirect_url .= '?';
        }
        else if (substr($redirect_url, strlen($redirect_url)-1, 1) != '?' && strpos($redirect_url, '&') === false)
        {
            // Nếu biến $redirect_url có '?' nhưng không kết thúc bằng '?' và có chứa dấu '&' thì bổ sung vào cuối
            $redirect_url .= '&';
        }

        // Tạo đoạn url chứa tham số
        $url_params = '';
        foreach ($params as $key=>$value)
        {
            if ($url_params == '')
                $url_params .= $key . '=' . urlencode($value);
            else
                $url_params .= '&' . $key . '=' . urlencode($value);
        }

        return $redirect_url.$url_params;
    }

    /**
     * Function check response url
     *
     * @param array $url_params
     * @return array|bool
     */
    public function verifyResponseUrl($url_params = [])
    {
        if(empty($url_params['checksum'])){
            echo "invalid parameters: checksum is missing";
            return ['success' => false, 'order_id' => '', 'transaction_id' => '', 'payment_amount' => 0, 'message' => 'Invalid parameters: checksum is missing.'];
        }

        $checksum = $url_params['checksum'];
        unset($url_params['checksum']);

        ksort($url_params);

        if(strcasecmp($checksum,hash_hmac('SHA1',implode('',$url_params), $this->secure_pass))===0)
            return ['success' => true, 'order_id' => $url_params['order_id'], 'transaction_id' => $url_params['transaction_id'], 'payment_amount' => (int)$url_params['total_amount'], 'net_amount' => $url_params['net_amount']];
        else
            return ['success' => false, 'order_id' => '', 'transaction_id' => '', 'payment_amount' => 0, 'message' => 'Checksum failed.'];
    }
}