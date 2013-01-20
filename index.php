<?php
	/**
	 * ce code est amicalement sponsorisé par la méthode rache
	 */
	require 'config/app.php';

	define('PROD', (!empty($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], APP_SERVER) !== false));
	if (PROD)
		error_reporting(0);

	require 'config/database.php';
	require 'vendor/autoload.php';
	require 'lib/JedeprimeItemDatabase.php';
	require 'lib/helpers.php';

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

	/**
	 * accueil
	 * 
	 */
	$app->get('/', function() use ($app, $db) {
		$nextItemSlug = $db->getRandomItemSlug();
		$app->render(APP_NAME . '/question.php', array('simpleText' => true, 'nextItemSlug' => $nextItemSlug));
	})->name('home');

	/**
	 * image
	 * 
	 */
	$app->get('/' . APP_ROUTE_ITEM . '/:slug', function($slug) use ($app, $db) {
		$cookieId = null;
		$seenImgsCookie = $app->getCookie('seen_item_ids');
		if ($seenImgsCookie && is_numeric($seenImgsCookie)) {
			$cookieId = (int) $seenImgsCookie;
		}
		$item = $db->getItemBySlug($slug);
		$nextItemSlug = $db->getRandomItemSlug($cookieId);

		if ($cookieId == null)
			$app->setCookie('seen_item_ids', $db->createCookie(), time()+60*60*24*30);

		$db->addSeenId($cookieId, $item['id']);

		$title = ($item['content-type'] == 'img-url' ? "Une image" : "Un truc") . (APP_NAME == "jedepri" ?
			" qui fait arrêter de déprimer - Je déprime" :
			" qui te fait rigolay - J'ai rigolu");
		$app->render(APP_NAME . '/question.php', array(
			'item' => $item,
			'nextItemSlug' => $nextItemSlug,
			'twitterCard' => twitterCard($item),
			'title' => $title
		));
	})->name('question');

	/**
	 * 
	 * 
	 */
	$app->get('/' . APP_ROUTE_ITEM . '/', function() use ($app, $db) {
		$app->redirect('/' . APP_ROUTE_ITEM . '/'.$db->getRandomItemSlug());
	})->name('question-empty');

	/**
	 * partage
	 * 
	 */
	$app->get('/' . APP_ROUTE_SHARE . '/:slug', function($slug) use ($app, $db) {
		$title = APP_NAME == "jedepri" ?
			"J'ai arrêté ma dépression grâce à ".($item['content-type'] == 'img-url' ? "cette image" : "ce truc").' ! - Je déprime' :
			"J'ai rigolu trop méga fort en voyant ".($item['content-type'] == 'img-url' ? "cette image" : "ce truc").'.';
		$app->render(APP_NAME . '/partage.php', array(
			'item' => $db->getItemBySlug($slug),
			'twitterCard' => twitterCard($item),
			'title' => $title
		));
	})->name('partage');

	/**
	 * 
	 * 
	 */
	$app->get('/' . APP_ROUTE_SHARE . '/', function() use ($app, $db) {
		$title = APP_NAME == "jedepri" ?
			"J'ai arrêté ma depression sur jedepri.me ! - Je déprime" :
			"J'me suis roulé par terre et je le dis à toute la planète ! - J'ai rigolu'";
		$app->render(APP_NAME . '/partage.php', array(
			"title" => $title
		));
	})->name('partage-empty');

	/**
	 * 
	 * 
	 */
	$app->get('/updateImages', function() use($db) {
		$db->fillDB();
	})->name('updateImages');

	/**
	 * 
	 * 
	 */
	$app->get('/random', function() use ($app, $db) {
		echo HOST.'/' . APP_ROUTE_ITEM . '/'.$db->getRandomItemSlug();
	});

	/**
	 * 
	 * 
	 */
	$app->get('/:slug', function($slug) use($app) {
		$app->redirect('/' . APP_ROUTE_ITEM . '/'.$slug, 301);
	});

	/**
	 * 
	 * 
	 */
	$app->run();
?>