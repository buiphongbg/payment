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
    protected static $URL             = 'https://vnpayment.vn';
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

        $vnp_Url .= self::$QUERY_DR;

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
        $vnp_SecureHash = $url_params['vnp_SecureHash'];
        $inputData = array();
        foreach ($url_params as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        unset($inputData['vnp_SecureHashType']);
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . $key . "=" . $value;
            } else {
                $hashData = $hashData . $key . "=" . $value;
                $i = 1;
            }
        }

        $secureHash = md5($this->vnp_HashSecret . $hashData);

        if ($secureHash == $vnp_SecureHash) {
            // Check response code
            switch ($url_params['vnp_ResponseCode']) {
                case '00':
                    return ['success' => true, 'order_id' => $url_params['vnp_OrderInfo'], 'transaction_id' => $url_params['vnp_TransactionNo'], 'payment_amount' => (int)$url_params['vnp_Amount'] / 100];
                    break;
                default:
                    $message = $this->getErrorMessage($url_params['vnp_ResponseCode']);
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
            case "00" :
                $result = "Giao dịch thành công - Approved";
                break;
            case "01" :
                $result = "	Giao dịch đã tồn tại";
                break;
            case "02" :
                $result = "Merchant không hợp lệ (kiểm tra lại vnp_TmnCode)";
                break;
            case "04" :
                $result = "Khởi tạo GD không thành công do Website đang bị tạm khóa";
                break;
            case "05" :
                $result = "Giao dịch không thành công do: Quý khách nhập sai mật khẩu quá số lần quy định. Xin quý khách vui lòng thực hiện lại giao dịch";
                break;
            case "06" :
                $result = "Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP). Xin quý khách vui lòng thực hiện lại giao dịch.";
                break;
            case "07" :
                $result = "	Giao dịch bị nghi ngờ là giao dịch gian lận";
                break;
            case '08':
                $result = 'Giao dịch không thành công do: Hệ thống Ngân hàng đang bảo trì. Xin quý khách tạm thời không thực hiện giao dịch bằng thẻ/tài khoản của Ngân hàng này.';
                break;
            case "09" :
                $result = "Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng.";
                break;
            case "10" :
                $result = "Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần";
                break;
            case "11" :
                $result = "Giao dịch không thành công do: Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch.";
                break;
            case "12" :
                $result = "Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa.";
                break;
            case "51" :
                $result = "Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch.";
                break;
            case "65" :
                $result = "Giao dịch không thành công do: Tài khoản của Quý khách đã vượt quá hạn mức giao dịch trong ngày.";
                break;
            default :
                $result = "Giao dịch thất bại - Failured";
        }
        return $result;
    }
}