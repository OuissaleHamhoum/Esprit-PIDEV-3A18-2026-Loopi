<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[ORM\Table(name: 'coupon')]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_coupon')]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $discountPercent = null;

    #[ORM\Column(type: Types::DATE, nullable: true)]
    private ?\DateTimeInterface $donationDate = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $used = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', nullable: true, referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_donation', nullable: true, referencedColumnName: 'id_donation')]
    private ?Donation $donation = null;

    #[ORM\Column(type: Types::DATE, nullable: true)]
    private ?\DateTimeInterface $expirationDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private ?string $minAmount = '0.00';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getDiscountPercent(): ?string
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(string $discountPercent): static
    {
        $this->discountPercent = $discountPercent;
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

    public function isUsed(): ?bool
    {
        return $this->used;
    }

    public function setUsed(?bool $used): static
    {
        $this->used = $used;
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

    public function getDonation(): ?Donation
    {
        return $this->donation;
    }

    public function setDonation(?Donation $donation): static
    {
        $this->donation = $donation;
        return $this;
    }

    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?\DateTimeInterface $expirationDate): static
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function getMinAmount(): ?string
    {
        return $this->minAmount;
    }

    public function setMinAmount(?string $minAmount): static
    {
        $this->minAmount = $minAmount;
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
}