<?php

namespace App\Form\Email;

use App\Action\Email\EmailTemplateAction;
use App\Entity\Email\EmailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form related to submitting changes (or creating new entity) for {@see EmailTemplate}
 * Also see {@see EmailTemplateAction::save()}
 */
class EmailTemplateForm extends AbstractType
{
    const FIELD_NAME_ID                  = "id";
    const FIELD_NAME_BODY                = "body";
    const FIELD_NAME_SUBJECT             = "subject";
    const FIELD_NAME_EMAIL_TEMPLATE_NAME = "emailTemplateName";
    const FIELD_NAME_EMAIL_EXAMPLE_HTML  = "exampleHtml";

    /**
     * @param FormBuilderInterface<FormBuilder> $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(self::FIELD_NAME_ID, NumberType::class)
            ->add(self::FIELD_NAME_SUBJECT, TextType::class, [
                'empty_data' => ''
            ])
            ->add(self::FIELD_NAME_EMAIL_TEMPLATE_NAME, TextType::class)
            ->add(self::FIELD_NAME_BODY, TextType::class)
            ->add(self::FIELD_NAME_EMAIL_EXAMPLE_HTML, TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => EmailTemplate::class,
        ]);
    }

}
