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
        // Fake the storage disk for testing purposes.
        Storage::fake('local');

        // Create a fake user and moderator for testing.
        $user = $this->user();
        $moderator = $this->user('Moderator');
        $ban = $user->ban('Evidence-backed ban');
        $manager = app(ExileManager::class);

        // Store evidence for the ban using a fake uploaded file and metadata.
        $evidence = $manager->storeEvidence(
            $ban,
            UploadedFile::fake()->create('report.txt', 4, 'text/plain'),
            $moderator,
            ['case' => 'EX-200'],
        );

        // Assert that the evidence file exists in the storage disk and that it is correctly associated with the ban.
        Storage::disk('local')->assertExists($evidence->path);
        self::assertTrue($evidence->evidenceable->is($ban));
        self::assertSame('EX-200', $evidence->metadata['case']);

        // Delete the evidence and assert that it is removed from the storage disk.
        self::assertTrue($manager->deleteEvidence($evidence));
        Storage::disk('local')->assertMissing($evidence->path);
    }

    #[Test]
    public function it_rejects_evidence_that_exceeds_the_size_limit(): void
    {
        // Set the maximum evidence size to 1 KB for testing purposes.
        config()->set(
            'exile.evidence.max_size_kilobytes',
            1
        );

        // Create a fake user and ban for testing.
        $user = $this->user();

        $ban = $user->ban(
            reason: 'Evidence size test'
        );

        // Expect an InvalidArgumentException to be thrown when trying to store evidence that exceeds the size limit.
        $this->expectException(
            InvalidArgumentException::class
        );

        // Expect the exception message to indicate that evidence files may not exceed 1 KB.
        $this->expectExceptionMessage(
            'Evidence files may not exceed 1 KB.'
        );

        // Attempt to store evidence that exceeds the size limit (2 KB in this case).
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
