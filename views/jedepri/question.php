<?php 
	if (!$app->request()->isAjax()) include(__DIR__ . '/../head.php');

	if (!isset($simpleText)) $simpleText = false;
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

<?php include(__DIR__ . '/../item.php') ?>

<div class="choices">
	<h1 class="title"><?php echo $rand[0]; ?></h1>
	<a href="<?php echo !empty($nextItemSlug) ? $app->urlFor('question', array('slug' => $nextItemSlug)) : $app->urlFor('question-empty') ?>" class="reader-choice sad"><?php echo $rand[1]; ?></a>
	<a href="<?php echo !empty($item) ? $app->urlFor('partage', array('slug' => $item['slug'])) : $app->urlFor('partage-empty') ?>" class="reader-choice happy"><?php echo $rand[2]; ?></a>
</div>
<?php if (!$app->request()->isAjax()) include(__DIR__ . '/../foot.php'); ?>