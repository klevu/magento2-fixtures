<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Catalog\Category;

use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Catalog\Model\AbstractModel;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ImageUploader;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem;
use Magento\Framework\Module\Dir as Directory;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Exception\InvalidModelException;

class CategoryBuilder
{
    /**
     * @param array<int, string> $skus
     * @param array<int, array<string, mixed>> $storeSpecificValues
     */
    public function __construct(
        private readonly CategoryResource $categoryResource,
        private readonly CategoryLinkRepositoryInterface $categoryLinkRepository,
        private readonly CategoryProductLinkInterfaceFactory $productLinkFactory,
        private CategoryInterface & AbstractModel $category,
        private array $skus,
        private array $storeSpecificValues,
    ) {
    }

    public static function rootCategory(): CategoryBuilder
    {
        $objectManager = Bootstrap::getObjectManager();

        // use interface to reflect DI configuration but assume instance of the real model because we need its methods
        /** @var Category $category */
        $category = $objectManager->create(type: CategoryInterface::class);

        $category->setName(name: 'Root Category');
        $category->setIsActive(isActive: true);
        $category->setPath(path: '1');
        $category->setParentId(parentId: 1);

        return new self(
            categoryResource: $objectManager->create(type: CategoryResource::class),
            categoryLinkRepository: $objectManager->create(type: CategoryLinkRepositoryInterface::class),
            productLinkFactory: $objectManager->create(type: CategoryProductLinkInterfaceFactory::class),
            category: $category,
            skus: [],
            storeSpecificValues: [],
        );
    }

    public static function topLevelCategory(?int $rootCategoryId = null): CategoryBuilder
    {
        $rootCategoryId = $rootCategoryId ?? static::getLowestRootCategoryId();
        $objectManager = Bootstrap::getObjectManager();

        // use interface to reflect DI configuration but assume instance of the real model because we need its methods
        /** @var Category $category */
        $category = $objectManager->create(type: CategoryInterface::class);

        $category->setName(name: 'Top Level Category');
        $category->setIsActive(isActive: true);
        $category->setPath(path: '1/' . $rootCategoryId);
        $category->setParentId(parentId: $rootCategoryId);

        return new self(
            categoryResource: $objectManager->create(type: CategoryResource::class),
            categoryLinkRepository: $objectManager->create(type: CategoryLinkRepositoryInterface::class),
            productLinkFactory: $objectManager->create(type: CategoryProductLinkInterfaceFactory::class),
            category: $category,
            skus: [],
            storeSpecificValues: [],
        );
    }

    public static function childCategoryOf(
        CategoryInterface $parent,
    ): CategoryBuilder {
        $objectManager = Bootstrap::getObjectManager();
        // use interface to reflect DI configuration but assume instance of the real model because we need its methods
        /** @var Category $category */
        $category = $objectManager->create(type: CategoryInterface::class);

        $category->setName(name: 'Child Category');
        $category->setIsActive(isActive: true);
        $category->setPath(path: (string)$parent->getPath());

        return new self(
            categoryResource: $objectManager->create(type: CategoryResource::class),
            categoryLinkRepository: $objectManager->create(type: CategoryLinkRepositoryInterface::class),
            productLinkFactory: $objectManager->create(type: CategoryProductLinkInterfaceFactory::class),
            category: $category,
            skus: [],
            storeSpecificValues: [],
        );
    }

    /**
     * Assigns products by sku. The keys of the array will be used for the sort position
     *
     * @param array<int, string> $skus
     */
    public function withProducts(array $skus): CategoryBuilder
    {
        $builder = clone $this;
        foreach ($skus as $position => $sku) {
            $builder->skus[(int)$position] = (string)$sku;
        }

        return $builder;
    }

