<?php

namespace App\Models;

use CodeIgniter\Model;

class BlogModel extends Model
{
    protected $table            = 'blogs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'title',
        'content',
        'language',
        'featured_image',
        'status',
        'slug',
        'meta_title',
        'meta_description',
        'attachments',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function Testting()
    {
        $builder = $this->db->table('pages');
    
        // Join the 'languages' table with alias 'l' and correct condition
        $builder->join('languages l', 'l.id = pages.language');
        
        // Select fields (all from 'pages', and alias 'l.language' as 'lang_language')
        $builder->select('pages.*, l.language as lang_language');
        
        // Execute the query and get the result
        $query = $builder->get();
        
        // Fetch the result as an array of objects
        $result = $query->getResult();
        
        // Return the result
        return $result;
    }
}
