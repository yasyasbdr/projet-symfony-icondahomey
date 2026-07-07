<?php

namespace App\Entity;

use App\Enum\SelectedType;
use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ligne de commande. On y "snapshot" le nom et le prix unitaire au moment de
 * l'achat : ainsi une modification ulterieure du produit ne change pas les
 * commandes deja passees.
 */
#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'parent_order_id', nullable: false)]
    private ?Order $parentOrder = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\Column(length: 180)]
    private ?string $productName = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $unitPrice = null;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(length: 20, enumType: SelectedType::class)]
    private SelectedType $selectedType = SelectedType::Physical;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $measurements = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentOrder(): ?Order
    {
        return $this->parentOrder;
    }

    public function setParentOrder(?Order $parentOrder): static
    {
        $this->parentOrder = $parentOrder;

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

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

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

    public function getLineTotal(): string
    {
        return bcmul((string) $this->unitPrice, (string) $this->quantity, 2);
    }
}
