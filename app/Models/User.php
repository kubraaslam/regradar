<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'retention_consent_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Roles an admin may assign, with their display names.
     */
    public const ROLES = [
        self::ROLE_QA_ENGINEER => 'QA Engineer',
        self::ROLE_DEVELOPER => 'Developer',
        self::ROLE_ADMIN => 'Admin',
    ];

    public function analyses()
    {
        return $this->hasMany(Analysis::class);
    }

    public function featureRegistries()
    {
        return $this->hasMany(FeatureRegistry::class);
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }

    /**
     * Whether this user has consented to their analyses being kept beyond the
     * retention period (SRS NFR8).
     */
    public function hasRetentionConsent(): bool
    {
        return $this->retention_consent_at !== null;
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    /**
     * Whether changing this user's role or deactivating them would leave the
     * system with no active administrator, after which no one could manage
     * accounts at all.
     */
    public function isLastActiveAdmin(): bool
    {
        return $this->isAdmin()
            && $this->isActive()
            && static::where('role', self::ROLE_ADMIN)->whereNull('deactivated_at')->count() <= 1;
    }

    public const ROLE_QA_ENGINEER = 'qa_engineer';
    public const ROLE_DEVELOPER = 'developer';
    public const ROLE_ADMIN = 'admin';

    /**
     * May run analyses and maintain the Feature Registry.
     *
     * Developers are deliberately excluded: the SRS defines the Developer role
     * as read-only (FR2), so they view QA findings without being able to alter
     * them.
     */
    public function canManage(): bool
    {
        return in_array($this->role, [self::ROLE_QA_ENGINEER, self::ROLE_ADMIN], true);
    }

    /**
     * Sees analyses and registry entries across the whole team rather than
     * only their own. A developer reviewing a pull request needs the report a
     * QA engineer produced for it, so read-only access is only useful if it
     * reaches beyond the developer's own (empty) records.
     */
    public function canViewAll(): bool
    {
        return in_array($this->role, [self::ROLE_DEVELOPER, self::ROLE_ADMIN], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Whether this user may change a record owned by another user.
     */
    public function owns(Model $record): bool
    {
        return $this->isAdmin() || (int) $record->user_id === (int) $this->id;
    }
}
