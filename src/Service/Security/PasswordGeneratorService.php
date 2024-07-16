<?php

namespace App\Service\Security;

use Hackzilla\PasswordGenerator\Exception\CharactersNotFoundException;
use Hackzilla\PasswordGenerator\Exception\ImpossibleMinMaxLimitsException;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Hackzilla\PasswordGenerator\Generator\RequirementPasswordGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles password generating logic
 * @link https://github.com/hackzilla/password-generator
 */
class PasswordGeneratorService
{
    private const PASSWORD_MIN_LENGTH = 8;
    private const MIN_UPPER_CASE = 1;
    private const MIN_LOWER_CASE = 1;
    private const MIN_NUMBER = 1;
    private const MIN_SYMBOLS = 1;

    /**
     * @var RequirementPasswordGenerator $passwordGenerator
     */
    private RequirementPasswordGenerator $passwordGenerator;

    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
        $this->passwordGenerator = new RequirementPasswordGenerator();
        $this->passwordGenerator->setLength(self::PASSWORD_MIN_LENGTH)
                                ->setOptionValue(ComputerPasswordGenerator::OPTION_UPPER_CASE, true)
                                ->setOptionValue(ComputerPasswordGenerator::OPTION_LOWER_CASE, true)
                                ->setOptionValue(ComputerPasswordGenerator::OPTION_NUMBERS, true)
                                ->setOptionValue(ComputerPasswordGenerator::OPTION_SYMBOLS, true)
                                ->setMinimumCount(ComputerPasswordGenerator::OPTION_UPPER_CASE, self::MIN_UPPER_CASE)
                                ->setMinimumCount(ComputerPasswordGenerator::OPTION_LOWER_CASE, self::MIN_LOWER_CASE)
                                ->setMinimumCount(ComputerPasswordGenerator::OPTION_NUMBERS, self::MIN_NUMBER)
                                ->setMinimumCount(ComputerPasswordGenerator::OPTION_SYMBOLS, self::MIN_SYMBOLS);
    }

    /**
     * Will generate custom password
     *
     * @return string
     * @throws CharactersNotFoundException
     * @throws ImpossibleMinMaxLimitsException
     */
    public function generateCustomPassword(): string
    {
        return $this->passwordGenerator->generatePassword();
    }

    /**
     * Check if given password is valid for set validator configurations
     *
     * @param string $password
     *
     * @return bool
     */
    public function validatePassword(string $password): bool
    {
        if (mb_strlen($password) < self::PASSWORD_MIN_LENGTH) {
            return false;
        }

        return $this->passwordGenerator->validatePassword($password);
    }

    /**
     * Returns constraints as array
     *
     * @return array
     */
    public function getConstraintTexts(): array
    {
        return [
            $this->translator->trans('security.password.constraint.minLength',    ["%len%"  => self::PASSWORD_MIN_LENGTH]),
            $this->translator->trans('security.password.constraint.minUpperCase', ["%count%" => self::MIN_UPPER_CASE]),
            $this->translator->trans('security.password.constraint.minLowerCase', ["%count%" => self::MIN_LOWER_CASE]),
            $this->translator->trans('security.password.constraint.minNumbers',   ["%count%" => self::MIN_NUMBER]),
            $this->translator->trans('security.password.constraint.minSymbols',   ["%count%" => self::MIN_SYMBOLS]),
        ];
    }
}