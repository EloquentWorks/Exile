<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Models\Ban;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\CustomBan;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    #[Test]
    public function a_custom_ban_model_can_be_configured(): void
    {
        config()->set(
            'exile.models.ban',
            CustomBan::class
        );

        $ban = $this->user()->ban(
            reason: 'Custom model test'
        );

        self::assertInstanceOf(
            CustomBan::class,
            $ban
        );
    }

    #[Test]
    public function a_custom_ban_table_can_be_configured(): void
    {
        config()->set(
            'exile.tables.bans',
            'custom_exile_bans'
        );

        self::assertSame(
            'custom_exile_bans',
            (new Ban)->getTable()
        );
    }
}
