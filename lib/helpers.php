<?php
function twitterCard($item) {
	if (!empty($item) && $item['content-type'] == 'img-url' && substr($item['content'], -4) !== '.gif')
		return array('card' => 'photo', 'image' => $item['content'], 'title' => $item['title']);
	return false;
}

function getCookie($app) {
	$cookieId = null;
	$seenImgsCookie = $app->getCookie('seen_item_ids');
	if ($seenImgsCookie && is_numeric($seenImgsCookie)) {
		$cookieId = (int) $seenImgsCookie;
	}
	return $cookieId;
}