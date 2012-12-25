<?php
/**
 * classe s'occupant de gérer en base de données les liens vers les images/citations/autre à utiliser sur jedepri.me
 *
 * on passe en paramètre un fichier json contenant les sources à utiliser, classées par type, et disposant d'un poids, ex:
 * {
 * 	 "tumblr-photo": [{"http://bonjourhamster.fr": 10}, {"http://dailybunny.tumblr.com": 20}],
 *   "subreddit": [{"funny": 50}]
 * }
 *
 * les types possibles sont actuellement : 
 * 		"tumblr-nomdutype" pour des sites tumblr. Si on veut ajouter un tumblr postant des photos, on note "tumblr-photo"
 * 			pour un tumblr avec des posts normaux, ca sera "tumblr-regular", etc... les types suivent les nom de l'api
 * 			c'est les urls des tumblr qu'on ajoute dans ce tableau
 * 		"subreddit" pour des images provenant d'un subreddit
 * 			c'est les noms des subreddit qu'on ajoute dans ce tableau
 * 		"imgur-keyword" pour des images venant d'une recherche imgur
 * 			c'est les mots clé de recherche qu'on ajoute dans ce tableau
 *
 * on peut ensuite enregistrer les liens vers ces images dans la base, ou récupérer des images de la base au hasard
 */
class JedeprimeItemDatabase {

