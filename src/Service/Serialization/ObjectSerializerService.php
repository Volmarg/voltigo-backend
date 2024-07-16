<?php

namespace App\Service\Serialization;

use App\Enum\Service\Serialization\SerializerType;
use App\Service\Validation\ValidationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles serialization of data:
 * - objects
 *
 * Into:
 * - json,
 * - array
 */
class ObjectSerializerService
{

    public function __construct(
        private ValidationService   $validationService,
        private SerializerInterface $serializer
    ){}

    /**
     * Will attempt to serialize object into json
     *
     * @param object $object
     * @param SerializerType $serializerType
     * @param array $groups
     *
     * @return string
     */
    public function toJson(object $object, SerializerType $serializerType = SerializerType::STANDARD, array $groups = []): string
    {
        return $this->decideSerializer($serializerType)->serialize($object, "json", [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
            AbstractNormalizer::GROUPS => $groups,
        ]);
    }

    /**
     * Will attempt to serialize object into array
     *
     * @param object         $object
     * @param SerializerType $serializerType
     * @param array $groups
     *
     * @return Array<string>
     */
    public function toArray(object $object, SerializerType $serializerType = SerializerType::STANDARD, array $groups = []): array
    {
        $json  = $this->toJson($object, $serializerType, $groups);
        $array = json_decode($json, true);

        return $array;
    }

    /**
     * Will deserialize provided json into target class
     *
     * @param string         $json
     * @param string         $targetClass
     * @param SerializerType $serializerType
     *
     * @return object
     * @throws Exception
     */
    public function fromJson(string $json, string $targetClass, SerializerType $serializerType = SerializerType::STANDARD): object
    {
        $this->validationService->validateJson($json);
        if (!class_exists($targetClass)) {
            throw new Exception("Tried to deserialize json to non existing class: {$targetClass}");
        }

        $object = $this->decideSerializer($serializerType)->deserialize($json, $targetClass, "json");
        return $object;
    }

    /**
     * Will decide which serializer is going to be used
     *
     * @param SerializerType $serializerType
     *
     * @return SerializerInterface
     */
    private function decideSerializer(SerializerType $serializerType): SerializerInterface
    {
        if ($serializerType->name === SerializerType::STANDARD->name) {
            return $this->serializer;
        }

        return $this->getPreconfiguredSerializer();
    }

    /**
     * Provides manually configured serializer:
     * - in some cases it's better because for example it prevents the serializer crashing when expected and provided type mismatch,
     * - on the other hand it tends to crash when serializing entities returned as {@see ArrayCollection} or {@see PersistentCollection}
     *
     * @return SerializerInterface
     */
    private function getPreconfiguredSerializer(): SerializerInterface
    {
        $normalizer = new ObjectNormalizer(
            null,
            null,
            null,
            new ReflectionExtractor(),
            null,
            null,
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true, // let php resolve the type via auto cas instead of serializer checking strict type match
            ]
        );

        $enumNormalizer = new BackedEnumNormalizer();
        $serializer     = new Serializer([$enumNormalizer, $normalizer], [new JsonEncoder()]);

        return $serializer;
    }
}