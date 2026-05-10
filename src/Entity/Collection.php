<?php

namespace App\Entity;

use App\Repository\CollectionRepository;
use Doctrine\DBAL\Types\Types;
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
    private ?string $title = null;

    #[ORM\Column(name: 'material_type', length: 255)]
    private ?string $materialType = null;

    #[ORM\Column(name: 'image_collection', length: 255)]
    private ?string $imageCollection = null;

    #[ORM\Column(name: 'goal_amount', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $goalAmount = null;

    #[ORM\Column(name: 'current_amount', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0'])]
    private ?string $currentAmount = '0';

    #[ORM\Column(length: 50)]
    private ?string $unit = null;

    #[ORM\Column(length: 50, options: ['default' => 'active'])]
    private ?string $status = 'active';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user_id', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getMaterialType(): ?string
    {
        return $this->materialType;
    }

    public function setMaterialType(string $materialType): static
    {
        $this->materialType = $materialType;
        return $this;
    }

    public function getImageCollection(): ?string
    {
        return $this->imageCollection;
    }

    public function setImageCollection(?string $imageCollection): static
    {
        $this->imageCollection = $imageCollection;
        return $this;
    }

    public function getGoalAmount(): ?string
    {
        return $this->goalAmount;
    }

    public function setGoalAmount(string $goalAmount): static
    {
        $this->goalAmount = $goalAmount;
        return $this;
    }

    public function getCurrentAmount(): ?string
    {
        return $this->currentAmount;
    }

    public function setCurrentAmount(?string $currentAmount): static
    {
        $this->currentAmount = $currentAmount;
        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

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
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}