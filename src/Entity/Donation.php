<?php

<<<<<<< HEAD
namespace App\Entity;

use App\Repository\DonationRepository;
use Doctrine\DBAL\Types\Types;
=======
declare(strict_types=1);

namespace App\Entity;

use App\Repository\DonationRepository;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
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
<<<<<<< HEAD
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
=======
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
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

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
<<<<<<< HEAD
=======

>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
        return $this;
    }

    public function getCollection(): ?Collection
    {
        return $this->collection;
    }

    public function setCollection(?Collection $collection): static
    {
        $this->collection = $collection;
<<<<<<< HEAD
        return $this;
    }

    public function getAmount(): ?string
=======

        return $this;
    }

    public function getAmount(): float
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->amount;
    }

<<<<<<< HEAD
    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDonationDate(): ?\DateTimeInterface
=======
    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDonationDate(): ?\DateTimeImmutable
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->donationDate;
    }

<<<<<<< HEAD
    public function setDonationDate(?\DateTimeInterface $donationDate): static
    {
        $this->donationDate = $donationDate;
        return $this;
    }

    public function getStatus(): ?string
=======
    public function setDonationDate(?\DateTimeImmutable $donationDate): static
    {
        $this->donationDate = $donationDate;

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
}
=======
    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
