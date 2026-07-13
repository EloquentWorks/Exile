<?php

namespace EloquentWorks\Exile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int|null $size_bytes
 * @property array<string, mixed>|null $metadata
 * @property-read Model $evidenceable
 * @property-read Model|null $uploadedBy
 */
class Evidence extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'evidenceable_type',
        'evidenceable_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'metadata',
        'uploaded_by_type',
        'uploaded_by_id',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string Return the table name for this model.
     */
    public function getTable(): string
    {
        // Return the table name for this model, which is configurable via the 'exile.tables.evidence' configuration option, defaulting to 'exile_evidence'.
        return (string) config('exile.tables.evidence', 'exile_evidence');
    }

    /**
     * Get the parent evidenceable model (e.g., Ban, Restriction, Strike, Warning).
     *
     * @return MorphTo<Model, $this> Returns the polymorphic relationship to the evidenceable model.
     */
    public function evidenceable(): MorphTo
    {
        // Return the polymorphic relationship to the evidenceable model.
        return $this->morphTo();
    }

    /**
     * Get the entity that uploaded the evidence.
     *
     * @return MorphTo<Model, $this> Returns the polymorphic relationship to the entity that uploaded the evidence.
     */
    public function uploadedBy(): MorphTo
    {
        // Return the polymorphic relationship to the entity that uploaded the evidence.
        return $this->morphTo(__FUNCTION__, 'uploaded_by_type', 'uploaded_by_id');
    }

    /**
     * Get the URL for the evidence file.
     *
     * @return string Returns the URL for the evidence file.
     */
    public function url(): string
    {
        // Return the URL for the evidence file using the configured disk.
        return Storage::disk($this->disk)->url($this->path);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'metadata' => 'array',
        ];
    }
}
