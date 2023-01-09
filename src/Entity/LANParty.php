<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\LANPartyRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ApiResource(shortName: "lan_parties")]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: LANPartyRepository::class)]
class LANParty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $maxPlayers = null;

    #[ORM\Column]
    private ?bool $private = null;

    #[ORM\Column]
    private ?bool $registrationOpen = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(nullable: true)]
    private ?float $cost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated = null;

    #[ORM\OneToMany(mappedBy: 'lanParty', targetEntity: Registration::class, orphanRemoval: true)]
    private Collection $registrations;

    public function __construct()
    {
        $this->registrations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): self
    {
        $this->maxPlayers = $maxPlayers;

        return $this;
    }

    public function isPrivate(): ?bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): self
    {
        $this->private = $private;

        return $this;
    }

    public function isRegistrationOpen(): ?bool
    {
        return $this->registrationOpen;
    }

    public function setRegistrationOpen(bool $registrationOpen): self
    {
        $this->registrationOpen = $registrationOpen;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): self
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(?float $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(\DateTimeInterface $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatedTimestamps(): void
    {
        $dateTimeNow = new DateTime('now');

        $this->setUpdated($dateTimeNow);

        if ($this->getCreated() === null) {
            $this->setCreated($dateTimeNow);
        }
    }

    /**
     * @return Collection<int, Registration>
     */
    public function getRegistrations(): Collection
    {
        return $this->registrations;
    }

    public function addRegistration(Registration $registration): self
    {
        if (!$this->registrations->contains($registration)) {
            $this->registrations->add($registration);
            $registration->setLanParty($this);
        }

        return $this;
    }

    public function removeRegistration(Registration $registration): self
    {
        if ($this->registrations->removeElement($registration)) {
            // set the owning side to null (unless already changed)
            if ($registration->getLanParty() === $this) {
                $registration->setLanParty(null);
            }
        }

        return $this;
    }
}
