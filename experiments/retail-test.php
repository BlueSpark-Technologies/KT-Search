<?php

// set the GOOGLE_APPLICATION_CREDENTIALS env.var. to the service account credentials JSON
// install the Google Cloud Retail V2 Client: composer require google/cloud-retail

require_once __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Retail\V2\Client\CatalogServiceClient;
use Google\Cloud\Retail\V2\ListCatalogsRequest;
use Google\Cloud\Retail\V2\Client\ProductServiceClient;
use Google\Cloud\Retail\V2\ListProductsRequest;

$PROJECT = 'kt-poc-vertex';
$LOCATION = 'global';
$CATALOG = 'default_catalog';
$BRANCH = 'default_branch';

echo "Project [$PROJECT]" . PHP_EOL;

$client = new CatalogServiceClient();
$request = new ListCatalogsRequest();
$request->setParent($client->locationName($PROJECT, $LOCATION));
$catalogs = $client->listCatalogs($request);

echo 'Catalogs:' . PHP_EOL;
foreach ($catalogs as $catalog) {
    echo '- Catalog: ' . $catalog->getName() . PHP_EOL;
}

$client = new ProductServiceClient();
$request = new ListProductsRequest();
// ProductServiceClient() doesn't have a ->locationName(), use a hand-crafted location
$request->setParent("projects/$PROJECT/locations/$LOCATION/catalogs/$CATALOG/branches/$BRANCH");
$request->setPageSize(10);
// $request->setFilter('categories: ANY("Projektmanagement");');
$products = $client->listProducts($request);

echo 'Products:' . PHP_EOL;
foreach ($products->iterateAllElements() as $product) {
    echo "- Product ID: " . $product->getId() . PHP_EOL;
    echo "- Product Name: " . $product->getName() . PHP_EOL;
    echo "- Title: " . $product->getTitle() . PHP_EOL;
    echo "- Description: " . $product->getDescription() . PHP_EOL . PHP_EOL;
    break;  // avoid spam
}

?>
