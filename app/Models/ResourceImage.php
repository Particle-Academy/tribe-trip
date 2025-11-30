<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * ResourceImage model for resource photos.
 *
 * Supports multiple images per resource with ordering and primary selection.
 */
class ResourceImage extends Model
{
    /** @use HasFactory<\Database\Factories\ResourceImageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'resource_id',
        'path',
        'filename',
        'order',
        'is_primary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the resource this image belongs to.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the full URL to the image.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark this image as the primary image.
     */
    public function markAsPrimary(): bool
    {
        // Clear other primary flags for this resource
        self::where('resource_id', $this->resource_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        return $this->update(['is_primary' => true]);
    }

    /**
     * Delete the image file and record.
     */
    public function deleteWithFile(): bool
    {
        if ($this->path) {
            Storage::disk('public')->delete($this->path);
        }

        return $this->delete();
    }
}
