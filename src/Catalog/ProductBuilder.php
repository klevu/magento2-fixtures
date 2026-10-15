<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductTierPriceExtensionInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\Data\ProductWebsiteLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ProductWebsiteLinkRepositoryInterface;
use Magento\Catalog\Model\ImageUploader;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Downloadable\Api\Data\LinkInterface as DownloadableLinkInterface;
use Magento\Downloadable\Api\Data\LinkInterfaceFactory as DownloadableLinkInterfaceFactory;
use Magento\Downloadable\Api\DomainManagerInterface;
use Magento\Downloadable\Api\LinkRepositoryInterface as DownloadableLinkRepositoryInterface;
use Magento\Downloadable\Model\Product\Type as DownloadableType;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir as Directory;
use Magento\Indexer\Model\IndexerFactory;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

//phpcs:disable SlevomatCodingStandard.Classes.ClassStructure.IncorrectGroupOrder

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.UnusedPrivateField)
 */
class ProductBuilder
{
    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;
    /**
     * @var StockItemRepositoryInterface
     */
    private StockItemRepositoryInterface $stockItemRepository;
    /**
     * @var ProductWebsiteLinkRepositoryInterface
     */
    private ProductWebsiteLinkRepositoryInterface $websiteLinkRepository;
    /**
     * @var ProductWebsiteLinkInterfaceFactory
     */
    private ProductWebsiteLinkInterfaceFactory $websiteLinkFactory;
    /**
     * @var IndexerFactory
     */
    private IndexerFactory $indexerFactory;
    /**
     * @var DownloadableLinkRepositoryInterface
     */
    private DownloadableLinkRepositoryInterface $downloadLinkRepository;
    /**
     * @var DownloadableLinkInterfaceFactory
     */
    private DownloadableLinkInterfaceFactory $downloadLinkFactory;
    /**
     * @var DomainManagerInterface
     */
    private DomainManagerInterface $domainManager;
    /**
     * @var ProductTierPriceInterfaceFactory
     */
    private ProductTierPriceInterfaceFactory $tierPriceFactory;
    /**
     * @var Product
     */
    protected ProductInterface $product;
    /**
     * @var int[]
     */
    private array $websiteIds;
    /**
     * @var mixed[][]
     */
    private array $storeSpecificValues;
    /**
     * @var int[]
     */
    private array $categoryIds = [];

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param ProductWebsiteLinkRepositoryInterface $websiteLinkRepository
     * @param ProductWebsiteLinkInterfaceFactory $websiteLinkFactory
     * @param IndexerFactory $indexerFactory
     * @param DownloadableLinkRepositoryInterface $downloadLinkRepository
     * @param DownloadableLinkInterfaceFactory $downloadLinkFactory
     * @param DomainManagerInterface $domainManager
     * @param ProductTierPriceInterfaceFactory $tierPriceFactory
     * @param Product $product
     * @param int[] $websiteIds
     * @param mixed[] $storeSpecificValues
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StockItemRepositoryInterface $stockItemRepository,
        ProductWebsiteLinkRepositoryInterface $websiteLinkRepository,
        ProductWebsiteLinkInterfaceFactory $websiteLinkFactory,
        IndexerFactory $indexerFactory,
        DownloadableLinkRepositoryInterface $downloadLinkRepository,
        DownloadableLinkInterfaceFactory $downloadLinkFactory,
        DomainManagerInterface $domainManager,
        ProductTierPriceInterfaceFactory $tierPriceFactory,
        Product $product,
        array $websiteIds,
        array $storeSpecificValues,
    ) {
        $this->productRepository = $productRepository;
        $this->websiteLinkRepository = $websiteLinkRepository;
        $this->stockItemRepository = $stockItemRepository;
        $this->websiteLinkFactory = $websiteLinkFactory;
        $this->indexerFactory = $indexerFactory;
        $this->downloadLinkRepository = $downloadLinkRepository;
        $this->downloadLinkFactory = $downloadLinkFactory;
        $this->domainManager = $domainManager;
        $this->tierPriceFactory = $tierPriceFactory;
        $this->product = $product;
        $this->websiteIds = $websiteIds;
        $this->storeSpecificValues = $storeSpecificValues;
    }

    /**
     * @return void
     */
    public function __clone(): void
    {
        $this->product = clone $this->product;
    }

    /**
     * @return ProductBuilder
     */
    public static function aSimpleProduct(): ProductBuilder
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Product $product */
        $product = $objectManager->create(ProductInterface::class);

        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId(4)
            ->setName('Simple Product')
            ->setPrice(10)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setImage('no_selection')
            ->setThumbnail('no_selection')
            ->setSmallImage('no_selection')
            ->setKlevuImage('no_selection')
            ->setImage('no_selection')
            ->setStatus(Status::STATUS_ENABLED);
        $product->addData(
            [
                'tax_class_id' => 1,
                'description' => 'Description',
            ],
        );
        /** @var StockItemInterface $stockItem */
        $stockItem = $objectManager->create(StockItemInterface::class);
        $stockItem->setManageStock(true)
                  ->setQty(100)
                  ->setIsQtyDecimal(false)
                  ->setIsInStock(true);

        $extensionAttributes = $product->getExtensionAttributes();
        $extensionAttributes->setStockItem($stockItem);

        return new static(
            $objectManager->create(ProductRepositoryInterface::class),
            $objectManager->create(StockItemRepositoryInterface::class),
            $objectManager->create(ProductWebsiteLinkRepositoryInterface::class),
            $objectManager->create(ProductWebsiteLinkInterfaceFactory::class),
            $objectManager->create(IndexerFactory::class),
            $objectManager->create(DownloadableLinkRepositoryInterface::class),
            $objectManager->create(DownloadableLinkInterfaceFactory::class),
            $objectManager->create(DomainManagerInterface::class),
            $objectManager->create(ProductTierPriceInterfaceFactory::class),
            $product,
            [1],
            [],
        );
    }

    /**
     * @return ProductBuilder
     */
    public static function aVirtualProduct(): ProductBuilder
    {
        $builder = self::aSimpleProduct();
        $builder->product->setName('Virtual Product');
        $builder->product->setTypeId(Type::TYPE_VIRTUAL);

        return $builder;
    }

    /**
     * @return ProductBuilder
     */
    public static function aDownloadableProduct(): ProductBuilder
    {
        $builder = self::aSimpleProduct();
        $builder->product->setName('Downloadable Product');
        $builder->product->setTypeId(DownloadableType::TYPE_DOWNLOADABLE);

        return $builder;
    }

    /**
     * @param mixed[] $data
     *
     * @return ProductBuilder
     */
    public function withData(array $data): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->addData($data);

        return $builder;
    }

    /**
     * @param string $sku
     *
     * @return $this
     */
    public function withSku(string $sku): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setSku($sku);

        return $builder;
    }

    /**
     * @param string $name
     * @param int|null $storeId
     *
     * @return $this
     */
    public function withName(string $name, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::NAME] = $name;
        } else {
            $builder->product->setName($name);
        }

        return $builder;
    }

    /**
     * @param int $status
     * @param int|null $storeId Pass store ID to set value for specific store.
     *                          Attention: Status is configured per website, will affect all stores of the same website
     *
     * @return ProductBuilder
     */
    public function withStatus(int $status, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::STATUS] = $status;
        } else {
            $builder->product->setStatus($status);
        }

        return $builder;
    }

    /**
     * @param int $visibility
     * @param int|null $storeId
     *
     * @return $this
     */
    public function withVisibility(int $visibility, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][ProductInterface::VISIBILITY] = $visibility;
        } else {
            $builder->product->setVisibility($visibility);
        }

        return $builder;
    }

    /**
     * @param int[] $websiteIds
     *
     * @return ProductBuilder
     */
    public function withWebsiteIds(array $websiteIds): ProductBuilder
    {
        $builder = clone $this;
        $builder->websiteIds = $websiteIds;

        return $builder;
    }

    /**
     * @param int[] $categoryIds
     *
     * @return ProductBuilder
     */
    public function withCategoryIds(array $categoryIds): ProductBuilder
    {
        $builder = clone $this;
        $builder->categoryIds = $categoryIds;

        return $builder;
    }

    /**
     * @param float $price
     *
     * @return $this
     */
    public function withPrice(float $price): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setPrice($price);

        return $builder;
    }

    /**
     * @param mixed[] $tierPrices
     *
     * @return $this
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
            if (($tierPriceData['website_id'] ?? null)) {
                $extensionAttributes = $extensionAttributes
                    ?? ObjectManager::getInstance()->get(ProductTierPriceExtensionInterface::class);
                $extensionAttributes->setWebsiteId($tierPriceData['website_id']);
            }
            if (($tierPriceData['price_type'] ?? null)) {
                $extensionAttributes = $extensionAttributes
                    ?? ObjectManager::getInstance()->get(ProductTierPriceExtensionInterface::class);
                $extensionAttributes->setPercentageValue($tierPriceData['price']);
            }
            $tierPrice->setExtensionAttributes($extensionAttributes);
            $pricesToSet[] = $tierPrice;
        }
        $builder = clone $this;
        if ($pricesToSet) {
            $builder->product->setTierPrices(tierPrices: $pricesToSet);
        }

        return $builder;
    }

    /**
     * @param int $taxClassId
     *
     * @return $this
     */
    public function withTaxClassId(int $taxClassId): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setData('tax_class_id', $taxClassId);

        return $builder;
    }

    /**
     * @param bool $inStock
     *
     * @return $this
     */
    public function withIsInStock(bool $inStock): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->getStockItem()->setIsInStock($inStock);

        return $builder;
    }

    /**
     * @param float $qty
     *
     * @return $this
     */
    public function withStockQty(float $qty): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->getStockItem()->setQty($qty);

        return $builder;
    }

    /**
     * @param float $backorders
     *
     * @return $this
     */
    public function withBackorders(float $backorders): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->getStockItem()->setBackorders($backorders);

        return $builder;
    }

    /**
     * @param string[] $links
     *
     * @return $this
     */
    public function withDownloadLinks(?array $links = []): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->getExtensionAttributes()->setDownloadableProductLinks($links);

        return $builder;
    }

    /**
     * @param float $weight
     *
     * @return $this
     */
    public function withWeight(float $weight): ProductBuilder
    {
        $builder = clone $this;
        $builder->product->setWeight($weight);

        return $builder;
    }

    /**
     * @param mixed[] $values
     * @param int|null $storeId
     *
     * @return ProductBuilder
     */
    public function withCustomAttributes(array $values, ?int $storeId = null): ProductBuilder
    {
        $builder = clone $this;
        foreach ($values as $code => $value) {
            if ($storeId) {
                $builder->storeSpecificValues[$storeId][$code] = $value;
            } else {
                $builder->product->setCustomAttribute($code, $value);
            }
        }

        return $builder;
    }

    /**
     * @param string $fileName
     * @param string|null $imageType
     *
     * @return $this
     * @throws LocalizedException
     */
    public function withImage(string $fileName, ?string $imageType = 'image'): ProductBuilder
    {
        $builder = clone $this;

        $objectManager = Bootstrap::getObjectManager();
        $dbStorage = $objectManager->create(Database::class);
        $filesystem = $objectManager->get(Filesystem::class);
        $tmpDirectory = $filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $directory = $objectManager->get(Directory::class);
        $imageUploader = $objectManager->create(
            ImageUploader::class,
            [
                'baseTmpPath' => 'catalog/tmp/product',
                'basePath' => 'catalog/product',
                'coreFileStorageDatabase' => $dbStorage,
                'allowedExtensions' => ['jpg', 'jpeg', 'gif', 'png'],
                'allowedMimeTypes' => ['image/jpg', 'image/jpeg', 'image/gif', 'image/png'],
            ],
        );

        $fixtureImagePath = $directory->getDir(moduleName: 'Klevu_TestFixtures')
            . DIRECTORY_SEPARATOR . '_files'
            . DIRECTORY_SEPARATOR . 'images'
            . DIRECTORY_SEPARATOR . $fileName;
        $tmpFilePath = $tmpDirectory->getAbsolutePath($fileName);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
        copy(from: $fixtureImagePath, to: $tmpFilePath);
        // phpcs:ignore Magento2.Security.Superglobal.SuperglobalUsageError, SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
        $_FILES['image'] = [
            'name' => $fileName,
            'type' => 'image/jpeg',
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
     * @return ProductInterface
     * @throws \Exception
     */
    public function build(): ProductInterface
    {
        try {
            $product = $this->createProduct();

            $indexer = $this->indexerFactory->create();
            $indexerNames = [
                'cataloginventory_stock',
                'catalog_product_price',
            ];
            foreach ($indexerNames as $indexerName) {
                $indexer->load($indexerName)->reindexRow($product->getId());
            }

            return $product;
        } catch (\Exception $e) {
            $e->getPrevious();
            if (self::isTransactionException($e) || self::isTransactionException($e->getPrevious())) {
                throw IndexFailed::becauseInitiallyTriggeredInTransaction($e);
            }
            throw $e;
        }
    }

    /**
     * @return ProductInterface
     */
    public function buildWithoutSave(): ProductInterface
    {
        if (!$this->product->getSku()) {
            $this->product->setSku(sha1(uniqid('', true)));
        }
        $this->product->setCustomAttribute('url_key', $this->product->getSku());
        $this->product->setData('category_ids', $this->categoryIds);

        return clone $this->product;
    }

    /**
     * @return ProductInterface
     * @throws \Exception
     */
    private function createProduct(): ProductInterface
    {
        $builder = clone $this;
        if (!$builder->product->getSku()) {
            $builder->product->setSku(sha1(uniqid('', true)));
        }
        if (!$builder->product->getCustomAttribute('url_key')) {
            $builder->product->setCustomAttribute('url_key', $builder->product->getSku());
        }
        $builder->product->setData('category_ids', $builder->categoryIds);
        $product = $builder->productRepository->save($builder->product);
        foreach ($builder->websiteIds as $websiteId) {
            $websiteLink = $builder->websiteLinkFactory->create();
            $websiteLink->setWebsiteId($websiteId)->setSku($product->getSku());
            $builder->websiteLinkRepository->save($websiteLink);
        }
        if (!empty($builder->websiteIds)) {
            $extensionAttributes = $product->getExtensionAttributes();
            $extensionAttributes?->setWebsiteIds($builder->websiteIds);
        }
        foreach ($builder->storeSpecificValues as $storeId => $values) {
            /** @var Product $storeProduct */
            $storeProduct = clone $product;
            $storeProduct->setStoreId($storeId);
            $storeProduct->addData($values);
            $storeProduct->save();
        }
        if ($product->getTypeId() === DownloadableType::TYPE_DOWNLOADABLE) {
            $this->setDownloadableLinks(product: $product);
        }

        return $product;
    }

    /**
     * @param ProductInterface $product
     *
     * @return void
     */
    private function setDownloadableLinks(ProductInterface $product): void
    {
        $builder = clone $this;
        $links = $builder->product->getExtensionAttributes()->getDownloadableProductLinks();
        if (!$links) {
            /** @var DownloadableLinkInterface $link */
            $link = $builder->downloadLinkFactory->create();
            $link->setTitle('Downloadable Item');
            $link->setNumberOfDownloads(100);
            $link->setIsShareable(1);
            $link->setLinkType('url');
            $link->setLinkUrl('https://magento.test/');
            $link->setPrice(54.99);
            $link->setSortOrder(1);
            $links = [$link];
        }
        $domains = array_map(
            callback: static function (DownloadableLinkInterface $link) {
                $urlParts = explode('://', $link->getLinkUrl());
                $url = explode('/', $urlParts[1]);

                return $url[0];
            },
            array: $links,
        );
        $builder->domainManager->addDomains($domains);

        foreach ($links as $link) {
            $builder->downloadLinkRepository->save(
                sku: $product->getSku(),
                link: $link,
                isGlobalScopeContent: true,
            );
        }
        // Removing these added domains can lead to an empty array for downloadable_domains which causes
        // ERROR: deployment configuration is corrupted. The application state is no longer valid.
        // $builder->domainManager->removeDomains($domains);
    }

    /**
     * @param \Throwable|null $exception
     *
     * @return bool
     */
    private static function isTransactionException(?\Throwable $exception): bool
    {
        if ($exception === null) {
            return false;
        }

        return (bool)preg_match(
            '{please retry transaction|DDL statements are not allowed in transactions}i',
            $exception->getMessage(),
        );
    }
}
