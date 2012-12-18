<!DOCTYPE html>
<!--[if lt IE 7]>      <html lang="fr" class="lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="fr" class="lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="fr" class="lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="fr" class=""> <!--<![endif]-->
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title>Je déprime mais je veux arrêter, je suis en pleine dépression et ça m'emmerde : heureusement, jedepri.me est là</title>
		<meta name="description" content="Un petit coup de déprime ? Allez viens.">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<!-- dev : /css/style.css -->
		<link rel="stylesheet" href="/css/style.min.css?v=12345678">

		<?php if (!empty($img) && exif_imagetype($img['src']) != IMAGETYPE_GIF): ?>
		<meta name="twitter:card" content="photo">
		<meta name="twitter:image" content="<?php echo $img['src'] ?>">
		<?php if ((strpos($img['src'], 'imgur') !== false || $img['type'] == 'tumblr-regular') && !empty($img['title'])): ?>
		<meta name="twitter:title" content="<?php echo $img['title'] ?>">
		<?php else: ?>
		<meta name="twitter:title" content="">
		<?php endif ?>
		<?php endif ?>
	</head>
	<body>

		<!--[if lte IE 7]>
			<p class="obsolete-browser">Vous utilisez un navigateur <strong>obsolète</strong>. <a href="http://browsehappy.com/">Mettez-le à jour</a> pour naviguer sur Internet de façon <strong>sécurisée</strong> !</p>
		<![endif]-->

		<div id="content">