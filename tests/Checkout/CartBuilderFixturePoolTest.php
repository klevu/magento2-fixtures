<?php

declare(strict_types=1);

namespace TddWizard\Fixtures\Checkout;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CartBuilderFixturePoolTest extends TestCase
{
    private CartFixturePool $cartFixtures;
    private CartRepositoryInterface $cartRepository;
    private ?ObjectManagerInterface $objectManager = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->cartFixtures = new CartFixturePool();
        $this->cartRepository = $this->objectManager->create(type: CartRepositoryInterface::class);
    }

    public function testLastCartFixtureReturnedByDefault(): void
    {
        $firstCart = $this->createCart();
        $lastCart = $this->createCart();
        $this->cartFixtures->add(cart: $firstCart);
        $this->cartFixtures->add(cart: $lastCart);
        $cartFixture = $this->cartFixtures->get();
        $this->assertEquals(expected: $lastCart->getId(), actual: $cartFixture->getCartId());
    }

    public function testExceptionThrownWhenAccessingEmptyCartPool(): void
    {
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->cartFixtures->get();
    }

    public function testCartFixtureReturnedByKey(): void
    {
        $firstCart = $this->createCart();
        $lastCart = $this->createCart();
        $this->cartFixtures->add(cart: $firstCart, key: 'first');
        $this->cartFixtures->add(cart: $lastCart, key: 'last');
        $cartFixture = $this->cartFixtures->get(key: 'first');
        $this->assertEquals(expected: $firstCart->getId(), actual: $cartFixture->getCartId());
    }

    public function testCartFixtureReturnedByNumericKey(): void
    {
        $firstCart = $this->createCart();
        $lastCart = $this->createCart();
        $this->cartFixtures->add(cart: $firstCart);
        $this->cartFixtures->add(cart: $lastCart);
        $cartFixture = $this->cartFixtures->get(key: 0);
        $this->assertEquals(expected: $firstCart->getId(), actual: $cartFixture->getCartId());
    }

    public function testExceptionThrownWhenAccessingNonexistingKey(): void
    {
        $cart = $this->createCart();
        $this->cartFixtures->add(cart: $cart, key: 'foo');
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->cartFixtures->get(key: 'bar');
    }

    /**
     * @throws \Exception
     */
    public function testRollbackRemovesCartsFromPool(): void
    {
        $cart = $this->createCartInDb();
        $this->cartFixtures->add(cart: $cart);
        $this->cartFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->cartFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackWorksWithKeys(): void
    {
        $cart = $this->createCartInDb();
        $this->cartFixtures->add(cart: $cart, key: 'key');
        $this->cartFixtures->rollback();
        $this->expectException(exception: \OutOfBoundsException::class);
        $this->cartFixtures->get();
    }

    /**
     * @throws \Exception
     */
    public function testRollbackDeletesCartsFromDb(): void
    {
        $cart = $this->createCartInDb();
        $this->cartFixtures->add(cart: $cart);
        $this->cartFixtures->rollback();
        $this->expectException(exception: NoSuchEntityException::class);
        $this->cartRepository->get(cartId: $cart->getId());
    }

    /**
     * Creates dummy cart object
     */
    private function createCart(): CartInterface
    {
        static $nextId = 1;
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(type: CartInterface::class);
        $cart->setId($nextId++);

        return $cart;
    }

    /**
     * Creates cart using builder
     *
     * @throws \Exception
     */
    private function createCartInDb(): CartInterface
    {
        return CartBuilder::forCurrentSession()->build();
    }
}
