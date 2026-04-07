<?php

<<<<<<< HEAD
=======
declare(strict_types=1);

>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_produit')]
    private ?int $id = null;

<<<<<<< HEAD
    #[ORM\Column(length: 200)]
    private ?string $nomProduit = null;
=======
    #[ORM\Column(name: 'nom_produit', length: 200)]
    private string $nom = '';
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

<<<<<<< HEAD
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageProduit = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_cat', referencedColumnName: 'id_cat')]
    private ?CategoryProduit $category = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', nullable: true, referencedColumnName: 'id')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;
=======
    #[ORM\Column(name: 'image_produit', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    #[ORM\JoinColumn(name: 'id_cat', referencedColumnName: 'id_cat', nullable: false)]
    private ?CategoryProduit $category = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id', nullable: true)]
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
    public function getNomProduit(): ?string
    {
        return $this->nomProduit;
    }

    public function setNomProduit(string $nomProduit): static
    {
        $this->nomProduit = $nomProduit;
=======
    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
<<<<<<< HEAD
        return $this;
    }

    public function getImageProduit(): ?string
    {
        return $this->imageProduit;
    }

    public function setImageProduit(?string $imageProduit): static
    {
        $this->imageProduit = $imageProduit;
=======

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
        return $this;
    }

    public function getCategory(): ?CategoryProduit
    {
        return $this->category;
    }

    public function setCategory(?CategoryProduit $category): static
    {
        $this->category = $category;
<<<<<<< HEAD
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
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
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
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
