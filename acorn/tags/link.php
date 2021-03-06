<?php
  
// Link Tag
// created by Andy Braren
// A kirbytag that displays nicely-formatted links to internal (and eventually external) pages

/* CHANGELOG
2016-11-15 - Initial creation. Focused on internal links, grabbing hero image, date, author, excerpt, title
*/

// link tag
kirbytext::$tags['link'] = array(
  'attr' => array(
    'title',
    'author',
    'date',
    'excerpt',
    'image',
  ),
  'html' => function($tag) {
    
    $url       = $tag->attr('link');
    $title     = $tag->attr('title');
    $author    = $tag->attr('author');
    $date      = $tag->attr('date');
    $excerpt   = $tag->attr('excerpt');
    $quote     = $tag->attr('quote'); // Not used for anything
    $image     = $tag->attr('image');
    
    
    $host = parse_url($url, PHP_URL_HOST);
    
    $manual_image_url = null;
    $new_excerpt = null;
    
    
    // $tag->page()
    // The page that's calling the tag
    
    // site()->page(trim(parse_url($url, PHP_URL_PATH), '/')
    // The internal page that's being linked to
    
    
    // Check if the url is internal
    if (strpos($url, 'http') !== false) {
      if ($_SERVER['SERVER_NAME'] == parse_url($url, PHP_URL_HOST)) {
        $internal = true;
      } else {
        $internal = false;
      }
    } else {
      $internal = true;
    }

    
    // If the URL is external and the title does not exist then the tag has
    // never been processed and should be
    
    if ($internal) {
      
      if (strpos($url, 'http') !== false) {
        $internalpage = site()->page(trim(parse_url($url, PHP_URL_PATH), '/'));
      } else {
        $internalpage = site()->page($url);
      }
            
      $title = $internalpage->title();
      $date = date('M j Y', $internalpage->datePublished());
      $excerpt = $internalpage->excerpt();
      $image = ($internalpage->heroImage()) ? $internalpage->heroImage()->crop(170, 110) : '';
            
      $author = $internalpage->authors()->first(); // Get the first (primary) author of a page
      if (site()->user($author)) {                 // If the author is registered, get their info
        $author = site()->user($author);
        $author = $author->firstname() . ' ' . $author->lastname();
      }
      
    } else {
      
      $checkAgain = false;
      if ($image) {
        if (!$tag->page()->image($image)) {
          $checkAgain = true;
        }
      }
      
      // If the tag doesn't have a title field, then it hasn't been processed
      // and should be. This will cache the Open Graph data within the tag
      // itself to reduce external requests and thereby improve page load time
      if (!isset($title) or $checkAgain) {
        
        // Grab the external page's Open Graph data
        $og = get_opengraph($url);
        $title = $og['title'] ?? $url; // Make the URL the title if one isn't found just to prevent re-processing
        $new_excerpt = $og['description'] ?? null;
        $image_url = $og['image'] ?? null;
        
        // Download the og:image
        if ($image_url and strpos($image_url, 'http') !== false) {
          //$image_filename = 'link-' . str_replace('.','-',$host) . '-' . str_replace('.','-', pathinfo($image_url, PATHINFO_FILENAME));
          $image_filename = 'link-' . str_replace('.','-',$host) . '-' . uniqid();
          
          $downloadedURL = downloadedImageURL($image_filename, $tag->page(), $image_url);
          
          //$new_image = $image_filename . '.' . strtolower(pathinfo($image_url, PATHINFO_EXTENSION));
          $new_image = basename($downloadedURL);
          
          //$image = '<img src="' . downloadedImageURL($image_filename, $tag->page(), $image_url) . '">';
          $manual_image_url = $tag->page()->url() . '/' . $new_image;
          //$manual_image_url = 'https://dev.acorn.blog/cache/thumbs/articles/update-2017/hero-170x110.jpg';
        }
        
        // Create the new tag
        $title = sanitizeText($title);
        $new_title = ' title: ' . $title;
        
        if (isset($new_excerpt)) {
          $excerpt = sanitizeText($new_excerpt);
          $new_excerpt = ' excerpt: ' . $excerpt;
        } else {
          $new_excerpt = '';
        }
        if (isset($new_image)) {
          $new_image = ' image: ' . $new_image;
        } else {
          $new_image = '';
        }
        
        $newtag = '(link: ' . $url . $new_title . $new_excerpt . $new_image;
        
        // Replace the old tag with the new tag containing more information
        $oldtag = '(link: ' . $url;
        $newtext = str_replace($oldtag, $newtag, $tag->page()->content()->text());
        
        $tag->page()->update(array(
          'text' => $newtext
        ));
        
      } else {
        
      }
      
    }
    
    $sitename = parse_url($url, PHP_URL_HOST);
    
    if ($author) {
      $author = $author . ' - ';
    }
    
    if ($date) {
      $date = ' - ' . date('M j Y', strtotime($date));
    }
    
    // A bit weird, but ensures that the image is loaded even on the first page load
    if ($manual_image_url) {
      $image = '<img src="' . $manual_image_url . '">';
    } elseif ($image) {
      if ($tag->page()->image($image)) {
        $image = '<img src="' . $tag->page()->image($image)->resize(340)->url() . '">';
      }
    } else {
      $image = '';
    }
    
    // Generate and return the HTML
    //$classes = ($quote) ? 'link quote' : 'link';
    $classes = 'link';
    $title = '<strong>' . $title . '</strong>';
    $excerpt = '<span>' . $excerpt . '</span>';
    $sitename = isset($sitename) ? $sitename : '';
    $info = '<span class="info">' . $author . $sitename . $date . '</span>';
    
    if (isFeedRequest()) {
      return '<a href="' . $url . '">' . 'Link: ' . $title . '</a>';
    }
    
    $html = '<div class="' . $classes . '"><a href="' . $url . '">' . '<div>' . '<div class="linkcontent">' . $title . $excerpt . '</div>'. $info . '</div>' . $image . '</a></div>';
    
    return $html;
    
  }
);






