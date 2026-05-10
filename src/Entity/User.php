<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Ignore]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = 'default.jpg';

    #[ORM\Column(length: 50)]
    private string $role = 'participant';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_genre', nullable: true, referencedColumnName: 'id_genre')]
    private ?Genre $genre = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated_at = null;

#[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $has_donated_first_time = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $total_plastic = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $total_paper = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $total_glass = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $total_metal = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $total_cardboard = '0.00';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $xp = 0;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getRoles(): array
    {
        return match ($this->role) {
            'admin' => ['ROLE_ADMIN'],
            'organisateur' => ['ROLE_ORGANISATEUR'],
            default => ['ROLE_PARTICIPANT'],
        };
    }

    public function eraseCredentials(): void
    {
        // no temporary data to clear
    }

    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    public function setGenre(?Genre $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getDisplayName(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function isHasDonatedFirstTime(): bool
    {
        return $this->has_donated_first_time;
    }

    public function setHasDonatedFirstTime(bool $has_donated_first_time): static
    {
        $this->has_donated_first_time = $has_donated_first_time;
        return $this;
    }

    public function getTotalPlastic(): string
    {
        return $this->total_plastic;
    }

    public function setTotalPlastic(string $total_plastic): static
    {
        $this->total_plastic = $total_plastic;
        return $this;
    }

    public function getTotalPaper(): string
    {
        return $this->total_paper;
    }

    public function setTotalPaper(string $total_paper): static
    {
        $this->total_paper = $total_paper;
        return $this;
    }

    public function getTotalGlass(): string
    {
        return $this->total_glass;
    }

    public function setTotalGlass(string $total_glass): static
    {
        $this->total_glass = $total_glass;
        return $this;
    }

    public function getTotalMetal(): string
    {
        return $this->total_metal;
    }

    public function setTotalMetal(string $total_metal): static
    {
        $this->total_metal = $total_metal;
        return $this;
    }

    public function getTotalCardboard(): string
    {
        return $this->total_cardboard;
    }

    public function setTotalCardboard(string $total_cardboard): static
    {
        $this->total_cardboard = $total_cardboard;

        return $this;
    }

    public function getXp(): int
    {
        return $this->xp;
    }

    public function setXp(int $xp): static
    {
        $this->xp = $xp;
        return $this;
    }
}