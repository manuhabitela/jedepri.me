<?php
class ImageDatabase {

	var $db;
	var $tumblrs;
	var $subreddits;
	var $imgurGallery;

	function __construct($pdo, $data) {
		$this->db = $pdo;
		if ($data['tumblrs'])
			$this->tumblrs = $data['tumblrs'];
		if ($data['subreddits'])
			$this->subreddits = $data['subreddits'];
		if ($data['imgurGallery'])
			$this->imgurGallery = $data['imgurGallery'];
	}

	public function updateImages() {
		if ($this->isUpdateOk()) {
			if ($this->db->exec('UPDATE jedeprime_config c SET c.value = 1 where c.key = "updating"')  !== false) {
				$this->emptyDB();
				$this->fillWithTumblrImages();
				$this->fillWithSubRedditsImages();
				$this->fillWithImgurGallery();
				$now = date('Y-m-d H:i:s');
				$this->db->exec('UPDATE jedeprime_config c SET c.value = "'.$now.'" where c.key = "lastUpdate"');
				$this->db->exec('UPDATE jedeprime_config c SET c.value = 0 where c.key = "updating"');
			}
		}
	}

	public function getRandomImage() {
		$type = mt_rand(0, 100) > 75 ? "tumblr" : "imgur"; //75% de chance d'avoir de l'imgur
		$data = $this->db->query('SELECT * from jedeprime_data WHERE type = "'.$type.'" ORDER BY RAND() LIMIT 0,1');
		if ($data) $data = $data->fetch(PDO::FETCH_OBJ);
		libxml_use_internal_errors(true);
		$xml = new SimpleXMLElement($data->data);
		if ($xml) {		
			if ($data->type == "tumblr") {
				$item = $xml->post[mt_rand(0, $xml->post->count())];
				if (empty($item))
					return $this->getRandomImage();
				$img = $this->_tumblrImage($item);
			} elseif ($data->type == "imgur") {
				$item = $xml->item[mt_rand(0, $xml->item->count())];
				if (empty($item))
					return $this->getRandomImage();
				$img = $this->_imgurImage($item);
			}
			return $img;
		}
		return false;
	}

	protected function _tumblrImage($xml) {
		return array(
			"src" => (string) $xml->{'photo-url'}[1],
			"url" => (string) $xml['url'],
			"title" => strip_tags((string) $xml->{'photo-caption'}),
		);
	}

	protected function _imgurImage($xml) {
		return array(
			"src" => (string) 'http://i.imgur.com/'.$xml->hash.$xml->ext,
			"url" => (string) 'http://imgur.com/'.$xml->hash,
			"title" => (string) $xml->title
		);
	}

	protected function isUpdateOk() {
		$updating = $this->db->query('SELECT value FROM jedeprime_config c WHERE c.key = "updating"')->fetch(PDO::FETCH_OBJ);
		$updating = (int) $updating->value;
		if (!empty($updating))
			return false;

		$now = time();
		$lastUpdate = $this->db->query('SELECT value FROM jedeprime_config c WHERE c.key = "lastUpdate"')->fetch(PDO::FETCH_OBJ);
		$lastUpdate = strtotime((string) $lastUpdate->value);
		if ($now > $lastUpdate)
			$diff = ceil(($now - $lastUpdate)/3600);
		if ($diff < 15)
			return false;

		return true;
	}

	protected function emptyDB() {
		return $this->db->exec("TRUNCATE TABLE jedeprime_data") !== false;
	}

	public function fillWithTumblrImages() {
		libxml_use_internal_errors(true);
		foreach ($this->tumblrs as $tumblr) {
			$max = 300;
			$num = 50;
			$checkMax = @simplexml_load_file($tumblr.'/api/read?num=1');
			if ($checkMax) $max = $checkMax->posts['total']; //download allll the posts
			$pages = floor($max/$num) > 11 ? 11 : floor($max/$num);
			for ($i=0; $i < $pages; $i++) {
				$xml = @simplexml_load_file($tumblr.'/api/read?start='.($i*50).'&num='.$num.'&type=photo'); //CRADOOOOO OUUUHHH
				if ($xml) {
					$data = $xml->posts->asXML();
					$q = 'INSERT INTO jedeprime_data (type, category, page, data) VALUES ("tumblr", "'.$tumblr.'", '.$i.', "'.addslashes($data).'")';
					$this->db->exec($q);
				}
			}			
		}
	}

	public function fillWithSubRedditsImages() {
		libxml_use_internal_errors(true);
		foreach ($this->subreddits as $subreddit) {
			for ($i=0; $i < 11; $i++) { 
				$xml = @simplexml_load_file('http://imgur.com/r/'.$subreddit.'/top/page/'.$i.'.xml'); //CRADOOOOO OUUUHHH
				if ($xml) {
					$data = $xml->asXML();
					$q = 'INSERT INTO jedeprime_data (type, category, page, data) VALUES ("imgur", "'.$subreddit.'", '.$i.', "'.addslashes($data).'")';
					$this->db->exec($q);
				}
			}
		}
	}

	public function fillWithImgurGallery() {
		if (!$this->imgurGallery) return;
		libxml_use_internal_errors(true);
		for ($i=0; $i < 11; $i++) { 
			$xml = @simplexml_load_file('http://imgur.com/gallery/page/'.$i.'.xml'); //CRADOOOOO OUUUHHH
			if ($xml) {
				$data = $xml->asXML();
				$q = 'INSERT INTO jedeprime_data (type, category, page, data) VALUES ("imgur", "gallery", '.$i.', "'.addslashes($data).'")';
				$this->db->exec($q);
			}
		}
	}
}