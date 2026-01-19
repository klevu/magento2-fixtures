<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Customer;

use Faker\Factory as FakerFactory;
use InvalidArgumentException;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Directory\Model\Region;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Builder to be used by fixtures
 */
class AddressBuilder
{
    public function __construct(
        private readonly AddressRepositoryInterface $addressRepository,
        private AddressInterface $address,
    ) {
    }

    public function __clone()
    {
        $this->address = clone $this->address;
    }

    public static function anAddress(
        string $locale = 'de_DE',
    ): AddressBuilder {
        $objectManager = Bootstrap::getObjectManager();

        $address = self::prepareFakeAddress(objectManager: $objectManager, locale: $locale);

        return new self(
            addressRepository: $objectManager->create(type: AddressRepositoryInterface::class),
            address: $address,
        );
    }

    public static function aCompanyAddress(
        string $locale = 'de_DE',
        string $vatId = '1234567890',
    ): AddressBuilder {
        $objectManager = Bootstrap::getObjectManager();

        $address = self::prepareFakeAddress(objectManager: $objectManager, locale: $locale);
        $address->setVatId(vatId: $vatId);

        return new self(
            addressRepository: $objectManager->create(type: AddressRepositoryInterface::class),
            address: $address,
        );
    }

    public function asDefaultShipping(): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setIsDefaultShipping(isDefaultShipping: true);

        return $builder;
    }

    public function isDefaultShipping(): ?bool
    {
        $builder = clone $this;

        return $builder->address->isDefaultShipping();
    }

    public function asDefaultBilling(): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setIsDefaultBilling(isDefaultBilling: true);

        return $builder;
    }

    public function isDefaultBilling(): ?bool
    {
        $builder = clone $this;

        return $builder->address->isDefaultBilling();
    }

    public function withCustomerId(int $customerId): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setCustomerId(customerId: $customerId);

        return $builder;
    }

    public function withPrefix(string $prefix): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setPrefix(prefix: $prefix);

        return $builder;
    }

    public function withFirstname(string $firstname): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setFirstname(firstName: $firstname);

        return $builder;
    }

    public function withMiddlename(string $middlename): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setMiddlename(middleName: $middlename);

        return $builder;
    }

    public function withLastname(string $lastname): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setLastname(lastName: $lastname);

        return $builder;
    }

    public function withSuffix(string $suffix): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setSuffix(suffix: $suffix);

        return $builder;
    }

    public function withStreet(string $street): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setStreet(street: (array)$street);

        return $builder;
    }

    public function withCompany(string $company): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setCompany(company: $company);

        return $builder;
    }

    public function withTelephone(string $telephone): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setTelephone(telephone: $telephone);

        return $builder;
    }

    public function withPostcode(string $postcode): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setPostcode(postcode: $postcode);

        return $builder;
    }

    public function withCity(string $city): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setCity(city: $city);

        return $builder;
    }

    public function withCountryId(string $countryId): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setCountryId(countryId: $countryId);

        return $builder;
    }

    public function withRegionId(int $regionId): AddressBuilder
    {
        $builder = clone $this;
        $builder->address->setRegionId(regionId: $regionId);

        return $builder;
    }

    /**
     * @param mixed[] $values
     */
    public function withCustomAttributes(array $values): AddressBuilder
    {
        $builder = clone $this;
        foreach ($values as $code => $value) {
            $builder->address->setCustomAttribute(attributeCode: $code, attributeValue: $value);
        }

        return $builder;
    }

    /**
     * @throws LocalizedException
     */
    public function build(): AddressInterface
    {
        return $this->addressRepository->save(address: $this->address);
    }

    public function buildWithoutSave(): AddressInterface
    {
        return clone $this->address;
    }

    private static function prepareFakeAddress(
        ObjectManagerInterface $objectManager,
        string $locale = 'de_DE',
    ): AddressInterface {
        $faker = FakerFactory::create(locale: $locale);
        $countryCode = substr(string: $locale, offset: -2);

        try {
            $regionName = $faker->province();
        } catch (InvalidArgumentException) {
            $regionName = $faker->state();
        }

        $region = $objectManager->create(type: Region::class);
        $region = $region->loadByName(name: $regionName, countryId: $countryCode);

        /** @var AddressInterface $address */
        $address = $objectManager->create(type: AddressInterface::class);
        $phoneNumberArray = explode(separator: 'x', string: $faker->phoneNumber());
        $address->setTelephone(
            telephone: trim(string: str_replace(search: '.', replace: '-', subject: $phoneNumberArray[0])),
        );
        $address->setPostcode(postcode: $faker->postcode());
        $address->setCountryId(countryId: $countryCode);
        $address->setCity(
            city: str_replace(search: ['/', '(', ')'], replace: ['-', '-', ''], subject: $faker->city()),
        );
        $address->setCompany(company: $faker->company());
        $address->setStreet(street: [$faker->streetAddress()]);
        $address->setLastname(lastName: $faker->lastName());
        $address->setFirstname(firstName: $faker->firstName());
        $address->setRegionId(regionId: $region->getId());

        return $address;
    }
}
