<?php

// set the GOOGLE_APPLICATION_CREDENTIALS env.var. to the service account credentials JSON
// install the Google Cloud Retail V2 Client: composer require google/cloud-retail

require_once __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Retail\V2\ListProductsRequest;
use Google\Cloud\Retail\V2\SearchRequest;
use Google\Cloud\Retail\V2\SearchRequest\FacetSpec;
use Google\Cloud\Retail\V2\SearchRequest\FacetSpec\FacetKey;
use Google\Cloud\Retail\V2\Client\SearchServiceClient;
use Google\ApiCore\ApiException;


$PROJECT = 'kt-poc-vertex';
$LOCATION = 'global';
$CATALOG = 'default_catalog';
$SERVING = 'default_search';
$PROD_ATTRIBUTES = ['attributes.Bildungsart', 'attributes.Dauer', 'attributes.Durchfuehrungsform',
        'attributes.Kategorien', 'attributes.NaechstesStarttermin', 'attributes.Subkategorien',
        'attributes.Unterrichtsform', 'attributes.Zertifikate'];
        // Note: 'categories' can also be added, it is an array with both "Kategorien" and "Subkategorien"


function searchProductsWithFacets($query, $facets = true, $pageSize = 10, $filter = null) {
    // $facets == true => dynamic facets
    global $PROJECT, $LOCATION, $CATALOG, $SERVING, $PROD_ATTRIBUTES;
    $client = new SearchServiceClient();
    // reference: https://cloud.google.com/php/docs/reference/cloud-retail/latest/V2.Client.SearchServiceClient#_Google_Cloud_Retail_V2_Client_SearchServiceClient__search__
    $request = (new SearchRequest())
        ->setPlacement("projects/$PROJECT/locations/$LOCATION/catalogs/$CATALOG/servingConfigs/$SERVING")
        ->setVisitorId('314159265')
        ->setQuery($query)
        ->setPageSize($pageSize);
    if ($filter) {
        $request->setFilter($filter);
    }
    // ->setOrderBy('title asc');

    if ($facets) {
        if ($facets === true) {
            $facets = [];
            foreach ($PROD_ATTRIBUTES as $attribute) {
                $facetKey = (new FacetKey())
                    ->setKey($attribute);
                /*
                Note: using custom attributes as facets shouldn't work (per the documentation) without using
                a FacetKey.query (see "Allowed facet keys when FacetKey.query is not specified: [...]"
                @ https://cloud.google.com/retail/docs/reference/rest/v2/FacetSpec#facetkey ),
                but it does.
                */
                $facet = new FacetSpec();
                $facet->setFacetKey($facetKey)->setLimit(20);
                // get up to 20 facet values per facet
                $facets[] = $facet;
            }
            $request->setFacetSpecs($facets);
        } else {

        }
    }

    $results = ['products' => [], 'facets' => []];
    try {
        $response = $client->search($request);
        // products
        foreach ($response->iteratePages() as $page) {
            foreach ($page as $result) {
                $results['products'][] = [
                    'id' => $result->getId(),
                    'name' => $result->getProduct()->getName()
                    // only id and name are directly retrievable
                    // ref: https://cloud.google.com/php/docs/reference/cloud-retail/2.1.1/V2.SearchResponse.SearchResult#_Google_Cloud_Retail_V2_SearchResponse_SearchResult__getProduct__
                ];
            }
        }
        // facets
        $searchResponse = $response->getPage()->getResponseObject();
        foreach ($searchResponse->getFacets() as $facet) {
            $values = [];
            foreach ($facet->getValues() as $value) {
                $values[] = [
                    'name' => $value->getValue(),
                    'count' => $value->getCount()
                ];
            };
            $facetKey = $facet->getKey();
            $results['facets'][] = [
                'key' => $facetKey,
                'values' => $values
            ];
        }
        return $results;
    } catch (ApiException $e) {
        echo "Failed retrieving products: {$e->getMessage()}\n";
        return $results;
    } finally {
        $client->close();
    }
}


$query = $_REQUEST['query'] ?? 'PeopleCert Projektmanagement';

$results = searchProductsWithFacets(query: $query);

if ($results['facets']) {
    echo 'Got ' . count($results['facets']) . " applicable facets (matching products in parentheses):\n\n";
    foreach ($results['facets'] as $index => $facet) {
        echo "Facet " . ($index + 1) . ": {$facet['key']}\n";
        echo "Values:\n";
        foreach ($facet['values'] as $entry) {
            echo "- {$entry['name']} ({$entry['count']})\n";
        }
        echo "\n";
    }

    // repeat the query, use the first facet / facet value as a filter:
    $facet = $results['facets'][0];
    $filter = "{$facet['key']}: ANY(\"{$facet['values'][0]['name']}\")";
    echo "Repeating the query using the filter {$filter}...\n";
    $results = searchProductsWithFacets(query: $query, facets: $results['facets'], filter: $filter);

    if ($results['products']) {
        echo 'Found ' . count($results['products']) . " products:\n";
        foreach ($results['products'] as $index => $product) {
            echo "\nProduct " . ($index + 1) . ":\n";
            echo "ID: {$product['id']}\n";
            echo "Name: {$product['name']}\n";
        }
    }
}

?>
