<?php
/**
 * Copyright (C) 2009-2012 Couchbase, Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALING
 * IN THE SOFTWARE.
 */

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;

/**
 * Config Block.
 *
 * To keep all settings (not many) organized in one place, they are defined
 * here as constants. They could be done inline as well, but keeping them in
 * one place makes it better organized and easier to refactor.
 */
define("SILEX_DEBUG", true);
define("COUCHBASE_CONNSTR", "http://127.0.0.1");
define("COUCHBASE_BUCKET", "beer-sample");
define("COUCHBASE_PASSWORD", "");

define("INDEX_DISPLAY_LIMIT", 20);

/**
 * Init Block.
 *
 * This block requires the autoloader and initializes the Silex Application.
 * It also connects to our Couchbase Cluster and registeres the template
 * engine (Twig).
 */

// Autoloader
require_once __DIR__.'/vendor/autoload.php';

// Silex-Application Bootstrap
$app = new Application();
$app['debug'] = SILEX_DEBUG;

// Connecting to Couchbase
$cluster = new CouchbaseCluster(COUCHBASE_CONNSTR);
$cb = $cluster->openBucket(COUCHBASE_BUCKET, COUCHBASE_PASSWORD);

// Register the Template Engine
$app->register(new TwigServiceProvider(), array(
	'twig.path' => __DIR__.'/templates'
));

/**
 * Action Block.
 *
 * From here on all actions are defined as simple closures. They render view
 * templates or direct responses as needed.
 */

// Show the Welcome Page (GET /)
$app->get('/', function() use ($app, $cb) {
    return $app['twig']->render('welcome.twig.html');
});

// List all Beers (GET /beers)
$app->get('/beers', function() use ($app, $cb) {
    $results = $cb->query(
        CouchbaseViewQuery::from("beer", "by_name")
            ->limit(INDEX_DISPLAY_LIMIT)
    );

    $beers = array();
    foreach($results['rows'] as $row) {
        $doc = $cb->get($row['id']);
        if($doc) {
            $doc = json_decode($doc->value, true);
            $beers[] = array(
                'name' => $doc['name'],
                'brewery' => $doc['brewery_id'],
                'id' => $row['id']
            );
        }

    }

    return $app['twig']->render('beers/index.twig.html', compact('beers'));
});

// List all Breweries (GET /breweries)
$app->get('/breweries', function() use ($app, $cb) {
    $results = $cb->query(
        CouchbaseViewQuery::from("brewery", "by_name")
            ->limit(INDEX_DISPLAY_LIMIT)
    );

    $breweries = array();
    foreach($results['rows'] as $row) {
        $doc = $cb->get($row['id'])->value;
        if($doc) {
            $breweries[] = array(
                'name' => $row['key'],
                'id' => $row['id']
            );
        }

    }

    return $app['twig']->render(
        'breweries/index.twig.html',
        compact('breweries')
    );
});


// Show a beer (GET /beers/show/<ID>)
$app->get('/beers/show/{id}', function($id) use ($app, $cb) {
    $beer = $cb->get($id);
    if($beer) {
       $beer = json_decode($beer->value, true);
       $beer['id'] = $id;
    } else {
       return $app->redirect('/beers');
    }

    return $app['twig']->render(
        'beers/show.twig.html',
        compact('beer')
    );
});

// Show a brewery (GET /breweries/show/<ID>)
$app->get('/breweries/show/{id}', function($id) use ($app, $cb) {
    $brewery = $cb->get($id);
    if($brewery) {
       $brewery = json_decode($brewery->value, true);
       $brewery['id'] = $id;
    } else {
       return $app->redirect('/breweries');
    }

    return $app['twig']->render(
        'breweries/show.twig.html',
        compact('brewery')
    );
});

// Delete Beer (GET /beers/delete/<ID>)
$app->get('/beers/delete/{id}', function($id) use ($app, $cb) {
    $cb->delete($id);
    return $app->redirect('/beersample-php/beers');
});

// Delete Brewery (GET /breweries/delete/<ID>)
$app->get('/breweries/delete/{id}', function($id) use ($app, $cb) {
    $cb->delete($id);
    return $app->redirect('/beersample-php/breweries');
});

// Store submitted Beer Data (POST /beers/edit/<ID>)
$app->post('/beers/edit/{id}', function(Request $request, $id) use ($app, $cb) {
    $data = $request->request;

    $newbeer = array();
    foreach($data as $name => $value) {
        $name = str_replace('beer_', '', $name);
        $newbeer[$name] = $value;
    }

    $newbeer['type'] = 'beer';
    $cb->set($id, json_encode($newbeer));

    return $app->redirect('/beersample-php/beers/show/' . $id);
});

// Show Beer Form
$app->get('/beers/edit/{id}', function($id) use ($app, $cb) {
    $beer = $cb->get($id);
    if($beer) {
       $beer = json_decode($beer->value, true);
       $beer['id'] = $id;
    } else {
       return $app->redirect('/beers');
    }

    return $app['twig']->render(
        'beers/edit.twig.html',
        compact('beer')
    );
});

// Show Brewery Form
$app->get('/breweries/edit/{id}', function($id) use ($app, $cb) {
    $brewery = $cb->get($id);
    if($brewery) {
       $brewery = json_decode($brewery->value, true);
       $brewery['id'] = $id;
    } else {
       return $app->redirect('/breweries');
    }

    return $app['twig']->render(
        'breweries/edit.twig.html',
        compact('brewery')
    );
});

// Store submitted Brewery Data (POST /breweries/edit/<ID>)
$app->post('/breweries/edit/{id}', function(Request $request, $id) use ($app, $cb) {
    $data = $request->request;

    $newbrewery = array();
    foreach($data as $name => $value) {
        $name = str_replace('brewery_', '', $name);
        $newbrewery[$name] = $value;
    }

    $newbrewery['type'] = 'brewery';
    $cb->upsert($id, json_encode($newbrewery));

    return $app->redirect('/beersample-php/breweries/show/' . $id);
});

// Search via AJAX for beers (GET /beers/search)
$app->get('/beers/search', function(Request $request) use ($app, $cb) {
    $input = strtolower($request->query->get('value'));

    // Query the view
    $q = CouchbaseViewQuery::from('beer', 'by_name')
        ->limit(INDEX_DISPLAY_LIMIT)
        ->range($input, $input . '\uefff');
    $results = $cb->query($q);

    $beers = array();
    foreach($results['rows'] as $row) {
        $doc = $cb->get($row['id']);
        if($doc) {
            $doc = json_decode($doc->value, true);
            $beers[] = array(
                'name' => $doc['name'],
                'brewery' => $doc['brewery_id'],
                'id' => $row['id']
            );
        }

    }

    return $app->json($beers, 200);
});

// Search via AJAX for breweries (GET /breweries/search)
$app->get('/breweries/search', function(Request $request) use ($app, $cb) {
    $input = strtolower($request->query->get('value'));

    // Define the Query options
    // Query the view
    $q = CouchbaseViewQuery::from('brewery', 'by_name')
        ->limit(INDEX_DISPLAY_LIMIT)
        ->range($input, $input . '\uefff');
    $results = $cb->query($q);

    $breweries = array();
    foreach($results['rows'] as $row) {
        $doc = $cb->get($row['id']);
        if($doc) {
            $doc = json_decode($doc->value, true);
            $breweries[] = array(
                'name' => $doc['name'],
                'id' => $row['id']
            );
        }

    }

    return $app->json($breweries, 200);
});

// Run the Application
$app->run();
?>
