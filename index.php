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

	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME."", DB_USER, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
	$imageDB = new ImageDatabase($db, realpath('sources.json'), 1);
	$view = new LayoutedView();
	$app = new \Slim\Slim(array(
		'view' => $view,
		'templates.path' => './views',
		'debug' => true
	));
	define('HOST', strpos($app->request()->getHost(), 'http://') === false ? 'http://'.$app->request()->getHost() : $app->request()->getHost());

	$app->hook('slim.before.dispatch', function() use ($app, $view) {
		$app->view()->appendData(array(
			'app' => $app
		));
		if ($app->request()->isAjax())
			$view::setLayout();
		else
			$view::setLayout('layout.php');
	});

	$app->get('/', function () use ($app, $imageDB) {
		$_SESSION['img'] = null;
		$nextImgSlug = $imageDB->getRandomImageSlug();
		$app->render('home.php', array('simpleText' => true, 'nextImgSlug' => $nextImgSlug));
	})->name('home');

	$app->get('/jarretededeprimer/:slug', function($slug) use ($app, $imageDB) {
		$img = $_SESSION['img'] = $imageDB->getImageBySlug($slug);
		if (empty($_SESSION['seen_img_ids']) || !in_array($img['id'], $_SESSION['seen_img_ids']))
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

	$app->run();
?>