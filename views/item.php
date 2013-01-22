<div id="item" class="<?php echo !empty($item) ? 'item-source-'.$item['source-type'].' item-content-'.$item['content-type'] : '' ?>" <?php echo !empty($item) ? "data-hash=\"".$item['hash']."\"" : "" ?>>
	<?php if (!empty($item)): ?>

	<?php if (!empty($item['title'])): ?>
	<?php $randomClasses = array('orange', 'pink', 'blue', 'green', 'purple'); ?>
	<p class="item-title item-title-<?php echo $randomClasses[mt_rand(0, count($randomClasses)-1)]; ?>">
		<?php echo $item['title'] ?>
	<?php endif ?>
		<span class="item-external-url"><a href="<?php echo $item['external-url'] ?>" target="_blank">source</a></span>
	<?php if (!empty($item['title'])): ?>
	</p>
	<?php endif ?>

	<?php if ($item['content-type'] == 'img-url'): ?>
	<img class="item-img" src="<?php echo $item['content'] ?>" alt="<?php echo $item['title'] ?>">
	<?php endif ?>

	<?php if ($item['content-type'] == 'text'): ?>
	<p class="item-text"><?php echo $item['content'] ?></p>
	<?php endif ?>

	<span class="hidden for-analytics osef-grave"><?php echo $item['content'] ?></span>
	<?php endif ?>
</div>