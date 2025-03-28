<?php

namespace App\Models;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\UserProgress;
use App\Models\Enigma;
use App\Models\Chapter;
use App\Models\Achievement;
use App\Models\UserProgressChapter;
use App\Models\Role;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
        'bio',
        'avatar',
        'google_id',
        'google_token',
        'google_refresh_token',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'points' => 'integer'
    ];

    protected $attributes = [
        'points' => 0
    ];

    // Désactiver l'envoi automatique de l'email de vérification par Laravel
    public function sendEmailVerificationNotification()
    {
        // Ne rien faire pour empêcher Laravel d'envoyer l'e-mail automatique
    }

    public function enigmaProgress(): HasMany
    {
        return $this->hasMany(UserProgress::class);
    }

    public function enigmas(): BelongsToMany
    {
        return $this->belongsToMany(Enigma::class, 'user_progress')
                    ->withPivot(['completed', 'completed_at', 'hints_used', 'attempts'])
                    ->withTimestamps();
    }

    public function chapters(): BelongsToMany
    {
        return $this->belongsToMany(Chapter::class, 'user_progress_chapters')
                    ->withPivot(['completed', 'completed_at', 'points_earned', 'collected_items'])
                    ->withTimestamps();
    }

    public function chapterProgress(): HasMany
    {
        return $this->hasMany(UserProgressChapter::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
                    ->withPivot(['earned_at'])
                    ->withTimestamps();
    }

    public function getTotalPoints(): int
    {
        return 0;
    }

    public function getCompletionPercentage(): float
    {
        $totalEnigmas = Enigma::count();
        if ($totalEnigmas === 0) return 0;

        $completedEnigmas = $this->enigmas()
                                ->wherePivot('completed', true)
                                ->count();

        return ($completedEnigmas / $totalEnigmas) * 100;
    }

    public static function getLeaderboard($limit = 10)
    {
        return static::orderByDesc('points')->limit($limit)->get();
    }

    public function getRank(): int
    {
        return static::where('points', '>', $this->points)->count() + 1;
    }

    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }

    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Les rôles associés à l'utilisateur.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique.
     *
     * @param string|Role $role
     * @return bool
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('slug', $role);
        }
        return !!$role->intersect($this->roles)->count();
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique via ses rôles.
     *
     * @param string|Permission $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur est un administrateur.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }
}