    public function withDescription(string $description, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId]['description'] = $description;
        } else {
            $builder->category->setCustomAttribute(attributeCode: 'description', attributeValue: $description);
        }

        return $builder;
    }

    public function withName(string $name, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][CategoryInterface::KEY_NAME] = $name;
        } else {
            $builder->category->setName(name: $name);
        }

        return $builder;
    }

    public function withUrlKey(string $urlKey, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId]['url_key'] = $urlKey;
        } else {
            $builder->category->setData(key: 'url_key', value: $urlKey);
        }

        return $builder;
    }

    public function withIsActive(bool $isActive, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        if ($storeId) {
            $builder->storeSpecificValues[$storeId][CategoryInterface::KEY_IS_ACTIVE] = $isActive;
        } else {
            $builder->category->setIsActive(isActive: $isActive);
        }

        return $builder;
    }

    public function withIsAnchor(bool $isAnchor): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setData(key: 'is_anchor', value: $isAnchor);

        return $builder;
    }

    public function withDisplayMode(string $displayMode): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setData(key: 'display_mode', value: $displayMode);

        return $builder;
    }

    public function withStoreId(int $storeId): CategoryBuilder
    {
        $builder = clone $this;
        $builder->category->setData(key: 'store_id', value: $storeId);

        return $builder;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function withCustomAttributes(array $values, ?int $storeId = null): CategoryBuilder
    {
        $builder = clone $this;
        foreach ($values as $code => $value) {
            if ($storeId) {
                $builder->storeSpecificValues[$storeId][$code] = $value;
            } else {
                $builder->category->setCustomAttribute(attributeCode: $code, attributeValue: $value);
            }
        }

        return $builder;
    }

    public function withImage(
        string $fileName = 'image1',
        string $mimeType = 'image/png',
        ?string $imagePath = null,
    ): CategoryBuilder {
        $builder = clone $this;

        $objectManager = Bootstrap::getObjectManager();
        $dbStorage = $objectManager->create(type: Database::class);
        $filesystem = $objectManager->get(type: Filesystem::class);
        $tmpDirectory = $filesystem->getDirectoryWrite(directoryCode: DirectoryList::SYS_TMP);
        $directory = $objectManager->get(type: Directory::class);
        $imageUploader = $objectManager->create(
            type: ImageUploader::class,
            arguments: [
                'baseTmpPath' => 'catalog/tmp/category',
                'basePath' => 'media/catalog/category',
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
        $imagePath = $imageUploader->moveFileFromTmp(imageName: $fileName, returnRelativePath: false);

        $builder->category->setData(key: 'image', value: $imagePath);

        return $builder;
    }

    public function __clone()
    {
        $this->category = clone $this->category;
    }

    /**
     * @throws \Exception
     */
    public function build(): CategoryInterface & AbstractModel
    {
        $builder = clone $this;

        if (!$builder->category->getData(key: 'url_key')) {
            $builder->category->setData(key: 'url_key', value: sha1(uniqid(prefix: '', more_entropy: true)));
        }

        // Save with global scope if not specified otherwise
        if (!$builder->category->hasData(key: 'store_id')) {
            if (!method_exists(object_or_class: $builder->category, method: 'setStoreId')) {
                throw new InvalidModelException(message: '$builder->category is missing method setStoreId.');
            }
            $builder->category->setStoreId(storeId: Store::DEFAULT_STORE_ID);
        }
        $category = $builder->category;
        $builder->categoryResource->save(object: $category);

        foreach ($builder->skus as $position => $sku) {
            $productLink = $builder->productLinkFactory->create();
            $productLink->setSku(sku: (string)$sku);
            $productLink->setPosition(position: (int)$position);
            $productLink->setCategoryId(categoryId: $builder->category->getId());
            $builder->categoryLinkRepository->save(productLink: $productLink);
        }
        foreach ($builder->storeSpecificValues as $storeId => $values) {
            $storeCategory = clone $category;
            if (!method_exists(object_or_class: $storeCategory, method: 'setStoreId')) {
                throw new InvalidModelException(message: '$storeCategory is missing method setStoreId.');
            }
            $storeCategory->setStoreId(storeId: $storeId);
            $storeCategory->addData($values);
            $builder->categoryResource->save(object: $storeCategory);
        }
        if ($builder->storeSpecificValues) {
            $this->clearCategoryRepositoryCache();
        }

        return $builder->category;
    }

    private function clearCategoryRepositoryCache(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $categoryRepository = $objectManager->get(type: CategoryRepositoryInterface::class);
        $categoryRepository->_resetState();
    }

    /**
     * @throws InputException
     */
    private static function getLowestRootCategoryId(): int
    {
        $objectManager = Bootstrap::getObjectManager();

        $filter = $objectManager->create(type: Filter::class);
        $filter->setField(field: CategoryInterface::KEY_PARENT_ID);
        $filter->setValue(value: 1);

        $filterGroup = $objectManager->create(type: FilterGroup::class);
        $filterGroup->setFilters(filters: [$filter]);

        $sortOrder = $objectManager->get(type: SortOrder::class);
        $sortOrder->setField(field: 'category_id');
        $sortOrder->setDirection(direction: SortOrder::SORT_ASC);

        $searchCriteria = $objectManager->create(type: SearchCriteriaInterface::class);
        $searchCriteria->setFilterGroups(filterGroups: [$filterGroup]);
        $searchCriteria->setSortOrders(sortOrders: [$sortOrder]);
        $searchCriteria->setPageSize(pageSize: 1);
        $searchCriteria->setCurrentPage(currentPage: 1);

        $categoryList = $objectManager->create(type: CategoryListInterface::class);
        $categoryListSearchResult = $categoryList->getList(searchCriteria: $searchCriteria);
        $categories = $categoryListSearchResult->getItems();
        $category = array_shift(array: $categories);

        return (int)$category->getId();
    }
}
