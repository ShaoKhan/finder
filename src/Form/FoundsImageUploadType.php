<?php

namespace App\Form;

use App\Entity\FoundsImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class FoundsImageUploadType extends AbstractType
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder
            ->add('file', FileType::class, [
                'label' => $this->translator->trans('form.choose_photo', [], 'founds'),
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('name', TextType::class, [
                'label' => $this->translator->trans('form.name', [], 'founds'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('note', TextareaType::class, [
                'label' => $this->translator->trans('form.note', [], 'founds'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('isPublic', CheckboxType::class, [
                'label' => $this->translator->trans('form.public', [], 'founds'),
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('submit', SubmitType::class,
            [
                'label' => $this->translator->trans('form.save', [], 'founds'),
                'attr' => [
                    'class' => 'btn btn-primary mt-2'
                ]
            ]
            )

        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => FoundsImage::class,
                               ]);
    }
}
