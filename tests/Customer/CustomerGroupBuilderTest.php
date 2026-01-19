<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface as CustomerGroupRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Tax\Model\ClassModel as TaxClass;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Store\WebsiteFixturePool;
use TddWizard\Fixtures\Store\WebsiteTrait;
use TddWizard\Fixtures\Tax\TaxClassFixturePool;
use TddWizard\Fixtures\Tax\TaxClassTrait;

class CustomerGroupBuilderTest extends TestCase
{
    use TaxClassTrait;
    use WebsiteTrait;

    private ?ObjectManagerInterface $objectManager = null;
    private CustomerGroupRepositoryInterface $customerGroupRepository;
    private GroupExcludedWebsiteRepositoryInterface $groupExcludedWebsiteRepository;
    /**
     * @var CustomerGroupFixture[]
     */
    private array $customerGroups = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerGroupRepository = $this->objectManager->create(type: CustomerGroupRepositoryInterface::class);
        $this->groupExcludedWebsiteRepository = $this->objectManager->create(type: GroupExcludedWebsiteRepositoryInterface::class);
        $this->taxClassFixturePool = $this->objectManager->get(type: TaxClassFixturePool::class);
        $this->websiteFixturePool = $this->objectManager->get(type: WebsiteFixturePool::class);
        $this->customerGroups = [];
    }

    protected function tearDown(): void
    {
        if (!empty($this->customerGroups)) {
            foreach ($this->customerGroups as $customerGroup) {
                CustomerGroupFixtureRollback::create()->execute($customerGroup);
            }
        }
        $this->taxClassFixturePool->rollback();
        $this->websiteFixturePool->rollback();
    }

    public function testCustomerGroup_withDefaultValues(): void
    {
        $customerGroupFixture = new CustomerGroupFixture(
            CustomerGroupBuilder::addCustomerGroup()->build(),
        );
        $customerGroup = $customerGroupFixture->getCustomerGroup();
        $this->customerGroups[] = $customerGroupFixture;
        $customerGroupFromDb = $this->customerGroupRepository->getById(id: $customerGroupFixture->getId());

        $this->assertSame(expected: $customerGroup->getId(), actual: $customerGroupFromDb->getId());
        $this->assertSame(expected: $customerGroup->getCode(), actual: $customerGroupFromDb->getCode());
        $this->assertSame(expected: $customerGroup->getTaxClassName(), actual: $customerGroupFromDb->getTaxClassName());
        $this->assertEquals(expected: $customerGroup->getTaxClassId(), actual: $customerGroupFromDb->getTaxClassId());
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testCustomerGroup_withCustomValues(): void
    {
        $this->createTaxClass(taxClassData: [
            'key' => 'tdd_customer_tax_class',
            'class_name' => 'TDD_CUSTOMER_TAX_CLASS',
            'class_type' => TaxClass::TAX_CLASS_TYPE_CUSTOMER,
        ]);
        $customerTaxClassFixture = $this->taxClassFixturePool->get(key: 'tdd_customer_tax_class');

        $this->createWebsite();
        $websiteFixture = $this->websiteFixturePool->get(key: 'tdd_website');

        $customerGroupBuilder = CustomerGroupBuilder::addCustomerGroup();
        $customerGroupBuilder = $customerGroupBuilder->withCode(code: 'tdd_customer_group_code');
        $customerGroupBuilder = $customerGroupBuilder->withTaxClassId(taxClassId: $customerTaxClassFixture->getId());
        $customerGroupBuilder = $customerGroupBuilder->withExcludedWebsiteIds(excludedIds: [$websiteFixture->getId()]);
        $customerGroupFixture = new CustomerGroupFixture(
            group: $customerGroupBuilder->build(),
        );
        $customerGroup = $customerGroupFixture->getCustomerGroup();
        $this->customerGroups[] = $customerGroupFixture;
        $customerGroupFromDb = $this->customerGroupRepository->getById(id: $customerGroupFixture->getId());

        $this->assertSame(expected: $customerGroup->getId(), actual: $customerGroupFromDb->getId());
        $this->assertSame(expected: 'tdd_customer_group_code', actual: $customerGroupFromDb->getCode());
        $this->assertSame(expected: 'TDD_CUSTOMER_TAX_CLASS', actual: $customerGroupFromDb->getTaxClassName());
        $this->assertEquals(expected: $customerTaxClassFixture->getId(), actual: $customerGroupFromDb->getTaxClassId());
        $excludedWebsites = $this->groupExcludedWebsiteRepository->getCustomerGroupExcludedWebsites(
            customerGroupId: $customerGroupFixture->getId(),
        );
        $this->assertContains(needle: (string)$websiteFixture->getId(), haystack: $excludedWebsites);
    }
}
