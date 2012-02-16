<?php
/**
 * 
 * CSS操作相关的静态类
 * @author welefen
 *
 */
class Fl_Css_Static {
	/**
	 * 
	 * @的详细类型
	 * @var array
	 */
	public static $atType = array ('@import ' => FL_TOKEN_CSS_AT_IMPORT, '@charset ' => FL_TOKEN_CSS_AT_CHARSET, '@media ' => FL_TOKEN_CSS_AT_MEDIA, '@font-face' => FL_TOKEN_CSS_AT_FONTFACE, '@page' => FL_TOKEN_CSS_AT_PAGE, '/^\@(?:\-(?:webkit|moz|o|ms)\-)?keyframes/' => FL_TOKEN_CSS_AT_KEYFRAMES );
	/**
	 * 
	 * 一些特殊的token
	 * @var array
	 */
	public static $specialTokens = array (array ('[;', ';]', FL_TOKEN_CSS_HACK ) );
	/**
	 * 
	 * CSS注释的正则
	 * @var RegexIterator
	 */
	public static $commentPattern = '/\/\*.*?\*\//';
	/**
	 * 
	 * 属性hack字符
	 * @var array
	 */
	public static $propertyHack = array ('*', '!', '$', '&', '*', '(', ')', '=', '%', '+', '@', ',', '.', '/', '`', '[', ']', '#', '~', '?', ':', '<', '>', '|', '_', '-', '£', '¬', '¦' );
	/**
	 * 
	 * 取出文本里的注释
	 * @param string $text
	 */
	public static function removeComment($text = '') {
		$text = preg_replace ( self::$commentPattern, '', $text );
		return $text;
	}
	/**
	 * 
	 * 获取@的详细类型
	 * @param string $text
	 */
	public static function getAtDetailType($text = '', Fl_Base $instance) {
		$text = self::removeComment ( $text );
		foreach ( self::$atType as $key => $type ) {
			if ($key {0} === '/') {
				if (preg_match ( $key, $text )) {
					return $type;
				}
			} else {
				if (strpos ( $text, $key ) === 0) {
					return $type;
				}
			}
		}
		return FL_TOKEN_CSS_AT_OTHER;
	}
}