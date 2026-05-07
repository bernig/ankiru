<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $original_file_name
 * @property array<array-key, mixed>|null $csv_rows
 * @property bool $has_csv_loaded
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\CsvDraftFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereCsvRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereHasCsvLoaded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereOriginalFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereUserId($value)
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
	class CsvDraft extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\CsvDraft|null $csvDraft
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @noinspection PhpFullyQualifiedNameUsageInspection
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
	class User extends \Eloquent {}
}

