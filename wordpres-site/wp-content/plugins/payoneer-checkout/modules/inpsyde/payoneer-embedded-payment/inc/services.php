<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Dhii\Services\Factories\FuncService;
use Syde\Vendor\Dhii\Services\Factories\Value;
use Syde\Vendor\Dhii\Services\Factory;
use Syde\Vendor\Inpsyde\Assets\Script;
use Syde\Vendor\Inpsyde\PaymentGateway\PaymentFieldsRendererInterface;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\Checkout\HashProvider\HashProviderInterface;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\EmbeddedPayment\AjaxOrderPay\AjaxPayAction;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\EmbeddedPayment\AjaxOrderPay\OrderPayload;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\EmbeddedPayment\ListUrlEnvironmentExtractor;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\EmbeddedPayment\PaymentFieldsRenderer\CheckoutHashFieldRenderer;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\EmbeddedPayment\PaymentFieldsRenderer\ListDebugFieldRenderer;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\EmbeddedPayment\PaymentFieldsRenderer\ListUrlFieldRenderer;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\EmbeddedPayment\PaymentFieldsRenderer\WidgetPlaceholderFieldRenderer;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\ListSession\ListSession\ListSessionProvider;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\WebSdk\Config\StylesColor;
use Syde\Vendor\Inpsyde\PayoneerSdk\Api\Entities\ListSession\ListSerializerInterface;
use Syde\Vendor\Psr\Container\ContainerInterface;
return static function (): array {
    return [
        'embedded_payment.is_enabled' => new Factory(['checkout.selected_payment_flow'], static function (string $configuredFlow): bool {
            return $configuredFlow === 'embedded';
        }),
        'embedded_payment.settings.fields' => static function (ContainerInterface $container) {
            /** @var array<array> $fields */
            $fields = [
                /**
                 * Style fields disabled for now.
                 * JS SDK does not yet support them. Also, we are unsure whether
                 * we even expose them through the config pages
                 * See PN-967
                 */
                //(require __DIR__ . "/custom_style_fields.php")($container),
                (require __DIR__ . "/custom_css_fields.php")($container),
            ];
            return \array_merge(...$fields);
        },
        'embedded_payment.settings.checkout_css_custom_css.default' => new Value((string) \file_get_contents(\dirname(__DIR__) . '/static/css/custom-css-default.css')),
        'embedded_payment.widget.websdk_styles' => new Factory(['inpsyde_payment_gateway.options'], static function (ContainerInterface $options): array {
            $styles = [];
            foreach (StylesColor::OPTIONS as $key) {
                if ($options->has("checkout_color_{$key}")) {
                    $styles["{$key}Color"] = (string) $options->get("checkout_color_{$key}");
                }
            }
            return $styles;
        }),
        'embedded_payment.widget_script_data' => new Factory(['core.list_url_container_id', 'payment_methods.payoneer-checkout.list_url_container_attibute_id', 'payment_methods.payoneer-checkout.list_url_container_attibute_env', 'core.payment_fields_container_id', 'payment_methods.payoneer-checkout.payment_fields_attribute_component', 'checkout.payment_flow_override_flag', 'checkout.on_error_refresh_fragment_flag', 'embedded_payment.pay_order_error_flag', 'embedded_payment.assets.websdk.umd.url.template', 'embedded_payment.widget.websdk_styles'], static function (string $listUrlContainerId, string $listUrlContainerIdAttribute, string $listUrlContainerEnvAttribute, string $paymentFieldsContainerId, string $paymentFieldsComponentAttribute, string $hostedModeOverrideFlag, string $onErrorRefreshFragmentFlag, string $payOrderErrorFlag, string $webSdkUmdUrlTemplate, array $websdkStyles): array {
            return ['listUrlContainerId' => $listUrlContainerId, 'listUrlContainerIdAttribute' => $listUrlContainerIdAttribute, 'listUrlContainerEnvAttribute' => $listUrlContainerEnvAttribute, 'paymentFieldsContainerId' => $paymentFieldsContainerId, 'paymentFieldsComponentAttribute' => $paymentFieldsComponentAttribute, 'isPayForOrder' => is_wc_endpoint_url('order-pay'), 'hostedModeOverrideFlag' => $hostedModeOverrideFlag, 'onErrorRefreshFragmentFlag' => $onErrorRefreshFragmentFlag, 'payOrderErrorFlag' => $payOrderErrorFlag, 'webSdkUmdUrlTemplate' => $webSdkUmdUrlTemplate, 'websdkStyles' => (object) $websdkStyles];
        }),
        'embedded_payment.pay_order_error_flag' => new Value('payoneer-checkout-on-before-server-error'),
        'embedded_payment.path.assets' => new Factory(['core.local_modules_directory_name'], static function (string $modulesDirectoryRelativePath): string {
            $moduleRelativePath = \sprintf('%1$s/%2$s', $modulesDirectoryRelativePath, 'payoneer-embedded-payment');
            return \sprintf('%1$s/assets/', $moduleRelativePath);
        }),
        'embedded_payment.assets.can_enqueue' => new FuncService(['wc.is_checkout', 'payment_methods.payoneer-checkout.is_enabled'], static function (bool $isCheckout, bool $isGatewayEnabled): bool {
            return $isCheckout && $isGatewayEnabled;
        }),
        'embedded_payment.assets.js.websdk' => new Factory(['embedded_payment.assets.js.websdk.url', 'embedded_payment.assets.can_enqueue'], static function (string $webSdkJsUrl, callable $canEnqueue): Script {
            $script = new Script('payoneer-websdk-loader', $webSdkJsUrl);
            /** @psalm-var callable():bool $canEnqueue */
            $script->canEnqueue($canEnqueue);
            return $script;
        }),
        'embedded_payment.assets.js.checkout' => new Factory(['core.main_plugin_file', 'embedded_payment.path.assets', 'embedded_payment.widget_script_data', 'embedded_payment.assets.can_enqueue'], static function (string $mainPluginFile, string $assetsPath, array $widgetScriptData, callable $canEnqueue): Script {
            $url = \plugins_url($assetsPath . 'payoneer-checkout.js', $mainPluginFile);
            $script = new Script('payoneer-checkout', $url);
            $script->withLocalize('PayoneerData', $widgetScriptData);
            /** @psalm-var callable():bool $canEnqueue */
            $script->canEnqueue($canEnqueue);
            return $script;
        }),
        'embedded_payment.assets' => new Factory(['embedded_payment.assets.js.checkout'], static function (Script $checkoutJs): array {
            return [$checkoutJs];
        }),
        /**
         *
         * Checkout payment fields.
         * For embedded flow, these take care of rendering containers and configuration
         * for the interactive payment widget of the WebSDK
         *
         */
        'embedded_payment.payment_fields_renderer.placeholder' => new Constructor(WidgetPlaceholderFieldRenderer::class, ['payment_methods.payoneer-checkout.payment_fields_container_id', 'payment_methods.payoneer-checkout.payment_fields_attribute_component', 'payment_methods.payoneer-checkout.payment_fields_component']),
        'embedded_payment.payment_fields_renderer.list_url' => new Factory(['list_session.manager', 'embedded_payment.list_url_environment_extractor', 'payment_methods.payoneer-checkout.list_url_container_id', 'payment_methods.payoneer-checkout.list_url_container_attibute_id', 'payment_methods.payoneer-checkout.list_url_container_attibute_env'], static function (ListSessionProvider $listSessionProvider, ListUrlEnvironmentExtractor $environmentExtractor, string $containerId, string $idAttributeName, string $envAttributeName): PaymentFieldsRendererInterface {
            return new ListUrlFieldRenderer($listSessionProvider, $environmentExtractor, $containerId, $idAttributeName, $envAttributeName);
        }),
        'embedded_payment.payment_fields_renderer.list_hash' => new Factory(['checkout.checkout_hash_provider', 'inpsyde_payment_gateway.list_hash_container_id'], static function (HashProviderInterface $hashProvider, string $containerId): PaymentFieldsRendererInterface {
            return new CheckoutHashFieldRenderer($hashProvider, $containerId);
        }),
        'embedded_payment.payment_fields_renderer.debug' => new Factory(['list_session.manager', 'core.list_serializer'], static function (ListSessionProvider $listSessionProvider, ListSerializerInterface $serializer): PaymentFieldsRendererInterface {
            return new ListDebugFieldRenderer($listSessionProvider, $serializer);
        }),
        'embedded_payment.ajax_order_pay.is_ajax_order_pay' => static function (): bool {
            //phpcs:disable WordPress.Security.NonceVerification.Missing
            return \wp_doing_ajax() && isset($_POST['action']) && $_POST['action'] === 'payoneer_order_pay';
        },
        'embedded_payment.ajax_order_pay.checkout_payload' => new Factory(['embedded_payment.ajax_order_pay.is_ajax_order_pay'], static function (bool $isAjaxOrderPay): OrderPayload {
            if (!$isAjaxOrderPay) {
                throw new \RuntimeException('Invalid Request');
            }
            return OrderPayload::fromGlobals();
        }),
        'embedded_payment.ajax_order_pay.payment_action' => new Factory(
            ['payment_gateways'],
            /**
             * @param string[] $payoneerPaymentMethodIds
             */
            static function (array $payoneerPaymentMethodIds): AjaxPayAction {
                return new AjaxPayAction($payoneerPaymentMethodIds);
            }
        ),
        'embedded_payment.list_url_environment_extractor' => new Constructor(ListUrlEnvironmentExtractor::class),
    ];
};
