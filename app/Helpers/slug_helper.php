<?php

use App\Libraries\Slug;

if (!function_exists('slugify')) {
    function slugify($text)
    {
        $slug = new Slug();
        return $slug->createSlug($text);
    }
}