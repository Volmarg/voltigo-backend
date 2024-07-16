<?php

namespace App\Service\Snapshot;

use App\Entity\Address\Address;
use App\Entity\Ecommerce\Product\PointProduct;
use App\Entity\Ecommerce\Product\Product;
use App\Entity\Ecommerce\Snapshot\AddressSnapshot;
use App\Entity\Ecommerce\Snapshot\Product\OrderPointProductSnapshot;
use App\Entity\Ecommerce\Snapshot\Product\OrderProductSnapshot;
use App\Entity\Ecommerce\Snapshot\UserDataSnapshot;
use App\Entity\Security\User;

class SnapshotBuilderService
{

    /**
     * @param Product $product
     * @param float   $taxPercentage
     * @param int     $quantity
     * @param float   $priceWithTax
     *
     * @return OrderProductSnapshot
     */
    public function buildOrderProductSnapshot(
        Product $product,
        float   $taxPercentage,
        int     $quantity,
        float   $priceWithTax
    ): OrderProductSnapshot
    {
        $orderProductSnapshot = new OrderProductSnapshot();
        if ($product instanceof PointProduct) {
            $orderProductSnapshot = new OrderPointProductSnapshot();
            $orderProductSnapshot->setAmount($product->getAmount());
        }

        $orderProductSnapshot->setTaxPercentage($taxPercentage);
        $orderProductSnapshot->setProduct($product);
        $orderProductSnapshot->setName($product->getName());
        $orderProductSnapshot->setPrice($product->getPrice());
        $orderProductSnapshot->setPriceWithTax($priceWithTax);
        $orderProductSnapshot->setQuantity($quantity);
        $orderProductSnapshot->setBaseCurrencyCode($product->getBaseCurrencyCode());

        return $orderProductSnapshot;
    }

    /**
     * @param User $user
     *
     * @return UserDataSnapshot
     */
    public function buildUserSnapshot(User $user): UserDataSnapshot
    {
        $userSnapshot = new UserDataSnapshot();
        $userSnapshot->setEmail($user->getEmail());
        $userSnapshot->setAccountTypeName($user->getAccount()->getType()->getName());
        $userSnapshot->setRoles($user->getRoles());
        $userSnapshot->setUsername($user->getUsername());
        $userSnapshot->setFirstname($user->getFirstname());
        $userSnapshot->setLastname($user->getLastname());

        return $userSnapshot;
    }

    /**
     * @param Address $address
     *
     * @return AddressSnapshot
     */
    public function buildAddressSnapshot(Address $address): AddressSnapshot
    {
        $addressSnapshot = new AddressSnapshot();
        $addressSnapshot->setCountry($address->getCountry());
        $addressSnapshot->setZip($address->getZip());
        $addressSnapshot->setCity($address->getCity());
        $addressSnapshot->setHomeNumber($address->getHomeNumber());
        $addressSnapshot->setStreet($address->getStreet());

        return $addressSnapshot;
    }
}