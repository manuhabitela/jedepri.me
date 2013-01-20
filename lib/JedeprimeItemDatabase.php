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

	protected $table;

	function __construct($pdo, $sourceFile, $debug = 0) {
		$this->db = $pdo;
		$this->sources = json_decode(file_get_contents($sourceFile), true);
		libxml_use_internal_errors(true);
		$this->debug = $debug;
		$this->table = $debug ? 'jedeprime_items_dev' : 'jedeprime_items';
	}

	/**
	 * met à jour la BDD avec les sources données à la construction de l'objet
	 * @param  string $givenType met à jour uniquement un certain type de donnée
	 */
	public function fillDB($givenType = null) {
		$methods = self::$sourceTypeMethods;
		if ($this->_isUpdateOk() || $this->debug == 1) {
			$this->ids = $this->getItemIds();
			foreach ($this->sources as $type => $sources) {
				if ($givenType == null || $type == $givenType) {
					foreach ($sources as $source => $sourceInfo) {
						$sourceOpts = array('type' => $type);
						$sourceOpts['title'] = empty($sourceInfo[1]) ? false : $sourceInfo[1];
						if (method_exists($this, $methods[$type]))
							$this->{$methods[$type]}($source, $sourceOpts);
					}
				}
			}
			$now = date('Y-m-d H:i:s');
			$this->db->exec('UPDATE jedeprime_config c SET c.value = "'.$now.'" where c.key = "lastUpdate"');
			$this->db->exec('UPDATE jedeprime_config c SET c.value = 0 where c.key = "updating"');
		}
	}

	/**
	 * supprime les éléments de base de données : vieux cookies + vielles photos inactives
	 * @param  array  $options 
	 *         				since: les éléments plus vieux que le temps donné seront supprimés. 30 jours par défaut ("30 days")
	 *         				cookies: supprime-t-on les vieux cookies ? oui par défaut
	 *         				active: supprime-t-on les vieux items inactifs ? oui par défaut
	 */
	public function cleanDB($options = array()) {
		$options = $options + array('since' => "30 days", 'cookies' => true, 'active' => true);
		$since = date('Y-m-d H:i:s', strtotime("-".$options['since']));

		if ($options['cookies']) {
			$ids = array();
			$query = "DELETE FROM jedeprime_cookies_ids WHERE cookie_id IN (
						SELECT id FROM jedeprime_cookies WHERE created < \"".$since."\"
					)";
			$data = $this->db->exec($query);
			$query = "DELETE FROM jedeprime_cookies WHERE created < \"".$since."\"";
			$data = $this->db->exec($query);
		}

		if ($options['active']) {
			$ids = array();
			$query = "DELETE FROM ".$this->table." WHERE active=0 AND created < \"".$since."\"";
			$data = $this->db->exec($query);
		}
	}

	/**
	 * liste des trucs en base
	 * @return array tous les hash des objets de la base classés par id
	 */
	public function getItemIds() {
		$ids = array();
		$data = $this->db->query('SELECT id, hash from '.$this->table.' order by id');
		if ($data) {
			while ($row = $data->fetch(PDO::FETCH_OBJ)) {
				$ids[$row->id]= $row->hash;
			}
		}
		return $ids;
	}

	public function getItemById($id) {
		$query = 'SELECT * from '.$this->table.' WHERE id = '.$id;
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
	 *         			"except" : id du cookie utilisateur (on retrouve les images déjà vues pour les exclure)
	 *         			"exceptSources" : tableau de [type, source] à ne pas intégrer dans la recherce
	 *         			"weighted": est-ce qu'on prend en compte le poids des sources ? oui par défaut
	 * @return array      truc (image, ou citation genre vdm)
	 */
	public function getRandomItem($options = array()) {
		$options = $options + array('select' => "*", 'except' => array(), 'exceptSources' => array(), 'weighted' => true);
		$query = 'SELECT '.$options['select'].' from '.$this->table.' WHERE active=1';
		if (!empty($options['except'])) {
			$query .= " AND id NOT IN (SELECT item_id FROM jedeprime_cookies_ids WHERE cookie_id = ".$options['except'].")";
		}
		if ($options['weighted'] && $filter = $this->_getNotSoRandomSource($options['exceptSources'])) {
			$query .= " AND `source-type` = \"".addslashes($filter['type'])."\" AND source = \"".addslashes($filter['source'])."\"";
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

	public function getRandomItemId($not = null) {
		$item = $this->getRandomItem(array('select' => 'id', 'except' => $not));
		if ($item) return $item['id'];
		return false;
	}

	public function getRandomItemSlug($not = null) {
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
			foreach ($sources as $source => $sourceInfo) {
				$weight = $sourceInfo[0];
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
	 * tableau représentant un objet de la base
	 * champs: id, slug, title, content, content-type, source, source-type, external-url, hash, created
	 * @param  array $item tableau non complet décrivant l'objet
	 * @return array       tableau complet décrivant l'objet
	 */
	protected function _item($item, $options = array()) {
		$item = $item + array(
			'content' => '',
			'content-type' => '',
			'title' => '',
			'url' => '',
			'source-type' => !empty($options['type']) ? $options['type'] : ''
		);
		$item['hash'] = md5($item['url'].$item['content']);
		if (!empty($item['id']))
			$item['slug'] = $this->getItemSlugById($item['id']);
		if (isset($options['title']) && empty($options['title']))
			$item['title'] = '';
		return $item;
	}

	/**
	 * transforme un objet xml représentant une image sur tumblr en un tableau avec des attributs communs à toute image en base
	 * @param  SimpleXMLElement $xml objet xml représentant l'image 
	 * @return array tableau représentant l'image ayant une 'src', une 'url', un 'title', et le 'xml'
	 */
	protected function _tumblrImage($xml, $options) {
		$image = array(
			"url" => (string) $xml['url'],
			'content-type' => 'img-url'
		);
		if ($xml['type'] == 'photo') {
			$image['content'] = (string) $xml->{'photo-url'}[1];
			$image['title'] = strip_tags((string) $xml->{'photo-caption'});
		}
		if ($xml['type'] == 'regular') {
			preg_match('/<p><img.+src="(.+)"\/><\/p>/s', html_entity_decode($xml->{'regular-body'}), $src); // A LA CRADE OUAIS
			$image['content'] = $src[1];
			$image['title'] = (string) $xml->{'regular-title'};
		}
		return !empty($image['content']) ? $this->_item($image, $options) : false;
	}

	/**
	 * transforme un objet xml représentant une image sur imgur en un tableau avec des attributs communs à toute image en base
	 * @param  SimpleXMLElement $xml objet xml représentant l'image 
	 * @return array tableau représentant l'image ayant une 'src', une 'url', un 'title', et le 'xml'
	 */
	protected function _imgurImage($xml, $options) {
		return $this->_item(array(
			"content" => (string) 'http://i.imgur.com/'.$xml->hash.$xml->ext,
			"content-type" => 'img-url',
			"url" => (string) 'http://imgur.com/'.$xml->hash,
			"title" => (string) $xml->title
		), $options);
	}

	/**
	 * transforme un objet xml représentant une vdm sur le rss vdm en un tableau avec des attributs communs à tout objet
	 * @param  SimpleXMLElement $xml objet xml représentant la vdm
	 * @return array tableau représentant la vdm ayant une 'src', un 'title', et le 'xml'
	 */
	protected function _vdmText($xml, $options) {
		return $this->_item(array(
			"url" => (string) $xml->id,
			"content" => (string) $xml->content,
			"content-type" => "text"
		), $options);
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
	 * @param  array $items tableau d'objets
	 * @return int nombre d'objets insérées
	 */
	protected function _insertItems($items) {
		if (empty($this->ids)) $this->ids = $this->getItemIds();
		$query = 'INSERT INTO '.$this->table.'(hash, content, `content-type`, `external-url`, title, `source-type`, source) VALUES ';
		$queryValues = array();
		foreach ($items as $item) {
			if (!empty($item['hash']) && !in_array($item['hash'], $this->ids))
				$queryValues[]= '("'.$item['hash'].'", "'.addslashes($item['content']).'", "'.$item['content-type'].'", "'.addslashes($item['url']).'", "'.addslashes($item['title']).'", "'.addslashes($item['source-type']).'", "'.addslashes($item['source']).'")';
		}
		if (empty($queryValues))
			return false;
		$query = $query.implode(', ', $queryValues)." ON DUPLICATE KEY UPDATE hash=hash;";
		return $this->db->exec($query);
	}

	/**
	 * insert en base les images venant d'un tumblr
	 * @param  string $tumblr url du tumblr dont on veut choper les images
	 * @return int nombre d'images insérées
	 */
	protected function _insertTumblr($tumblr, $options) {
		$tumblrType = substr(strstr($options['type'], '-'), 1);
		$max = 300;
		$num = 50;
		//avec les tumblr on passe par yql, ça semble mieux passer qu'en direct, bizarrement...
		$checkMax = @simplexml_load_file("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20xml%20where%20url%3D'".urlencode($tumblr.'/api/read?num=1')."'");
		if ($checkMax) $max = (int) $checkMax->results->tumblr->posts['total']; //download allll the posts
		$pages = floor($max/$num) > 5 ? 5 : floor($max/$num);
		if ($max < $num && $pages == 0) {
			$pages = 1;
			$num = $max;
		}
		$imgs = array();
		for ($i=0; $i < $pages; $i++) {
			$xml = @simplexml_load_file("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20xml%20where%20url%3D'".urlencode($tumblr.'/api/read?start='.($i*$num).'&num='.$num.'&type='.$tumblrType)."'");
			if (!empty($xml->results->tumblr->posts)) {
				foreach ($xml->results->tumblr->posts->children() as $item) {
					$img = $this->_tumblrImage($item, $options);
					if ($img)
						$imgs[] = $img + array('source' => $tumblr);
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
	protected function _insertSubreddit($subreddit, $options) {
		$imgs = array();
		for ($i=0; $i < 2; $i++) {
			$xml = @simplexml_load_file('http://imgur.com/r/'.$subreddit.'/top/page/'.$i.'.xml');
			if ($xml) {
				foreach ($xml->item as $item) {
					$imgs[]= $this->_imgurImage($item, $options) + array('source' => $subreddit);
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
	protected function _insertImgurFilteredGallery($keyword, $options) {
		$imgs = array();
		for ($i=0; $i < 2; $i++) {
			$xml = @simplexml_load_file('http://imgur.com/gallery/page/'.$i.'.xml?q='.str_replace(' ', '+', $keyword));
			if ($xml) {
				foreach ($xml->item as $item) {
					$imgs[]= $this->_imgurImage($item, $options) + array('source' => $keyword);
				}
			}
		}
		return $this->_insertItems($imgs);
	}

	/**
	 * insert en base les dernières VDM http://viedemerde.fr
	 * @return int nombre de vdm insérées
	 */
	protected function _insertVDM($source, $options) {
		$vdms = array();
		$xml = @simplexml_load_file($source);
		if ($xml) {
			foreach ($xml->entry as $item) {
				$vdms[]= $this->_vdmText($item, $options) + array('source' => $source);
			}
		}
		return $this->_insertItems($vdms);
	}

	protected function _updateAllHashes() {
		$q = $this->db->query('select * from '.$this->table);
		while ($row = $q->fetch(PDO::FETCH_BOTH)) {
			$this->db->exec('UPDATE '.$this->table.' SET hash = "'.md5($row['external-url'].$row['content']).'" where id='.$row['id']);
		}
	}


	public function createCookie() {
		$q = $this->db->query('select MAX(cookie_id) as max from jedeprime_cookies_ids');
		$max = $q->fetch(PDO::FETCH_BOTH);
		$q = $this->db->query('INSERT INTO jedeprime_cookies(id) VALUES ('.($max['max']+1).')');
		$this->db->exec($q);
		return $max['max']+1;
	}

	/**
	 * marque l'item donné comme vu pour l'utilisateur représenté par le cookie donné
	 * @param int $cookieId
	 * @param int $itemId
	 */
	public function addSeenId($cookieId, $itemId) {
		$query = 'INSERT INTO jedeprime_cookies_ids(cookie_id, item_id) VALUES ('.$cookieId.', '.$itemId.') ON DUPLICATE KEY UPDATE cookie_id=cookie_id';
		return $this->db->exec($query);
	}

	/**
	 * récupère x items de la base au pif
	 * @param  integer $limit nombre d'items à récupérer, 100 par défaut
	 * @return array         tableau d'items
	 */
	public function getRandomItems($limit = 100) {
		$items = array();
		$query = "SELECT * FROM ".$this->table." WHERE active=1 ORDER BY RAND() LIMIT 0,".$limit;
		$data = $this->db->query($query);
		if ($data) {
			while ($item = $data->fetch(PDO::FETCH_BOTH)) {
				$items[]= $this->_item($item);
			}
		}
		return $items;
	}

	public function banItemById($itemId) {
		$query = "UPDATE ".$this->table." SET active=0 WHERE id=".$itemId;
		return $this->db->exec($query);
	}
}