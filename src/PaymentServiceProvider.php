<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:48
 */

namespace PhongBui\Payment;


use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/payment.php', 'payment');

        $this->app->singleton('payment', function () {
            return new Payment();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/payment.php' => base_path('config/payment.php')
        ]);
    }
}