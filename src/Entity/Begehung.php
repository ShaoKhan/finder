<?php

namespace App\Entity;

use App\Repository\BegehungRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BegehungRepository::class)]
class Begehung
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $uuid = null;

    #[ORM\ManyToOne(inversedBy: 'begehungen')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?float $startLatitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?float $startLongitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?float $endLatitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?float $endLongitude = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null; // in Sekunden

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $polygonData = null; // JSON mit GPS-Track

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @var Collection<int, FoundsImage>
     */
    #[ORM\OneToMany(targetEntity: FoundsImage::class, mappedBy: 'begehung')]
    private Collection $foundsImages;

    public function __construct()
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTime();
        $this->foundsImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;
        return $this;
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

    public function getStartLatitude(): ?float
    {
        return $this->startLatitude;
    }

    public function setStartLatitude(?float $startLatitude): static
    {
        $this->startLatitude = $startLatitude;
        return $this;
    }

    public function getStartLongitude(): ?float
    {
        return $this->startLongitude;
    }

    public function setStartLongitude(?float $startLongitude): static
    {
        $this->startLongitude = $startLongitude;
        return $this;
    }

    public function getEndLatitude(): ?float
    {
        return $this->endLatitude;
    }

    public function setEndLatitude(?float $endLatitude): static
    {
        $this->endLatitude = $endLatitude;
        return $this;
    }

    public function getEndLongitude(): ?float
    {
        return $this->endLongitude;
    }

    public function setEndLongitude(?float $endLongitude): static
    {
        $this->endLongitude = $endLongitude;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getPolygonData(): ?string
    {
        return $this->polygonData;
    }

    public function setPolygonData(?string $polygonData): static
    {
        $this->polygonData = $polygonData;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return Collection<int, FoundsImage>
     */
    public function getFoundsImages(): Collection
    {
        return $this->foundsImages;
    }

    public function addFoundsImage(FoundsImage $foundsImage): static
    {
        if (!$this->foundsImages->contains($foundsImage)) {
            $this->foundsImages->add($foundsImage);
            $foundsImage->setBegehung($this);
        }
        return $this;
    }

    public function removeFoundsImage(FoundsImage $foundsImage): static
    {
        if ($this->foundsImages->removeElement($foundsImage)) {
            if ($foundsImage->getBegehung() === $this) {
                $foundsImage->setBegehung(null);
            }
        }
        return $this;
    }

    /**
     * Berechnet die Dauer der Begehung in Sekunden
     */
    public function calculateDuration(): ?int
    {
        if ($this->startTime && $this->endTime) {
            $this->duration = $this->endTime->getTimestamp() - $this->startTime->getTimestamp();
            return $this->duration;
        }
        return null;
    }

    /**
     * Gibt die Dauer formatiert zurück (z.B. "2h 15m")
     */
    public function getFormattedDuration(): string
    {
        if (!$this->duration) {
            return '0m';
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        $result = '';
        if ($hours > 0) {
            $result .= $hours . 'h ';
        }
        if ($minutes > 0) {
            $result .= $minutes . 'm ';
        }
        if ($seconds > 0 && $hours == 0) {
            $result .= $seconds . 's';
        }

        return trim($result) ?: '0m';
    }

    /**
     * Gibt den GPS-Track als Array zurück
     */
    public function getTrackAsArray(): array
    {
        if (!$this->polygonData) {
            return [];
        }
        return json_decode($this->polygonData, true) ?: [];
    }

    /**
     * Setzt den GPS-Track aus Array
     */
    public function setTrackFromArray(array $track): static
    {
        $this->polygonData = json_encode($track);
        return $this;
    }

    /**
     * Fügt einen GPS-Punkt zum Track hinzu
     */
    public function addTrackPoint(float $latitude, float $longitude, ?\DateTimeInterface $timestamp = null): static
    {
        $track = $this->getTrackAsArray();
        $track[] = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timestamp' => $timestamp ? $timestamp->format('c') : (new \DateTime())->format('c')
        ];
        $this->setTrackFromArray($track);
        return $this;
    }
}
