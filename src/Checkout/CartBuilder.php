<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Eav\Model\Attribute;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\AddressFactory as QuoteAddressFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResourceModel;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

class CartBuilder
{
    private CartInterface $cart;
    private CheckoutSession $session;

    private QuoteAddress $quoteAddress;
    /**
     * @var DataObject[][] Array in the form [sku => [buyRequest]] (multiple requests per sku are possible)
     */
    private array $addToCartRequests;

    final public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        readonly CheckoutSession $checkoutSession,
        readonly QuoteAddressFactory $quoteAddressFactory,
    ) {
        $this->session = $checkoutSession;
        $this->quoteAddress = $quoteAddressFactory->create();
        $this->cart = $this->session->getQuote();
        $this->addToCartRequests = [];
    }

    public static function forCurrentSession(): CartBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        $result = new static(
            productRepository: $objectManager->create(type: ProductRepositoryInterface::class),
            checkoutSession: $objectManager->get(type: CheckoutSession::class),
            quoteAddressFactory: $objectManager->create(QuoteAddressFactory::class),
        );
        $result->session->clearQuote();
        $result->session->clearStorage();
        $result->cart->setStoreId(storeId: Store::DISTRO_STORE_ID);
        $result->cart->setIsMultiShipping(value: 0);
        $result->cart->setIsActive(isActive: true);
        $result->cart->setCheckoutMethod(checkoutMethod: OnePage::METHOD_GUEST);
        $result->cart->setDataUsingMethod(key: 'email', args: 'customer@example.com');

        return $result;
    }

    public function withStoreId(?int $storeId = null): CartBuilder
    {
        $result = clone $this;
        $result->cart->setStoreId(storeId: $storeId);

        return $result;
    }

    public function withReservedOrderId(?int $reservedOrderId = null): CartBuilder
    {
        $result = clone $this;
        $result->cart->setReservedOrderId(
            reservedOrderId: $reservedOrderId ?: random_int(min: 1000000000000, max: 9999999999999),
        );

        return $result;
    }

    public function withCustomer(CustomerInterface $customer): CartBuilder
    {
        $result = clone $this;
        $result->cart->setCustomer(customer: $customer);
        $result->cart->setCheckoutMethod(checkoutMethod: Onepage::METHOD_CUSTOMER);
        $result->cart->setCustomerIsGuest(customerIsGuest: false);
        $result->session->setCustomerData(customer: $customer);

        return $result;
    }

    public function withAddress(
        QuoteAddressInterface | CustomerAddressInterface | null $address = null,
        bool $isBillingAddress = true,
        bool $isShippingAddress = true,
        string $shippingMethod = 'flatrate_flatrate',
    ): CartBuilder {
        if ($address instanceof CustomerAddressInterface) {
            $address = $this->quoteAddress->importCustomerAddressData($address);
        }
        $result = clone $this;
        $defaultAddress = [
            'prefix' => '',
            'firstname' => 'John',
            'middlename' => '',
            'lastname' => 'Smith',
            'suffix' => '',
            'company' => '',
            'street' => [
                '0' => '221b Baker Street',
                '1' => 'Marylebone',
            ],
            'city' => 'London',
            'country_id' => 'GB',
            'region' => '',
            'postcode' => 'NW1 5RS',
            'telephone' => '0123456789',
            'fax' => '',
            'vat_id' => '',
            'save_in_address_book' => 1,
            'email' => 'test@example.com',
        ];
        if ($isBillingAddress) {
            if (null === $address) {
                $address = $result->cart->getBillingAddress();
                $address->addData($defaultAddress);
            }
            $result->cart->setBillingAddress($address);
            $customer = $result->cart->getCustomer();
            if (!$customer->getId()) {
                $result->cart->setCustomerFirstname($address->getFirstname());
                $result->cart->setCustomerLastname($address->getLastname());
                $result->cart->setCustomerEmail('test@example.com');
            }
        }
        if ($isShippingAddress) {
            $shippingAddress = $result->cart->getShippingAddress();
            $shippingAddress->addData(null !== $address ? $address->getData() : $defaultAddress);
            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($shippingMethod);
        }

        return $result;
    }

    public function withSimpleProduct(string $sku, float $qty = 1.0): CartBuilder
    {
        $result = clone $this;
        $result->addToCartRequests[$sku][] = new DataObject(data: ['qty' => $qty]);

        return $result;
    }

    /**
     * @param array<string, int> $options ['attribute_code' => 'option_id']
     */
    public function withConfigurableProduct(
        string $sku,
        array $options,
        float $qty = 1.0,
    ): CartBuilder {
        return $this->withProductRequest(
            sku: $sku,
            qty: $qty,
            request: [
                'options' => $options,
            ],
        );
    }

    /**
     * @param array<string, float> $options ['sku' => 'qty']
     */
    public function withGroupedProduct(
        string $sku,
        array $options,
        float $qty = 1.0,
    ): CartBuilder {
        return $this->withProductRequest(
            sku: $sku,
            qty: $qty,
            request: [
                'options' => $options,
            ],
        );
    }

    /**
     * Lower-level API to support arbitrary products
     *
     * @param array<string, array<string, mixed>> $request
     */
    public function withProductRequest(string $sku, float | int $qty = 1.0, array $request = []): CartBuilder
    {
        $result = clone $this;
        $requestInfo = array_merge(['qty' => (float)$qty], $request);
        $result->addToCartRequests[$sku][] = new DataObject(data: $requestInfo);

        return $result;
    }

    /**
     * @throws LocalizedException
     */
    public function build(): CartInterface
    {
        $objectManager = ObjectManager::getInstance();
        foreach ($this->addToCartRequests as $sku => $requests) {
            /** @var Product $product */
            $product = $this->productRepository->get(sku: $sku);
            // @todo Remove and resolve stock issues with configurable products
            $objectManager->get(type: ProductHelper::class)->setSkipSaleableCheck(skipSaleableCheck: true);

            foreach ($requests as $requestInfo) {
                switch ($product->getTypeId()) {
                    case Grouped::TYPE_CODE:
                        $requestInfo = $this->buildGroupedProductRequestInfo(
                            requestInfo: $requestInfo,
                            product: $product,
                        );
                        break;
                    case Configurable::TYPE_CODE:
                        $requestInfo = $this->buildConfigurableProductRequestInfo(
                            requestInfo: $requestInfo,
                            product: $product,
                        );
                        break;
                }
                $this->cart->addProduct(
                    product: $product,
                    request: $requestInfo,
                );
            }
        }
        $this->cart->collectTotals();

        $quoteResourceModel = $objectManager->get(type: QuoteResourceModel::class);
        $quoteResourceModel->save($this->cart);

        /** @var QuoteIdMask $quoteIdMask */
        $quoteIdMask = $objectManager->create(type: QuoteIdMaskFactory::class)->create();
        $quoteIdMask->setDataUsingMethod(key: 'quote_id', args: $this->cart->getId());
        $quoteIdMask->setDataChanges(value: true);
        $quoteIdMaskResourceModel = $objectManager->get(type: QuoteIdMaskResourceModel::class);
        $quoteIdMaskResourceModel->save($quoteIdMask);

        $this->session->replaceQuote(quote: $this->cart);
        $this->session->unsLastRealOrderId();

        return $this->cart;
    }

    private function buildGroupedProductRequestInfo(DataObject $requestInfo, Product $product): DataObject
    {
        if ($product->getTypeId() !== Grouped::TYPE_CODE) {
            return $requestInfo;
        }
        $requestOptions = $requestInfo->getData(key: 'options') ?: [];
        $requestInfo->unsetData(key: 'options');

        /** @var Grouped $typeInstance */
        $typeInstance = $product->getTypeInstance();
        /** @var ProductInterface[] $associatedProducts */
        $associatedProducts = $typeInstance->getAssociatedProducts(product: $product);

        $requestInfo->setData(key: 'product', value: $product->getId());
        $requestInfo->setData(key: 'item', value: $product->getId());
        // @todo Replace with child id => qty
        $superGroup = [];
        foreach ($requestOptions as $associatedSku => $qtyOrdered) {
            /** @var ProductInterface $childProduct */
            $childProduct = current(
                array: array_filter(
                    array: $associatedProducts,
                    callback: static fn (ProductInterface $associatedProduct): bool => (
                        $associatedSku === $associatedProduct->getSku()
                    ),
                ),
            );
            if (!$childProduct) {
                continue;
            }
            $superGroup[(int)$childProduct->getId()] = (float)$qtyOrdered;
        }
        $requestInfo->setData(key: 'super_group', value: $superGroup);

        return $requestInfo;
    }

    private function buildConfigurableProductRequestInfo(DataObject $requestInfo, Product $product): DataObject
    {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return $requestInfo;
        }
        $requestOptions = $requestInfo->getData(key: 'options')
            ?: [];
        $requestInfo->unsetData(key: 'options');
        $requestInfo->setData(key: 'product', value: $product->getId());

        $superAttribute = [];
        /** @var Configurable $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $configurableAttributes = $typeInstance->getConfigurableAttributesAsArray(product: $product);
        foreach ($requestOptions as $attributeCode => $optionId) {
            /** @var Attribute $configurableAttribute */
            $configurableAttribute = current(
                array: array_filter(
                    array: $configurableAttributes,
                    callback: static fn (array $attribute): bool => ($attributeCode === $attribute['attribute_code']),
                ),
            );
            if (!$configurableAttribute) {
                continue;
            }
            $superAttribute[$configurableAttribute['attribute_id']] = (int)$optionId;
        }
        $requestInfo->setData(key: 'super_attribute', value: $superAttribute);

        return $requestInfo;
    }
}
