# Object Instantiation Tests

A suite of tests to check if the object under test

* can be instantiated.
* has the correct interface.
* has its preference defined correctly in `di.xml` and is not overwritten.

## Test Class Can Be Instantiated

The test `testFqcnResolvesToExpectedImplementation` will run as long as `implementationFqcn` and `objectManager` are
defined

```php
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Traits\InstantiationTests\TestObjectInstantiationTrait;
use Vendor\Module\Model\ClassToBeTested;

class SomeTest extends TestCase
{
    use TestObjectInstantiationTrait;
    
    private ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {        
        $this->objectManager = Bootstrap::getObjectManager();
        $this->implementationFqcn = ClassToBeTested::class;
    }
    
    public function testSomethingElse(): void
    {
        // create the class defined by $this->implementationFqcn
        $classUnderTest = $this->instantiateTestObject();
        $result = $classUnderTest->execute();
        ...
    }
}
```

### With Constructor Arguments

The class constructor may require arguments to be passed to it. We can define those in the `setUp` method or when
instantiating the class in a test.

```php
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Traits\InstantiationTests\TestObjectInstantiationTrait;
use Vendor\Module\Model\ClassToBeTested;

class SomeTest extends TestCase
{
    use TestObjectInstantiationTrait;
    
    private ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {        
        $this->objectManager = Bootstrap::getObjectManager();
        $this->implementationFqcn = ClassToBeTested::class;
        $this->constructorArgumentDefaults = [
            'argument1' => 'value1',
            'argument2' => 'value2',
        ];
    }
    
    public function testSomethingElse(): void
    {
        // create the class defined by $this->implementationFqcn (with overridden arguments if required)
        $classUnderTest = $this->instantiateTestObject(
            arguments: [
                'argument1' => 'value3',
                'argument2' => 'value4',
            ],
        );
        $result = $classUnderTest->execute();
        ...
    }
}
```

### Virtual Types

Test that the class can be created when using a Virtual Type

```php
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Traits\InstantiationTests\TestObjectInstantiationTrait;
use Vendor\Module\Model\ClassToBeTested;
use Vendor\Module\Model\ClassToBeTested\VirtualType;

class SomeTest extends TestCase
{
    use TestObjectInstantiationTrait;
    
    private ObjectManagerInterface $objectManager;
    
    protected function setUp(): void
    {        
        $this->objectManager = Bootstrap::getObjectManager();
        // implementationFqcn is the virtual type (note: when not using virtual types this is the actual class)
        $this->implementationFqcn = VirtualType::class;
        // implementationForVirtualType is the original class the virtual type is based on
        $this->implementationForVirtualType = ClassToBeTested::class;
    }
    
    public function testSomethingElse(): void
    {
        // create the class defined by $this->implementationFqcn
        $classUnderTest = $this->instantiateTestObject();
        $result = $classUnderTest->execute();
        ...
    }
}
```

---

## Test Class Has The Correct Interface

The test `testImplementsExpectedInterface` will run as long as `interfaceFqcn`, `implementationFqcn` and `objectManager`
are defined.  
This Trait also requires `\TddWizard\Fixtures\Traits\InstantiationTests\TestObjectInstantiationTrait` to be included.

```php
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Traits\InstantiationTests\TestObjectInstantiationTrait;
use TddWizard\Fixtures\Traits\InstantiationTests\TestImplementsInterfaceTrait;
use Vendor\Module\Model\ClassToBeTested;
use Vendor\Module\Model\ClassToBeTestedInterface;

class SomeTest extends TestCase
{
    use TestImplementsInterfaceTrait;
    use TestObjectInstantiationTrait;
    
    private ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {        
        $this->objectManager = Bootstrap::getObjectManager();
        $this->implementationFqcn = ClassToBeTested::class;
        $this->interfaceFqcn = ClassToBeTestedInterface::class;
    }
}
```

---

## Test DI Preference Is Correctly Configured

Ensure that the preference has been added to `di.xml` correctly and the correct class is returned.  
The test `testInterfacePreferenceResolvesToExpectedImplementation` will run as long as `interfaceFqcn`,
`implementationFqcn` and `objectManager` are defined.
This Trait also requires `\TddWizard\Fixtures\Traits\InstantiationTests\TestObjectInstantiationTrait` to be included.

```php
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Traits\InstantiationTests\TestObjectInstantiationTrait;
use TddWizard\Fixtures\Traits\InstantiationTests\TestInterfacePreferenceTrait;
use Vendor\Module\Model\ClassToBeTested;
use Vendor\Module\Model\ClassToBeTestedInterface;

class SomeTest extends TestCase
{
    use TestInterfacePreferenceTrait;
    use TestObjectInstantiationTrait;
    
    private ObjectManagerInterface $objectManager;
    
    protected function setUp(): void
    {        
        $this->objectManager = Bootstrap::getObjectManager();
        $this->implementationFqcn = ClassToBeTested::class;
        $this->interfaceFqcn = ClassToBeTestedInterface::class;
    }
}
```
