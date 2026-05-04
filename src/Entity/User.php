<?php

<<<<<<< HEAD
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

=======
declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
<<<<<<< HEAD
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
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
=======
    private string $nom = '';

    #[ORM\Column(length: 100)]
    private string $prenom = '';

    #[ORM\Column(length: 100, unique: true)]
    private string $email = '';

    #[ORM\Column(length: 100)]
    private string $password = '';

    #[ORM\Column(length: 255, options: ['default' => 'default.jpg'])]
    private string $photo = 'default.jpg';

    #[ORM\Column(name: 'role', type: 'string', length: 32, enumType: UserRole::class)]
    private UserRole $role = UserRole::PARTICIPANT;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_genre', referencedColumnName: 'id_genre', nullable: true)]
    private ?Genre $genre = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

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

<<<<<<< HEAD
    public function getNom(): ?string
=======
    public function getNom(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

<<<<<<< HEAD
    public function getPrenom(): ?string
=======
    public function getPrenom(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

<<<<<<< HEAD
    public function getEmail(): ?string
=======
    public function getEmail(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

<<<<<<< HEAD
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): ?string
=======
    public function getPassword(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

<<<<<<< HEAD
    public function getPhoto(): ?string
=======
    public function getPhoto(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->photo;
    }

<<<<<<< HEAD
    public function setPhoto(?string $photo): static
=======
    public function setPhoto(string $photo): static
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        $this->photo = $photo;

        return $this;
    }

<<<<<<< HEAD
    public function getRole(): string
=======
    public function getRole(): UserRole
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->role;
    }

<<<<<<< HEAD
    public function setRole(string $role): static
=======
    public function setRole(UserRole $role): static
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        $this->role = $role;

        return $this;
    }

<<<<<<< HEAD
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

=======
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    public function setGenre(?Genre $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

<<<<<<< HEAD
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
=======
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

        return $this;
    }

<<<<<<< HEAD
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }
<<<<<<< HEAD
}
=======
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
=======

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
>>>>>>> web-Evenement

        return $this;
    }

<<<<<<< HEAD
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        $roles[] = match ($this->role) {
            UserRole::ADMIN => 'ROLE_ADMIN',
            UserRole::ORGANISATEUR => 'ROLE_ORGANISATEUR',
            UserRole::PARTICIPANT => 'ROLE_PARTICIPANT',
        };

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
=======
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
>>>>>>> web-Evenement
