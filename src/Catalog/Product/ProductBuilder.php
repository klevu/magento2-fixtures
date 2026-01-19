<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Product;

use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\Data\ProductWebsiteLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductWebsiteLinkRepositoryInterface;
use Magento\Catalog\Model\AbstractModel;
use Magento\Catalog\Model\ImageUploader;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as ConfigurableOptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Downloadable\Api\Data\LinkInterface as DownloadableLinkInterface;
use Magento\Downloadable\Api\Data\LinkInterfaceFactory as DownloadableLinkInterfaceFactory;
use Magento\Downloadable\Api\DomainManagerInterface;
use Magento\Downloadable\Api\LinkRepositoryInterface as DownloadableLinkRepositoryInterface;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir as Directory;
use Magento\GroupedProduct\Model\Product\Initialization\Helper\ProductLinks\Plugin\Grouped as GroupedProductHelperPlugin;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Indexer\Model\IndexerFactory;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use TddWizard\Fixtures\Exception\IndexFailedException;
use TddWizard\Fixtures\Traits\IsTransactionExceptionTrait;

//phpcs:disable SlevomatCodingStandard.Classes.ClassStructure.IncorrectGroupOrder

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class ProductBuilder
{
    use IsTransactionExceptionTrait;

    /**
     * @var int[]
     */
    private array $categoryIds = [];
    /**
     * @var AttributeInterface[]
     */
    private array $configurableAttributes = [];
    /**
     * @var ProductInterface[]
     */
    private array $variantProducts = [];
    /**
     * @var ProductInterface[]
     */
    private array $linkedProducts = [];

    /**
     * @param int[] $websiteIds
     * @param array<int, array<string, mixed>> $storeSpecificValues
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductWebsiteLinkRepositoryInterface $websiteLinkRepository,
        private readonly ProductWebsiteLinkInterfaceFactory $websiteLinkFactory,
        private readonly IndexerFactory $indexerFactory,
        private readonly DownloadableLinkRepositoryInterface $downloadLinkRepository,
        private readonly DownloadableLinkInterfaceFactory $downloadLinkFactory,
        private readonly DomainManagerInterface $domainManager,
        private readonly ProductTierPriceInterfaceFactory $tierPriceFactory,
        private readonly ConfigurableOptionsFactory $configurableOptionsFactory,
        private readonly ProductLinkInterfaceFactory $productLinkFactory,
        private ProductInterface & AbstractModel $product,
        private array $websiteIds,
        private array $storeSpecificValues,
        private readonly ?string $downloadableLinkDomain = 'https://magento.test/',
    ) {
    }

    public function __clone(): void
    {
        $this->product = clone $this->product;
    }

    public static function aSimpleProduct(): ProductBuilder // phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Product $product */
        $product = $objectManager->create(type: ProductInterface::class);

        $product->setTypeId(typeId: Type::TYPE_SIMPLE);
        $product->setAttributeSetId(attributeSetId: 4);
        $product->setName(name: 'TDD Test Simple Product');
        $product->setStatus(status: Status::STATUS_ENABLED);
        $product->setVisibility(visibility: Visibility::VISIBILITY_BOTH);
        $product->setPrice(price: 10);
        $product->addData([
            'tax_class_id' => 1, // @TODO get default tax class id
            'description' => 'TDD Test Product Description.',
            'short_description' => 'TDD Test Product Short Description.',
            'image' => 'no_selection',
            'small_image' => 'no_selection',
            'thumbnail' => 'no_selection',
        ]);
        $product->setStockData(stockData: [
            'manage_stock' => 1,
            'is_in_stock' => 1,
            'qty' => 100,
            'is_qty_decimal' => 0,
        ]);
        $extensionAttributes = $product->getExtensionAttributes();
        if (method_exists(object_or_class: $extensionAttributes, method: 'setData')) {
            /** @var StockItemInterface $stockItem */
            $stockItem = $objectManager->create(type: StockItemInterface::class);
            $stockItem->setManageStock(manageStock: true);
            $stockItem->setQty(qty: 100);
            $stockItem->setIsQtyDecimal(isQtyDecimal: false);
            $stockItem->setIsInStock(isInStock: true);
            $extensionAttributes->setData(key: 'stock_item', value: $stockItem);
        }

        return new static(
            productRepository: $objectManager->create(type: ProductRepositoryInterface::class),
            websiteLinkRepository: $objectManager->create(type: ProductWebsiteLinkRepositoryInterface::class),
            websiteLinkFactory: $objectManager->create(type: ProductWebsiteLinkInterfaceFactory::class),
            indexerFactory: $objectManager->create(type: IndexerFactory::class),
            downloadLinkRepository: $objectManager->create(type: DownloadableLinkRepositoryInterface::class),
            downloadLinkFactory: $objectManager->create(type: DownloadableLinkInterfaceFactory::class),
            domainManager: $objectManager->create(type: DomainManagerInterface::class),
            tierPriceFactory: $objectManager->create(type: ProductTierPriceInterfaceFactory::class),
            configurableOptionsFactory: $objectManager->create(type: ConfigurableOptionsFactory::class),
            productLinkFactory: $objectManager->create(type: ProductLinkInterfaceFactory::class),
            product: $product,
            websiteIds: [1],
            storeSpecificValues: [],
        );
    }

    public static function aVirtualProduct(): ProductBuilder // phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $builder = self::aSimpleProduct();
        $builder->product->setName(name: 'TDD Test Virtual Product');
        $builder->product->setTypeId(typeId: Type::TYPE_VIRTUAL);

        return $builder;
    }

    public static function aDownloadableProduct(): ProductBuilder // phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $builder = self::aSimpleProduct();
        $builder->product->setName(name: 'TDD Test Downloadable Product');
        $builder->product->setTypeId(typeId: DownloadableType::TYPE_DOWNLOADABLE);

        return $builder;
    }

    public static function aGroupedProduct(): ProductBuilder
    {
        $builder = self::aSimpleProduct();
        $builder->product->setName(name: 'TDD Test Grouped Product');
        $builder->product->setTypeId(typeId: Grouped::TYPE_CODE);

        return $builder;
    }

    public static function aConfigurableProduct(): ProductBuilder // phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        $builder = self::aSimpleProduct();
        $builder->product->setName(name: 'TDD Test Configurable Product');
        $builder->product->setTypeId(typeId: Configurable::TYPE_CODE);
        $builder->product->unsetData(key: 'price');

        return $builder;
    }

    public function withConfigurableAttribute(
        AttributeInterface $attribute,
    ): ProductBuilder {
        $builder = clone $this;
        $attributeCode = $attribute->getAttributeCode();
        if (!array_key_exists(key: $attributeCode, array: $builder->configurableAttributes)) {
            $builder->configurableAttributes[$attributeCode] = $attribute;
        }

        return $builder;
    }

    public function withVariant(ProductInterface $variantProduct): ProductBuilder
    {
        $builder = clone $this;
        $builder->variantProducts[] = $variantProduct;

        return $builder;
    }

    public function withLinkedProduct(ProductInterface $linkedProduct): ProductBuilder
    {
        $builder = clone $this;
        $builder->linkedProducts[] = $linkedProduct;

        return $builder;
    }

    public function withSku(string $sku): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setSku(sku: $sku);

        return $builder;
    }

    public function withName(string $name, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::NAME] = $name;
        } else {
            $builder->product->setName(name: $name);
        }

        return $builder;
    }

    /**
     * @param int|null $storeId Pass store ID to set value for specific store.
     *  Attention: Status is configured per website, will affect all stores of the same website
     */
    public function withStatus(int $status, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::STATUS] = $status;
        } else {
            $builder->product->setStatus(status: $status);
        }

        return $builder;
    }

    public function withVisibility(int $visibility, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::VISIBILITY] = $visibility;
        } else {
            $builder->product->setVisibility(visibility: $visibility);
        }

        return $builder;
    }

    /**
     * @param int[] $websiteIds
     */
    public function withWebsiteIds(array $websiteIds): ProductBuilder
    {
        $builder = clone $this;
        $builder->websiteIds = array_map(
            callback: static fn (mixed $websiteId): int => (int)$websiteId,
            array: $websiteIds,
        );

        return $builder;
    }

    /**
     * @param int[] $categoryIds
     */
    public function withCategoryIds(array $categoryIds): ProductBuilder
    {
        $builder = clone $this;
        $builder->categoryIds = array_map(
            callback: static fn (mixed $categoryId): int => (int)$categoryId,
            array: $categoryIds,
        );

        return $builder;
    }

    public function withPrice(float $price): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setPrice(price: $price);

        return $builder;
    }

    /**
     * @param array<int, array<string, int|float>> $tierPrices
     */
    public function withTierPrices(array $tierPrices): ProductBuilder
    {
        $pricesToSet = [];
        foreach ($tierPrices as $tierPriceData) {
            if (!($tierPriceData['price'] ?? null)) {
                continue;
            }
            /** @var ProductTierPriceInterface $tierPrice */
            $tierPrice = $this->tierPriceFactory->create();
            $tierPrice->setCustomerGroupId(
                customerGroupId: $tierPriceData['customer_group_id'] ?? CustomerGroup::CUST_GROUP_ALL,
            );
            $tierPrice->setValue(value: $tierPriceData['price']);
            $tierPrice->setQty(qty: $tierPriceData['qty'] ?? 1);
            /** @var ProductTierPriceExtensionInterface|null $extensionAttributes */
            $extensionAttributes = $tierPrice->getExtensionAttributes();
            $objectManager = Bootstrap::getObjectManager();
            if (($tierPriceData['website_id'] ?? null)) {
                $extensionAttributes = $extensionAttributes
                                       ?? $objectManager->get(type: ProductTierPriceExtensionInterface::class);
                $extensionAttributes->setWebsiteId($tierPriceData['website_id']);
            }
            if (($tierPriceData['price_type'] ?? null)) {
                $extensionAttributes = $extensionAttributes
                                       ?? $objectManager->get(type: ProductTierPriceExtensionInterface::class);
                $extensionAttributes->setPercentageValue($tierPriceData['price']);
            }
            $tierPrice->setExtensionAttributes(extensionAttributes: $extensionAttributes);
            $pricesToSet[] = $tierPrice;
        }
        $builder = clone $this;
        if ($pricesToSet) {
            $builder->product->setTierPrices(tierPrices: $pricesToSet);
        }

        return $builder;
    }

    public function withTaxClassId(int $taxClassId): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setData(key: 'tax_class_id', value: $taxClassId);

        return $builder;
    }

    public function withIsInStock(bool $inStock): ProductBuilder
    {
        $builder = clone $this;
        $stockData = [];
        if (method_exists(object_or_class: $builder->product, method: 'getStockData')) {
            $stockData = $builder->product->getStockData();
        }
        $stockData['is_in_stock'] = $inStock;
        if (method_exists(object_or_class: $builder->product, method: 'setStockData')) {
            $builder->product->setStockData(stockData: $stockData);
        }

        $extensionAttributes = $builder->product->getExtensionAttributes();
        if (method_exists(object_or_class: $extensionAttributes, method: 'getData')) {
            $extensionAttributes->getData('stock_item')?->setIsInStock($inStock);
        }

        return $builder;
    }

    public function withStockQty(int|float $qty): ProductBuilder
    {
        $builder = clone $this;
        $stockData = [];
        if (method_exists(object_or_class: $builder->product, method: 'getStockData')) {
            $stockData = $builder->product->getStockData();
        }
        $stockData['qty'] = $qty;
        if (method_exists(object_or_class: $builder->product, method: 'setStockData')) {
            $builder->product->setStockData(stockData: $stockData);
        }

        $extensionAttributes = $builder->product->getExtensionAttributes();
        if (method_exists(object_or_class: $extensionAttributes, method: 'getData')) {
            $extensionAttributes->getData('stock_item')?->setQty(qty: $qty);
        }

        return $builder;
    }

    public function withBackorders(int $backorders): ProductBuilder
    {
        $builder = clone $this;
        $stockData = [];
        if (method_exists(object_or_class: $builder->product, method: 'getStockData')) {
            $stockData = $builder->product->getStockData();
        }
        $stockData['backorders'] = $backorders;
        if (method_exists(object_or_class: $builder->product, method: 'setStockData')) {
            $builder->product->setStockData(stockData: $stockData);
        }
        $extensionAttributes = $builder->product->getExtensionAttributes();
        if (method_exists(object_or_class: $extensionAttributes, method: 'getData')) {
            $extensionAttributes->getData('stock_item')?->setBackorders($backorders);
        }

        return $builder;
    }

    public function withManageStock(bool $manageStock): ProductBuilder
    {
        $builder = clone $this;
        $stockData = [];
        if (method_exists(object_or_class: $builder->product, method: 'getStockData')) {
            $stockData = $builder->product->getStockData();
        }
        $stockData['manage_stock'] = $manageStock;
        if (method_exists(object_or_class: $builder->product, method: 'setStockData')) {
            $builder->product->setStockData(stockData: $stockData);
        }
        $extensionAttributes = $builder->product->getExtensionAttributes();
        if (method_exists(object_or_class: $extensionAttributes, method: 'getData')) {
            $extensionAttributes->getData('stock_item')?->setManageStock($manageStock);
        }

        return $builder;
    }

    public function withIsQtyDecimal(bool $isQtyDecimal): ProductBuilder
    {
        $builder = clone $this;
        $stockData = [];
        if (method_exists(object_or_class: $builder->product, method: 'getStockData')) {
            $stockData = $builder->product->getStockData();
        }
        $stockData['is_qty_decimal'] = $isQtyDecimal;
        if (method_exists(object_or_class: $builder->product, method: 'setStockData')) {
            $builder->product->setStockData(stockData: $stockData);
        }
        $extensionAttributes = $builder->product->getExtensionAttributes();
        if (method_exists(object_or_class: $extensionAttributes, method: 'getData')) {
            $extensionAttributes->getData('stock_item')?->setIsQtyDecimal($isQtyDecimal);
        }

        return $builder;
    }

    /**
     * @param DownloadableLinkInterface[] $links
     */
    public function withDownloadLinks(?array $links = []): ProductBuilder
    {
        $builder = clone $this;
        foreach ($links as $link) {
            if (!($link instanceof DownloadableLinkInterface)) {
                throw new \InvalidArgumentException(
                    message: sprintf(
                        'Links must be instance of DownloadableLinkInterface. %s provided',
                        get_debug_type($link),
                    ),
                );
            }
        }
        $extensionAttributes = $builder->product->getExtensionAttributes();
        if (method_exists(object_or_class: $extensionAttributes, method: 'setData')) {
            $extensionAttributes->setData(key: 'downloadable_product_links', value: $links);
        }

        return $builder;
    }

    public function withWeight(float $weight): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setWeight(weight: $weight);

        return $builder;
    }

    /**
     * @param array<string, mixed> $attributeValues
     */
    public function withCustomAttributes(array $attributeValues, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        foreach ($attributeValues as $code => $value) {
            if ($storeId) {
                $builder->storeSpecificValues[$storeId][$code] = $value;
            } else {
                $builder->product->setCustomAttribute(attributeCode: (string)$code, attributeValue: $value);
            }
        }

        return $builder;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function withData(array $data, ?int $storeId = null): ProductBuilder
    {
        return $this->withCustomAttributes(attributeValues: $data, storeId: $storeId);
    }

    /**
     * @param string $imageType image, small_image, thumbnail
     *
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function withImage(
        string $fileName = 'image1',
        string $imageType = 'image',
        string $mimeType = 'image/png',
        ?string $imagePath = null,
    ): ProductBuilder {
        $builder = clone $this;

        $objectManager = Bootstrap::getObjectManager();
        $dbStorage = $objectManager->create(type: Database::class);
        $filesystem = $objectManager->get(type: Filesystem::class);
        $tmpDirectory = $filesystem->getDirectoryWrite(directoryCode: DirectoryList::SYS_TMP);
        $directory = $objectManager->get(type: Directory::class);
        $imageUploader = $objectManager->create(
            type: ImageUploader::class,
            arguments: [
                'baseTmpPath' => 'catalog/tmp/product',
                'basePath' => 'catalog/product',
                'coreFileStorageDatabase' => $dbStorage,
                'allowedExtensions' => ['jpg', 'jpeg', 'gif', 'png'],
                'allowedMimeTypes' => ['image/jpg', 'image/jpeg', 'image/gif', 'image/png'],
            ],
        );
        if (!$imagePath) {
            $imagePath = $directory->getDir(moduleName: 'TddWizard_Fixtures')
                         . DIRECTORY_SEPARATOR
                         . '_files'
                         . DIRECTORY_SEPARATOR
                         . 'images';
        }
        $fixtureImagePath = $imagePath . DIRECTORY_SEPARATOR . $fileName;
        $tmpFilePath = $tmpDirectory->getAbsolutePath(path: $fileName);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
        copy(from: $fixtureImagePath, to: $tmpFilePath);
        // phpcs:ignore Magento2.Security.Superglobal.SuperglobalUsageError, SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
        $_FILES['image'] = [
            'name' => $fileName,
            'type' => $mimeType,
            'tmp_name' => $tmpFilePath,
            'error' => 0,
            'size' => 12500,
        ];
        $imageUploader->saveFileToTmpDir(fileId: 'image');
        $imagePath = $imageUploader->moveFileFromTmp(imageName: $fileName, returnRelativePath: true);
        $builder->product->addImageToMediaGallery(
            file: $imagePath,
            mediaAttribute: $imageType,
            move: true,
            exclude: false,
        );

        return $builder;
    }

    /**
     * @throws \Exception
     */
    public function build(): ProductInterface
    {
        try {
            $product = $this->createProduct();
            if ($product->getTypeId() === Grouped::TYPE_CODE) {
                $this->associateLinkedProducts(
                    parentProduct: $product,
                    linkedProducts: $this->linkedProducts,
                );
            }
            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                $product = $this->associateChildren(
                    configurableProduct: $product,
                    configurableAttributes: $this->configurableAttributes,
                    variantProducts: $this->variantProducts,
                );
            }

            $indexer = $this->indexerFactory->create();
            $indexerNames = [
                'cataloginventory_stock',
                'catalog_product_price',
            ];
            foreach ($indexerNames as $indexerName) {
                $indexerToRun = $indexer->load(indexerId: $indexerName);
                $indexerToRun->reindexRow(id: $product->getId());
            }

            return $product;
        } catch (\Exception $exception) {
            if (
                self::isTransactionException(exception: $exception)
                || self::isTransactionException(exception: $exception->getPrevious())
            ) {
                throw IndexFailedException::becauseInitiallyTriggeredInTransaction(previous: $exception);
            }
            throw $exception;
        }
    }

    public function buildWithoutSave(): ProductInterface
    {
        if (!$this->product->getSku()) {
            $this->product->setSku(sku: sha1(uniqid(prefix: '', more_entropy: true)));
        }
        $this->product->setCustomAttribute(attributeCode: 'url_key', attributeValue: $this->product->getSku());
        $this->product->setData(key: 'category_ids', value: $this->categoryIds);

        return clone $this->product;
    }

    /**
     * @throws \Exception
     */
    private function createProduct(): ProductInterface
    {
        $builder = clone $this;
        if (!$builder->product->getSku()) {
            $builder->product->setSku(sku: sha1(uniqid(prefix: '', more_entropy: true)));
        }
        if (!$builder->product->getCustomAttribute(attributeCode: 'url_key')) {
            $builder->product->setCustomAttribute(
                attributeCode: 'url_key',
                attributeValue: strtolower(
                    string: str_replace(search: ' ', replace: '_', subject: $builder->product->getSku()),
                ),
            );
        }
        $builder->product->setData(key: 'category_ids', value: $builder->categoryIds);
        $product = $builder->productRepository->save(product: $builder->product);
        foreach ($builder->websiteIds as $websiteId) {
            $websiteLink = $builder->websiteLinkFactory->create();
            $websiteLink->setWebsiteId(websiteId: $websiteId);
            $websiteLink->setSku(sku: $product->getSku());
            $builder->websiteLinkRepository->save(productWebsiteLink: $websiteLink);
        }
        if (!empty($builder->websiteIds)) {
            $extensionAttributes = $product->getExtensionAttributes();
            $extensionAttributes?->setWebsiteIds($builder->websiteIds);
        }
        foreach ($builder->storeSpecificValues as $storeId => $values) {
            /** @var Product $storeProduct */
            $storeProduct = clone $product;
            $storeProduct->setStoreId(storeId: $storeId);
            $storeProduct->addData($values);
            $storeProduct->save();
        }
        if ($product->getTypeId() === DownloadableType::TYPE_DOWNLOADABLE) {
            $this->setDownloadableLinks(product: $product);
        }

        return $product;
    }

    private function setDownloadableLinks(ProductInterface $product): void
    {
        $builder = clone $this;
        $links = $builder->product->getExtensionAttributes()->getDownloadableProductLinks();
        if (!$links) {
            /** @var DownloadableLinkInterface $link */
            $link = $builder->downloadLinkFactory->create();
            $link->setTitle(title: 'Downloadable Item');
            $link->setNumberOfDownloads(numberOfDownloads: 100);
            $link->setIsShareable(isShareable: 1);
            $link->setLinkType(linkType: 'url');
            $link->setLinkUrl(linkUrl: $this->downloadableLinkDomain);
            $link->setPrice(price: 54.99);
            $link->setSortOrder(sortOrder: 1);
            $links = [$link];
        }
        $domains = array_map(
            callback: static function (DownloadableLinkInterface $link) {
                $urlParts = explode(separator: '://', string: $link->getLinkUrl());
                $url = explode(separator: '/', string: $urlParts[1]);

                return $url[0];
            },
            array: $links,
        );
        $builder->domainManager->addDomains(hosts: $domains);

        foreach ($links as $link) {
            $builder->downloadLinkRepository->save(
                sku: $product->getSku(),
                link: $link,
            );
        }
        // Removing these added domains can lead to an empty array for downloadable_domains which causes
        // ERROR: deployment configuration is corrupted. The application state is no longer valid.
        // $builder->domainManager->removeDomains($domains);
    }

    /**
     * @param array<string, AttributeInterface> $configurableAttributes
     * @param ProductInterface[] $variantProducts
     *
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    private function associateChildren(
        ProductInterface $configurableProduct,
        array $configurableAttributes,
        array $variantProducts,
    ): ProductInterface {
        if (!$configurableAttributes) {
            return $configurableProduct;
        }

        $attributeValues = [];
        foreach ($configurableAttributes as $attributeCode => $configurableAttribute) {
            $attributeValues[$attributeCode] = [];

            /** @var Product $variantProduct */
            foreach ($variantProducts as $variantProduct) {
                $attributeCode = $configurableAttribute->getAttributeCode();
                $attributeValues[$attributeCode][] = [
                    'label' => 'test',
                    'attribute_id' => $configurableAttribute->getId(),
                    'value_index' => $variantProduct->getData(key: $attributeCode),
                ];
            }
        }

        $configurableAttributesData = [];
        $position = 0;
        foreach ($attributeValues as $attributeCode => $values) {
            $configurableAttribute = $configurableAttributes[$attributeCode];

            $configurableAttributesData[] = [
                'attribute_id' => $configurableAttribute->getId(),
                'code' => $configurableAttribute->getAttributeCode(),
                'label' => $configurableAttribute->getDataUsingMethod(key: 'store_label'),
                'position' => $position++,
                'values' => $values,
            ];
        }

        $extensionConfigurableAttributes = $configurableProduct->getExtensionAttributes();
        if (!$extensionConfigurableAttributes) {
            $objectManager = ObjectManager::getInstance();
            $extensionConfigurableAttributes = $objectManager->create(type: ProductExtensionInterface::class);
        }

        $configurableOptions = $this->configurableOptionsFactory->create(attributesData: $configurableAttributesData);
        $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionConfigurableAttributes->setConfigurableProductLinks(
            array_map(
                static fn (ProductInterface $variantProduct): int => (int)$variantProduct->getId(),
                $variantProducts,
            ),
        );
        $configurableProduct->setExtensionAttributes(extensionAttributes: $extensionConfigurableAttributes);

        return $this->productRepository->save(product: $configurableProduct);
    }

    /**
     * @param ProductInterface[] $linkedProducts
     *
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    private function associateLinkedProducts(
        ProductInterface $parentProduct,
        array $linkedProducts,
    ): void {
        $productLinks = [];
        $position = 1;
        foreach ($linkedProducts as $linkedProduct) {
            if (!($linkedProduct instanceof ProductInterface)) {
                throw new \InvalidArgumentException(
                    message: sprintf(
                        '$linkedProducts must be instance of ProductInterface. %s provided',
                        get_debug_type($linkedProduct),
                    ),
                );
            }
            $productLink = $this->productLinkFactory->create();
            $productLink->setSku(sku: $parentProduct->getSku());
            $productLink->setLinkType(linkType: GroupedProductHelperPlugin::TYPE_NAME);
            $productLink->setLinkedProductSku(linkedProductSku: $linkedProduct->getSku());
            $productLink->setPosition(position: $position++);
            $extensionAttributes = $productLink->getExtensionAttributes();
            $extensionAttributes?->setQty(qty: 1);
            $productLinks[] = $productLink;
        }
        $parentProduct->setProductLinks(links: $productLinks);

        $this->productRepository->save(product: $parentProduct);
    }
}
