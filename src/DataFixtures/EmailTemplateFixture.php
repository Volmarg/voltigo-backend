<?php

namespace App\DataFixtures;

use App\Entity\Email\EmailTemplate;
use App\Entity\Security\User;
use App\Repository\Email\EmailTemplateRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Factory;
use Faker\Generator;

class EmailTemplateFixture extends Fixture implements DependentFixtureInterface
{
    private readonly Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    /**
     * @param ObjectManager $manager
     *
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        /** @var EmailTemplateRepository $repo */
        $repo = $manager->getRepository(EmailTemplate::class);
        $user = $manager->find(User::class, 1);
        if (is_null($user)) {
            throw new Exception("No user exists for id: 1");
        }

        /** @var EmailTemplate[] $predefinedTemplates */
        $predefinedTemplates = $repo->getAllCloneAble();
        foreach ($predefinedTemplates as $entity) {
            $clonedTemplate = clone $entity;
            $clonedTemplate->setUser($user);
            $clonedTemplate->setEmailTemplateName($this->faker->uuid());

            $manager->persist($clonedTemplate);
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            UserFixture::class
        ];
    }
}