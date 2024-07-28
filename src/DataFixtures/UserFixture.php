<?php

namespace App\DataFixtures;

use App\Entity\Address\Address;
use App\Entity\Ecommerce\Account\Account;
use App\Entity\Ecommerce\Account\AccountType;
use App\Entity\Security\User;
use App\Enum\Address\CountryEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private readonly Generator $faker;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @param ObjectManager $manager
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $accountType = $manager->getRepository(AccountType::class)->findOneBy(['name' => AccountType::TYPE_FREE]);
        if (is_null($accountType)) {
            throw new Exception("No account type found for name: " . AccountType::TYPE_FREE);
        }

        $account = new Account();
        $account->setActive(true);
        $account->setType($accountType);

        // the persist + flush here is a must
        $manager->persist($account);
        $manager->flush();

        $user = new User();
        $user->setEmail("demo-user@voltigo.pl");
        $user->setPassword($this->passwordHasher->hashPassword($user, "demo"));
        $user->setUsername("Volmarg");

        $user->setLastname($this->faker->lastName);
        $user->setFirstname($this->faker->firstName);
        $user->setActive(true);
        $user->setAccount($account);
        $user->setRoles([User::ROLE_USER]);
        $user->setPointsAmount($this->faker->numberBetween(300, 900));

        // the persist + flush here is a must
        $manager->persist($user);
        $manager->flush();

        $address = new Address();
        $address->setCountry(CountryEnum::PL);
        $address->setCity($this->faker->city);
        $address->setZip($this->faker->postcode);
        $address->setHomeNumber($this->faker->numberBetween(1,30));
        $address->setStreet($this->faker->streetName);

        $account->setUser($user);

        $user->setAddress($address);

        $manager->persist($account);
        $manager->persist($address);
        $manager->persist($user);

        $manager->flush();
    }
}