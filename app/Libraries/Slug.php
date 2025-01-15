<?php

namespace App\Libraries;

class Slug
{
    public function createSlug($text)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
        return $slug;
    }
}