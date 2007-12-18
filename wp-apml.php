<?php
/*
Plugin Name: APML support for WordPress
Plugin URI: http://notizblog.org/projects/apml-for-wordpress/
Description: This plugin creates an APML Feed using the tags and categories.
Version: 2.0
Author: Matthias Pfefferle
Author URI: http://notizblog.org/
*/

/* register */
if (isset($wp_version)) {
  add_filter('rewrite_rules_array', array('APML', 'rewriteRules'));
  add_action('parse_query', array('APML', 'apmlXml'));
  add_filter('query_vars', array('APML', 'queryVars'));
}

class APML { 
  static function getTags() {
    global $wp_version;
    global $wpdb;
    global $warriortags2tag;
    global $table_prefix;
    
    $simpletags = $table_prefix . "stp_tags";
    $tabletags = $table_prefix . "tags";
    $tablepost2tag = $table_prefix . "post2tag";
    
    if ($wp_version >= 2.3) {
      $tags = get_tags(); // Always query top tags
    } elseif ($wp_version >= 2.0) {
        if (class_exists('UltimateTagWarriorCore')) {
          $tags = $wpdb->get_results("SELECT tag as name, COUNT(*) count FROM $tabletags t inner join $tablepost2tag p2t on t.tag_id = p2t.tag_id GROUP BY tag");
        // check SimpleTagging Plugin
        } elseif (class_exists('SimpleTagging')) {
          $tags = $wpdb->get_results("SELECT tag_name as name, COUNT(*) count FROM $simpletags GROUP BY tag_name");
        }
    }
    
    return $tags;
  }
  
  static function getMaxTag() {
    global $wp_version;
    global $wpdb;
    global $table_prefix;
    
    $simpletags = $table_prefix . "stp_tags";
    
    if ($wp_version >= 2.3) {
      $tag_max = $wpdb->get_var("SELECT MAX(count) FROM $wpdb->term_taxonomy WHERE taxonomy = 'post_tag';");
    } elseif ($wp_version >= 2.0) {
        if (class_exists('UltimateTagWarriorCore')) :
          $tag_max = UltimateTagWarriorCore::GetMostPopularTagCount();
        // check SimpleTagging Plugin
        elseif (class_exists('SimpleTagging')) :
          $tag_max = $wpdb->get_var("SELECT count(tag_name) FROM $simpletags GROUP BY tag_name");
        endif;
    }
    
    
    return $tag_max;
  }
  
  static function getCategories() {
    global $wp_version;
    global $wpdb;

    if ($wp_version >= 2.3) {
      $categories = get_categories();
    } elseif ($wp_version >= 2.0) {
      $categories = $wpdb->get_results("SELECT * FROM $wpdb->categories");
    }

    return $categories;
  }
  
  static function getMaxCategory() {
    global $wp_version;
    global $wpdb;
    
    if ($wp_version >= 2.3) {
      $cat_max = $wpdb->get_var("SELECT MAX(count) FROM $wpdb->term_taxonomy WHERE taxonomy = 'category';");
    } elseif ($wp_version >= 2.0) {
      $cat_max = $wpdb->get_var("SELECT MAX(category_count) FROM $wpdb->categories");
    }
    
    return $cat_max;
  }
  
  /**
   * URL rewriting stuff, to serve xrds.xml
   */
  function rewriteRules($rules) {
    $apml_rules = array(
      'apml$' => 'index.php?apml=apml',
      //'wp-apml.php$' => 'index.php?apml=apml',
      'index.php/apml$' => 'index.php?apml=apml'
    );
    return $rules + $apml_rules;
  }
  
  /**
   * Add 'apml' as a valid query variables.
   **/
  function queryVars($vars) {
    $vars[] = 'apml';

    return $vars;
  }
  
  /**
   * Print APML document if 'apml' query variable is present
   **/
  function apmlXml($query) {

    if ($query) $apml = $query->query_vars['apml'];
    if (!empty($apml)) {
      self::printAPML();
    }
  }
  
  static function printAPML() {
    global $wp_version;

    $date = date('Y-m-d\Th:i:s');
    $url = get_bloginfo('url');
    $url = str_replace('https://', '', $url);
    $url = str_replace('http://', '', $url);
    
    $tags = self::getTags();
    $tag_max = self::getMaxTag();
    
    $categories = self::getCategories();
    $cat_max = self::getMaxCategory();
    
    header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
    echo '<?xml version="1.0"?>';
    
?>
<APML xmlns="http://www.apml.org/apml-0.6" version="0.6" >
<Head>
   <Title>Taxonomy APML for <?php echo get_bloginfo('name', 'display') ?></Title>
   <Generator>wordpress/<?php echo $wp_version ?></Generator>
   <DateCreated><?php echo $date; ?></DateCreated>
</Head>
<Body defaultprofile="taxonomy">
    <Profile name="tags">
        <ImplicitData>
            <Concepts>
<?php foreach ($tags as $tag) : ?>
                <Concept key="<?php echo $tag->name; ?>" value="<?php echo (($tag->count*100)/$tag_max)/100; ?>" from="<?php echo $url; ?>" updated="<?php echo $date; ?>"/>
<?php endforeach; ?>
            </Concepts>
        </ImplicitData>
    </Profile>
    <Profile name="categories">
        <ImplicitData>
            <Concepts>
<?php foreach ($categories as $cat) : ?>
                <Concept key="<?php echo isset($cat->name) ? $cat->name : $cat->cat_name; ?>" value="<?php echo ((isset($cat->count) ? $cat->count : $cat->category_count *100)/$cat_max)/100; ?>" from="<?php echo $url; ?>" updated="<?php echo $date; ?>"/>
                
<?php endforeach; ?>
            </Concepts>
        </ImplicitData>
    </Profile>
</Body>
</APML>
<?php exit; } 

} ?>