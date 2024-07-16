<?php


namespace App\DTO\Security;

use App\Traits\ObjectValuesValidation;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represents the data obtained from register form
 */
class RegisterDataDTO
{
    use ObjectValuesValidation;

    /**
     * @var string $email
     */
    #[NotBlank]
    private string $email;

    /**
     * @var string $password
     */
    #[NotBlank]
    private string $password;

    /**
     * @var string $username
     */
    #[NotBlank]
    private string $username;

    /**
     * @var string $confirmedPassword
     */
    #[NotBlank]
    private string $confirmedPassword;

    /**
     * @var string $firstname
     */
    #[NotBlank]
    private string $firstname;

    /**
     * @var string $lastname
     */
    #[NotBlank]
    private string $lastname;

    /**
     * @var string $zip
     */
    #[NotBlank]
    private string $zip;

    /**
     * @var string $street
     */
    #[NotBlank]
    private string $street;

    /**
     * @var string $city
     */
    #[NotBlank]
    private string $city;

    /**
     * @var string $homeNumber
     */
    #[NotBlank]
    private string $homeNumber;

    /**
     * @var string $country
     */
    #[NotBlank]
    private string $country;

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getConfirmedPassword(): string
    {
        return $this->confirmedPassword;
    }

    /**
     * @param string $confirmedPassword
     */
    public function setConfirmedPassword(string $confirmedPassword): void
    {
        $this->confirmedPassword = $confirmedPassword;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getHomeNumber(): string
    {
        return $this->homeNumber;
    }

    /**
     * @param string $homeNumber
     */
    public function setHomeNumber(string $homeNumber): void
    {
        $this->homeNumber = $homeNumber;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    /**
     * @param ExecutionContextInterface $context
     */
    public function validateValues(ExecutionContextInterface $context): void
    {
        /** @var RegisterDataDTO $object*/
        $object = $context->getObject();

        if($object->getPassword() !== $object->getConfirmedPassword()){
            $context->buildViolation("Password is not equal to confirmed password.")->atPath("password")->addViolation();
        }

        if( !filter_var($object->getEmail(), FILTER_VALIDATE_EMAIL) ){
            $context->buildViolation("E-mail is synthetically incorrect")->atPath("email")->addViolation();
        }

    }
}