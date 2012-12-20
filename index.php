<?php
	/**
	 * ce code est amicalement sponsorisé par la méthode rache
	 */
	define('PROD', (!empty($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'jedepri.me') !== false));
	if (PROD)
		error_reporting(0);
	
	require 'php/Slim/Slim.php';
	\Slim\Slim::registerAutoloader();
	require 'php/config.php';
	require 'php/ImageDatabase.php';

	session_cache_limiter(false);
	session_start();
	
	if (empty($_SESSION['seen_img_ids']))
		$_SESSION['seen_img_ids'] = array();

	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME."", DB_USER, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
	$imageDB = new ImageDatabase($db, realpath('sources.json'), intval(!PROD));
	$app = new \Slim\Slim(array(
		'templates.path' => './views',
		'debug' => intval(!PROD)
	));
	define('HOST', strpos($app->request()->getHost(), 'http://') === false ? 'http://'.$app->request()->getHost() : $app->request()->getHost());

	$app->hook('slim.before.dispatch', function() use ($app, $view) {
		$app->view()->appendData(array(
			'app' => $app
		));
	});

	$app->get('/', function() use ($app, $imageDB) {
		$_SESSION['img'] = null;
		$nextImgSlug = $imageDB->getRandomImageSlug();
		$app->render('home.php', array('simpleText' => true, 'nextImgSlug' => $nextImgSlug));
	})->name('home');

	$app->get('/jarretededeprimer/:slug', function($slug) use ($app, $imageDB) {
		$img = $_SESSION['img'] = $imageDB->getImageBySlug($slug);
		if (!in_array($img['id'], $_SESSION['seen_img_ids']))
			$_SESSION['seen_img_ids'][]= $img['id'];
		$nextImgSlug = $imageDB->getRandomImageSlug($_SESSION['seen_img_ids']);
		$app->render('home.php', array('img' => $img, 'nextImgSlug' => $nextImgSlug));
	})->name('jarretededeprimer');

	$app->get('/jarretededeprimer/', function() use ($app, $imageDB) {
		$app->redirect('/jarretededeprimer/'.$imageDB->getRandomImageSlug());
	})->name('jarretededeprimer-empty');

	$app->get('/cayestjedeprimeplus/:slug', function($slug) use ($app, $imageDB) {
		$data = array('share' => true, 'img' => $imageDB->getImageBySlug($slug));
		$app->render('home.php', $data);
	})->name('cayestjedeprimeplus');

	$app->get('/cayestjedeprimeplus/', function() use ($app, $imageDB) {
		$data = array('share' => true);
		if (!empty($_SESSION['img']))
			$app->redirect('/cayestjedeprimeplus/'.$imageDB->getImageSlugById($_SESSION['img']['id']));
		$app->render('home.php', $data);
	})->name('cayestjedeprimeplus-empty');
	
	$app->get('/updateImages', function() use($imageDB) {
		$imageDB->fillDB();
	})->name('updateImages');

	$app->get('/random', function() use ($app, $imageDB) {
		echo HOST.'/jarretededeprimer/'.$imageDB->getRandomImageSlug();
	});

	$app->get('/:slug', function($slug) use($app) {
		$app->redirect('/jarretededeprimer/'.$slug, 301);
	});

	$app->run();
?>