<?php

declare (strict_types=1);
namespace Syde\Vendor;

return [
    //Payoneer-checkout gateway
    'title-payoneer-checkout' => [
        /* Title field of embedded mode */
        'title' => \__('Title', 'payoneer-checkout'),
        'type' => 'text',
        'description' => \__('The title that customers see at checkout', 'payoneer-checkout'),
        'default' => \__('Credit / Debit Card', 'payoneer-checkout'),
        'desc_tip' => \true,
        'class' => 'section-payoneer-checkout',
    ],
    //Payoneer-hosted payment gateway
    'title-payoneer-hosted' => [
        'title' => \__('Title', 'payoneer-checkout'),
        'type' => 'text',
        /* Title field of hosted mode */
        'description' => \__('The title that customers see at checkout', 'payoneer-checkout'),
        'default' => \__('Credit / Debit Card', 'payoneer-checkout'),
        'desc_tip' => \true,
        'class' => 'section-payoneer-hosted',
    ],
    'description-payoneer-hosted' => [
        'title' => \__('Description', 'payoneer-checkout'),
        'type' => 'text',
        /* Description field of hosted mode */
        'description' => \__('The description that customers see at checkout', 'payoneer-checkout'),
        'default' => '',
        'desc_tip' => \true,
        'class' => 'section-payoneer-hosted',
    ],
];
