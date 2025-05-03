<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArMod extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ar_mods';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'author',
        'version',
        'server_id',
        'preview_url',
        'tags',
        'description',
        'is_installed',
        'meta',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'tags' => 'array',
        'meta' => 'array',
        'is_installed' => 'boolean',
    ];

    /**
     * Get the server that owns this mod.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Scope a query to only include mods for a specific server.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|Server $server
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForServer($query, $server)
    {
        $serverId = $server instanceof Server ? $server->id : $server;
        return $query->where('server_id', $serverId);
    }

    /**
     * Check if a user can view this mod
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function canBeViewedBy(User $user): bool
    {
        // User can view if they are admin or have access to this server
        return $user->isAdmin() || $user->servers()->where('servers.id', $this->server_id)->exists();
    }
} 