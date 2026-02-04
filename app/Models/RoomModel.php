<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomModel extends Model
{
    protected $table      = 'rooms';
    protected $primaryKey = 'id';

    protected $returnType     = 'array'; // Define return type as an array
    protected $useSoftDeletes = true; // Enable soft deletes

    // Allow the following fields to be used in CRUD operations
    protected $allowedFields = ['name', 'slug', 'image', 'type'];

    // Set up validation rules
    protected $validationRules = [
        'name' => 'required|string|max_length[255]',
        'slug' => 'string|max_length[255]|is_unique[rooms.slug]',
        'image' => 'required', // Optional image field
        'type' => 'required'
    ];

    // Set validation messages
    protected $validationMessages = [
        'name' => [
            'required' => 'Room name is required.',
            'string'   => 'Room name must be a string.',
            'max_length' => 'Room name can be up to 255 characters.',
        ],
        'slug' => [
            'required' => 'Room slug is required.',
            'string'   => 'Room slug must be a string.',
            'max_length' => 'Room slug can be up to 255 characters.',
            'is_unique' => 'This slug already exists. Please choose a different one.',
        ],
        'image' => [
            'required' => 'Please upload a image.',
        ],
    ];

    // Automatically handle timestamps if needed
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Optionally, you can define custom methods if needed for additional logic
}
