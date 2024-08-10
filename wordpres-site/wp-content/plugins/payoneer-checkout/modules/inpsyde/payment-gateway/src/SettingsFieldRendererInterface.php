<?php

namespace Syde\Vendor\Inpsyde\PaymentGateway;

interface SettingsFieldRendererInterface
{
    public function render(string $fieldId, array $fieldConfig, PaymentGateway $gateway): string;
}
