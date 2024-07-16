<?php

namespace App\Form\Job;

use App\DTO\Internal\Job\JobSearchDTO;
use App\Form\Type\JsonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobSearchForm extends AbstractType
{
    const FIELD_JOB_SEARCH_KEYWORDS = "jobSearchKeywords";
    const FIELD_TARGET_AREA         = "targetArea";
    const FIELD_LOCATION_NAME       = "locationName";
    const FIELD_MAX_DISTANCE        = "maxDistance";
    const FIELD_OFFERS_LIMIT        = "offersLimit";

    /**
     * @param FormBuilderInterface<FormBuilder> $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(self::FIELD_JOB_SEARCH_KEYWORDS, JsonType::class)
            ->add(self::FIELD_TARGET_AREA, TextType::class)
            ->add(self::FIELD_LOCATION_NAME, TextType::class)
            ->add(self::FIELD_OFFERS_LIMIT, NumberType::class)
            ->add(self::FIELD_MAX_DISTANCE, NumberType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => JobSearchDTO::class,
        ]);
    }

}
