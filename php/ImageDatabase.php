<?php
/**
 * classe s'occupant de gérer en base de données les liens vers les images à utiliser sur jedepri.me
 *
 * on passe en paramètre un fichier json contenant les sources à utiliser, classés par type, ex:
 * {
 * 	 "tumblr-photo": ["http://bonjourhamster.fr", "http://dailybunny.tumblr.com"],
 *   "subreddit": ["funny"]
 * }
 *
 * les types possibles sont actuellement : 
 * 		"tumblr-photo" pour des sites tumblr bien structurés (avec des posts de type photo et non des images mises dans un article texte)
 * 			c'est les urls des tumblr qu'on ajoute dans ce tableau
 * 		"subreddit" pour des images provenant d'un subreddit
 * 			c'est les noms des subreddit qu'on ajoute dans ce tableau
 * 		"imgur-keyword" pour des images venant d'une recherche imgur
 * 			c'est les mots clé de recherche qu'on ajoute dans ce tableau
 *
 * on peut ensuite enregistrer les liens vers ces images dans la base, ou récupérer des images de la base au hasard
 */
class ImageDatabase {

	/**
	 * objet PDO représentant notre BDD
	 * @var PDOObject
	 */
	protected $db;

	/**
	 * liste des sources à utiliser pour remplir la base de données
	 *
	 * tableau de type => sources
	 * @var array
	 */
	protected $sources;

	/**
	 * méthodes à utiliser pour remplir la base pour chaque type de source
	 * @var array
	 */
	protected static $sourceTypeMethods = array(
		'tumblr-photo' => '_insertTumblr',
		'subreddit' => '_insertSubreddit',
		'imgur-search' => '_insertImgurFilteredGallery'
	);

	function __construct($pdo, $sourceFile, $debug = 0) {
		$this->db = $pdo;
		$this->sources = json_decode(file_get_contents($sourceFile), true);
		libxml_use_internal_errors(true);
		$this->debug = $debug;
	}

	/**
	 * met à jour la BDD avec les sources données à la construction de l'objet
	 * @param  string $givenType met à jour uniquement un certain type de donnée
	 */
	public function fillDB($givenType = null) {
		$methods = self::$sourceTypeMethods;
		if ($this->_isUpdateOk() || $this->debug == 1) {
			$this->ids = $this->_getImageIds();
			foreach ($this->sources as $type => $sources) {
				if ($givenType == null || $type == $givenType) {
					foreach ($sources as $source) {
						if (method_exists($this, $methods[$type]))
							$this->{$methods[$type]}($source);
					}
				}
			}
			$now = date('Y-m-d H:i:s');
			$this->db->exec('UPDATE jedeprime_config c SET c.value = "'.$now.'" where c.key = "lastUpdate"');
			$this->db->exec('UPDATE jedeprime_config c SET c.value = 0 where c.key = "updating"');
		}
	}

	public function getImageById($id) {
		$query = 'SELECT * from jedeprime_imgs WHERE id = '.$id;
		$data = $this->db->query($query);
		if ($data && $data = $data->fetch(PDO::FETCH_BOTH)) {
			return $this->_image($data);
		}
		return false;
	}

	public function getImageBySlug($slug) {
		$id = base_convert($slug, 16, 10);
		return $this->getImageById($id);
	}

	public function getImageSlugById($id) {
		return base_convert($id, 10, 16);
	}

	/**
	 * choppe une image au pif dans la base
	 * @param  array  $not tableau d'ids a éviter de récupérer
	 * @return array      image
	 */
	public function getRandomImage($options) {
		$query = 'SELECT ';
		$query .= empty($options['select']) ? "*" : $options['select'];
		$query .= ' from jedeprime_imgs';
		if (!empty($options['except']))
			$query .= " WHERE id NOT IN (".implode(', ', $options['except']).")";
		$query .= " ORDER BY RAND() LIMIT 0,1";
		$data = $this->db->query($query);
		if ($data && $data = $data->fetch(PDO::FETCH_BOTH)) {
			return $this->_image($data);
		}
		return false;
	}

	public function getRandomImageId($not = array()) {
		$img = $this->getRandomImage(array('select' => 'id', 'except' => $not));
		if ($img) return $img['id'];
		return false;
	}

	public function getRandomImageSlug($not = array()) {
		$id = $this->getRandomImageId($not);
		if ($id) return $this->getImageSlugById($id);
		return false;
	}

	/**
	 * liste des images
	 * @return array toutes les urls des images de la base classées par id
	 */
	protected function _getImageIds() {
		$ids = array();
		$data = $this->db->query('SELECT id, src from jedeprime_imgs order by id');
		if ($data) {
			while ($row = $data->fetch(PDO::FETCH_OBJ)) {
				$ids[$row->id]= $row->src;
			}
		}
		return $ids;
	}

	protected function _image($img) {
		return $img + array('slug' => $this->getImageSlugById($img['id']));
	}

	/**
	 * transforme un objet xml représentant une image sur tumblr en un tableau avec des attributs communs à toute image en base
	 * @param  SimpleXMLElement $xml objet xml représentant l'image 
	 * @return array tableau représentant l'image ayant une 'src', une 'url', un 'title', et le 'xml'
	 */
	protected function _tumblrImage($xml) {
		return array(
			"src" => (string) $xml->{'photo-url'}[1],
			"url" => (string) $xml['url'],
			"title" => strip_tags((string) $xml->{'photo-caption'}),
			"xml" => $xml->asXML()
		);
	}

