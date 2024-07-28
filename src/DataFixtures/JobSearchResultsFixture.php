<?php

namespace App\DataFixtures;

use App\Entity\Ecommerce\User\UserPointHistory;
use App\Entity\Job\JobSearchResult;
use App\Entity\Security\User;
use App\Enum\Job\SearchResult\SearchResultStatusEnum;
use App\Enum\Points\UserPointHistoryTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Factory;
use Faker\Generator;

class JobSearchResultsFixture extends Fixture implements DependentFixtureInterface
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
        $user = $manager->find(User::class, 1);
        if (is_null($user)) {
            throw new Exception("No user exists for id: 1");
        }

        $searcherExtractionCount = 22;

        // starting from 1 since it's used as an existing db id in job searcher
        for ($x = 1; $x <= $searcherExtractionCount; $x++) {
            $pointHistory = new UserPointHistory();
            $pointHistory->setUser($user);
            $pointHistory->setType(UserPointHistoryTypeEnum::USED->name);
            $pointHistory->setAmountBefore($this->faker->numberBetween(500,2000));
            $pointHistory->setAmountNow($pointHistory->getAmountBefore() - $this->faker->numberBetween(50,100));
            $pointHistory->setInformation($this->faker->sentence(4));

            $jobSearch = new JobSearchResult();
            $jobSearch->setKeywords([$this->faker->word]);
            $jobSearch->setLocationName($this->faker->city);
            $jobSearch->setTargetAreas([$this->faker->countryISOAlpha3]);
            $jobSearch->setMaxDistance($this->faker->numberBetween(0, 50));
            $jobSearch->setUser($user);
            $jobSearch->setUserPointHistory($pointHistory);

            // keep in sync with job searcher fixtures
            match ($x){
                1 => $jobSearch->setStatus(SearchResultStatusEnum::ERROR->name),
                2 => $jobSearch->setStatus(SearchResultStatusEnum::PENDING->name),
                default => $jobSearch->setStatus(SearchResultStatusEnum::DONE->name),
            };

            if ($jobSearch->isDone()) {
                $jobSearch->setExternalExtractionId($x);
            }

            $manager->persist($jobSearch);
            $manager->persist($pointHistory);
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