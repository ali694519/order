<?php

namespace App\Models;

use App\Models\Catalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Color extends Model
{
  use HasFactory;
  protected $fillable = ['Name', 'Quantity', 'CatalogId'];
  protected $table = 'Colors';
  protected $primaryKey = 'Id';
  public $timestamps = false;

  public function catalog()
  {
    return $this->belongsTo(Catalog::class, 'CatalogId');
  }
}
