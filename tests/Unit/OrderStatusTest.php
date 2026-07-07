<?php

namespace App\Tests\Unit;

use App\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire de la logique de la timeline de suivi de commande.
 */
class OrderStatusTest extends TestCase
{
    public function testTimelineHasFourVisibleSteps(): void
    {
        self::assertCount(4, OrderStatus::timeline());
    }

    public function testTimelineIndexOrdersStatuses(): void
    {
        self::assertSame(0, OrderStatus::Pending->timelineIndex());
        self::assertSame(1, OrderStatus::InProgress->timelineIndex());
        self::assertSame(2, OrderStatus::Preparing->timelineIndex());
        self::assertSame(3, OrderStatus::Shipped->timelineIndex());
    }

    public function testCancelledIsOutOfTimeline(): void
    {
        self::assertSame(-1, OrderStatus::Cancelled->timelineIndex());
    }

    public function testEveryStatusHasLabel(): void
    {
        foreach (OrderStatus::cases() as $status) {
            self::assertNotEmpty($status->label());
        }
    }
}
