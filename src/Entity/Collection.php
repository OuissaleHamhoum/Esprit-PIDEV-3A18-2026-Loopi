<?php

<<<<<<< HEAD
namespace App\Entity;

use App\Repository\CollectionRepository;
use Doctrine\DBAL\Types\Types;
=======
declare(strict_types=1);

namespace App\Entity;

use App\Repository\CollectionRepository;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionRepository::class)]
#[ORM\Table(name: 'collection')]
class Collection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_collection')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
<<<<<<< HEAD
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $materialType = null;

    #[ORM\Column(length: 255)]
    private ?string $imageCollection = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $goalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0'])]
    private ?string $currentAmount = '0';

    #[ORM\Column(length: 50)]
    private ?string $unit = null;

    #[ORM\Column(length: 50, options: ['default' => 'active'])]
    private ?string $status = 'active';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;
=======
    private string $title = '';

    #[ORM\Column(name: 'material_type', length: 255)]
    private string $materialType = '';

    #[ORM\Column(name: 'image_collection', length: 255)]
    private string $imageCollection = '';

    #[ORM\Column(name: 'goal_amount')]
    private float $goalAmount = 0.0;

    #[ORM\Column(name: 'current_amount', nullable: true)]
    private ?float $currentAmount = 0.0;

    #[ORM\Column(length: 50)]
    private string $unit = 'kg';

    #[ORM\Column(length: 50, options: ['default' => 'active'])]
    private string $status = 'active';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

    public function getId(): ?int
    {
        return $this->id;
    }

<<<<<<< HEAD
    public function getTitle(): ?string
=======
    public function getTitle(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
<<<<<<< HEAD
        return $this;
    }

    public function getMaterialType(): ?string
=======

        return $this;
    }

    public function getMaterialType(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->materialType;
    }

    public function setMaterialType(string $materialType): static
    {
        $this->materialType = $materialType;
<<<<<<< HEAD
        return $this;
    }

    public function getImageCollection(): ?string
=======

        return $this;
    }

    public function getImageCollection(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->imageCollection;
    }

<<<<<<< HEAD
    public function setImageCollection(?string $imageCollection): static
    {
        $this->imageCollection = $imageCollection;
        return $this;
    }

    public function getGoalAmount(): ?string
=======
    public function setImageCollection(string $imageCollection): static
    {
        $this->imageCollection = $imageCollection;

        return $this;
    }

    public function getGoalAmount(): float
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->goalAmount;
    }

<<<<<<< HEAD
    public function setGoalAmount(string $goalAmount): static
    {
        $this->goalAmount = $goalAmount;
        return $this;
    }

    public function getCurrentAmount(): ?string
=======
    public function setGoalAmount(float $goalAmount): static
    {
        $this->goalAmount = $goalAmount;

        return $this;
    }

    public function getCurrentAmount(): ?float
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->currentAmount;
    }

<<<<<<< HEAD
    public function setCurrentAmount(?string $currentAmount): static
    {
        $this->currentAmount = $currentAmount;
        return $this;
    }

    public function getUnit(): ?string
=======
    public function setCurrentAmount(?float $currentAmount): static
    {
        $this->currentAmount = $currentAmount;

        return $this;
    }

    public function getUnit(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;
<<<<<<< HEAD
        return $this;
    }

    public function getStatus(): ?string
=======

        return $this;
    }

    public function getStatus(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->status;
    }

<<<<<<< HEAD
    public function setStatus(?string $status): static
    {
        $this->status = $status;
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

    public function getCreatedAt(): ?\DateTimeInterface
=======
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->createdAt;
    }

<<<<<<< HEAD
    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
=======
    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->updatedAt;
    }

<<<<<<< HEAD
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
=======
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
