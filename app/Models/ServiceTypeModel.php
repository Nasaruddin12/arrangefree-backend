<?php

namespace App\Models;

use CodeIgniter\Model;

class ServiceTypeModel extends Model
{
    protected $table      = 'service_types'; // Table name
    protected $primaryKey = 'id'; // Primary key

    protected $allowedFields = ['name', 'slug', 'image', 'status']; // Columns that can be modified

    protected $useTimestamps = true; // Enables created_at and updated_at auto-fill
    protected $useSoftDeletes = true; // Enable soft deletes

    // Set up validation rules
    protected $validationRules = [
        'name' => 'required|string|max_length[255]',
        'slug' => 'string|max_length[255]|is_unique[service_types.slug]',
        'image' => 'required',
        'status' => 'required'
    ];

    // Set validation messages
    protected $validationMessages = [
        'name' => [
            'required' => 'Service type name is required.',
            'string'   => 'Service type name must be a string.',
            'max_length' => 'Service type name can be up to 255 characters.',
        ],
        'slug' => [
            'string'   => 'Service type slug must be a string.',
            'max_length' => 'Service type slug can be up to 255 characters.',
            'is_unique' => 'This slug already exists. Please choose a different one.',
        ],
        'image' => [
            'required' => 'Please upload an image.',
        ],
        'status' => [
            'required' => 'Status is required.',
        ],
    ];
}
