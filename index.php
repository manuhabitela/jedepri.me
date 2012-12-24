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
	require 'lib/ImageDatabase.php';

	$db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME."", DB_USER, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
	$imageDB = new ImageDatabase($db, SOURCES_FILE, intval(!PROD));
	$app = new \Slim\Slim(array(
		'templates.path' => './views',
		'debug' => intval(!PROD)
	));
	define('HOST', strpos($app->request()->getHost(), 'http://') === false ? 'http://'.$app->request()->getHost() : $app->request()->getHost());

	$app->hook('slim.before.dispatch', function() use ($app) {
		$app->view()->appendData(array(
			'app' => $app
		));
	});

	$app->get('/', function() use ($app, $imageDB) {
		$nextImgSlug = $imageDB->getRandomImageSlug();
		$app->render('home.php', array('simpleText' => true, 'nextImgSlug' => $nextImgSlug));
	})->name('home');

	$app->get('/jarretededeprimer/:slug', function($slug) use ($app, $imageDB) {
		//on vérifie le cookie contenant les ids d'images déjà vues (c'est un tableau d'ids)
		$seenImgsCookie = $app->getCookie('seen_img_ids');
		if ($seenImgsCookie) {
			$seenImgIds = array_filter(explode(';', htmlspecialchars($seenImgsCookie)));
			$idsOk = true;
			foreach ($seenImgIds as $id) { if (!is_numeric($id)) { $idsOk = false; break; } }
		}
		if (empty($seenImgIds) || empty($idsOk)) $seenImgIds = array();

		$img = $imageDB->getImageBySlug($slug);
		if (!in_array($img['id'], $seenImgIds))
			$seenImgIds[]= $img['id'];
		$nextImgSlug = $imageDB->getRandomImageSlug($seenImgIds);
		
		$app->setCookie('seen_img_ids', implode(';', $seenImgIds), time()+60*60*24*3);

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
		$app->render('home.php', array('share' => true));
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