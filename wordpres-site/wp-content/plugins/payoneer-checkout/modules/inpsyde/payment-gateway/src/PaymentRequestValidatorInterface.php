<?php

namespace Syde\Vendor\Inpsyde\PaymentGateway;

interface PaymentRequestValidatorInterface
{
    /**
     * @param \WC_Order $order
     * @param PaymentGateway $param
     * @throws \RuntimeException
     * @return void
     */
    public function assertIsValid(\WC_Order $order, PaymentGateway $param);
}
