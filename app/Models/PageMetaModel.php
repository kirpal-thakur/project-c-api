<?php

namespace App\Models;

use CodeIgniter\Model;

class PageMetaModel extends Model
{
    protected $table            = 'page_meta';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'page_id',
        'meta_key',
        'meta_value',
        'lang_id',
        'created_at',
        'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function savePageMeta($data, $conditions = array())
    {
        $logger = \Config\Services::logger();
        $logMessage = date('Y-m-d H:i:s') . " [INFO] Data Recived from Frontend: " . json_encode($data) . PHP_EOL;
        file_put_contents(WRITEPATH . 'logs/entry_in_db.log', $logMessage, FILE_APPEND);
        if (isset($data['page_id']) && !empty($data['page_id'])) {
            $query = $this->db->query("SELECT * FROM pages where id = " . $data['page_id']);
            $exist =  $query->getRowArray();
            // echo '<pre>'; print_r($exist); die;
            if (!empty($exist) && !empty($exist['language'])) {
                $data['lang_id'] = $exist['language'];
            }
        }
        // echo '<pre>'; print_r($data); die;
        $existingEntry = $this->where([
            'meta_key' => $data['meta_key'],
            'lang_id' => $data['lang_id'],
            'page_id' => $data['page_id']
        ])->first();
        // echo '<pre>'; print_r($existingEntry); die;

        if ($existingEntry) {
            $logger->info('Data updated successfully in the database.');
            // Write to a custom log file
            $logMessage = date('Y-m-d H:i:s') . " [INFO] Data updated successfully saved: " . json_encode($data) . PHP_EOL;
            file_put_contents(WRITEPATH . 'logs/entry_in_db.log', $logMessage, FILE_APPEND);
            return $this->update($existingEntry['id'], $data);
        } else {
            if (isset($data['page_id']) && !empty($data['page_id'])) {
                $query = $this->db->query("SELECT * FROM pages where id = " . $data['page_id']);
                $exist =  $query->getRowArray();
                if (!empty($exist) && !empty($exist['language'])) {
                    $data['lang_id'] = $exist['language'];
                }
               
            }
            
            $logger->info('Data successfully saved to the database.');
            // Write to a custom log file
            $logMessage = date('Y-m-d H:i:s') . " [INFO] Data successfully saved: " . json_encode($data) . PHP_EOL;
            file_put_contents(WRITEPATH . 'logs/entry_in_db.log', $logMessage, FILE_APPEND);
            return $this->insert($data);
            // $dbError = $this->db->error();
            // print_r($dbError); die;
        }
    }

    public function getFullPageMetaData($pageId, $langId)
    {
        $dataArr = $this->where('page_id', $pageId)->where('lang_id', $langId)->findAll();
        return $dataArr;
    }

    public function getByMetaKey($pageId, $langId, $meta_key)
    {
        $dataArr = $this->where('page_id', $pageId)->where('lang_id', $langId)->where('meta_key', $meta_key)->first();
        return $dataArr;
    }

    public function getPageIdBySlug($slug)
    {
        // $this->setTable('pages');
        return $this->select('id')->where('slug', $slug)->first(); // use `first()` to get a single result
    }

    public function getFullPageMetaDataById($pageId)
    {
        $dataArr = $this->where('page_id', $pageId)->findAll();
        return $dataArr;
    }
}
