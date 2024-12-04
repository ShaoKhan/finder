<?php

namespace App\Form;

use App\Entity\FoundsImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;


class FoundsImageUploadType extends AbstractType
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mimeTypeMessage = $this->translator->trans('form.imagesOnly', [], 'founds');

        $builder
            ->add('files', FileType::class, [
                'label'       => $this->translator->trans('form.choose_photos', [], 'founds'),
                'mapped'      => FALSE,
                'required'    => TRUE,
                'multiple'    => TRUE,
                'constraints' => [
                    new All([
                                'constraints' => [
                                    new File([
                                                 'maxSize'          => '10M',
                                                 'mimeTypes'        => [
                                                     'image/jpeg',
                                                     'image/png',
                                                 ],
                                                 'mimeTypesMessage' => $mimeTypeMessage,
                                             ]),
                                ],
                            ]),
                ],
                'attr'        => [
                    'accept' => 'image/*',
                    'class'  => 'form-control',
                ],
            ])
            ->add('name', TextType::class, [
                'label'    => $this->translator->trans('form.name', [], 'founds'),
                'required' => FALSE,
                'attr'     => [
                    'class' => 'form-control',
                ],
            ])
            ->add('note', TextareaType::class, [
                'label'    => $this->translator->trans('form.note', [], 'founds'),
                'required' => FALSE,
                'attr'     => [
                    'class' => 'form-control',
                    'rows' => 5,
                ],
            ])
            ->add('isPublic', CheckboxType::class, [
                'label'    => $this->translator->trans('form.public', [], 'founds'),
                'required' => FALSE,
                'attr'     => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add(
                'submit', SubmitType::class,
                [
                    'label' => $this->translator->trans('form.save', [], 'founds'),
                    'attr'  => [
                        'class' => 'btn btn-primary mt-2',
                    ],
                ],
            );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
                                   'data_class' => FoundsImage::class,
                               ]);
    }
}
