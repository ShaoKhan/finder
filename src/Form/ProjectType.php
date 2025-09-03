<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectType extends AbstractType
{
    public function __construct(
        private Security $security,
        private UserRepository $userRepository,
        private TranslatorInterface $translator
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add("name", TextType::class, [
                "label" => $this->translator->trans("projects.title",[],"projects"),
                "attr" => [
                    "placeholder" => $this->translator->trans("projects.project_name_placeholder",[],"projects"),
                    "class" => "form-control"
                ],
                "constraints" => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        "message" => $this->translator->trans("projects.name_empty",[],"projects")
                    ]),
                    new \Symfony\Component\Validator\Constraints\Length([
                        "min" => 3,
                        "max" => 255,
                        "minMessage" => $this->translator->trans("projects.name_min",["%limit%" => 3],"projects"),
                        "maxMessage" => $this->translator->trans("projects.name_max",["%limit%" => 255],"projects")
                    ])
                ]
            ])
            ->add("description", TextareaType::class, [
                "label" => $this->translator->trans("projects.project_details",[],"projects"),
                "required" => false,
                "attr" => [
                    "placeholder" => $this->translator->trans("projects.description_placeholder",[],"projects"),
                    "class" => "form-control",
                    "rows" => 4
                ]
            ])
            ->add("users", EntityType::class, [
                "class" => User::class,
                "choice_label" => function (User $user) {
                    return $user->getVorname() && $user->getNachname() 
                        ? $user->getVorname() . " " . $user->getNachname() . " (" . $user->getEmail() . ")"
                        : $user->getEmail();
                },
                "multiple" => true,
                "expanded" => false,
                "label" => "Projektmitglieder",
                "attr" => [
                    "class" => "form-select"
                ],
                "query_builder" => function (UserRepository $ur) {
                    return $ur->createQueryBuilder("u")
                        ->where("u.isActive = :active")
                        ->setParameter("active", true)
                        ->orderBy("u.vorname", "ASC")
                        ->addOrderBy("u.nachname", "ASC");
                },
                "data" => $this->getProjectUsers($options)
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class" => Project::class,
        ]);
    }

    private function getProjectUsers(array $options): array
    {
        // Wenn ein bestehendes Projekt bearbeitet wird, verwende dessen Mitglieder
        if (isset($options["data"]) && $options["data"] instanceof Project) {
            $project = $options["data"];
            if ($project->getId()) {
                // Projekt existiert bereits - verwende aktuelle Mitglieder
                return $project->getUsers()->toArray();
            }
        }
        
        // Bei neuen Projekten: aktuellen Benutzer als Standard
        $user = $this->security->getUser();
        return $user ? [$user] : [];
    }
}