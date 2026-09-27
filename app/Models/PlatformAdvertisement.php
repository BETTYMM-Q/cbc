<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PlatformAdvertisement extends Model { protected $fillable=['created_by','title','message','media_type','media_path','audiences','skip_after_seconds','starts_at','ends_at','is_active']; protected $casts=['audiences'=>'array','starts_at'=>'datetime','ends_at'=>'datetime','is_active'=>'boolean']; public function scopeCurrentlyActive($q){return $q->where('is_active',true)->where(fn($x)=>$x->whereNull('starts_at')->orWhere('starts_at','<=',now()))->where(fn($x)=>$x->whereNull('ends_at')->orWhere('ends_at','>=',now()));} }