	/**
	 * objet PDO représentant notre BDD
	 * @var PDOObject
	 */
	public $db;

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
		'tumblr-regular' => '_insertTumblr',
		'subreddit' => '_insertSubreddit',
		'imgur-search' => '_insertImgurFilteredGallery',
		'vdm' => '_insertVDM'
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
			$this->ids = $this->_getItemIds();
			foreach ($this->sources as $type => $sources) {
				if ($givenType == null || $type == $givenType) {
					foreach ($sources as $source => $weight) {
						if (method_exists($this, $methods[$type]))
							$this->{$methods[$type]}($source, $type);
					}
				}
			}
			$now = date('Y-m-d H:i:s');
			$this->db->exec('UPDATE jedeprime_config c SET c.value = "'.$now.'" where c.key = "lastUpdate"');
			$this->db->exec('UPDATE jedeprime_config c SET c.value = 0 where c.key = "updating"');
		}
	}

	public function getItemById($id) {
		$query = 'SELECT * from jedeprime_items WHERE id = '.$id;
		$data = $this->db->query($query);
		if ($data && $data = $data->fetch(PDO::FETCH_BOTH)) {
			return $this->_item($data);
		}
		return false;
	}

	public function getItemBySlug($slug) {
		$id = base_convert($slug, 16, 10);
		return $this->getItemById($id);
	}

	public function getItemSlugById($id) {
		return base_convert($id, 10, 16);
	}

	/**
	 * choppe un truc au pif dans la base
	 * @param  array  $options
	 *         			"select" : champs a selectionner, tout par défaut ("*")
	 *         			"except" : tableau d'ids à ne pas intégrer dans la recherche
	 *         			"exceptSources" : tableau de [type, source] à ne pas intégrer dans la recherce
	 *         			"weighted": est-ce qu'on prend en compte le poids des sources ? oui par défaut
	 * @return array      truc (image, ou citation genre vdm)
	 */
	public function getRandomItem($options = array()) {
		$options = $options + array('select' => "*", 'except' => array(), 'exceptSources' => array(), 'weighted' => true);
		$query = 'SELECT '.$options['select'].' from jedeprime_items WHERE 1=1';
		if (!empty($options['except'])) {
			$query .= " AND id NOT IN (".implode(', ', array_filter($options['except'])).")";
		}
		if ($options['weighted'] && $filter = $this->_getNotSoRandomSource($options['exceptSources'])) {
			$query .= " AND type = \"".addslashes($filter['type'])."\" AND source = \"".addslashes($filter['source'])."\"";
		}
		$query .= " ORDER BY RAND() LIMIT 0,1";
		$data = $this->db->query($query);
		if ($data) {
			$item = $data->fetch(PDO::FETCH_BOTH);
			if ($item)
				return $this->_item($item);
			else { //aucun truc trouvé
				if (!empty($filter)) { //on tente d'en trouver un avec un filtre sur type/source en moins
					$options['exceptSources'][] = $filter;
					return $this->getRandomItem($options);
				}
				return $this->getRandomItem();
			}
		}
		return false;
	}

	public function getRandomItemId($not = array()) {
		$img = $this->getRandomItem(array('select' => 'id', 'except' => $not));
		if ($img) return $img['id'];
		return false;
	}

	public function getRandomItemSlug($not = array()) {
		$id = $this->getRandomItemId($not);
		if ($id) return $this->getItemSlugById($id);
		return false;
	}

	/**
	 * UGGLLLYYYY
	 * chope une source au pif dans la liste de sources
	 * on prend en compte le "poids" de chaque source
	 * @param array not tableau de [type, source] sur lesquels on ne veut pas tomber
	 * @return array [type, source]
	 */
	protected function _getNotSoRandomSource($not = array()) {
		$totalWeight = 0;
		$weightRanges = array();
		foreach ($this->sources as $type => $sources) {
			foreach ($sources as $source => $weight) {
				//on ne prend pas en compte cette source si elle est dans le tableau $not
				if (!empty($not) && in_array( array('type' => $type, 'source' => $source), $not ))
					continue;
				$totalWeight += $weight;
				$weightRanges[$type.'|'.$source]= $totalWeight;
			}
		}
		$rand = mt_rand(0, $totalWeight);
		foreach ($weightRanges as $source => $weight) {
			if ($rand < $weight)
				return array('type' => strstr($source, '|', true), 'source' => substr(strstr($source, '|'), 1));
		}
		return false;
	}

	/**
	 * liste des trucs en base
	 * @return array toutes les urls des objets de la base classées par id
	 */
	protected function _getItemIds() {
		$ids = array();
		$data = $this->db->query('SELECT id, src from jedeprime_items order by id');
		if ($data) {
			while ($row = $data->fetch(PDO::FETCH_OBJ)) {
				$ids[$row->id]= $row->src;
			}
		}
		return $ids;
	}

	protected function _item($img) {
		if (empty($img['url'])) $img['url'] = $img['src'];
		return $img + array('slug' => $this->getItemSlugById($img['id']));
	}

	/**
	 * transforme un objet xml représentant une image sur tumblr en un tableau avec des attributs communs à toute image en base
	 * @param  SimpleXMLElement $xml objet xml représentant l'image 
	 * @return array tableau représentant l'image ayant une 'src', une 'url', un 'title', et le 'xml'
	 */
	protected function _tumblrImage($xml) {
		$image = array(
			"url" => (string) $xml['url'],
			"xml" => $xml->asXML()
		);
		if ($xml['type'] == 'photo') {
			$image['src'] = (string) $xml->{'photo-url'}[1];
			$image['title'] = strip_tags((string) $xml->{'photo-caption'});
		}
		if ($xml['type'] == 'regular') {
			preg_match('/<p><img.+src="(.+)"\/><\/p>/s', html_entity_decode($xml->{'regular-body'}), $src); // A LA CRADE OUAIS
			$image['src'] = $src[1];
			$image['title'] = (string) $xml->{'regular-title'};
		}
		return !empty($image['src']) ? $image : false;
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
	 * transforme un objet xml représentant une vdm sur le rss vdm en un tableau avec des attributs communs à tout objet
	 * @param  SimpleXMLElement $xml objet xml représentant la vdm
	 * @return array tableau représentant la vdm ayant une 'src', un 'title', et le 'xml'
	 */
	protected function _vdmText($xml) {
		return array(
			"src" => (string) $xml->id,
			"title" => (string) $xml->content,
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
	 * insert des objets en base de données
	 *
	 * les objets déjà présents en base ne sont pas mis à jour
	 * 	
	 * @param  array $imgs tableau d'objets (un objet = tableau de 'src', 'url', 'title', 'xml')
	 * @return int nombre d'objets insérées
	 */
	protected function _insertItems($items) {
		if (empty($this->ids)) $this->ids = $this->_getItemIds();
		$query = "INSERT INTO jedeprime_items(src, url, title, type, source) VALUES ";
		$queryValues = array();
		foreach ($items as $item) {
			if (!empty($item['src']) && !in_array($item['src'], $this->ids))
				$queryValues[]= '("'.addslashes($item['src']).'", "'.addslashes($item['url']).'", "'.addslashes($item['title']).'", "'.addslashes($item['type']).'", "'.addslashes($item['source']).'")';
		}
		if (empty($queryValues))
			return false;
		$query = $query.implode(', ', $queryValues)." ON DUPLICATE KEY UPDATE src=src;";
		return $this->db->exec($query);
	}

	/**
	 * insert en base les images venant d'un tumblr
	 * @param  string $tumblr url du tumblr dont on veut choper les images
	 * @return int nombre d'images insérées
	 */
	protected function _insertTumblr($tumblr, $type) {
		$tumblrType = substr(strstr($type, '-'), 1);
		$max = 300;
		$num = 50;
		//avec les tumblr on passe par yql, ça semble mieux passer qu'en direct, bizarrement...
		$checkMax = @simplexml_load_file("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20xml%20where%20url%3D'".urlencode($tumblr.'/api/read?num=1')."'");
		if ($checkMax) $max = (int) $checkMax->results->tumblr->posts['total']; //download allll the posts
		$pages = floor($max/$num) > 10 ? 10 : floor($max/$num);
		if ($max < $num && $pages == 0) {
			$pages = 1;
			$num = $max;
		}
		$imgs = array();
		for ($i=0; $i < $pages; $i++) {
			$xml = @simplexml_load_file("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20xml%20where%20url%3D'".urlencode($tumblr.'/api/read?start='.($i*$num).'&num='.$num.'&type='.$tumblrType)."'");
			if (!empty($xml->results->tumblr->posts)) {
				foreach ($xml->results->tumblr->posts->children() as $item) {
					$img = $this->_tumblrImage($item);
					if ($img)
						$imgs[] = $img + array('type' => $type, 'source' => $tumblr);
				}
			}
		}
		return $this->_insertItems($imgs);
	}

	/**
	 * insert en base les images venant d'un subreddit
	 * @param  string $subreddit nom du subreddit dont on veut choper les images
	 * @return int nombre d'images insérées
	 */
	protected function _insertSubreddit($subreddit, $type) {
		$imgs = array();
		for ($i=0; $i < 10; $i++) {
			$xml = @simplexml_load_file('http://imgur.com/r/'.$subreddit.'/top/page/'.$i.'.xml');
			if ($xml) {
				foreach ($xml->item as $item) {
					$imgs[]= $this->_imgurImage($item) + array('type' => $type, 'source' => $subreddit);
				}
			}
		}
		return $this->_insertItems($imgs);
	}

	/**
	 * insert en base les images venant d'une recherche sur imgur
	 * @param  string $keyword mot(s) clé de la recherche qu'on fait sur imgur
	 * @return int nombre d'images insérées
	 */
	protected function _insertImgurFilteredGallery($keyword, $type) {
		$imgs = array();
		for ($i=0; $i < 3; $i++) {
			$xml = @simplexml_load_file('http://imgur.com/gallery/page/'.$i.'.xml?q='.str_replace(' ', '+', $keyword));
			if ($xml) {
				foreach ($xml->item as $item) {
					$imgs[]= $this->_imgurImage($item) + array('type' => $type, 'source' => $keyword);
				}
			}
		}
		return $this->_insertItems($imgs);
	}

	/**
	 * insert en base les dernières VDM http://viedemerde.fr
	 * @return int nombre de vdm insérées
	 */
	public function _insertVDM($source, $type) {
		$vdms = array();
		$xml = @simplexml_load_file($source);
		if ($xml) {
			foreach ($xml->entry as $item) {
				$vdms[]= $this->_vdmText($item) + array('type' => $type, 'source' => $source);
			}
		}
		return $this->_insertItems($vdms);
	}
}