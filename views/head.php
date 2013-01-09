<!DOCTYPE html>
<!--[if lt IE 7]>      <html lang="fr" class="lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="fr" class="lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="fr" class="lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="fr" class=""> <!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title><?php echo !empty($title) ? $title : APP_TITLE  ?></title>
		<meta name="description" content="<?php echo !empty($description) ? $description : APP_TITLE ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<!-- dev : /css/style.css -->
		<?php $css = PROD ? '/css/style.min.css?v=957456120101' : '/css/style.css?v='.time() ?>
		<link rel="stylesheet" href="<?php echo $css ?>">

		<?php if (!empty($twitterCard)): ?>
		<meta name="twitter:card" content="<?php echo $twitterCard['card'] ?>">
		<meta name="twitter:image" content="<?php echo $twitterCard['image'] ?>">
		<meta name="twitter:title" content="<?php echo $twitterCard['title'] ?>">
		<?php endif ?>
	</head>
	<body>

		<!--[if lte IE 7]>
			<p class="obsolete-browser">Vous utilisez un navigateur <strong>obsolète</strong>. <a href="http://browsehappy.com/" target="_blank">Mettez-le à jour</a> pour naviguer sur Internet de façon <strong>sécurisée</strong> !</p>
		<![endif]-->

		<p class="switch-mode no-mobile">Pour simplement rigoler, va sur <a href="http://jairigo.lu">jairigo.lu</a>.</p>

		<div id="content">