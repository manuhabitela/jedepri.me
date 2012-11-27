<?php
class RandomTumblrImage {
	var $tumblrs = array(
		'http://www.bonjourmadame.fr',
		'http://bonjourlesgeeks.com',
		'http://bonjourhamster.fr',
		'http://bonjourlechat.fr',
	);
	var $data = null;
	var $src = '';
	var $title = '';
	var $url = '';

	function __construct($list = array()) {
		if (!empty($list)) $this->tumblrs = $list;
		libxml_use_internal_errors(true);
		$this->gimmeGimme();
		return $this;
	}

	protected function gimmeGimme() {
		$from = $this->tumblrs[mt_rand(0, count($this->tumblrs)-1)];
		$startRand = mt_rand(0, 99);
		$postRand = mt_rand(0, 49);
		//je teste d'utiliser yql car j'ai l'impression qu'au bout d'un peu de spamm l'ip du serv doit atteindre une limite au niveau de l'api tumblr ?
		$xml = simplexml_load_file("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20xml%20where%20url%3D'".urlencode($from.'/api/read?start='.$startRand.'&num=50&type=photo')."'");
		if (!$xml && !empty($this->debug)) {
			foreach (libxml_get_errors() as $error) {
				var_dump($error);
			}
		}
		if ($xml) {
			//y'a une erreur "Serialization of 'SimpleXMLElement' is not allowed" dès que je mets 
			//$xml->posts->post[mt_rand(0, 49)] dans une variable o_O
			$rand = $postRand;
			$this->data = $xml->results->tumblr->posts->post[$rand]->asXML();
			$this->src = (string) $xml->results->tumblr->posts->post[$rand]->{'photo-url'}[1];
			$this->url = (string) $xml->results->tumblr->posts->post[$rand]['url'];
			$this->title = strip_tags((string) $xml->results->tumblr->posts->post[$rand]->{'photo-caption'});
		}
	}
}