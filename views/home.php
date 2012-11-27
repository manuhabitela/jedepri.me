<?php
	if (!isset($simpleText)) $simpleText = false;
	if (!isset($share)) $share = false;
	if ($simpleText) {
		$strings = array(array('Tu déprimes ?', 'Ouais', 'Nan'));
	} else {
		$neutralYeses = array('Ouais', 'Oui');
		$coolYeses = array_merge($neutralYeses, array('Carrément !', 'Yeah', 'Oui :)', 'Yes !', 'C\'est bon !'));
		$notCoolYeses = array_merge($neutralYeses, array('Oui...', 'Ouais :('));
		$neutralNoes = array('Nan', 'Non', 'Carrément pas', 'Trop pas');
		$coolNoes = array_merge($neutralNoes, array('Nan !', 'Non :)', 'Nan, génial !!'));
		$notCoolNoes = array_merge($neutralNoes, array('Non...', 'Non :(', 'Toujours pas...'));
		$coolYes = $coolYeses[mt_rand(0, count($coolYeses)-1)];
		$notCoolYes = $notCoolYeses[mt_rand(0, count($notCoolYeses)-1)];
		$coolNo = $coolNoes[mt_rand(0, count($coolNoes)-1)];
		$notCoolNo = $notCoolNoes[mt_rand(0, count($notCoolNoes)-1)];
		$strings = array(
			array('Et maintenant, ça le fait ?', $notCoolNo, $coolYes),
			array('Allez, là c\'est bon nan ?', $notCoolNo, $coolYes),
			array('Encore déprimé ?', $notCoolYes, $coolNo),
			array('Ça va mieux ?', $notCoolNo, $coolYes),
			array('Tu chiales encore ?', $notCoolYes, $coolNo),
		);
	}
	$rand = $strings[mt_rand(0, count($strings)-1)];
?>

<div class="choices <?php if ($share) echo 'hidden' ?>">
	<h1 class="title"><a href="<?php echo $app->urlFor('home') ?>"><?php echo $rand[0]; ?></a></h1>
	<a href="<?php echo $app->urlFor('jarretededeprimer') ?>" class="reader-choice sad"><?php echo $rand[1]; ?></a>
	<a href="<?php echo $app->urlFor('cayestjedeprimeplus') ?>" class="reader-choice happy"><?php echo $rand[2]; ?></a>
</div>
<div class="share <?php if (!$share) echo 'hidden' ?>">
	<h1 class="title">Alors dis-le !</h1>
	<?php 
		$sharingSentence = "Sur http://jedepri.me,";
		$coolSharingSentences = array(
			"j'ai arrêté de déprimer",
			"j'ai remis un peu de joie dans ma vie",
			"ma vie a changé",
			"ma vie est devenue plus belle",
			"joie et bonheur refont partie de mon vocabulaire",
			"j'ai kiffé ma race",
		);
		$sharingSentence .= " ".$coolSharingSentences[mt_rand(0, count($coolSharingSentences)-1)];
		if (!empty($img)) {
			$sharingSentence .= " grâce à cette superbe image ".$img['src'];	
		}
		$sharingSentence .= " !";
	?>
	<p>Partage ta joie <a class="share-link twitter" href="https://twitter.com/intent/tweet?text=<?php echo urlencode($sharingSentence) ?>" target="_blank">directement sur Twitter</a> ou autre part en copiant le texte ci-dessous :</p>
	<form action="#">
		<textarea class="share-text"><?php echo $sharingSentence ?></textarea>
	</form>

	<a href="<?php echo $app->urlFor('home') ?>" class="toggle-view">Retour</a>
</div>
<div id="img">
	<?php if (!empty($img)): ?>
	<?php if (strpos($img['src'], 'imgur') !== false && !empty($img['title'])): ?>
	<p class="img-caption"><?php echo $img['title'] ?></p>
	<?php endif ?>
	<img class="img" src="<?php echo $img['src'] ?>" alt="<?php echo $img['title'] ?>">
	<div class="img-source"><a href="<?php echo $img['url'] ?>" target="_blank">source</a></div>
	<?php endif ?>
</div>
<div class="hidden">
<?php var_dump($img) ?>
</div>