<?php
class RandomImgurImage {
	var $subreddits = array(
		'funny', 
		'gif'
	);
	var $data;
	var $src = '';
	var $title = '';
	var $url = '';

	function __construct($list = array()) {
		if (!empty($list)) $this->subreddits = $list;
		libxml_use_internal_errors(true);
		$this->gimmeGimme();
		return $this;
	}

	protected function gimmeGimme() {
		$subreddit = $this->subreddits[mt_rand(0, count($this->subreddits)-1)];
		$xml = simplexml_load_file('http://imgur.com/r/'.$subreddit.'/top/page/'.mt_rand(0, 10).'.xml');
		if (!$xml && !empty($this->debug)) {
			foreach (libxml_get_errors() as $error) {
				var_dump($error);
			}
		}
		if ($xml['success'] == 1 && $xml['status'] == 200) {
			//y'a une erreur "Serialization of 'SimpleXMLElement' is not allowed" dès que je mets 
			//$xml->posts->post[mt_rand(0, 49)] dans une variable o_O
			$rand = mt_rand(0, 49);
			$this->data = $xml->item[$rand]->asXML();
			$this->src = (string) 'http://i.imgur.com/'.$xml->item[$rand]->hash.$xml->item[$rand]->ext;
			$this->title = (string) $xml->item[$rand]->title;
			$this->url = (string) 'http://imgur.com/'.$xml->item[$rand]->hash;
		}
	}
}