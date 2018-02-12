<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:54
 */

namespace PhongBui\Payment\Drivers;


use PhongBui\Payment\Interfaces\PaymentInterface;

class VNPay implements PaymentInterface
{
    protected static $QUERY_DR        = '/paymentv2/vpcpay.html';
    protected static $REFUND_URL      = '/gateway/vpcdps';
    protected static $PAYMENT_GATEWAY = '/onecomm-pay/vpc.op';
    protected static $URL             = 'https://onepay.vn';
    protected static $SANDBOX_URL     = 'http://sandbox.vnpayment.vn';

    const VERSION = '2.0.0';

    protected $vnp_TmnCode;
    protected $vnp_HashSecret;

    public function __construct($tmnCode, $secure_secret)
    {
        $this->vnp_TmnCode = $tmnCode;
        $this->vnp_HashSecret = $secure_secret;
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
    public function createRequestPayUrl($orderId, $amount, $successUrl, $cancelUrl, $orderType = 'billpayment')
    {
        $vnp_Url = config('payment.vnpay.env') == 'sandbox' ? self::$SANDBOX_URL : self::$URL;

        //$total_amount = str_replace('.', '', $amount);
        //in Napas price is interger - vnd
        $total_amount = (int) $amount * 100;
        $base_url = "http://" . $_SERVER['SERVER_NAME'];
        $currency = 'VND'; // USD
        $locale = 'vn';

        $inputData = array(
            "vnp_Version" => self::VERSION,
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_Amount" => $amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => $currency,
            "vnp_IpAddr" => $_SERVER['REMOTE_ADDR'],
            "vnp_Locale" => $locale,
            "vnp_OrderInfo" => $orderId,
            "vnp_OrderType" => $orderType,
            "vnp_ReturnUrl" => $successUrl,
            "vnp_TxnRef" => $orderId,
        );
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . $key . "=" . $value;
            } else {
                $hashdata .= $key . "=" . $value;
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        $vnpSecureHash = md5($this->vnp_HashSecret . $hashdata);
        $vnp_Url .= 'vnp_SecureHashType=MD5&vnp_SecureHash=' . $vnpSecureHash;

        return $vnp_Url;
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

        $stringHashData = "";

        // sort all the incoming vpc response fields and leave out any with no value
        foreach ($url_params as $key => $value ) {
            if ($key != "vpc_SecureHash" && (strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
                $stringHashData .= $key . "=" . $value . "&";
            }
        }
        $stringHashData = rtrim($stringHashData, "&");

        $secureHash = strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*',$this->secure_secret)));

        if ($secureHash == $checksum) {
            // Check response code
            switch ($url_params['vpc_TxnResponseCode']) {
                case 0:
                    return ['success' => true, 'order_id' => $url_params['vpc_OrderInfo'], 'transaction_id' => $url_params['vpc_TransactionNo'], 'payment_amount' => (int)$url_params['vpc_Amount'] / 100];
                    break;
                default:
                    $message = $this->getErrorMessage($url_params['vpc_TxnResponseCode']);
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