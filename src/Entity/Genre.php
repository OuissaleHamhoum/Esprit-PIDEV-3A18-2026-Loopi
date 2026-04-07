<?php

<<<<<<< HEAD
=======
declare(strict_types=1);

>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
namespace App\Entity;

use App\Repository\GenreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GenreRepository::class)]
#[ORM\Table(name: 'genre')]
class Genre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
<<<<<<< HEAD
    #[ORM\Column]
    private ?int $id_genre = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $sexe = null;

    public function getIdGenre(): ?int
    {
        return $this->id_genre;
    }

    public function getSexe(): ?string
=======
    #[ORM\Column(name: 'id_genre')]
    private ?int $id = null;

    #[ORM\Column(name: 'sexe', length: 50, unique: true)]
    private string $sexe = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSexe(): string
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 52d701171b98191117769fda401d16d57735b5e5
