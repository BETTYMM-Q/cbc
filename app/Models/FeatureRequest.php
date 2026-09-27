<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
class FeatureRequest extends Model { use BelongsToSchool; protected $fillable=['requested_by','title','description','status','admin_response','responded_by']; public function requester(){return $this->belongsTo(User::class,'requested_by');} }
