<?php

namespace App\Entity;

use App\Repository\DonationRepository;
use Doctrine\DBAL\Types\Types;
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
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_collection', referencedColumnName: 'id_collection')]
    private ?Collection $collection = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $donationDate = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'en_attente'])]
    private ?string $status = 'en_attente';

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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDonationDate(): ?\DateTimeInterface
    {
        return $this->donationDate;
    }

    public function setDonationDate(?\DateTimeInterface $donationDate): static
    {
        $this->donationDate = $donationDate;
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
}