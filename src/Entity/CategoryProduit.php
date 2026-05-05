<?php

namespace App\Entity;

use App\Repository\CategoryProduitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryProduitRepository::class)]
#[ORM\Table(name: 'category_produit')]
class CategoryProduit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_cat')]
    private ?int $id = null;

    #[ORM\Column(name: 'nom_cat', length: 100, unique: true)]
    private ?string $nomCat = null;

    public function getId(): ?int
    {
        return $this->id;
    }

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