function get_opengraph($url) {
  
  $og = array();
  $graph = OpenGraph::fetch($url);
  
  //print_r($graph);
  
  foreach ($graph as $key => $value) {
    switch ($key) {
      case 'title':
        //$og['title'] = $value; break;
        //$og['title'] = (!empty($value)) ?? null; break;
        $og['title'] = ($value != '') ? $value : null; break;
      case 'description':
        //$og['description'] = $value; break;
        $og['description'] = ($value != '') ? substr($value,0,300) : null; break; // Gets the first 300 characters
      case 'image':
        $og['image'] = $value; break;
    }
  }
  return $og;
}


function sanitizeText($text) {
  
  $cleaned  = str_replace(array('(',')',),array('[',']'),$text);
  $nobreaks = str_replace("\n", ' ', $cleaned);
  $other = str_replace(array('…','...'),'',$nobreaks);
  $nomultispace = preg_replace('!\s+!', ' ', $other);
  
  $blah = trim($nomultispace);
  
  $final = $blah;
  return $final;
}











/*
  Copyright 2010 Scott MacVicar

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

	Original can be found at https://github.com/scottmac/opengraph/blob/master/OpenGraph.php

*/

class OpenGraph implements Iterator
{
  /**
   * There are base schema's based on type, this is just
   * a map so that the schema can be obtained
   *
   */
	public static $TYPES = array(
		'activity' => array('activity', 'sport'),
		'business' => array('bar', 'company', 'cafe', 'hotel', 'restaurant'),
		'group' => array('cause', 'sports_league', 'sports_team'),
		'organization' => array('band', 'government', 'non_profit', 'school', 'university'),
		'person' => array('actor', 'athlete', 'author', 'director', 'musician', 'politician', 'public_figure'),
		'place' => array('city', 'country', 'landmark', 'state_province'),
		'product' => array('album', 'book', 'drink', 'food', 'game', 'movie', 'product', 'song', 'tv_show'),
		'website' => array('blog', 'website'),
	);
	
