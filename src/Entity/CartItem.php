<?php

namespace App\Entity;

use App\Enum\SelectedType;
use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'cartItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column]
    #[Assert\Positive]
    private int $quantity = 1;

    #[ORM\Column(length: 20, enumType: SelectedType::class)]
    private SelectedType $selectedType = SelectedType::Physical;

    /** @var array<string, mixed>|null Mensurations saisies (json). */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $measurements = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $customizationNote = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getSelectedType(): SelectedType
    {
        return $this->selectedType;
    }

    public function setSelectedType(SelectedType $selectedType): static
    {
        $this->selectedType = $selectedType;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getMeasurements(): ?array
    {
        return $this->measurements;
    }

    /** @param array<string, mixed>|null $measurements */
    public function setMeasurements(?array $measurements): static
    {
        $this->measurements = $measurements;

        return $this;
    }

    public function getCustomizationNote(): ?string
    {
        return $this->customizationNote;
    }

    public function setCustomizationNote(?string $customizationNote): static
    {
        $this->customizationNote = $customizationNote;

        return $this;
    }

    public function getLineTotal(): string
    {
        $unit = $this->product?->getBasePrice() ?? '0.00';

        return bcmul($unit, (string) $this->quantity, 2);
    }
}
