<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_workspace_id',
        'is_super_admin',
    ];

    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_users')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function currentWorkspace()
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    public function currentWorkspaceRecord(): ?Workspace
    {
        if ($this->current_workspace_id) {
            $workspace = $this->workspaces()
                ->where('workspaces.id', $this->current_workspace_id)
                ->first();

            if ($workspace) {
                return $workspace;
            }
        }

        return $this->workspaces()->first();
    }

    public function currentWorkspaceRole(): ?string
    {
        $workspace = $this->currentWorkspaceRecord();

        if (! $workspace) {
            return null;
        }

        $membership = $this->workspaces()
            ->where('workspaces.id', $workspace->id)
            ->first();

        return $membership?->pivot?->role;
    }

    public function canManageCurrentWorkspaceUsers(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($this->currentWorkspaceRole(), ['owner', 'admin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

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
            'is_super_admin' => 'boolean',
        ];
    }
}
