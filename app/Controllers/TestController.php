<?php

namespace App\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class TestController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        // $this->db = \Config\Database::connect();
        // $this->userNationalityModel = new UserNationalityModel();
        // $this->clubPlayerModel = new ClubPlayerModel();
        // $this->playerPositionModel = new PlayerPositionModel();
        // $this->clubModel = new ClubModel();
        // $this->userIdentityModel = new UserIdentityModel();
        // $this->ownershipTransferDataModel = new OwnershipTransferDataModel();
        // $this->favoriteModel = new FavoriteModel();

        helper('test');

    }


    public function getUsersTesting()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);

        $whereClause = [];
        $metaQuery = [];
        $orderBy =  $order = $search = '';
        $noLimit =  $countOnly = FALSE;


        $limit = $searchParams['limit'] ?? PER_PAGE;
        $offset = $searchParams['offset'] ?? 0;

        if (isset($searchParams['whereClause']) && count($searchParams['whereClause']) > 0) {
            $whereClause = $searchParams['whereClause'];
        }

        if (isset($searchParams['metaQuery']) && count($searchParams['metaQuery']) > 0) {
            $metaQuery = $searchParams['metaQuery'];
        }

        if (isset($searchParams['search']) && !empty($searchParams['search'])) {
            $search = $searchParams['search'];
        }

        if (isset($searchParams['orderBy']) && !empty($searchParams['orderBy'])) {
            $orderBy = $searchParams['orderBy'];
        }

        if (isset($searchParams['order']) && !empty($searchParams['order'])) {
            $order = $searchParams['order'];
        }

        if (isset($searchParams['noLimit']) && !empty($searchParams['noLimit'])) {
            $noLimit = $searchParams['noLimit'];
        }

        if (isset($searchParams['countOnly']) && !empty($searchParams['countOnly'])) {
            $countOnly = $searchParams['countOnly'];
        }

        //$users = getPlayers($whereClause, $metaQuery, $search, $orderBy, $order, $limit, $offset, $noLimit, $countOnly);
        $lang_id = 2;
        $users = getPlayersTesting($whereClause, $metaQuery, $search, $orderBy, $order, $limit, $offset, $noLimit, $lang_id);
        //pr($users); exit;

        if ($users) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'totalCount'    => $users['totalCount'],
                    'userData'      => $users['users']
                ]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }

        // $activity_data = [
        //     'user_id'               => auth()->id(),
        //     'activity_type_id'      => 9,      // viewed
        //     'activity'              => 'viewed user',
        //     'ip'                    => $this->request->getIPAddress()
        // ];
        // createActivityLog($activity_data);

        return $this->respondCreated($response);
    }
}