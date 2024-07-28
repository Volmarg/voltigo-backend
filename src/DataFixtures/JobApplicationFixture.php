<?php

namespace App\DataFixtures;

use App\Entity\Email\Email;
use App\Entity\Job\JobApplication;
use App\Entity\Job\JobOfferInformation;
use App\Entity\Security\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker\Factory;
use Faker\Generator;

class JobApplicationFixture extends Fixture implements DependentFixtureInterface
{
    private const SUPPORTED_DOMAINS = [
        'xing.com',
        'gowork.pl',
        'kimeta.de',
        'jobbsafari.se',
        "monster.de",
        "tideri.de",
        "es.indeed.com",
        "pracuj.pl",
        "anzeigen.jobsintown.de",
        "se.talent.com",
        "jobb.blocket.se",
        "thehub.io",
        "monster.fr"
    ];

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

        // starting from 1 since it's used as an existing db id in job searcher
        for ($x = 1; $x <= $this->faker->numberBetween(20, 50); $x++) {
            $email = new Email();
            $email->setStatus(Email::KEY_STATUS_PENDING);
            $email->setAnonymized(true);
            $email->setBody('');
            $email->setSubject('');
            $email->setRecipients([$this->faker->companyEmail]);

            $url  = $this->faker->url();
            $host = parse_url($url, PHP_URL_HOST);
            $url  = str_replace($host, self::SUPPORTED_DOMAINS[array_rand(self::SUPPORTED_DOMAINS)], $url);

            $jobInformation = new JobOfferInformation();
            $jobInformation->setCompanyName($this->faker->company);
            $jobInformation->setOriginalUrl($url);
            $jobInformation->setExternalId($this->faker->numberBetween(1,9999));
            $jobInformation->setTitle($this->faker->jobTitle);

            $application = new JobApplication();
            $application->setStatus($this->faker->boolean ? JobApplication::STATUS_EMAIL_SENT : JobApplication::STATUS_EMAIL_PENDING);
            $application->setUser($user);
            $application->setEmail($email);

            $application->setJobOffer($jobInformation);
            $email->setJobApplication($application);

            $manager->persist($email);
            $manager->persist($jobInformation);
            $manager->persist($application);
            $manager->flush();
        }
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