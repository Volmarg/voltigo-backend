<?php

namespace App\Service\Validation;


use App\DTO\Validation\ValidationResultDTO;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationService
{
    const STANDARD_VIOLATIONS      = "standardViolations";
    const OBJECT_VALUES_VIOLATIONS = "objectValuesViolations";
    private const BASE_64_REGEXP   = "/^[a-zA-Z0-9\/\r\n+]*={0,2}$/";

    /**
     * @var ValidatorInterface $validator
     */
    private ValidatorInterface $validator;

    /**
     * @var ValidatorInterface $objectValuesValidator
     */
    private ValidatorInterface $objectValuesValidator;

    /**
     * ValidationService constructor.
     *
     * @param ValidatorInterface $validator
     * @param LoggerInterface    $logger
     */
    public function __construct(
        ValidatorInterface               $validator,
        private readonly LoggerInterface $logger
    )
    {
        $this->validator     = $validator;

        $this->objectValuesValidator = Validation::createValidatorBuilder()
            ->addMethodMapping('objectValuesValidator')
            ->getValidator();
    }

    /**
     * Validates the object and returns the array of violations
     *
     * @param object $object
     * @return ValidationResultDTO
     */
    public function validateAndReturnArrayOfInvalidFieldsWithMessages(object $object): ValidationResultDTO
    {
        $validationResultDto    = new ValidationResultDTO();

        $standardViolations     = $this->getViolationsWithMessagesForValidator($object, $this->validator);
        $objectValuesViolations = $this->getViolationsWithMessagesForValidator($object, $this->objectValuesValidator);
        $allViolations          = array_merge($standardViolations, $objectValuesViolations);

        $violationsForViolationType = [
            self::STANDARD_VIOLATIONS      => $standardViolations,
            self::OBJECT_VALUES_VIOLATIONS => $objectValuesViolations,
        ];

        $validationResultDto->setSuccess(true);
        if( !empty($allViolations) ){
            $validationResultDto->setSuccess(false);
            $validationResultDto->setViolationsWithMessages($violationsForViolationType);
        }

        return $validationResultDto;
    }

    /**
     * Will validate the provided json string and return bool value:
     * - true if everything is ok
     * - false if something went wrong
     *
     * @param string $json
     * @return bool
     */
    public function validateJson(string $json): bool
    {
        json_decode($json);
        if( JSON_ERROR_NONE !== json_last_error() ){
            $this->logger->critical("Provided json is not valid", [
                "json"             => $json,
                'jsonLastErrorMsg' => json_last_error_msg(),
                "trace"            => (new Exception())->getTraceAsString(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Check if value is base64 encoded value
     * @link https://stackoverflow.com/questions/4278106/how-to-check-if-a-string-is-base64-valid-in-php
     *       known problem that even string such as "test123" return something when calling "base64_decode" (as if it was encoded),
     *       that's an undesired result if the provided string is not base64 encoded.
     *
     *       With this solution the decoded value is encoded back and then compared if the new encoded string is equal
     *       to the same that value provided in function - WHICH SHOULD BE - if not then value is not base64 encoded
     *
     * Known issues:
     * - if url will contain base64 valid characters then whole link is considered base64, but that's not valid from current
     *   use case, therefore all the links should contain non base64 characters like simple `-`
     *
     * @param string $value
     * @return bool
     * @throws Exception
     */
    public function isBase64EncodedValue(string $value): bool
    {
        $base64decodedValue = base64_decode($value);
        if (base64_encode($base64decodedValue) !== $value) {
            return false;
        }

        /**
         * Additional check for case such as:
         * - "jjjjjjjj" (it claims that it's base64 encoded value)
         */
        if (!preg_match(self::BASE_64_REGEXP, $value)) {
            return false;
        }

        /**
        * It sometimes happens that base64 is incorrectly recognised, and it decodes the normal strings
         * the result of such decode is usually binary content.
         *
         * The problem here is that base64 has some range of supported characters and
         * even company names etc. can be recognised as properly formatted base64
         *
         * One might think that images are decoded from base64 to binary, but the upload skips the encryption at all
         * so there is no risk for that
        */
        if (!ctype_print($value)) {
            return false;
        }

        return true;
    }

    /**
     * Will return violations with messages for provided validator
     *
     * @param object $object
     * @param ValidatorInterface $validator
     * @return array
     */
    private function getViolationsWithMessagesForValidator(object $object, ValidatorInterface $validator): array
    {
        $violations             = $validator->validate($object);
        $violationsWithMessages = [];

        /**@var $constraintViolation ConstraintViolation*/
        foreach($violations as $constraintViolation){
            $violationsWithMessages[$constraintViolation->getPropertyPath()] = $constraintViolation->getMessage();
        }

        return $violationsWithMessages;
    }

}