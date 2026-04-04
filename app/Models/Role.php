<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends BaseModel
{
    protected $table = 'tbl_roles';
    public $timestamps = false;
    protected $fillable = ['name','removable'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tbl_users_roles', 'role_id', 'user_id');
    }
}
