<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnquiryMessage extends BaseModel
{
    protected $table = 'tbl_enquiry_messages';
    public $timestamps = false;
    protected $fillable = ['enquiry_id','author_id','message','created_on'];

    public function enquiry(): BelongsTo { return $this->belongsTo(Enquiry::class, 'enquiry_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
}
