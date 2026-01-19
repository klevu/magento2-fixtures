<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;

class CustomerCheckout
{
    final public function __construct(
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly QuoteManagement $quoteManagement,
        private readonly PaymentConfig $paymentConfig,
        private readonly CartInterface $cart,
        private ?int $shippingAddressId = null,
        private ?int $billingAddressId = null,
        private ?string $shippingMethodCode = null,
        private ?string $paymentMethodCode = null,
    ) {
    }

    public static function fromCart(CartInterface $cart): CustomerCheckout
    {
        $objectManager = Bootstrap::getObjectManager();

        return new static(
            addressRepository: $objectManager->create(type: AddressRepositoryInterface::class),
            quoteRepository: $objectManager->create(type: CartRepositoryInterface::class),
            quoteManagement: $objectManager->create(type: QuoteManagement::class),
            paymentConfig: $objectManager->create(type: PaymentConfig::class),
            cart: $cart,
        );
    }

    public function withCustomerBillingAddressId(int $addressId): CustomerCheckout
    {
        $checkout = clone $this;
        $checkout->billingAddressId = $addressId;

        return $checkout;
    }

    public function withCustomerShippingAddressId(int $addressId): CustomerCheckout
    {
        $checkout = clone $this;
        $checkout->shippingAddressId = $addressId;

        return $checkout;
    }

    public function withShippingMethodCode(string $code): CustomerCheckout
    {
        $checkout = clone $this;
        $checkout->shippingMethodCode = $code;

        return $checkout;
    }

    public function withPaymentMethodCode(string $code): CustomerCheckout
    {
        $checkout = clone $this;
        $checkout->paymentMethodCode = $code;

        return $checkout;
    }

    /**
     * Customer shipping address as configured or try default shipping address
     */
    private function getCustomerShippingAddressId(): ?int
    {
        if ($this->shippingAddressId) {
            return $this->shippingAddressId;
        }
        $customer = $this->cart->getCustomer();

        return null === $customer->getId() ? null : (int)$customer->getDefaultShipping();
    }

    /**
     * Customer billing address as configured or try default billing address
     */
    private function getCustomerBillingAddressId(): ?int
    {
        if ($this->billingAddressId) {
            return $this->billingAddressId;
        }
        $customer = $this->cart->getCustomer();

        return null === $customer->getId() ? null : (int)$customer->getDefaultBilling();
    }

    /**
     * Shipping method code as configured, or try first available rate
     */
    private function getShippingMethodCode(): string
    {
        if ($this->shippingMethodCode) {
            return $this->shippingMethodCode;
        }
        $allShippingRates = $this->cart->getShippingAddress()->getAllShippingRates();

        return count($allShippingRates) ? $allShippingRates[0]->getCode() : 'flatrate_flatrate';
    }

    /**
     * Payment method code as configured, or try first available method
     */
    private function getPaymentMethodCode(): string
    {
        return $this->paymentMethodCode
               ?? array_values(array: $this->paymentConfig->getActiveMethods())[0]->getCode();
    }

    /**
     * @throws \Exception
     */
    public function placeOrder(): Order
    {
        $this->saveBilling();
        $this->saveShipping();
        $this->savePayment();
        /** @var Quote $reloadedQuote */
        $reloadedQuote = $this->quoteRepository->get(cartId: $this->cart->getId());
        // Collect missing totals, like shipping
        $reloadedQuote->collectTotals();
        $order = $this->quoteManagement->submit(quote: $reloadedQuote);
        if (!$order instanceof Order) {
            throw new \RuntimeException(
                message: sprintf(
                    'QuoteManagement::submit() returned %s instead of Order',
                    get_debug_type($order),
                ),
            );
        }
        if (method_exists(object_or_class: $this->cart, method: 'getCheckoutSession')) {
            $this->cart->getCheckoutSession()->clearQuote();
        }

        return $order;
    }

    /**
     * @throws \Exception
     */
    private function saveBilling(): void
    {
        $billingAddress = $this->cart->getBillingAddress();
        $customerBillingAddressId = $this->getCustomerBillingAddressId();
        if ($customerBillingAddressId) {
            $billingAddress?->importCustomerAddressData(
                address: $this->addressRepository->getById(addressId: $customerBillingAddressId),
            );
        }

        $billingAddress?->save();
    }

    /**
     * @throws \Exception
     */
    private function saveShipping(): void
    {
        $shippingAddress = $this->cart->getShippingAddress();
        $customerShippingAddressId = $this->getCustomerShippingAddressId();
        if ($customerShippingAddressId) {
            $shippingAddress->importCustomerAddressData(
                address: $this->addressRepository->getById(addressId: $customerShippingAddressId),
            );
        }
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();
        $shippingAddress->setShippingMethod($this->getShippingMethodCode());
        $shippingAddress->save();
    }

    /**
     * @throws \Exception
     */
    private function savePayment(): void
    {
        $payment = $this->cart->getPayment();
        $payment->setMethod(method: $this->getPaymentMethodCode());
        $payment->save();
    }
}
