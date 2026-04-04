<?php

namespace App\Http\Controllers;

/**
 * Class BlogController
 * @package App\Http\Controllers
 */
class BlogController extends PageController
{
    public function index()
    {
        return $this->view('blog', ['message' => 'Index page']);
    }

    public function post()
    {
        return $this->view('blog_post', ['message' => 'Index page']);
    }
}
