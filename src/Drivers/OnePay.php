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
            'vpc_ReturnURL' => $successUrl,
            'vpc_MerchTxnRef' => $orderId,
            'vpc_OrderInfo' => $orderId,
            'vpc_Amount' => $total_amount,
            'vpc_BackURL' => $cancelUrl,
            'vpc_TicketNo' => $_SERVER['REMOTE_ADDR'],
            'Title' => 'Thanh toan'
        );

        ksort($params);

        $vpcURL = $server . self::$PAYMENT_GATEWAY . '?';
        $stringHashData = '';
        $appendAmp = 0;

        foreach($params as $key => $value) {

            // create the md5 input and URL leaving out any fields that have no value
            // tạo chuỗi đầu dữ liệu những tham số có dữ liệu
            if (strlen($value) > 0) {
                // this ensures the first paramter of the URL is preceded by the '?' char
                if ($appendAmp == 0) {
                    $vpcURL .= urlencode($key) . '=' . urlencode($value);
                    $appendAmp = 1;
                } else {
                    $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
                }
                //$stringHashData .= $value; *****************************sử dụng cả tên và giá trị tham số để mã hóa*****************************
                if ((strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
                    $stringHashData .= $key . "=" . $value . "&";
                }
            }
        }
        //*****************************xóa ký tự & ở thừa ở cuối chuỗi dữ liệu mã hóa*****************************
        $stringHashData = rtrim($stringHashData, "&");
        // Create the secure hash and append it to the Virtual Payment Client Data if
        // the merchant secret has been provided.
        // thêm giá trị chuỗi mã hóa dữ liệu được tạo ra ở trên vào cuối url
        if (strlen($this->secure_secret) > 0) {
            //$vpcURL .= "&vpc_SecureHash=" . strtoupper(md5($stringHashData));
            // *****************************Thay hàm mã hóa dữ liệu*****************************
            $vpcURL .= "&vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $stringHashData, pack('H*',$this->secure_secret)));
        }

        return $vpcURL;
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