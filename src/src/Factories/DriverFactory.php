<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:52
 */

namespace PhongBui\Payment\Factories;


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
     * Nexmo service
     * @param array $config
     */
    protected function napas(array $config)
    {
        return new Napas($config['merchant_id'], $config['access_code'], $config['username'], $config['password'], $config['secure_hash']);
    }

    /**
     * ESms service
     * @param array $config
     */
    protected function payoo(array $config)
    {

    }
}