<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CollectionRepository;
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getMaterialType(): string
    {
        return $this->materialType;
    }

    public function setMaterialType(string $materialType): static
    {
        $this->materialType = $materialType;

        return $this;
    }

    public function getImageCollection(): string
    {
        return $this->imageCollection;
    }

    public function setImageCollection(string $imageCollection): static
    {
        $this->imageCollection = $imageCollection;

        return $this;
    }

    public function getGoalAmount(): float
    {
        return $this->goalAmount;
    }

    public function setGoalAmount(float $goalAmount): static
    {
        $this->goalAmount = $goalAmount;

        return $this;
    }

    public function getCurrentAmount(): ?float
    {
        return $this->currentAmount;
    }

    public function setCurrentAmount(?float $currentAmount): static
    {
        $this->currentAmount = $currentAmount;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

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
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
