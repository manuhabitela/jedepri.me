<?php
	/**
	 * ce code est amicalement sponsorisé par la méthode rache
	 */
	define('PROD', (!empty($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'jedepri.me') !== false));
	if (PROD)
		error_reporting(0);
	
	require 'config/app.php';
	require 'config/database.php';
	require 'vendor/autoload.php';
	require 'lib/JedeprimeItemDatabase.php';

	$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME."", DB_USER, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
	$db = new JedeprimeItemDatabase($pdo, SOURCES_FILE, intval(!PROD));
	$app = new \Slim\Slim(array(
		'templates.path' => './views',
		'debug' => intval(!PROD)
	));
	define('HOST', $app->request()->getUrl());

	$app->hook('slim.before.dispatch', function() use ($app) {
		$app->view()->appendData(array(
			'app' => $app
		));
	});

	$app->get('/', function() use ($app, $db) {
		$nextImgSlug = $db->getRandomItemSlug();
		$app->render('home.php', array('simpleText' => true, 'nextImgSlug' => $nextImgSlug));
	})->name('home');

	$app->get('/jarretededeprimer/:slug', function($slug) use ($app, $db) {
		//on vérifie le cookie contenant les ids d'images déjà vues (c'est un tableau d'ids)
		$seenImgsCookie = $app->getCookie('seen_img_ids');
		if ($seenImgsCookie) {
			$seenImgIds = array_filter(explode(';', htmlspecialchars($seenImgsCookie)));
			$idsOk = true;
			foreach ($seenImgIds as $id) { if (!is_numeric($id)) { $idsOk = false; break; } }
		}
		if (empty($seenImgIds) || empty($idsOk)) $seenImgIds = array();
		$img = $db->getItemBySlug($slug);
		if (!in_array($img['id'], $seenImgIds))
			$seenImgIds[]= $img['id'];
		$nextImgSlug = $db->getRandomItemSlug($seenImgIds);
		
		$app->setCookie('seen_img_ids', implode(';', $seenImgIds), time()+60*60*24*3);

		$app->render('home.php', array('img' => $img, 'nextImgSlug' => $nextImgSlug));
	})->name('jarretededeprimer');

	$app->get('/jarretededeprimer/', function() use ($app, $db) {
		$app->redirect('/jarretededeprimer/'.$db->getRandomItemSlug());
	})->name('jarretededeprimer-empty');

	$app->get('/cayestjedeprimeplus/:slug', function($slug) use ($app, $db) {
		$data = array('share' => true, 'img' => $db->getItemBySlug($slug));
		$app->render('home.php', $data);
	})->name('cayestjedeprimeplus');

	$app->get('/cayestjedeprimeplus/', function() use ($app, $db) {
		$app->render('home.php', array('share' => true));
	})->name('cayestjedeprimeplus-empty');
	
	$app->get('/updateImages', function() use($db) {
		$db->fillDB();
	})->name('updateImages');

	$app->get('/random', function() use ($app, $db) {
		echo HOST.'/jarretededeprimer/'.$db->getRandomItemSlug();
	});

	$app->get('/:slug', function($slug) use($app) {
		$app->redirect('/jarretededeprimer/'.$slug, 301);
	});

	$app->run();
?>