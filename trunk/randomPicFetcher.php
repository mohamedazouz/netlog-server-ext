<?php

//scrapes google search . Source: http://blog.5ubliminal.com/posts/google-image-search-new-edition/
// ---------------------------------------------------------- //
if(!function_exists('el_gg_imageSearch')):
	// Google Image search params
	define('GGIMG_PARAM_TYPE', 'imgtype', true);
	define('GGIMG_PARAM_SIZE', 'imgsz', true);
	define('GGIMG_PARAM_COLOR', 'imgc', true);
	define('GGIMG_PARAM_SPECOLOR', 'imgcolor', true);

	// Estimate sizes: &imgsz=
	define('GGIMG_SIZE_ANY', '', true);
	define('GGIMG_SIZE_ICON', 'i', true);
	define('GGIMG_SIZE_MEDIUM', 'm', true);
	define('GGIMG_SIZE_LARGE', 'l', true);
	// Larger than: &imgsz=
	define('GGIMG_SIZE_400x300', 'qsvga', true);
	define('GGIMG_SIZE_640x480', 'vga', true);
	define('GGIMG_SIZE_800x600', 'svga', true);
	define('GGIMG_SIZE_1024x768', 'xga', true);
	define('GGIMG_SIZE_1600x1200', '2mp', true);
	define('GGIMG_SIZE_2272x1704', '4mp', true);
	define('GGIMG_SIZE_2816x2112', '6mp', true);
	define('GGIMG_SIZE_3264x2448', '8mp', true);
	define('GGIMG_SIZE_3648x2736', '10mp', true);
	define('GGIMG_SIZE_4096x3072', '12mp', true);
	define('GGIMG_SIZE_4480x3360', '15mp', true);
	define('GGIMG_SIZE_5120x3840', '20mp', true);
	define('GGIMG_SIZE_7216x5412', '40mp', true);
	define('GGIMG_SIZE_9600x7200', '70mp', true);

	// Image types: &imgtype=
	define('GGIMG_TYPE_ANY', '', true);
	define('GGIMG_TYPE_FACE', 'face', true);
	define('GGIMG_TYPE_PHOTO', 'photo', true);
	define('GGIMG_TYPE_CLIPART', 'clipart', true);
	define('GGIMG_TYPE_LINEART', 'lineart', true);

	// Image types: &imgc=
	define('GGIMG_COLOR_ANY', '', true);
	define('GGIMG_COLOR_FULL', 'color', true);
	define('GGIMG_COLOR_GRAY', 'gray', true);
	define('GGIMG_COLOR_SPECIFIC', 'specific', true);

	// Specific colors &imgcolor=
	define('GGIMG_SPECOLOR_ANY', '', true);
	define('GGIMG_SPECOLOR_RED', 'red', true);
	define('GGIMG_SPECOLOR_ORANGE', 'orange', true);
	define('GGIMG_SPECOLOR_YELLOW', 'yellow', true);
	define('GGIMG_SPECOLOR_GREEN', 'green', true);
	define('GGIMG_SPECOLOR_TEAL', 'teal', true);
	define('GGIMG_SPECOLOR_PURPLE', 'purple', true);
	define('GGIMG_SPECOLOR_PINK', 'pink', true);
	define('GGIMG_SPECOLOR_WHITE', 'white', true);
	define('GGIMG_SPECOLOR_GRAY', 'gray', true);
	define('GGIMG_SPECOLOR_BROWN', 'brown', true);
	define('GGIMG_SPECOLOR_BLACK', 'black', true);

	/**
	* put your comment there...
	*
	* @param string $query
	* @param int $page
	* @param array $params
	*/
	function el_gg_imageSearch($query, $page = 1, $params = null){
		// Prepare default parameters
		$defaults = array(
			'q'		=> $query, // The query
			'start'		=> (($page - 1) * 21), // Start
			'hl'		=> 'en', // English is mandatory
			'um'		=> 1, // Not sure what this is
			'ie'		=> 'UTF-8', // HTML encoding
			'source'	=> 'hp', // Not sure what this is
			'tab'		=> 'wi', // Not sure what this is
		);
		// Merge parameters
		if(is_array($params) && !empty($params)) $defaults = array_merge($params, $defaults);
		// Remove the empty strings
		foreach($defaults as $k => $v){ if(is_string($v) && !strlen($v)) unset($defaults[$k]); }
		// Prepare the request URL
		$url = 'http://images.google.com/images?'.http_build_query($defaults);
		// Get the HTML of the image search
		$html = file_get_contents($url); // Or use elHttpClient here
		// Match all images, they are in JSONish format
		if(!preg_match('~dyn\.setResults\(\[\[(.+?)\]\]\);~si', $html, $matches)) return null;
		if(!count($matches = explode('],[', $matches[1]))) return null; // Split the images
		// Fix a few things inside the strings
		$decode_vars = create_function('$v', 'return urldecode(str_replace(array("\\x", "[]"), array("%", ","), $v));');
		$matches = array_map($decode_vars, $matches); // Decode the HTML content
		$images	= array(); // Extract images
		foreach($matches as $match){
			$match = trim($match, '[]');
			$slices = explode('","', substr($match, 1, strlen($match) - 2));
			$slices[2] = rtrim($slices[2], ':');
			$image = new stdClass(); // Prepare the new image
			$image->GoogleURL = sprintf('%s?q=tbn:%s:%s', $slices[14], $slices[2], $slices[3]);
			$image->Tbn = $slices[2];
			$image->URL->Thumb = sprintf('%s?q=tbn:%s', $slices[14], $slices[2]);
			$image->URL->Source = $slices[3];
			$image->Excerpt = strip_tags(htmlspecialchars_decode($slices[6]));
			$image->Size->Original = preg_replace('~^([0-9]+)\s+x\s+([0-9]+).+$~i', '$1 x $2', $slices[9]);
			$image->Size->Thumb = $slices[4].' x '.$slices[5];
			$images[$image->Tbn] = $image; // Insert image in results array
		}
		// Return images
		return $images;
	}
endif;
// ---------------------------------------------------------- //

function object2array($data)
{
   if(!is_object($data) && !is_array($data)) return $data;

   if(is_object($data)) $data = get_object_vars($data);

   return array_map('object2array', $data);
}

function getRandomImageUrl($query)
{
	$randomPage = rand(1,4);
	
	//will fetch medium-size google image search results
	$imageSearchResults  = el_gg_imageSearch($query, $randomPage, array(  GGIMG_PARAM_TYPE =>  GGIMG_TYPE_ANY,  GGIMG_PARAM_SIZE =>  GGIMG_SIZE_MEDIUM,  GGIMG_PARAM_COLOR => GGIMG_COLOR_ANY,  GGIMG_PARAM_SPECOLOR => GGIMG_SPECOLOR_ANY, )); 
	
	//select random chic from array
	$imageSearchResultsArray = object2array($imageSearchResults);
	$randomChickWithinPage = rand(0, 5);
	
	$i = 0;
	foreach ($imageSearchResultsArray as $key=>$container)
	{
		if ($i == $randomChickWithinPage)
		{
			$imgSource = $container['URL']['Source'];
			break;	
		}
		$i++;
	}
	return $imgSource;
}

function getRandomInternetChick()
{
	$randomQuery = rand (0, count(queries) - 1);
	$queries = Array('hot chick babe', 'babe', 'cute girl');
	$selectedQuery = $queries[$randomQuery];
	return getRandomImageUrl($selectedQuery);
}

?>