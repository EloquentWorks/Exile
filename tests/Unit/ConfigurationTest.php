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
        // Set the custom ban model in the configuration
        config()->set(
            'exile.models.ban',
            CustomBan::class
        );

        // Create a ban using the user model and check if it is an instance of the custom ban model
        $ban = $this->user()->ban(
            reason: 'Custom model test'
        );

        // Assert that the ban is an instance of the custom ban model
        self::assertInstanceOf(
            CustomBan::class,
            $ban
        );
    }

    #[Test]
    public function a_custom_ban_table_can_be_configured(): void
    {
        // Set the custom ban table in the configuration
        config()->set(
            'exile.tables.bans',
            'custom_exile_bans'
        );

        // Assert that the ban model uses the custom table name
        self::assertSame(
            'custom_exile_bans',
            (new Ban)->getTable()
        );
    }
}
