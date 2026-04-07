<?php

<<<<<<< HEAD
namespace App\Entity;

use App\Repository\FavorisRepository;
use Doctrine\DBAL\Types\Types;
=======
declare(strict_types=1);

namespace App\Entity;

use App\Repository\FavorisRepository;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavorisRepository::class)]
#[ORM\Table(name: 'favoris')]
class Favoris
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_favoris')]
    private ?int $id = null;

    #[ORM\ManyToOne]
<<<<<<< HEAD
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit')]
    private ?Produit $produit = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAjout = null;
=======
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column(name: 'date_ajout', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateAjout = null;
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

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;
<<<<<<< HEAD
        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
=======

        return $this;
    }

    public function getDateAjout(): ?\DateTimeImmutable
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->dateAjout;
    }

<<<<<<< HEAD
    public function setDateAjout(?\DateTimeInterface $dateAjout): static
    {
        $this->dateAjout = $dateAjout;
        return $this;
    }
}
=======
    public function setDateAjout(?\DateTimeImmutable $dateAjout): static
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
