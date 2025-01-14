<?php

namespace App\Models;

use CodeIgniter\Model;

class PageModel extends Model
{
    protected $table            = 'pages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'title',
        'content',
        'featured_image',
        'language',
        'status',
        'slug',
        'ad_types',
        'page_type',
        'meta_title',
        'meta_description',
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

    public function getPageIdBySlug($slug)
    {
        // $this->setTable('pages');
        $pageArr = $this->select('*')->where('slug', $slug)->first(); // use `first()` to get a single result
        //  echo $this->db->getLastQuery(); die;
        // echo '<pre>'; print_r($pageArr); die;
        return $pageArr;
    }
    public function getPageIdByID($id)
    {
        // $this->setTable('pages');
        $pageArr = $this->select('*')->where('id', $id)->first(); // use `first()` to get a single result
        //  echo $this->db->getLastQuery(); die;
        // echo '<pre>'; print_r($pageArr); die;
        return $pageArr;
    }
    public function getPageIdByPageType($type,$lang_id){
        $pageArr = $this->select('*')->where('page_type', $type)->where('language', $lang_id)->first();
        return $pageArr;
    }
}