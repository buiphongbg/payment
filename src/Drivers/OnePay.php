<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:54
 */

namespace PhongBui\Payment\Drivers;


use PhongBui\Payment\Interfaces\PaymentInterface;

class OnePay implements PaymentInterface
{
    protected static $QUERY_DR        = '/gateway/vpcdps';
    protected static $REFUND_URL      = '/gateway/vpcdps';
    protected static $PAYMENT_GATEWAY = '/onecomm-pay/vpc.op';
    protected static $URL             = 'https://onepay.vn';
    protected static $SANDBOX_URL     = 'https://mtf.onepay.vn';

    const VERSION = '2';

    protected $merchantId;
    protected $accessCode;
    protected $secure_secret;

    public function __construct($merchantId, $accessCode, $secure_secret)
    {
        $this->merchantId = $merchantId;
        $this->accessCode = $accessCode;
        $this->secure_secret = $secure_secret;
    }

    public function getPaymentUrl($orderId, $amount, $successUrl, $failedUrl, $cancelUrl)
    {
        // TODO: Implement getPaymentUrl() method.
        return $this->createRequestPayUrl($orderId, $amount, $successUrl, $cancelUrl, 'ATM');
    }

    /**
     * @param $orderId
     * @param $amount
     * @param $paymentInfo
     * @param $successUrl
     * @param $cancelUrl
     * @return string
     */
    public function createRequestPayUrl($orderId, $amount, $successUrl, $cancelUrl, $gateWay = 'ATM', $cardType = '')
    {
        $server = config('payment.onepay.env') == 'sandbox' ? self::$SANDBOX_URL : self::$URL;

        //$total_amount = str_replace('.', '', $amount);
        //in Napas price is interger - vnd
        $total_amount = (int) $amount * 100;
        $base_url = "http://" . $_SERVER['SERVER_NAME'];
        $currency = 'VND'; // USD
        $locale = 'vn';

        $params = array(
            'vpc_Version' => self::VERSION,
            'vpc_Currency' => $currency,
            'vpc_Command' => 'pay',
            'vpc_AccessCode' => $this->accessCode,
            'vpc_Merchant' => $this->merchantId,
            'vpc_Locale' => $locale,
            'vpc_MerchTxnRef' => $orderId . '/1',
            'vpc_OrderInfo' => $orderId,
            'vpc_Amount' => $total_amount,
            'vpc_ReturnURL' => $successUrl,
            'vpc_BackURL' => $cancelUrl,
            'vpc_TicketNo' => $_SERVER['REMOTE_ADDR']
        );

        if (!empty($gateWay)) {
            $params['vpc_PaymentGateway'] = $gateWay;
        }

        if (!empty($cardType)) {
            $params['vpc_CardType'] = $cardType;
        }

        ksort($params);
        $params['vpc_SecureHash'] = strtoupper(md5($this->secureHash . implode('', $params)));

        // Create url params
        $url_params = http_build_query($params);

        // Check redirect url
        $redirect_url = $server . self::$PAYMENT_GATEWAY;
        if (strpos($redirect_url, '?') === false)
        {
            $redirect_url .= '?';
        }
        else if (substr($redirect_url, strlen($redirect_url)-1, 1) != '?' && strpos($redirect_url, '&') === false)
        {
            // Nếu biến $redirect_url có '?' nhưng không kết thúc bằng '?' và có chứa dấu '&' thì bổ sung vào cuối
            $redirect_url .= '&';
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
        if(empty($url_params['vpc_SecureHash'])){
            echo "invalid parameters: vpc_SecureHash is missing";
            return FALSE;
        }

        $checksum = $url_params['vpc_SecureHash'];
        unset($url_params['vpc_SecureHash']);

        ksort($url_params);

        $secureHash = strtoupper(md5($this->secureHash . implode('', $url_params)));

        if ($secureHash == $checksum) {
            // Check response code
            switch ($url_params['vpc_ResponseCode']) {
                case 0:
                    return ['success' => true, 'order_id' => $url_params['vpc_OrderInfo'], 'transaction_id' => $url_params['vpc_TransactionNo'], 'payment_amount' => (int)$url_params['vpc_Amount'] / 100];
                    break;
                default:
                    $message = $this->getErrorMessage($url_params['vpc_ResponseCode']);
                    return ['success' => false, 'order_id' => '', 'transaction_id' => '', 'payment_amount' => 0, 'message' => $message];
                    break;
            }
        } else {
            return ['success' => false, 'order_id' => '', 'transaction_id' => '', 'payment_amount' => 0, 'message' => 'Checksum failed.'];
        }
    }

    public function getErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case "0" :
                $result = "Giao dịch thành công - Approved";
                break;
            case "1" :
                $result = "Ngân hàng từ chối giao dịch - Bank Declined";
                break;
            case "3" :
                $result = "Mã đơn vị không tồn tại - Merchant not exist";
                break;
            case "4" :
                $result = "Không đúng access code - Invalid access code";
                break;
            case "5" :
                $result = "Số tiền không hợp lệ - Invalid amount";
                break;
            case "6" :
                $result = "Mã tiền tệ không tồn tại - Invalid currency code";
                break;
            case "7" :
                $result = "Lỗi không xác định - Unspecified Failure ";
                break;
            case "8" :
                $result = "Số thẻ không đúng - Invalid card Number";
                break;
            case "9" :
                $result = "Tên chủ thẻ không đúng - Invalid card name";
                break;
            case "10" :
                $result = "Thẻ hết hạn/Thẻ bị khóa - Expired Card";
                break;
            case "11" :
                $result = "Thẻ chưa đăng ký sử dụng dịch vụ - Card Not Registed Service(internet banking)";
                break;
            case "12" :
                $result = "Ngày phát hành/Hết hạn không đúng - Invalid card date";
                break;
            case "13" :
                $result = "Vượt quá hạn mức thanh toán - Exist Amount";
                break;
            case "21" :
                $result = "Số tiền không đủ để thanh toán - Insufficient fund";
                break;
            case "99" :
                $result = "Người sủ dụng hủy giao dịch - User cancel";
                break;
            default :
                $result = "Giao dịch thất bại - Failured";
        }
        return $result;
    }
}