# Evidence

Evidence can be attached polymorphically to moderation records such as bans, restrictions, warnings, strikes, or appeals.

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

`storeEvidence()`:

- checks the configured maximum size
- stores the file on the configured disk and directory
- records the original name, MIME type, and byte size
- links the uploader
- records an audit action

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
);
```

This records metadata only. The file must already exist.

## Delete evidence

Delete the record and file:

```php
Exile::deleteEvidence($evidence);
```

Keep the file but delete the database record:

```php
Exile::deleteEvidence(
    $evidence,
    deleteFile: false
);
```

## Relationships

```php
$evidence->evidenceable;

$evidence->uploadedBy;

$ban->evidence;
```

## Downloading evidence

Evidence should normally use a private filesystem disk. Serve it through an authorized controller:

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

For cloud disks, an authorized controller may return a short-lived temporary URL.

## Validation

The package enforces the configured byte-size limit in `storeEvidence()`. The application should additionally validate:

- allowed MIME types
- allowed extensions
- malware scanning
- authorization
- case ownership
- retention requirements

Do not place sensitive evidence on a public disk.
