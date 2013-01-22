<div id="item" class="<?php echo !empty($item) ? 'item-source-'.$item['source-type'].' item-content-'.$item['content-type'] : '' ?>">
	<?php if (!empty($item)): ?>
	
	<p class="item-title">
		<?php if (!empty($item['title'])) echo $item['title'] ?> 
		<span class="item-source"><a href="<?php echo $item['external-url'] ?>" target="_blank">(source)</a></span>
	</p>
	
	<?php if ($item['content-type'] == 'img-url'): ?>
	<img class="item-img" src="<?php echo $item['content'] ?>" alt="<?php echo $item['title'] ?>">	
	<?php endif ?>

	<?php if ($item['content-type'] == 'text'): ?>
	<p class="item-text"><?php echo $item['content'] ?></p>
	<?php endif ?>
	
	<span class="hidden for-analytics osef-grave"><?php echo $item['content'] ?></span>
	<?php endif ?>
</div>