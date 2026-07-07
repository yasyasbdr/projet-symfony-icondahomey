<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Patron PDF telechargeable. Sous-type de Product en STI.
 */
#[ORM\Entity]
class DigitalPattern extends Product
{
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:detail'])]
    private ?string $pdfFilename = null;

    #[ORM\Column(length: 20)]
    #[Groups(['product:detail'])]
    private string $difficultyLevel = 'debutant';

    #[ORM\Column(nullable: true)]
    #[Groups(['product:detail'])]
    private ?int $pageCount = null;

    public function getType(): string
    {
        return 'pattern';
    }

    public function getPdfFilename(): ?string
    {
        return $this->pdfFilename;
    }

    public function setPdfFilename(?string $pdfFilename): static
    {
        $this->pdfFilename = $pdfFilename;

        return $this;
    }

    public function getDifficultyLevel(): string
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(string $difficultyLevel): static
    {
        $this->difficultyLevel = $difficultyLevel;

        return $this;
    }

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function setPageCount(?int $pageCount): static
    {
        $this->pageCount = $pageCount;

        return $this;
    }
}
