<?php if (!$app->request()->isAjax()) include('head.php'); ?>

<?php include('item.php') ?>

<div class="share">
	<h1 class="title">Alors dis-le !</h1>
	<?php 
		$sharingSentence = "Sur ".HOST.",";
		$coolSharingSentences = array(
			"j'ai arrêté de déprimer",
			"j'ai remis un peu de joie dans ma vie",
			"ma vie a changé",
			"ma vie est devenue plus belle",
			"joie et bonheur refont partie de mon vocabulaire",
			"j'ai kiffé ma race",
		);
		$sharingSentence .= " ".$coolSharingSentences[mt_rand(0, count($coolSharingSentences)-1)];
		if (!empty($item)) {
			$sharingSentence .= " grâce à ".HOST.'/'.$item['slug'];	
		}
		$sharingSentence .= " !";
	?>
	<p>Partage ta joie sur <a class="share-link twitter" href="https://twitter.com/intent/tweet?text=<?php echo urlencode($sharingSentence) ?>" target="_blank">Twitter</a>, <a class="share-link facebook" href="http://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(HOST.'/'.$item['slug']) ?>&amp;t=jedepri.me" target="_blank">Facebook</a> ou autre part en copiant le texte ci-dessous :</p>
	<form action="#">
		<textarea class="share-text"><?php echo $sharingSentence ?></textarea>
	</form>

	<a href="<?php echo $app->urlFor('home') ?>" class="toggle-view">Retour</a>
</div>
<?php if (!$app->request()->isAjax()) include('foot.php'); ?>