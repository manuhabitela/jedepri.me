<?php
	session_cache_limiter(false);
	session_start();
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
		$cookieId = getCookie($app);
		if ($cookieId !== null) {
			$app->view()->appendData(array(
				'cookieId' => $cookieId
			));
		}
	});

	/**
	 * accueil
	 *
	 */
	$app->get('/', function() use ($app, $db) {
		$nextItemSlug = $db->getRandomItemSlug();
		$app->render(APP_NAME . '/question.php', array('simpleText' => true, 'nextItemSlug' => $nextItemSlug));

		if (!empty($_GET['seeyouontheotherside'])) {
			$givenCookieId = filter_input(INPUT_GET, 'seeyouontheotherside', FILTER_VALIDATE_INT);
			if ($givenCookieId) {
				$app->setCookie('seen_item_ids', $givenCookieId, time()+60*60*24*30);
			}
		}

	})->name('home');

	/**
	 * image
	 *
	 */
	$app->get('/' . APP_ROUTE_ITEM . '/:slug', function($slug) use ($app, $db) {
		$item = $db->getItemBySlug($slug);
		$nextItemSlug = $db->getRandomItemSlug($cookieId);

		$cookieId = getCookie($app);
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
	 * maj de la base
	 *
	 */
	$app->get('/updateImages', function() use($db) {
		$db->cleanDB();
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
	 * admin : login
	 *
	 */
	$app->get('/admin', function() use($app) {
		$app->render('admin/login.php');
	});

	$app->post('/admin', function() use($app) {
		if (empty($_POST['word']))
			return;

		define('IM_COOL_BRO', 'yeah');
		include('../php_config/jedepri.php');

		$word = filter_input(INPUT_POST, 'word', FILTER_SANITIZE_SPECIAL_CHARS);
		$_SESSION['isLogged'] = sha1($word) === MAGIC_WORD;

		if ($_SESSION['isLogged'])
			$app->redirect('/admin/moderate', 301);
		else
			$app->redirect('/admin', 301);
	});

	$app->get('/admin/moderate_source/:active/', function($active) use($app, $db) {
		if (!$_SESSION['isLogged']) $app->redirect('/', 301);
		if (empty($_GET['source'])) return;
		$items = $db->getItemsFromSource($_GET['source'], $active);
		$app->render('admin/moderate.php', array('items' => $items));
	});
	/**
	 * admin : liste d'images
	 *
	 */
	$app->get('/admin/moderate', function() use($app, $db) {
		if (!$_SESSION['isLogged']) $app->redirect('/', 301);
		$items = $db->getRandomItems(100);
		$app->render('admin/moderate.php', array('items' => $items));
	});


	/**
	 * admin : ban d'une image
	 *
	 */
	$app->get('/admin/ban/:id', function($id) use($app, $db) {
		if (!$_SESSION['isLogged']) $app->redirect('/', 301);
		if (is_numeric($id)) {
			$id = (int) $id;
			$db->banItemById($id);
			echo "ok";
		}
	});

	$app->get('/admin/unban/:id', function($id) use($app, $db) {
		if (!$_SESSION['isLogged']) $app->redirect('/', 301);
		if (is_numeric($id)) {
			$id = (int) $id;
			$db->unbanItemById($id);
		}
	});

	$app->get('/admin/ban_source/', function() use($app, $db) {
		if (!$_SESSION['isLogged']) $app->redirect('/', 301);
		if (empty($_GET['source'])) return;
		$db->banItemsBySource($_GET['source']);
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