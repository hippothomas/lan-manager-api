<?php

namespace App\Entity;

use DateTime;
use App\Entity\LANParty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\InformationRepository;
use Doctrine\Common\Collections\Collection;
use App\Controller\UpsertInformationController;
use App\Controller\LANPartyCollectionController;
use Doctrine\Common\Collections\ArrayCollection;

#[ApiResource(
    uriTemplate: '/lan_parties/{lanId}/informations',
    uriVariables: [
        'lanId' => new Link(fromClass: LANParty::class, toProperty: 'lanParty'),
    ],
    operations: [
		new GetCollection(controller: LANPartyCollectionController::class),
		new Post(read: false, controller: UpsertInformationController::class)
	]
)]
#[ApiResource(
    uriTemplate: '/lan_parties/{lanId}/informations/{id}',
    uriVariables: [
        'lanId' => new Link(fromClass: LANParty::class, toProperty: 'lanParty'),
        'id' => new Link(fromClass: Information::class),
    ],
    operations: [
		new Get(security: "is_granted('PLAYER', object)"),
		new Put(security: "is_granted('STAFF', object)", controller: UpsertInformationController::class),
		new Delete(security: "is_granted('STAFF', object)")
	]
)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: InformationRepository::class)]
class Information
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\ManyToMany(targetEntity: User::class)]
	#[ApiProperty(securityPostDenormalize: "is_granted('ROLE_ADMIN')")]
    private Collection $author;

    #[ORM\ManyToOne(inversedBy: 'information')]
    #[ORM\JoinColumn(nullable: false)]
	#[ApiProperty(securityPostDenormalize: "is_granted('ROLE_ADMIN')")]
    private ?LANParty $lanParty = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated = null;

    public function __construct()
    {
        $this->author = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAuthor(): Collection
    {
        return $this->author;
    }

    public function addAuthor(User $author): self
    {
        if (!$this->author->contains($author)) {
            $this->author->add($author);
        }

        return $this;
    }

    public function removeAuthor(User $author): self
    {
        $this->author->removeElement($author);

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
}
