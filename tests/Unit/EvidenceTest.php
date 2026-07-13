<?php

namespace Tests\Unit;

use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EvidenceTest extends TestCase
{
    #[Test]
    public function it_stores_attaches_and_deletes_evidence(): void
    {
        Storage::fake('local');
        $user = $this->user();
        $moderator = $this->user('Moderator');
        $ban = $user->ban('Evidence-backed ban');
        $manager = app(ExileManager::class);

        $evidence = $manager->storeEvidence(
            $ban,
            UploadedFile::fake()->create('report.txt', 4, 'text/plain'),
            $moderator,
            ['case' => 'EX-200'],
        );

        Storage::disk('local')->assertExists($evidence->path);
        self::assertTrue($evidence->evidenceable->is($ban));
        self::assertSame('EX-200', $evidence->metadata['case']);

        self::assertTrue($manager->deleteEvidence($evidence));
        Storage::disk('local')->assertMissing($evidence->path);
    }

    #[Test]
    public function it_rejects_evidence_that_exceeds_the_size_limit(): void
    {
        config()->set(
            'exile.evidence.max_size_kilobytes',
            1
        );

        $user = $this->user();

        $ban = $user->ban(
            reason: 'Evidence size test'
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Evidence files may not exceed 1 KB.'
        );

        app(ExileManager::class)->storeEvidence(
            subject: $ban,
            file: UploadedFile::fake()->create(
                'too-large.txt',
                2,
                'text/plain'
            )
        );
    }
}
