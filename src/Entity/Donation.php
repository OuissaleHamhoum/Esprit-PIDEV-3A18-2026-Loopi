<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DonationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DonationRepository::class)]
#[ORM\Table(name: 'donation')]
class Donation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_donation')]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_collection', referencedColumnName: 'id_collection', nullable: false)]
    private ?Collection $collection = null;

    #[ORM\Column]
    private float $amount = 0.0;

    #[ORM\Column(name: 'donation_date', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $donationDate = null;

    #[ORM\Column(length: 20)]
    private string $status = 'en_attente';

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCollection(): ?Collection
    {
        return $this->collection;
    }

    public function setCollection(?Collection $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDonationDate(): ?\DateTimeImmutable
    {
        return $this->donationDate;
    }

    public function setDonationDate(?\DateTimeImmutable $donationDate): static
    {
        $this->donationDate = $donationDate;

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
}
