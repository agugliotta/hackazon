<?php
namespace App\Models;

class Coupon extends BaseModel
{
    protected $table = 'tbl_coupons';
    public $timestamps = false;
    protected $fillable = ['coupon','discount'];
}
