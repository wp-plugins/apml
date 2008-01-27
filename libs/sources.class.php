<?php
/*
 * Created on 28.12.2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class WordPressSources {
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
      $cat_max = $wpdb->get_var("SELECT MAX(count) FROM $wpdb->term_taxonomy WHERE taxonomy = 'category'");
    } elseif ($wp_version >= 2.0) {
      $cat_max = $wpdb->get_var("SELECT MAX(category_count) FROM $wpdb->categories");
    } else {
      $cat_max = null;
    }

    return $cat_max;
  }

  function getLinks() {
    global $wpdb;
    $sql = "SELECT link_url, link_name, link_rel, link_rating
      FROM $wpdb->links
      WHERE link_visible = 'Y'
      ORDER BY link_name" ;

    $results = $wpdb->get_results($sql);
    if (!$results) {
      return null;
    } else {
      return $results;
    }
  }
  
  function getFeedLinks() {
    global $wpdb;
    $sql = "SELECT link_rss, link_name, link_rel, link_rating
      FROM $wpdb->links
      WHERE link_visible = 'Y' AND link_rss != ''
      ORDER BY link_name" ;

    $results = $wpdb->get_results($sql);
    if (!$results) {
      return null;
    } else {
      return $results;
    }
  }
  
  function getTagsArray() {
    global $wpdb;
    $sql = "SELECT name
      FROM $wpdb->terms";

    $results = $wpdb->get_results($sql, 'ARRAY_N');
    if (!$results) {
      return null;
    } else {
    	foreach($results as $result) {
    		$r[] = strtolower($result[0]);
    	}
      return $r;
    }
  }
}
?>