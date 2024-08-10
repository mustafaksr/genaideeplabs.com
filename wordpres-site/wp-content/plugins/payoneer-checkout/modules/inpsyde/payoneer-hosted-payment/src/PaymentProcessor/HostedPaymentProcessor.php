<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\PayoneerForWoocommerce\HostedPayment\PaymentProcessor;

use Syde\Vendor\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\Checkout\Authentication\TokenGeneratorInterface;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\Checkout\CheckoutExceptionInterface;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\Checkout\MisconfigurationDetector\MisconfigurationDetectorInterface;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\Checkout\PaymentProcessor\AbstractPaymentProcessor;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\ListSession\ListSession\ListSessionPersistor;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\ListSession\ListSession\ListSessionProvider;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\ListSession\ListSession\PaymentContext;
use Syde\Vendor\Inpsyde\PayoneerForWoocommerce\Api\Gateway\CommandFactory\WcOrderBasedUpdateCommandFactoryInterface;
use Syde\Vendor\Inpsyde\PayoneerSdk\Api\ApiExceptionInterface;
use Syde\Vendor\Inpsyde\PayoneerSdk\Api\Entities\ListSession\ListInterface;
use WC_Order;
/**
 * @psalm-import-type PaymentResult from AbstractPaymentProcessor
 */
class HostedPaymentProcessor extends AbstractPaymentProcessor
{
    /**
     * @var ListSessionPersistor
     */
    protected $listSessionPersistor;
    /**
     * @var bool
     */
    protected $fallbackToHostedModeFlag;
    public function __construct(ListSessionPersistor $listSessionPersistor, string $transactionIdFieldName, MisconfigurationDetectorInterface $misconfigurationDetector, ListSessionProvider $sessionProvider, WcOrderBasedUpdateCommandFactoryInterface $updateCommandFactory, TokenGeneratorInterface $tokenGenerator, string $tokenKey, bool $fallbackToHostedModeFlag, string $checkoutSessionHashKey)
    {
        parent::__construct($misconfigurationDetector, $sessionProvider, $listSessionPersistor, $updateCommandFactory, $tokenGenerator, $tokenKey, $transactionIdFieldName, $checkoutSessionHashKey);
        $this->listSessionPersistor = $listSessionPersistor;
        $this->fallbackToHostedModeFlag = $fallbackToHostedModeFlag;
    }
    /**
     * @inheritDoc
     */
    public function processPayment(WC_Order $order, PaymentGateway $gateway): array
    {
        $this->clearOutdatedListInOrder($order);
        parent::processPayment($order, $gateway);
        $list = $this->sessionProvider->provide(new PaymentContext($order));
        $redirectUrl = $this->createRedirectUrl($list);
        /* translators: Order note added when processing an order in hosted flow */
        $note = __('The customer is being redirected to the hosted payment page.', 'payoneer-checkout');
        $order->update_status('on-hold', $note);
        return ['result' => 'success', 'redirect' => $redirectUrl];
    }
    /**
     * If fallback to HPP flag is set, we need to clear saved LIST. It may be created for embedded
     * flow, so we cannot use it.
     *
     * @param WC_Order $order
     *
     * @throws CheckoutExceptionInterface
     */
    protected function clearOutdatedListInOrder(WC_Order $order): void
    {
        if ($this->fallbackToHostedModeFlag) {
            $this->listSessionPersistor->persist(null, new PaymentContext($order));
        }
    }
    /**
     * If the LIST response contains a redirect object, craft a compatible URL
     * out of the given URL and its parameters. If none is found, use our own return URL
     * as a fallback
     *
     * @param ListInterface $list
     *
     * @return string
     * @throws ApiExceptionInterface
     */
    protected function createRedirectUrl(ListInterface $list): string
    {
        $redirect = $list->getRedirect();
        $baseUrl = $redirect->getUrl();
        $parameters = $redirect->getParameters();
        $parameterDict = [];
        array_walk($parameters, static function (array $param) use (&$parameterDict) {
            /** @psalm-suppress MixedArrayAssignment * */
            $parameterDict[(string) $param['name']] = urlencode((string) $param['value']);
        });
        return add_query_arg($parameterDict, $baseUrl);
    }
}