  /**
   * Holds all the Open Graph values we've parsed from a page
   *
   */
	private $_values = array();
	
  /**
   * Fetches a URI and parses it for Open Graph data, returns
   * false on error.
   *
   * @param $URI    URI to page to parse for Open Graph data
   * @return OpenGraph
   */
	static public function fetch($URI) {
    $cookie_path = 'cookie.txt';
    if ( defined('COOKIE_PATH_FOR_CURL') && !empty(COOKIE_PATH_FOR_CURL) ){
      $cookie_path = COOKIE_PATH_FOR_CURL;
    }
    $curl = curl_init($URI);

    curl_setopt($curl, CURLOPT_FAILONERROR, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, "facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)");
    //The following 2 set up lines work with sites like www.nytimes.com
    curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_path); //you can change this path to whetever you want.
    curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_path); //you can change this path to whetever you want.

    $response = mb_convert_encoding(curl_exec($curl), 'HTML-ENTITIES', 'UTF-8');

    curl_close($curl);

    if (!empty($response)) {
        return self::_parse($response);
    } else {
        return false;
    }
	}
	
	static public function parse($HTML){
		if ( empty( $HTML ) ){
			return false;
		}
		$response = mb_convert_encoding($HTML, 'HTML-ENTITIES', 'UTF-8');
		return self::_parse($response);
	}

  /**
   * Parses HTML and extracts Open Graph data, this assumes
   * the document is at least well formed.
   *
   * @param $HTML    HTML to parse
   * @return OpenGraph
   */
	static private function _parse($HTML) {
		$old_libxml_error = libxml_use_internal_errors(true);

		$doc = new DOMDocument();
		$doc->loadHTML($HTML);

		libxml_use_internal_errors($old_libxml_error);

		$tags = $doc->getElementsByTagName('meta');
		if (!$tags || $tags->length === 0) {
			return false;
		}

		$page = new self();

		$nonOgDescription = null;

		foreach ($tags AS $tag) {
			if ($tag->hasAttribute('property') && strpos($tag->getAttribute('property'), 'og:') === 0) {
				$key = strtr(substr($tag->getAttribute('property'), 3), '-', '_');

		        if( array_key_exists($key, $page->_values) ){
					if ( !array_key_exists($key.'_additional', $page->_values) ){
						$page->_values[$key.'_additional'] = array();
					}
		        	$page->_values[$key.'_additional'][] = $tag->getAttribute('content');
		        }else{
		        	$page->_values[$key] = $tag->getAttribute('content');
		        }
			}

			//Added this if loop to retrieve description values from sites like the New York Times who have malformed it.
			if ($tag->hasAttribute('value') && $tag->hasAttribute('property') &&
			    strpos($tag->getAttribute('property'), 'og:') === 0) {
				$key = strtr(substr($tag->getAttribute('property'), 3), '-', '_');
				$page->_values[$key] = $tag->getAttribute('value');
			}
			//Based on modifications at https://github.com/bashofmann/opengraph/blob/master/src/OpenGraph/OpenGraph.php
			if ($tag->hasAttribute('name') && $tag->getAttribute('name') === 'description') {
                $nonOgDescription = $tag->getAttribute('content');
            }

			if ($tag->hasAttribute('property') &&
			    strpos($tag->getAttribute('property'), 'twitter:') === 0) {
				$key = strtr($tag->getAttribute('property'), '-:', '__');
				$page->_values[$key] = $tag->getAttribute('content');
			}

			if ($tag->hasAttribute('name') &&
				strpos($tag->getAttribute('name'), 'twitter:') === 0) {
				$key = strtr($tag->getAttribute('name'), '-:', '__');
				if( array_key_exists($key, $page->_values) ){
					if (!array_key_exists($key.'_additional', $page->_values)){
						$page->_values[$key.'_additional'] = array();
					}
					$page->_values[$key.'_additional'][] = $tag->getAttribute('content');
				} else {
					$page->_values[$key] = $tag->getAttribute('content');
				}
			}

			// Notably this will not work if you declare type after you declare type values on a page.
			if ( array_key_exists('type', $page->_values) ){
				$meta_key = $page->_values['type'].':';
				if ($tag->hasAttribute('property') && strpos($tag->getAttribute('property'), $meta_key) === 0) {
					$meta_key_len = strlen($meta_key);
					$key = strtr(substr($tag->getAttribute('property'), $meta_key_len), '-', '_');
					$key = $page->_values['type'].'_'.$key;

					if( array_key_exists($key, $page->_values) ){
						if ( !array_key_exists($key.'_additional', $page->_values) ){
							$page->_values[$key.'_additional'] = array();
						}
						$page->_values[$key.'_additional'][] = $tag->getAttribute('content');
					}else{
						$page->_values[$key] = $tag->getAttribute('content');
					}
				}
			}
		}

		//Based on modifications at https://github.com/bashofmann/opengraph/blob/master/src/OpenGraph/OpenGraph.php
		if (!isset($page->_values['title'])) {
            $titles = $doc->getElementsByTagName('title');
            if ($titles->length > 0) {
                $page->_values['title'] = $titles->item(0)->textContent;
            }
        }
        if (!isset($page->_values['description']) && $nonOgDescription) {
            $page->_values['description'] = $nonOgDescription;
        }

        //Fallback to use image_src if ogp::image isn't set.
        if (!isset($page->_values['image'])) {
            $domxpath = new DOMXPath($doc);
            $elements = $domxpath->query("//link[@rel='image_src']");

            if ($elements->length > 0) {
                $domattr = $elements->item(0)->attributes->getNamedItem('href');
                if ($domattr) {
                    $page->_values['image'] = $domattr->value;
                    $page->_values['image_src'] = $domattr->value;
                }
            } else if (!empty($page->_values['twitter_image'])){
				$page->_values['image'] = $page->_values['twitter_image'];
			} else {
				$elements = $doc->getElementsByTagName("img");
				foreach ( $elements as $tag ){
					if ($tag->hasAttribute('width') && ( ($tag->getAttribute('width') > 300) || ($tag->getAttribute('width') == '100%') ) ){
						$page->_values['image'] = $tag->getAttribute('src');
						break;
					}
				}
			}
        }

		if (empty($page->_values)) { return false; }

		return $page;
	}

  /**
   * Helper method to access attributes directly
   * Example:
   * $graph->title
   *
   * @param $key    Key to fetch from the lookup
   */
	public function __get($key) {
		if (array_key_exists($key, $this->_values)) {
			return $this->_values[$key];
		}

		if ($key === 'schema') {
			foreach (self::$TYPES AS $schema => $types) {
				if (array_search($this->_values['type'], $types)) {
					return $schema;
				}
			}
		}
	}

  /**
   * Return all the keys found on the page
   *
   * @return array
   */
	public function keys() {
		return array_keys($this->_values);
	}

  /**
   * Helper method to check an attribute exists
   *
   * @param $key
   */
	public function __isset($key) {
		return array_key_exists($key, $this->_values);
	}

  /**
   * Will return true if the page has location data embedded
   *
   * @return boolean Check if the page has location data
   */
	public function hasLocation() {
		if (array_key_exists('latitude', $this->_values) && array_key_exists('longitude', $this->_values)) {
			return true;
		}

		$address_keys = array('street_address', 'locality', 'region', 'postal_code', 'country_name');
		$valid_address = true;
		foreach ($address_keys AS $key) {
			$valid_address = ($valid_address && array_key_exists($key, $this->_values));
		}
		return $valid_address;
	}

  /**
   * Iterator code
   */
	private $_position = 0;
	public function rewind() { reset($this->_values); $this->_position = 0; }
	public function current() { return current($this->_values); }
	public function key() { return key($this->_values); }
	public function next() { next($this->_values); ++$this->_position; }
	public function valid() { return $this->_position < sizeof($this->_values); }
}