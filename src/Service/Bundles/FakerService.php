<?php

namespace App\Service\Bundles;

use App\Entity\Interfaces\FakerInterface;
use App\Service\Bundles\Providers\Example\ExampleProvider;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;

/**
 * Handles providing faker with provided data,
 * all methods should stay static to prevent necessity of creating instances inside of entities
 * {@see FakerInterface}
 */
class FakerService
{

    public static function getAllCustomProviders(): array
    {
        $faker = FakerFactory::create();

        return [
            new ExampleProvider($faker),
        ];
    }

    /**
     * Will return faker instance
     * It's necessary to provide all the provider classes in here
     * otherwise IDE won't see the methods from providers
     *
     * @return FakerGenerator | ExampleProvider
     */
    public static function getFakerInstance(): FakerGenerator | ExampleProvider
    {
        $faker = FakerFactory::create();
        $faker = self::addProviders($faker);
        return $faker;
    }

    /**
     * Will handle adding custom providers to the faker
     *
     * @param FakerGenerator $faker
     * @return FakerGenerator
     */
    private static function addProviders(FakerGenerator $faker): FakerGenerator
    {
        foreach( self::getAllCustomProviders() as $provider ){
            $faker->addProvider($provider);
        }

        return $faker;
    }
}