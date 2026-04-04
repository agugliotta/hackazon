<?php
namespace App\Models;

class CustomerAddress extends BaseModel
{
    protected $table = 'tbl_customer_address';
    public $timestamps = false;
    protected $fillable = ['full_name','address_line_1','address_line_2','city','region','zip','country_id','phone','customer_id'];
}
