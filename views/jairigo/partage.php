<?php if (!$app->request()->isAjax()) include(__DIR__ . '/../head.php'); ?>

<?php include(__DIR__ . '/../item.php') ?>

<div class="share">
	<h1 class="title">Alors dis-le !</h1>
	<?php 
		if (!empty($item['title']) && strlen($item['title']) < 140-strlen('"" via ')-20) {
			$sharingSentence = '"'.$item['title'].'" via '.HOST.'/'.$item['slug'];
		} else {
			$coolSharingSentences = array(
				"J'ai rigolu",
				"J'ai trop lolé",
				"Je me suis esclaffé de rire",
				"Grosse barre de rire",
				"Je me suis roulé par terre tellement j'en pouvais plus",
				"J'ai failli crevé de rire",
				"J'ai souri très fort",
			);
			$sharingSentence = $coolSharingSentences[mt_rand(0, count($coolSharingSentences)-1)];
			if (!empty($item)) {
				$sharingSentence .= " grâce à ".HOST.'/'.$item['slug'];	
			}
			$sharingSentence .= " !";
		}
	?>
	<p>T'as rigolu, alors dis-le sur <a class="share-link twitter" href="https://twitter.com/intent/tweet?text=<?php echo urlencode($sharingSentence) ?>" target="_blank">Twitter</a>, <a class="share-link facebook" href="http://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(HOST.'/'.$item['slug']) ?>&amp;t=jedepri.me" target="_blank">Facebook</a> ou autre part en copiant le texte ci-dessous :</p>
	<form action="#">
		<textarea class="share-text"><?php echo $sharingSentence ?></textarea>
	</form>
	<?php $backUrl = !empty($item['slug']) ? $app->urlFor('question', array('slug' => $item['slug'])) : $app->urlFor('question-empty'); ?>
	<a href="<?php echo $backUrl ?>" class="toggle-view">Retour</a>
</div>
<?php if (!$app->request()->isAjax()) include(__DIR__ . '/../foot.php'); ?>
