# Evidence

Evidence attaches file metadata to moderation records.

## Store an uploaded file

```php
use EloquentWorks\Exile\Facades\Exile;

$evidence = Exile::storeEvidence(
    subject: $ban,
    file: $request->file('evidence'),
    uploadedBy: $moderator,
    metadata: [
        'case_number' => 'EX-1042',
    ],
);
```

Exile:

- validates the configured size limit
- stores the file on the configured disk
- records the original name, MIME type, and byte size
- calculates a SHA-256 checksum
- links the uploader
- creates an audit action

If checksum calculation or evidence-record creation fails, Exile deletes the newly stored file before rethrowing the error.

## Attach an existing file

```php
$evidence = Exile::attachEvidence(
    subject: $ban,
    disk: 'private',
    path: 'moderation/case-1042/report.pdf',
    originalName: 'report.pdf',
    mimeType: 'application/pdf',
    sizeBytes: 120000,
    uploadedBy: $moderator,
    metadata: [
        'source' => 'case-management',
    ],
    checksumSha256: $checksum,
);
```

The caller is responsible for supplying a checksum when attaching an existing file.

## Verify integrity

```php
if (! $evidence->hasValidChecksum()) {
    throw new RuntimeException(
        'The evidence file failed integrity verification.'
    );
}
```

A missing checksum returns `false`. Existing evidence created before checksum support may therefore require a backfill.

## Delete

Delete the record and stored file:

```php
Exile::deleteEvidence($evidence);
```

Keep the file while deleting the database record:

```php
Exile::deleteEvidence(
    $evidence,
    deleteFile: false,
);
```

## Secure downloads

Use a private disk and an authorized controller:

```php
public function show(Evidence $evidence)
{
    $this->authorize('view', $evidence);

    return Storage::disk($evidence->disk)
        ->download(
            $evidence->path,
            $evidence->original_name
        );
}
```

## Application validation

The consuming application should also enforce:

- allowed MIME types
- allowed extensions
- malware scanning
- moderator authorization
- case ownership
- retention requirements
