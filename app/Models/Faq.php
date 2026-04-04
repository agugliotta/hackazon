<?php
namespace App\Models;

class Faq extends BaseModel
{
    protected $table = 'tbl_faq';
    protected $primaryKey = 'faqID';
    public $timestamps = false;
    protected $fillable = ['question','answer','email'];

    public static function getEntries()
    {
        return static::all();
    }

    public static function create(array $post)
    {
        $faq = new static();
        $faq->email = $post['userEmail'];
        $faq->question = $post['userQuestion'];
        $faq->answer = 'Processing...';
        $faq->save();
        return $faq;
    }
}
