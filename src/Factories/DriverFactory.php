<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:52
 */

namespace PhongBui\Payment\Factories;


use PhongBui\Payment\Drivers\BaoKim;
use PhongBui\Payment\Drivers\Napas;

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