<?php

namespace App\Entity;


use App\Repository\FoundsImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FoundsImageRepository::class)]
class FoundsImage
{
    #[ORM\Column(type: 'string', length: 255)]
    public ?string            $filePath                 = NULL;
    #[ORM\Column(type: 'text', nullable: TRUE)]
    public ?string            $note                     = NULL;
    #[ORM\Column(type: 'string', length: 255)]
    public ?string            $username                 = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    public ?string            $user_uuid                = NULL;
    #[ORM\Column(type: 'datetime')]
    public \DateTimeInterface $createdAt;
    #[ORM\Column(type: 'decimal', precision: 20, scale: 8, nullable: TRUE)]
    public ?float             $utmX                     = NULL;
    #[ORM\Column(type: 'decimal', precision: 20, scale: 8, nullable: TRUE)]
    public ?float             $utmY                     = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $parcel                   = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $district                 = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $county                   = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $state                    = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $nearestStreet            = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $nearestTown              = NULL; // Flurstück
    #[ORM\Column(type: 'decimal', precision: 6, scale: 2, nullable: TRUE)]
    public ?float             $distanceToChurchOrCenter = NULL; // Gemarkung
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $churchOrCenterName       = NULL; // Landkreis
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $cameraModel              = NULL; // Bundesland
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $exposureTime             = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string            $fNumber                  = NULL;
    #[ORM\Column(type: 'integer', nullable: TRUE)]
    public ?int               $iso                      = NULL;
    #[ORM\Column(type: 'datetime', nullable: TRUE)]
    public ?\DateTime         $dateTime                 = NULL;
    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: TRUE)]
    public ?float             $latitude                 = NULL;
    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: TRUE)]
    public ?float             $longitude                = NULL;
    #[ORM\Column(type: 'boolean')]
    public bool               $isPublic                 = FALSE;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string $gemarkungName = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string $gemarkungNummer = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string $flurstueckName = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    public ?string $flurstueckNummer = NULL;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int              $id                       = NULL;
    #[ORM\Column(type: 'string', length: 255, nullable: TRUE)]
    private ?string           $name                     = NULL;

    #[ORM\ManyToOne(inversedBy: 'foundsImages')]
    private ?User $user = NULL;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setNearestStreet(string $string)
    {
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

}
