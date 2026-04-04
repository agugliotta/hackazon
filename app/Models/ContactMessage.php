<?php
namespace App\Models;

class ContactMessage extends BaseModel
{
    protected $table = 'tbl_contact_messages';
    public $timestamps = false;
    protected $fillable = ['name','email','phone','message','customer_id'];
}
