<?php 
	if (!$app->request()->isAjax()) include(__DIR__ . '/../head.php');

	if (!isset($simpleText)) $simpleText = false;
	if ($simpleText) {
		$strings = array(array('Tu rigoles ?', 'Nan', 'Ouais'));
	} else {
		$neutralYeses = array('Ouais', 'Oui');
		$coolYeses = array_merge($neutralYeses, array('Carrément !', 'Yeah', 'Oui :)', 'Yes !', 'C\'est bon !'));
		$notCoolYeses = array_merge($neutralYeses, array('Oui...', 'Ouais :('));
		$neutralNoes = array('Nan', 'Non', 'Carrément pas', 'Trop pas');
		$coolNoes = array_merge($neutralNoes, array('Nan !', 'Non :)', 'Nan, génial !!'));
		$notCoolNoes = array_merge($neutralNoes, array('Non...', 'Non :(', 'Toujours pas...'));
		$neutralYes = $neutralYeses[mt_rand(0, count($neutralYeses)-1)];
		$coolYes = $coolYeses[mt_rand(0, count($coolYeses)-1)];
		$notCoolYes = $notCoolYeses[mt_rand(0, count($notCoolYeses)-1)];
		$neutralNo = $coolNoes[mt_rand(0, count($neutralNoes)-1)];
		$coolNo = $coolNoes[mt_rand(0, count($coolNoes)-1)];
		$notCoolNo = $notCoolNoes[mt_rand(0, count($notCoolNoes)-1)];
		$strings = array(
			array('Et maintenant, ça le fait ?', $notCoolNo, $coolYes),
			array('Allez, là c\'est bon nan ?', $notCoolNo, $coolYes),
			array("Et là, t'as rigolay ?", $neutralNo, $coolYes),
			array("Grosse barre de rire hein ?", $neutralNo, $coolYes),
			array("T'es mdrlolz ?", $neutralNo, $coolYes),
			array("Tu rigoles maintenant ?", $neutralNo, $coolYes),
			array("T'as rigolu ?", $neutralNo, $coolYes),
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