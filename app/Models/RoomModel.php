<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomModel extends Model
{
    protected $table      = 'rooms';
    protected $primaryKey = 'id';

    protected $returnType     = 'array'; // Define return type as an array
    protected $useSoftDeletes = false; // No soft deletes for rooms

    // Allow the following fields to be used in CRUD operations
    protected $allowedFields = ['name', 'image', 'created_at', 'updated_at'];

    // Set up validation rules
    protected $validationRules = [
        'name' => 'required|string|max_length[255]',
        'image' => 'required', // Optional image field
    ];

    // Set validation messages
    protected $validationMessages = [
        'name' => [
            'required' => 'Room name is required.',
            'string'   => 'Room name must be a string.',
            'max_length' => 'Room name can be up to 255 characters.',
        ],
        'image' => [
            'required' => 'Please upload a image.',
        ],
    ];

    // Automatically handle timestamps if needed
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Optionally, you can define custom methods if needed for additional logic
}
