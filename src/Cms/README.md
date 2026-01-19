# CMS Fixtures

## Cms Page

### Defaults

```php
[
    'key' => 'tdd-page',
    'identifier' => 'tdd-page',
    'title' => 'Tdd Page Title', 
    'is_active' => true, 
    'store_id' => 0, 
    'stores' => [0], 
    'data' => [
        'content' => 'Content - Tdd Page Title',
        'content_heading' => 'Heading - Tdd Page Title',
        'meta_description' => 'Meta Description - Tdd Page Title',
        'meta_title' => 'Meta Title - Tdd Page Title',
        'page_layout' => '1column',
    ], 
]
```

### Build With Trait

```php
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Cms\CmsPageFixturesPool;
use TddWizard\Fixtures\Cms\CmsPageTrait;
use TddWizard\Fixtures\Store\StoreFixturesPool;
use TddWizard\Fixtures\Store\StoreTrait;

class SomeTest extends TestCase
{
    use CmsPageTrait;
    use StoreTrait;
    
    private ?ObjectManagerInterface $objectManager = null;
    
    protected function setUp(): void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->cmsPageFixturePool = $this->objectManager->create(CmsPageFixturesPool::class);
        $this->storeFixturePool = $this->objectManager->create(StoreFixturesPool::class);
    }

    protected function tearDown(): void
    {
        $this->cmsPageFixturePool->rollback();
        $this->storeFixturePool->rollback();
    }
    
    public function testSomething_withDefaultCmsPageValues(): void
    {
        $this->createCmsPage();
        $cmsPageFixture = $this->cmsPageFixturePool->get('tdd_page');
        ...
    }

    public function testSomething_withCustomCmsPageValues(): void
    {
        $this->createStore();
        $storeFixture1 = $this->storeFixturePool->get('tdd_store');
        $this->createStore([
            'key' => 'tdd_store_2',
            'code' => 'tdd_store_2',
        ]);
        $storeFixture2 = $this->storeFixturePool->get('tdd_store_2');
    
        $this->createCmsPage([
            'identifier' => 'tdd-custom-identifier',
            'title' -> 'TDD Custom Title',
            'is_active' => true,
            'stores' => [
                $storeFixture1->getId(),
                $storeFixture2->getId(),
            ],
            'data' => [
                PageInterface::CONTENT => 'TDD Custom Content',
                PageInterface::CONTENT_HEADING => 'TDD Custom Heading',
                PageInterface::META_TITLE => 'TDD Custom Meta Title',
                PageInterface::META_KEYWORDS => 'TDD Keyword List',
                PageInterface::META_DESCRIPTION => 'TDD Custom Meta Description',
                PageInterface::SORT_ORDER => 3,
                PageInterface::PAGE_LAYOUT => '2columns-right',
            ],
        ]);
        $cmsPageFixture = $this->cmsPageFixturePool->get('tdd_page');
        ...
    }

    public function testSomething_withMultipleCmsPages(): void
    {
        $this->createCmsPage();
        $cmsPageFixture1 = $this->cmsPageFixturePool->get('tdd_page');
        
        $this->createCmsPage([
            'key' => 'tdd_page_2',
            'identifier' => 'tdd-page-2',
        ]);
        $cmsPageFixture2 = $this->cmsPageFixturePool->get('tdd_page_2');
        ...
    }
```
