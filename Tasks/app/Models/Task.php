<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $casts = [ 'created_at' => 'datetime', 'completed_at' => 'datetime' ];
    protected $fillable = ['parent_id', 'user_id', 'title', 'priority', 'status', 'description', 'completed_at'];

    public function scopeGetById($query, $id): Builder
    {
        return $query->where($this->table . '.id', $id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function scopeGetByUserId($query, int $userId = null): Builder
    {
        if (!empty($userId)) {
            $query->where($this->table . '.user_id', $userId);
        }

        return $query;
    }

    public function scopeGetByStatus($query, string $status = null): Builder
    {
        if (!empty($status)) {
            $query->where($this->table . '.status', $status);
        }

        return $query;
    }

    public function scopeGetByPriority($query, string $priority = null): Builder
    {
        if (!empty($priority)) {
            $query->where($this->table . '.priority', $priority);
        }

        return $query;
    }

    public function scopeGetBySearch($query, ?string $search = null, bool $partial = false): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where($this->table . '.title', (!$partial ? '=' : 'like'), ($partial ? '%' : '') . $search . ($partial ? '%' : ''))
            ->orWhere($this->table . '.description', (!$partial ? '=' : 'like'), ($partial ? '%' : '') . $search . ($partial ? '%' : ''));
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id', 'id');
    }

    public function scopeGetByParentId($query, int $parentId = null): Builder
    {
        if (!empty($parentId)) {
            $query->where($this->table . '.parent_id', $parentId);
        }

        return $query;
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'id', 'parent_id');
    }
}
