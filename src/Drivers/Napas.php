<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:54
 */

namespace PhongBui\Payment\Drivers;


use PhongBui\Payment\Interfaces\PaymentInterface;

class Napas implements PaymentInterface
{
    protected static $QUERY_DR        = '/gateway/vpcdps';
    protected static $REFUND_URL      = '/gateway/vpcdps';
    protected static $PAYMENT_GATEWAY = '/gateway/vpcpay.do';
    protected static $URL             = 'https://napas.com.vn';
    protected static $SANDBOX_URL     = 'https://sandbox.napas.com.vn';

    const VERSION = '2.0';

    protected $merchantId;
    protected $accessCode;
    protected $username;
    protected $password;
    protected $secureHash;

    public function __construct($merchantId, $accessCode, $username, $password, $secureHash)
    {
        $this->merchantId = $merchantId;
        $this->accessCode = $accessCode;
        $this->username = $username;
        $this->password = $password;
        $this->secureHash = $secureHash;
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
        $server = config('payment.napas.env') == 'sandbox' ? self::$SANDBOX_URL : self::$URL;

        //$total_amount = str_replace('.', '', $amount);
        //in Napas price is interger - vnd
        $total_amount = (int) $amount * 100;
        $base_url = "http://" . $_SERVER['SERVER_NAME'];
        $currency = 'VND'; // USD
        $locale = 'vn';

        $params = array(
            'vpc_Version' => self::VERSION,
            'vpc_Command' => 'pay',
            'vpc_AccessCode' => $this->accessCode,
            'vpc_MerchTxnRef' => $orderId . '/1',
            'vpc_Merchant' => $this->merchantId,
            'vpc_OrderInfo' => $orderId,
            'vpc_Amount' => $total_amount,
            'vpc_ReturnURL' => $successUrl,
            'vpc_BackURL' => $cancelUrl,
            'vpc_Locale' => $locale,
            'vpc_CurrencyCode' => $currency,
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
            return ['success' => true, 'order_id' => $url_params['vpc_OrderInfo']];
        } else {
            return ['success' => false, 'order_id' => ''];
        }
    }

    public function getErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case 1:
                $message = trans('payment.card_closed');
                break;
            case 2:
                $message = '';
                break;
            case 3:
                $message = trans('payment.card_expired');
                break;
            case 4:
                $message = trans('payment.wrong_otp_or_limit_exceeded');
                break;
            case 5:
                $message = trans('payment.no_reply_from_bank');
                break;
            case 6:
                $message = trans('payment.bank_connect_failed');
                break;
            case 7:
                $message = trans('payment.insufficient_fund');
                break;
            case 8:
                $message = trans('payment.invalid_checksum');
                break;
            case 9:
                $message = trans('payment.transaction_type_not_support');
                break;
            case 10:
                $message = trans('payment.other_error');
                break;
            case 11:
                $message = trans('payment.card_closed');
                break;
            case 12:
                $message = trans('payment.card_closed');
                break;
            case 13:
                $message = trans('payment.card_closed');
                break;
            case 14:
                $message = trans('payment.invalid_otp');
                break;
            case 15:
                $message = trans('payment.invalid_static_password');
                break;
            case 16:
                $message = trans('payment.incorrect_card_holder_name');
                break;
            case 17:
                $message = trans('payment.incorrect_card_number');
                break;
            case 18:
                $message = trans('payment.card_closed');
                break;
            case 19:
                $message = trans('payment.card_closed');
                break;
            case 20:
                $message = trans('payment.card_closed');
                break;
            case 21:
                $message = trans('payment.card_closed');
                break;
            case 22:
                $message = trans('payment.card_closed');
                break;
            case 23:
                $message = trans('payment.card_closed');
                break;
            case 24:
                $message = trans('payment.card_closed');
                break;
            case 25:
                $message = trans('payment.card_closed');
                break;
            case 26:
                $message = trans('payment.card_closed');
                break;
            case 27:
                $message = trans('payment.card_closed');
                break;
            case 28:
                $message = trans('payment.card_closed');
                break;
            case 29:
                $message = trans('payment.card_closed');
                break;
            case 30:
                $message = trans('payment.card_closed');
                break;
            case 31:
                $message = trans('payment.card_closed');
                break;
            default:
                $message = trans('payment.unknown');
                break;
        }

        return $message;
    }
}