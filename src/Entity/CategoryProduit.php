<?php

<<<<<<< HEAD
namespace App\Entity;

use App\Repository\CategoryProduitRepository;
=======
declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryProduitRepository::class)]
#[ORM\Table(name: 'category_produit')]
class CategoryProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_cat')]
    private ?int $id = null;

<<<<<<< HEAD
    #[ORM\Column(length: 100, unique: true)]
    private ?string $nomCat = null;
=======
    #[ORM\Column(name: 'nom_cat', length: 100, unique: true)]
    private string $nom = '';

    /** @var Collection<int, Produit> */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'category')]
    private Collection $produits;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
    }
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5

    public function getId(): ?int
    {
        return $this->id;
    }

<<<<<<< HEAD
    public function getNomCat(): ?string
    {
        return $this->nomCat;
    }

    public function setNomCat(string $nomCat): static
    {
        $this->nomCat = $nomCat;
        return $this;
    }
}
=======
    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /** @return Collection<int, Produit> */
    public function getProduits(): Collection
    {
        return $this->produits;
    }
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
