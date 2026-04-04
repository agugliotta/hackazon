<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enquiry extends BaseModel
{
    protected $table = 'tbl_enquiries';
    public $timestamps = false;
    protected $fillable = ['created_by','assigned_to','title','description','status','created_on','updated_on'];

    public function messages(): HasMany { return $this->hasMany(EnquiryMessage::class, 'enquiry_id'); }
    public function createdByUser(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function assignedToUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}
