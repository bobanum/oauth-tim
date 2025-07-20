<?php

namespace Auth;

trait HasHTML {
	/**
	 * Generates a URL with query parameters and an optional hash.
	 *
	 * @param string $path The base path for the URL.
	 * @param array $params An associative array of query parameters.
	 * @param string|null $hash An optional hash to append to the URL.
	 * @return string The generated URL.
	 */
	public function url($path, $params = [], $hash = null) {
		$url = ltrim($path, '/');
		if (!empty($params)) {
			$url .= '?' . http_build_query($params);
		}
		if (!is_null($hash)) {
			$url .= '#' . trim($hash, '#');
		}
		return $url;
	}

	/**
	 * Generates an HTML tag with attributes and content.
	 *
	 * @param string $name The name of the HTML tag.
	 * @param string $content The content inside the tag.
	 * @param array $attributes An associative array of attributes for the tag.
	 * @return string The generated HTML tag.
	 */
	public function tag($name, $content, $attributes = []) {
		$attr = [];
		foreach ($attributes as $key => $value) {
			$attr[] = " $key=\"" . htmlspecialchars($value, ENT_QUOTES) . "\"";
		}
		$attr = implode('', $attr);
		return "<{$name}{$attr}>{$content}</{$name}>";
	}
}
