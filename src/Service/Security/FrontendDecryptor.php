<?php

namespace App\Service\Security;

use App\Controller\Core\ConfigLoader;
use App\Exception\BaseException;
use App\Service\Logger\LoggerService;
use App\Service\Validation\ValidationService;
use Exception;

/**
 * All / most data sent from backend is encrypted for security reasons. This class uses the private key paired
 * with the public available on front and decrypts provided data.
 *
 * Solution taken from
 * @link https://titanwolf.org/Network/Articles/Article?AID=f0e4d125-2863-4ff6-b7ad-b195e6dd60c5#gsc.tab=0
 */
class FrontendDecryptor
{

    const HEADER_ENCRYPTED_WITH           = "encrypted-with";
    const HEADER_ENCRYPTED_WITH_VALUE_RSA = "RSA";

    /**
     * @var ConfigLoader $configLoader
     */
    private ConfigLoader $configLoader;

    /**
     * @var LoggerService $loggerService
     */
    private LoggerService $loggerService;

    /**
     * @var ValidationService $validationService
     */
    private ValidationService $validationService;

    /**
     * @param ConfigLoader $configLoader
     * @param LoggerService $loggerService
     * @param ValidationService $validationService
     */
    public function __construct(ConfigLoader $configLoader, LoggerService $loggerService, ValidationService $validationService)
    {
        $this->configLoader      = $configLoader;
        $this->loggerService     = $loggerService;
        $this->validationService = $validationService;
    }

    /**
     * Will decrypt the provided value and return proper scalar value
     *
     * @param string $encryptedValue
     * @return string|int|float|null
     * @throws BaseException
     * @throws Exception
     */
    public function decrypt(string $encryptedValue): string | int | float | null
    {
        if (!Base64Service::hasBase64InternalPrefix($encryptedValue)) {
            return $encryptedValue;
        }

        $realBase64String = Base64Service::getRealBas64String($encryptedValue);
        if (!$this->validationService->isBase64EncodedValue($realBase64String)) {
            $msg = "
                 Frontend provided string with: " . Base64Service::BASE_64_PREFIX
               . ", yet the backend validation service claims that it's not valid base64 string, it's almost impossible "
               . "to make proper logic to fully validate base64 string, logging the content just in-case for debugging, "
               . "possible issues in future";
            $this->loggerService->warning($msg);
        }

        if (!ctype_print($realBase64String)) {
            return false;
        }

        $base64decodedValue = base64_decode($realBase64String);
        $isDecrypted        = openssl_private_decrypt($base64decodedValue, $decryptedValue, $this->configLoader->getSecurityConfigLoader()->getFrontendEncryptionPrivateKey());
        if(!$isDecrypted){
            $message = "Could not decrypt the decoded value";
            $this->loggerService->critical($message, [
                "lastError" => openssl_error_string(),
            ]);
            throw new BaseException($message);
        }

        return $decryptedValue;
    }

    /**
     * Will traverse over the array and decrypt its values
     *
     * @param array $dataArray
     * @return array
     * @throws Exception
     */
    public function decryptArrayValues(array $dataArray): array
    {
        array_walk_recursive($dataArray, function(&$value) use ($dataArray) {

            // this must be first else decryption crashes on `null`
            if( empty($value) ){
                $value = "";
                return;
            }

            if( !is_scalar($value) ){
                $message = "Only scalar values can be decrypted, wrong array structure has been provided";
                $this->loggerService->critical($message, [
                    "array" => $dataArray
                ]);
                throw new Exception($message);
            }else{
                $value = $this->decrypt($value);
            }
        });

        return $dataArray;
    }
}