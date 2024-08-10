<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Dhii\Services\Factory;
return new Factory([], static function (): array {
    return ['checkout_css_fieldset_title' => ['title' => \__('Appearance', 'payoneer-checkout'), 'type' => 'title', 'class' => 'section-payoneer-general'], 'show_amex_icon' => ['title' => \__('American Express logo', 'payoneer-checkout'), 'type' => 'checkbox', 'label' => \__('Display American Express logo next to the payment method title on the checkout page.', 'payoneer-checkout'), 'default' => 'yes', 'class' => 'section-payoneer-general'], 'show_jcb_icon' => ['title' => \__('JCB logo', 'payoneer-checkout'), 'type' => 'checkbox', 'label' => \__('Display JCB logo next to the payment method title on the checkout page.', 'payoneer-checkout'), 'default' => 'no', 'class' => 'section-payoneer-general']];
});
