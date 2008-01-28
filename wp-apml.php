<?php
/*
Plugin Name: APML support for WordPress
Plugin URI: http://notizblog.org/projects/apml-for-wordpress/
Description: This plugin creates an APML Feed using the tags and categories.
Version: 2.3.1
Author: Matthias Pfefferle
Author URI: http://notizblog.org/
*/

// include the wordpress sources like tags, categories and links
require_once('libs/sources.class.php');
require_once('libs/apml_parser.php');

// register
if (isset($wp_version)) {
  add_filter('query_vars', array('APML', 'query_vars'));
  add_action('parse_query', array('APML', 'apml_xml'));
  add_action('widgets_init', array('APML', 'widget_init'));
  add_action('init', array('APML', 'init'));
  add_filter('generate_rewrite_rules', array('APML', 'rewrite_rules'));
  
  add_action('wp_head', array('APML', 'insert_meta_tags'), 5);
}

global $taglist;

/**
 * APML Class
 * 
 */
class APML {
  function init() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'apml', APML::get_path() . '/js/apml.js', array('jquery') );
  }
  
  function widget_init() {
    global $wp_version, $wp_rewrite, $wp_query;
    
    /**
     * sidebar widget code to display the search form
     */
    function apml_search_widget($args) {
      global $wp_rewrite, $before_widget, $before_title, $after_title, $after_widget;
    
      extract($args);
    
      echo $before_widget;
      echo $before_title;
      echo (!empty($title)) ? $title : "Find interesting posts";
      echo $after_title; 
?>
    <p>Use your <a href="http://www.apml.org/endusers/overview/"><abbr title="Attention Profiling Mark-up Language">APML</abbr>-Feed</a> to find weblog-posts that might interest you.</p>
    <form name="apml" method="get" action="<?php echo get_option('home').($wp_rewrite->using_mod_rewrite_permalinks() ? '/apml/search/' : '/index.php?apml=search') ?>" class="apml-search-form">  
      <?php if (!$wp_rewrite->using_mod_rewrite_permalinks())  { echo '<input type="hidden" name="apml" value="search" />'; } ?>
      <input type="text" name="s" class="apml-search-field" />
      <input type="submit" value="Go" class="apml-search-button" />
    </form>
<?php
      echo $after_widget;  
    }
    
    /**
     * sidebar widget code to display the search form
     */
    function apml_meta_widget($args) {
      global $wp_rewrite, $wp_query, $before_widget, $before_title, $after_title, $after_widget;
    
      if ($wp_query->query_vars['apml'] == "search") {
        extract($args);
    
        echo $before_widget;
        echo $before_title;
        echo (!empty($title)) ? $title : "APML Matching";
        echo $after_title;
      
        $tags = explode(",", $wp_query->query_vars['tag']);
      
        //print_r($wp_query);
        $endtag = false;
?>
        <p>Your interests are matching with this blog:</p>
        <div style="width: 100%; border: 1px solid #000; text-align: left;">
          <p style="width: <?php echo $wp_query->query_vars['apml_matching']; ?>; text-align: center; background: #ccc; padding: 0; margin: 0; color: #000"><?php echo $wp_query->query_vars['apml_matching']; ?></p>
        </div>
        <div>The matching "tags" are:
<?php for ($i = 0; $i < count($tags); $i++) { ?>
          <a href="<?php echo get_tag_link() . $tags[$i]; ?>" rel="tag"><?php echo $tags[$i]; ?></a>
          <?php if ($i == 20 && count($tags) > 20) { 
            $endtag = true;
          ?>
            <div id="apml_tags">
          <?php } ?>
<?php } ?>
          <?php if($endtag == true) { ?>
          	</div>
            <p><a href="#" id="apml_more_link">show/hide more</a></p>
          <?php } ?>
        </div>
        <p>APML url: <a href="<?php echo html_special_chars( $wp_query->query_vars['s'] )?>"><?php echo html_special_chars( $wp_query->query_vars['s'] )?></a></p>
<?php      
        echo $after_widget;
      }
    }
    
    if ($wp_version >= 2.3 && (!function_exists( "register_sidebar_widget" ) || !function_exists( "register_widget_control" )))
      return;
    
    register_sidebar_widget('APML Matching', 'apml_search_widget');
    register_sidebar_widget('APML Meta', 'apml_meta_widget');
  }
  
  /**
   * Set the path for the plugin.
   */
  function get_path($abs = false) {
    $plugin = 'wp-apml';

    $base = plugin_basename(__FILE__);
    if ($base != __FILE__) {
      $plugin = dirname($base);
    }

    $path = 'wp-content/plugins/' . $plugin;
    
    if ($abs)
      return ABSPATH . $path;
    else
      return get_option('siteurl') . '/' . $path;
  }
  
  /**
   * Define the rewrite rules
   */
  function rewrite_rules($wp_rewrite) {
    $new_rules = array(
      'apml$' => 'index.php?apml=apml',
      'apml/(.+)' => 'index.php?apml=' . $wp_rewrite->preg_index(1),
      'wp-apml.php$' => 'index.php?apml=apml'
    );
    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
  }

  /**
   * Add 'apml' as a valid query variables.
   */
  function query_vars($vars) {
    $vars[] = 'apml';

    return $vars;
  }

  /**
   * Print APML document if 'apml' query variable is present
   */
  function apml_xml() {
    global $wp_query, $wp_version;;
    
    $vars = array('tags', 'categories', 'links', 'feeds');
    
    $var = $wp_query->query_vars['apml'];
    
    if( isset( $var )) {
    	if ($var == 'search' && $wp_version >= 2.3) {
    		add_action('template_redirect', array('APML', 'template_redirect'));
      } elseif (in_array($var, $vars)) {
        APML::printAPML($var);
      } else {
      	APML::printAPML();
      }
    }
  }
  
  /**
   * 
   **/
  function template_redirect() {
  	global $wp_query, $wpdb;
    
    $wp_query->is_single = false;
    $wp_query->is_page = false;
    $wp_query->is_archive = false;
    $wp_query->is_home = false;
    $wp_query->is_404 = false;
    $wp_query->is_archive = false;
    $wp_query->is_tag = false;
    $wp_query->is_search = false;

    $apml_array = APML::get_apml_as_array();
    
    // find matching tags
    $matching_tags = array_intersect($apml_array, WordPressSources::getTagsArray());
    
    $matching = round(count($matching_tags)*100/count($apml_array), 2) . "%";
    
    $taglist = implode (',',$matching_tags);
    
    $s = html_special_chars( $wp_query->query_vars['s'] );

    $wp_query->query('tag='.$taglist);
    
    $wp_query->set("s", $s);
    $wp_query->set("apml", "search");
    $wp_query->set("apml_matching", $matching);
    
    $wp_query->is_search = true;
  }
  
  /**
   * 
   */
  function get_apml_as_array() {
  	global $wp_query;
    
  	// define url of .apml file
    $url = html_special_chars( $wp_query->query_vars['s'] );

    $apml_array = wp_cache_get($url);
    if ($apml_array == false) {
      // define new parser class
      $parser = new APML_Parser();
    
      // get concepts
      $apml_array = $parser->getAPMLConcepts($url);
    
      wp_cache_add($url, $apml_array);
    }
    return $apml_array;
  }
  
  /**
   * Insert the meta tags
   */
  function insert_meta_tags() {
    global $wp_rewrite;
    
    $css_path = APML::get_path() . '/css/apml-style.css';
        
    echo '<link rel="meta" type="text/xml" title="APML" href="'.get_option('home').($wp_rewrite->using_mod_rewrite_permalinks() ? '/apml/' : '/index.php?apml=apml').'" />' . "\n";
    echo '<link rel="stylesheet" type="text/css" href="'.$css_path.'" />' . "\n";
  }
  
  /**
   * APML-XML output
   */
  function printAPML($var = 'apml') {
    global $wp_version;

    $date = date('Y-m-d\Th:i:s');
    $url = get_bloginfo('url');
    $url = str_replace('https://', '', $url);
    $url = str_replace('http://', '', $url);
    
    header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
    echo '<?xml version="1.0"?>';
?>
<APML xmlns="http://www.apml.org/apml-0.6" version="0.6" >
<Head>
   <Title>APML for <?php echo get_bloginfo('name', 'display') ?></Title>
   <Generator>wordpress/<?php echo $wp_version ?></Generator>
   <DateCreated><?php echo $date; ?></DateCreated>
</Head>
<Body defaultprofile="<?php echo $var == 'apml' ? 'tags' : $var ?>">
<?php
  if ($var == 'tags' OR $var == 'apml') {
    $tags = WordPressSources::getTags();
    $tag_max = WordPressSources::getMaxTag();
?>
    <Profile name="tags">
        <ImplicitData>
            <Concepts>
<?php if (!empty($tags)) { ?>
<?php foreach ($tags as $tag) { ?>
                <Concept key="<?php echo $tag->name; ?>" value="<?php echo (($tag->count*100)/$tag_max)/100; ?>" from="<?php echo $url; ?>" updated="<?php echo $date; ?>"/>
<?php } ?>
<?php } ?>
            </Concepts>
        </ImplicitData>
    </Profile>
<?php
  } 
  if ($var == 'categories' OR $var == 'apml') {
  	$categories = WordPressSources::getCategories();
    $cat_max = WordPressSources::getMaxCategory();
?>
    <Profile name="categories">
        <ImplicitData>
            <Concepts>
<?php foreach ($categories as $cat) { ?>
                <Concept key="<?php echo isset($cat->name) ? $cat->name : $cat->cat_name; ?>" value="<?php echo (isset($cat->count) ? $cat->count : $cat->category_count) *100/$cat_max/100; ?>" from="<?php echo $url; ?>" updated="<?php echo $date; ?>"/>
<?php } ?>
            </Concepts>
        </ImplicitData>
    </Profile>
<?php
  }
  
  if ($var == 'links' OR $var == 'apml') {
    $links = WordPressSources::getLinks();
?>
    <Profile name="links">
        <ExplicitData>
            <Concepts>
<?php foreach ($links as $link) { ?>
                <Source key="<?php echo $link->link_url ?>" name="<?php echo $link->link_name ?>" value="<?php echo $link->link_rating != 0 ? $link->link_rating*100/9/100 : "1.0"; ?>" type="text/html" from="<?php echo $url; ?>" updated="<?php echo $date; ?>">
                    <Author key="<?php echo $link->link_name ?>" value="1.0" from="<?php echo $url; ?>" updated="<?php echo $date; ?>" />
                </Source>
<?php } ?>
            </Concepts>
        </ExplicitData>
    </Profile>
<?php
  }
  
  if ($var == 'feeds' OR $var == 'apml') {
    $feed_links = WordPressSources::getFeedLinks();
?>
    <Profile name="feeds">
        <ExplicitData>
            <Concepts>
<?php foreach ($feed_links as $link) { ?>
                <Source key="<?php echo $link->link_rss ?>" name="<?php echo $link->link_name ?>" value="<?php echo $link->link_rating != 0 ? $link->link_rating*100/9/100 : "1.0"; ?>" type="text/xml" from="<?php echo $url; ?>" updated="<?php echo $date; ?>" />
<?php } ?>
            </Concepts>
        </ExplicitData>
    </Profile>
<?php } ?>
</Body>
</APML>
<?php
exit;
  }
}
?>