<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:52
 */

namespace PhongBui\Payment\Factories;


use PhongBui\Payment\Drivers\BaoKim;
use PhongBui\Payment\Drivers\Napas;
use PhongBui\Payment\Drivers\OnePay;

class DriverFactory
{
    /**
     * Create driver instance
     * @param $driver
     * @return mixed
     * @throws \Exception
     */
    public function get($driver)
    {
        $config = config('payment.' . $driver);

        if (is_array($config)) {
            return $this->{$driver}($config);
        } else {
            throw new \Exception('Config must be an array. Please choose an supported payment driver.');
        }
    }

    /**
     * @param array $config
     * @return Napas
     */
    protected function napas(array $config)
    {
        return new Napas($config['merchant_id'], $config['access_code'],
            isset($config['username']) ? $config['username'] : '',
            isset($config['password']) ? $config['password'] : '',
            $config['secure_hash']);
    }

    /**
     * @param array $config
     * @return OnePay
     */
    protected function onepay(array $config)
    {
        return new OnePay($config['merchant_id'], $config['access_code'], $config['secure_secret']);
    }

    /**
     * @param array $config
     * @return OnePay
     */
    protected function vnpay(array $config)
    {
        return new VNPay($config['vnp_tmn_code'], $config['vnp_hash_secret']);
    }

    /**
     * ESms service
     * @param array $config
     */
    protected function payoo(array $config)
    {

    }

    /**
     * @param array $config
     */
    protected function baokim(array $config)
    {
        return new BaoKim($config['merchant_id'], $config['email_business'], $config['secure_pass'], $config['api_user'], $config['api_pwd'], $config['private_key']);
    }
}