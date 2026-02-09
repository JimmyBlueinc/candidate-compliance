<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'password',
        'must_change_password',
        'role',
        'access_status',
        'avatar_path',
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
            'password' => 'hashed',
        ];
    }

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar_path) {
            return null;
        }
        
        // Use Storage::url() which handles S3 automatically
        $url = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->avatar_path);
        
        // Add cache-busting parameter using updated_at timestamp
        if ($this->updated_at) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            return $url . $separator . 'v=' . $this->updated_at->timestamp;
        }
        
        return $url;
    }

    /**
     * Get the credentials managed by this user.
     */
    public function credentials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Send the password reset notification with custom URL.
     */
    public function sendPasswordResetNotification($token, $resetUrl = null): void
    {
        if ($resetUrl) {
            // Use custom notification with frontend URL
            $this->notify(new \App\Notifications\ResetPasswordNotification($token, $resetUrl));
        } else {
            // Fallback to default Laravel notification
            $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
        }
    }
}