	/**
	 * transforme un objet xml représentant une image sur imgur en un tableau avec des attributs communs à toute image en base
	 * @param  SimpleXMLElement $xml objet xml représentant l'image 
	 * @return array tableau représentant l'image ayant une 'src', une 'url', un 'title', et le 'xml'
	 */
	protected function _imgurImage($xml) {
		return array(
			"src" => (string) 'http://i.imgur.com/'.$xml->hash.$xml->ext,
			"url" => (string) 'http://imgur.com/'.$xml->hash,
			"title" => (string) $xml->title,
			"xml" => $xml->asXML()
		);
	}

	/**
	 * peut-on lancer une maj de la BDD ?
	 * @return boolean oui ou non, dah
	 */
	protected function _isUpdateOk() {
		$updating = $this->db->query('SELECT value FROM jedeprime_config c WHERE c.key = "updating"')->fetch(PDO::FETCH_OBJ);
		$updating = (int) $updating->value;

		$now = time();
		$lastUpdate = $this->db->query('SELECT value FROM jedeprime_config c WHERE c.key = "lastUpdate"')->fetch(PDO::FETCH_OBJ);
		$lastUpdate = strtotime((string) $lastUpdate->value);
		if ($now > $lastUpdate)
			$diff = ceil(($now - $lastUpdate)/3600);

		//si on a maj y'a plus d'une heure et que updating est toujours à 1, c'est surement un bug, on remet updating à 0
		if (!empty($updating) && $diff > 1) {
			$this->db->exec('UPDATE jedeprime_config c SET c.value = 0 where c.key = "updating"');
			$updating = 0;
		}

		//si la maj est en cours, on ne permet pas le lancement d'une nouvelle maj
		//si on a maj y'a moins de 15h, on ne permet pas une nouvelle maj
		if (!empty($updating) || (isset($diff) && $diff < 15))
			return false;
		return true;
	}

	/**
	 * insert des images en base de données
	 *
	 * les images déjà présentes en base ne sont pas mises à jour
	 * 	
	 * @param  array $imgs tableau d'images (une image = tableau de 'src', 'url', 'title', 'xml')
	 * @return int nombre d'images insérées
	 */
	protected function _insertImages($imgs) {
		if (empty($this->ids)) $this->ids = $this->_getImageIds();
		$query = "INSERT INTO jedeprime_imgs(src, url, title, type, xml) VALUES ";
		$queryValues = array();
		foreach ($imgs as $img) {
			if (!in_array($img['src'], $this->ids))
				$queryValues[]= '("'.addslashes($img['src']).'", "'.addslashes($img['url']).'", "'.addslashes($img['title']).'", "'.addslashes($img['type']).'", "'.addslashes($img['xml']).'")';
		}
		$query = $query.implode(', ', $queryValues)." ON DUPLICATE KEY UPDATE src=src";
		return $this->db->exec($query);
	}

	/**
	 * insert en base les images venant d'un tumblr
	 * @param  string $tumblr url du tumblr dont on veut choper les images
	 * @return int nombre d'images insérées
	 */
	protected function _insertTumblr($tumblr) {
		$max = 300;
		$num = 50;
		//avec les tumblr on passe par yql, ça semble mieux passer qu'en direct, bizarrement...
		$checkMax = @simplexml_load_file("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20xml%20where%20url%3D'".urlencode($tumblr.'/api/read?num=1')."'");
		if ($checkMax) $max = $checkMax->results->tumblr->posts['total']; //download allll the posts
		$pages = floor($max/$num) > 11 ? 11 : floor($max/$num);

		$imgs = array();
		for ($i=0; $i < $pages; $i++) {
			$xml = @simplexml_load_file("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20xml%20where%20url%3D'".urlencode($tumblr.'/api/read?start='.($i*50).'&num='.$num.'&type=photo')."'");
			if (!empty($xml->results->tumblr->posts)) {
				foreach ($xml->results->tumblr->posts->children() as $item) {
					$imgs[] = $this->_tumblrImage($item) + array('type' => 'tumblr-photo');
				}
			}
		}
		return $this->_insertImages($imgs);
	}

	/**
	 * insert en base les images venant d'un subreddit
	 * @param  string $subreddit nom du subreddit dont on veut choper les images
	 * @return int nombre d'images insérées
	 */
	protected function _insertSubreddit($subreddit) {
		$imgs = array();
		for ($i=0; $i < 11; $i++) {
			$xml = @simplexml_load_file('http://imgur.com/r/'.$subreddit.'/top/page/'.$i.'.xml');
			if ($xml) {
				foreach ($xml->item as $item) {
					$imgs[]= $this->_imgurImage($item) + array('type' => 'subreddit');
				}
			}
		}
		return $this->_insertImages($imgs);
	}

	/**
	 * insert en base les images venant d'une recherche sur imgur
	 * @param  string $keyword mot(s) clé de la recherche qu'on fait sur imgur
	 * @return int nombre d'images insérées
	 */
	protected function _insertImgurFilteredGallery($keyword) {
		$imgs = array();
		for ($i=0; $i < 11; $i++) {
			$xml = @simplexml_load_file('http://imgur.com/gallery/page/'.$i.'.xml?q='.str_replace(' ', '+', $keyword));
			if ($xml) {
				foreach ($xml->item as $item) {
					$imgs[]= $this->_imgurImage($item) + array('type' => 'imgur-search');
				}
			}
		}
		return $this->_insertImages($imgs);
	}
}