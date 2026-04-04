<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['workspace_id', 'name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
