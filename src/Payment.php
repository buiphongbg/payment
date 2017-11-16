<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:50
 */

namespace PhongBui\Payment;


use PhongBui\Payment\Factories\DriverFactory;
use PhongBui\Payment\Interfaces\PaymentInterface;
use Illuminate\Support\Facades\Config;

class Payment implements PaymentInterface
{
    protected $driver;
    protected $config;

    /**
     * Payment constructor.
     * @param PaymentInterface|null $driver
     */
    public function __construct(PaymentInterface $driver = null)
    {
        $this->config = (class_exists('Config') ? Config::get('payment') : []);
        $this->driver = $this->getDriver($driver);
    }

    /**
     * @param null $driver
     * @return mixed|null
     */
    protected function getDriver($driver = null)
    {
        if (!$driver instanceof PaymentInterface) {
            $factory = new DriverFactory();
            $driver = $factory->get($this->config['driver']);
        }

        return $driver;
    }

    /**
     * @param $orderId
     * @param $amount
     * @param $successUrl
     * @param $failedUrl
     * @param $cancelUrl
     * @return mixed
     */
    public function getPaymentUrl($orderId, $amount, $successUrl, $failedUrl, $cancelUrl)
    {
        return $this->driver->getPaymentUrl($orderId, $amount, $successUrl, $failedUrl, $cancelUrl);
    }

    /**
     * @param $urlParams
     * @return mixed
     */
    public function verifyResponseUrl($urlParams)
    {
        return $this->driver->verifyResponseUrl($urlParams);
    }
}