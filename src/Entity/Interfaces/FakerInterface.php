<?php

namespace App\Entity\Interfaces;

/**
 * Marks the entity as the one which should support returning instance of self seeded by faker
 * @link https://github.com/FakerPHP/Faker
 * @link https://fakerphp.github.io/
 */
interface FakerInterface
{

    /**
     * Will return the entity seeded by faker
     *
     * @return $this
     */
    public function seedEntity(): self;

}