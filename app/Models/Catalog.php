<?php

namespace App\Models;

use App\Models\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Catalog extends Model
{
    use HasFactory;
    protected $fillable = ['Name', 'Price'];
    protected $table = 'Catalogs';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    public function quantities()
    {
        return $this->hasMany(Color::class, 'catalogId');
    }

}
