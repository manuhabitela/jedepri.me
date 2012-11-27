<?php
	/**
	 * ce code est amicalement sponsorisé par la méthode rache
	 */
	define('PROD', (!empty($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'jedepri.me') !== false));
	
	require 'php/Slim/Slim.php';
	\Slim\Slim::registerAutoloader();
	require 'php/LayoutedView.php';
	require 'php/config.php';
	require 'php/ImageDatabase.php';

	session_cache_limiter(false);
	session_start();

	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME."", DB_USER, DB_PASSWORD);
	$tumblrs = array(
		'http://bonjourhamster.fr',
		'http://dailybunny.tumblr.com',
		'http://bonjourlesgeeks.com',
		'http://bonjourhumour.tumblr.com',
		'http://viktoria-f.tumblr.com'
	);
	$subreddits = array('funny', 'gif');
	$imageDB = new ImageDatabase($db, array(
		'tumblrs' => $tumblrs, 
		'subreddits' => $subreddits, 
		'imgurGallery' => true
	));
	$view = new LayoutedView();
	$app = new \Slim\Slim(array(
		'view' => $view,
		'templates.path' => './views',
		'debug' => true
	));
	define('ROOT', $app->request()->getRootUri());


	$app->hook('slim.before.dispatch', function() use ($app, $view) {
		$app->view()->appendData(array(
			'app' => $app
		));
		if ($app->request()->isAjax())
			$view::setLayout();
		else
			$view::setLayout('layout.php');
	});

	$app->get('/', function () use ($app) {
		$_SESSION['img'] = null;
		$app->render('home.php', array('simpleText' => true));
	})->name('home');

	$app->get('/jarretededeprimer/', function() use ($app, $imageDB) {
		$img = $_SESSION['img'] = $imageDB->getRandomImage();
		$app->render('home.php', array('img' => $img));
	})->name('jarretededeprimer');

	$app->get('/cayestjedeprimeplus/', function() use ($app) {
		$data = array('share' => true);
		if (!empty($_SESSION['img'])) $data['img'] = $_SESSION['img'];
		$app->render('home.php', $data);
	})->name('cayestjedeprimeplus');
	
	$app->get('/updateImages', function() use($imageDB) {
		$imageDB->updateImages();
	})->name('updateImages');

	$app->run();
?>