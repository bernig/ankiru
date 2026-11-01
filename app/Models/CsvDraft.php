<?php

namespace App\Models;

use App\Observers\CsvDraftObserver;
use Database\Factories\CsvDraftFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(CsvDraftObserver::class)]
class CsvDraft extends Model
{
    /** @use HasFactory<CsvDraftFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'original_file_name',
        'csv_rows',
        'has_csv_loaded',
        'last_accessed_at',
    ];

    /**
     * A file name without its extension. Unlike pathinfo(), a slash or backslash in
     * a name the user typed is kept: "Russe/Verbes.csv" gives "Russe/Verbes".
     */
    public static function baseName(string $fileName): string
    {
        return (string) preg_replace('/\.[^.\/\\\\]*$/', '', $fileName);
    }

    /**
     * The extension of a file name, without the dot ('' when there is none).
     */
    public static function extension(string $fileName): string
    {
        return preg_match('/\.([^.\/\\\\]*)$/', $fileName, $match) === 1 ? $match[1] : '';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'csv_rows' => 'array',
            'has_csv_loaded' => 'boolean',
            'last_accessed_at' => 'datetime',
        ];
    }
}
