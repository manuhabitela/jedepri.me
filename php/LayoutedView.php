<?php
//oui oui oui, une vue layoutée toutafé meussieu
class LayoutedView extends Slim\View {
	static protected $_layout = 'layout.php';

	public static function setLayout($layout=NULL) {
		self::$_layout = $layout;
	}
	
	public function render($template) {
		extract($this->data);
		$templatePath = $this->getTemplatesDirectory() . '/' . ltrim($template, '/');
		if ( !file_exists($templatePath) ) {
			throw new RuntimeException('View cannot render template `' . $templatePath . '`. Template does not exist.');
		}
		ob_start();
		require $templatePath;
		$html = ob_get_clean();
		return $this->_renderLayout($html);
	}
	
	public function _renderLayout($content_for_layout) {
		if(self::$_layout !== NULL) {
			$layout_path = $this->getTemplatesDirectory() . '/' . ltrim(self::$_layout, '/');
			if ( !file_exists($layout_path) ) {
				throw new RuntimeException('View cannot render layout `' . $layout_path . '`. Layout does not exist.');
			}
			ob_start();
			require $layout_path;
			$content_for_layout = ob_get_clean();
		}
		return $content_for_layout;
	}
}