<?php

namespace Syde\Vendor\Inpsyde\PaymentGateway;

use Throwable;
interface PaymentFieldsRendererInterface
{
    /**
     * Renders the payment fields.
     *
     * @return string Rendered HTML.
     * @throws Throwable
     */
    public function renderFields(): string;
}
