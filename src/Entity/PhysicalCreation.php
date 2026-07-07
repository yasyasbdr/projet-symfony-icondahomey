<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Creation physique (vetement/accessoire au crochet).
 * Sous-type de Product en STI. Peut proposer un patron PDF associe.
 */
#[ORM\Entity]
class PhysicalCreation extends Product
{
    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    #[ORM\Column]
    private bool $requiresMeasurements = false;

    /** Patron PDF telechargeable eventuellement associe a cette creation. */
    #[ORM\OneToOne(targetEntity: DigitalPattern::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DigitalPattern $associatedPattern = null;

    public function getType(): string
    {
        return 'physical';
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function requiresMeasurements(): bool
    {
        return $this->requiresMeasurements;
    }

    public function setRequiresMeasurements(bool $requiresMeasurements): static
    {
        $this->requiresMeasurements = $requiresMeasurements;

        return $this;
    }

    public function getAssociatedPattern(): ?DigitalPattern
    {
        return $this->associatedPattern;
    }

    public function setAssociatedPattern(?DigitalPattern $associatedPattern): static
    {
        $this->associatedPattern = $associatedPattern;

        return $this;
    }

    public function hasPattern(): bool
    {
        return $this->associatedPattern !== null;
    }
}
