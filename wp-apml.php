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
  add_filter('query_vars', array('APML', 'query_vars'));
  add_action('parse_query', array('APML', 'apml_xml'));
  add_action('init', array('APML', 'flush_rewrite_rules'));
  add_filter('generate_rewrite_rules', array('APML', 'rewrite_rules'));

  add_action('wp_head', array('APML', 'insert_meta_tags'), 5);
}

class APML {
  function getTags() {
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
    } else {
      $tags = null;
    }

    return $tags;
  }

  function getMaxTag() {
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
    } else {
      $tag_max = null;
    }

    return $tag_max;
  }

  function getCategories() {
    global $wp_version;
    global $wpdb;

    if ($wp_version >= 2.3) {
      $categories = get_categories();
    } elseif ($wp_version >= 2.0) {
      $categories = $wpdb->get_results("SELECT * FROM $wpdb->categories");
    } else {
      $categories = null;
    }

    return $categories;
  }

  function getMaxCategory() {
    global $wp_version;
    global $wpdb;

    if ($wp_version >= 2.3) {
      $cat_max = $wpdb->get_var("SELECT MAX(count) FROM $wpdb->term_taxonomy WHERE taxonomy = 'category';");
    } elseif ($wp_version >= 2.0) {
      $cat_max = $wpdb->get_var("SELECT MAX(category_count) FROM $wpdb->categories");
    } else {
      $cat_max = null;
    }

    return $cat_max;
  }

  function flush_rewrite_rules() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
  }

  function rewrite_rules($wp_rewrite) {
    $new_rules = array(
      'apml$' => 'index.php?apml=apml',
      'apml/(.+)' => 'index.php?apml=apml',
      'wp-apml.php$' => 'index.php?apml=apml'
    );
    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
  }

  /**
   * Add 'apml' as a valid query variables.
   **/
  function query_vars($vars) {
    $vars[] = 'apml';

    return $vars;
  }

  /**
   * Print APML document if 'apml' query variable is present
   **/
  function apml_xml() {
    global $wp_query;
    if( isset( $wp_query->query_vars['apml'] )) {
      APML::printAPML();
    }
  }

  function insert_meta_tags() {
    global $wp_rewrite;

    echo '<link rel="meta" type="text+xml" title="APML" href="'.get_option('home').($wp_rewrite->using_mod_rewrite_permalinks() ? '/apml/' : '/index.php?apml=apml').'" />' . "\n";
  }

  function printAPML() {
    global $wp_version;

    $date = date('Y-m-d\Th:i:s');
    $url = get_bloginfo('url');
    $url = str_replace('https://', '', $url);
    $url = str_replace('http://', '', $url);

    $tags = APML::getTags();
    $tag_max = APML::getMaxTag();

    $categories = APML::getCategories();
    $cat_max = APML::getMaxCategory();

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
<?php if (!empty($tags)) { ?>
<?php foreach ($tags as $tag) : ?>
                <Concept key="<?php echo $tag->name; ?>" value="<?php echo (($tag->count*100)/$tag_max)/100; ?>" from="<?php echo $url; ?>" updated="<?php echo $date; ?>"/>
<?php endforeach; ?>
<?php } ?>
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
<?php
exit;
  }
}
?>