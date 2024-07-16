<?php

namespace App\Service\Bundles\Providers\Example;

use Faker\Generator;
use Faker\Provider\Base;

/**
 * Example provider for faker, can be used as base for other providers
 * {@link https://fakerphp.github.io/#faker-internals-understanding-providers}
 */
class ExampleProvider extends Base
{
    /**
     * @param Generator $generator
     */
    public function __construct(Generator $generator)
    {
        parent::__construct($generator);
    }

    /**
     * Just an example method callable from within faker
     *
     * @return string
     */
    public function exampleString(): string
    {
        return "exampleString";
    }
}