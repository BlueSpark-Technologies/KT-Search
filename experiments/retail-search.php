<?php

// set the GOOGLE_APPLICATION_CREDENTIALS env.var. to the service account credentials JSON
// install the Google Cloud Retail V2 Client: composer require google/cloud-retail

require_once __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Retail\V2\ListProductsRequest;
use Google\Cloud\Retail\V2\SearchRequest;
use Google\Cloud\Retail\V2\Client\SearchServiceClient;
use Google\ApiCore\ApiException;

$PROJECT = 'kt-poc-vertex';
$LOCATION = 'global';
$CATALOG = 'default_catalog';
$SERVING = 'default_search';


function searchProducts($query, $pageSize = 10) {
    global $PROJECT, $LOCATION, $CATALOG, $SERVING;
    $client = new SearchServiceClient();
    // reference: https://cloud.google.com/php/docs/reference/cloud-retail/latest/V2.Client.SearchServiceClient#_Google_Cloud_Retail_V2_Client_SearchServiceClient__search__
    $request = (new SearchRequest())
        ->setPlacement("projects/$PROJECT/locations/$LOCATION/catalogs/$CATALOG/servingConfigs/$SERVING")
        ->setVisitorId('314159265')
        ->setQuery($query)
        ->setPageSize($pageSize);
    // ->setFilter('categories:"Projektmanagement" AND Unterrichtsform:"Vollzeit"');
    // ->setOrderBy('title asc');

    try {
        $response = $client->search($request);

        $results = [];
        foreach ($response->iteratePages() as $page) {
            foreach ($page as $result) {
                $results[] = [
                    'id' => $result->getId(),
                    'name' => $result->getProduct()->getName()
                    // only id and name are directly retrievable
                    // ref: https://cloud.google.com/php/docs/reference/cloud-retail/2.1.1/V2.SearchResponse.SearchResult#_Google_Cloud_Retail_V2_SearchResponse_SearchResult__getProduct__
                ];
            }
        }
        return $results;
    } catch (ApiException $e) {
        echo 'Failed retrieving products: ' . $e->getMessage() . PHP_EOL;
        return null;
    } finally {
        $client->close();
    }
}


$query = isset($_REQUEST['query']) ? $_REQUEST['query'] : 'PeopleCert Projektmanagement';
$results = searchProducts(query: $query);
if ($results) {
    echo "Found " . count($results) . " products\n";
    foreach ($results as $index => $product) {
        echo "\nProduct " . ($index + 1) . ":\n";
        echo "ID: " . $product['id'] . "\n";
        echo "Name: " . $product['name'] . "\n";
    }
}

?>
