<?php

namespace App\Entity;

use DateTime;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\RegistrationRepository;
use App\Controller\CreateRegistrationController;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [
	new GetCollection(security: "is_granted('ROLE_USER')"),
	new Get(security: "is_granted('ROLE_USER')"),
	new Post(security: "is_granted('ROLE_USER')", controller: CreateRegistrationController::class),
	new Put(security: "is_granted('REGISTRATION_STAFF', object)"),
	new Delete(security: "object.getAccount() == user or is_granted('REGISTRATION_STAFF', object)"),
])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: RegistrationRepository::class)]
class Registration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
	#[ApiProperty(securityPostDenormalize: "is_granted('REGISTRATION_STAFF', object)")]
    private ?User $account = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LANParty $lanParty = null;

    #[ORM\Column(type: Types::ARRAY)]
	#[ApiProperty(securityPostDenormalize: "is_granted('REGISTRATION_STAFF', object)")]
    private array $roles = [];

    #[ORM\Column(length: 255)]
	#[ApiProperty(securityPostDenormalize: "is_granted('REGISTRATION_STAFF', object)")]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): ?User
    {
        return $this->account;
    }

    public function setAccount(?User $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getLanParty(): ?LANParty
    {
        return $this->lanParty;
    }

    public function setLanParty(?LANParty $lanParty): self
    {
        $this->lanParty = $lanParty;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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
        if (empty($this->getRoles())) {
            $this->setRoles(["PLAYER"]);
        }
        if ($this->getStatus() === null) {
            $this->setStatus("registered");
        }
    }
}
