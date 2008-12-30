<?php
/*
Plugin Name: APML support for WordPress
Plugin URI: http://notizblog.org/projects/apml-for-wordpress/
Description: This plugin creates an APML Feed using tags and categories.
Version: branch
Author: Matthias Pfefferle
Author URI: http://notizblog.org/
*/

// register
if (isset($wp_version)) {
  add_filter('query_vars', array('Apml', 'query_vars'));
  add_action('parse_request', array('Apml', 'parse_request'));
  add_action('wp_head', array('Apml', 'meta_tags'), 5);
  
  // services/filters
  add_filter('xrds_simple', array('Apml', 'xrds_apml_service'));
  add_filter('apml', array('Apml', 'apml_add_tags'));
  add_filter('apml', array('Apml', 'apml_add_categories'));
  add_filter('apml', array('Apml', 'apml_add_links'));
  add_filter('apml', array('Apml', 'apml_add_feeds'));
}

/**
 * 
 * 
 * @param array $apml
 * @param string $ProfileName
 * @param array $ImplicitData
 * @param array $ExplicitData
 * 
 * @return
 */
function apml_add_profile($apml, $ProfileName, $ImplicitData, $ExplicitData) {
  Apml::apml_add_profile($apml, $ProfileName, $ImplicitData, $ExplicitData);
  
  return $apml;
}


/**
 * APML Class
 * 
 * @author Matthias Pfefferle
 */
class Apml {
  
  /**
   * Insert the meta tags
   */
  function meta_tags() {
    global $wp_rewrite;
   
    echo '<link rel="meta" type="application/xml+apml" title="APML 0.6" href="'.get_option('home').'/index.php?apml" />' . "\n";
  }

  /**
   * Add 'apml' as a valid query variables.
   * 
   * @param array $vars
   * @return array 
   */
  function query_vars($vars) {
    $vars[] = 'apml';

    return $vars;
  }

  /**
   * Print APML document if 'apml' query variable is present
   */
  function parse_request() {
    global $wp_query;

    if( isset($_GET['apml']) ) {
      Apml::print_apml();
    }
  }
  
  /**
   *
   */
  function apml_add_profile($apml, $ProfileName, $ImplicitData, $ExplicitData) {
    if ($ImplicitData) {
      foreach($ImplicitData as $concept => $data) {
        if (is_array($apml[$ProfileName]['ImplicitData'][$concept])) {
          $apml[$ProfileName]['ImplicitData'][$concept] = array_merge($apml[$ProfileName]['ImplicitData'][$concept], $ImplicitData[$concept]);
        } else {
          $apml[$ProfileName]['ImplicitData'][$concept] = $ImplicitData[$concept];
        }
      }
    } elseif ($ExplicitData) {
      foreach($ExplicitData as $concept => $data) {
        if (is_array($apml[$ProfileName]['ExplicitData'][$concept])) {
          $apml[$ProfileName]['ExplicitData'][$concept] = array_merge($apml[$ProfileName]['ExplicitData'][$concept], $ExplicitData[$concept]);
        } else {
          $apml[$ProfileName]['ExplicitData'][$concept] = $ExplicitData[$concept];
        }
      }
    }
    
    return $apml;
  }
  
  
  
  
  /**
   * Generate APML-XML output
   * 
   * @return string APML-XML output
   */
  function generate_xml() {
    global $wp_version;
    
    $apml = array();
    $apml = apply_filters('apml', $apml);
    
    $date = date('Y-m-d\Th:i:s');
    
    $xml =  '<APML xmlns="http://www.apml.org/apml-0.6" version="0.6" >'."\n";
    $xml .= '  <Head>'."\n";
    $xml .= '    <Title>APML for '.get_bloginfo('name', 'display').'</Title>'."\n";
    $xml .= '    <Generator>wordpress/'.$wp_version.'</Generator>'."\n";
    $xml .= '    <DateCreated>'.$date.'</DateCreated>'."\n";
    $xml .= '  </Head>'."\n";
    $xml .= '  <Body>'."\n";
    
    foreach ($apml as $name => $data) {
      $xml .= '   <Profile name="'.$name.'">'."\n";
  
      foreach ($data as $dataType => $dataConcepts) {
        $xml .= '     <'.$dataType.'>'."\n";
        foreach ($dataConcepts as $conceptType => $values) {
          $xml .= '       <'.$conceptType.'>'."\n";
          switch ($conceptType) {
            case "Concepts":
              foreach ($values as $value) {  
                $xml .= '         <Concept key="'.@$value['key'].'" value="'.@$value['value'].'" from="'.@$value['from'].'" updated="'.@$value['updated'].'"/>'."\n";
              }
              break;
            case "Sources":
              foreach ($values as $value) {  
                $xml .= '         <Source key="'.@$value['key'].'" name="'.@$value['name'].'" value="'.@$value['value'].'" type="'.@$value['type'].'" from="'.@$value['from'].'" updated="'.@$value['updated'].'" />'."\n";
              }
              break;
          }
          $xml .= '       </'.$conceptType.'>'."\n";
        }
        $xml .= '     </'.$dataType.'>'."\n";
      }
      $xml .= '   </Profile>'."\n";
    }
    
    $xml .= '  </Body>'."\n";
    $xml .= '</APML>';
    
    return $xml;
  }
  
  /**
   * prints the APML-file
   *
   */
  function print_apml() {
    header('Content-Type: application/apml+xml; charset=' . get_option('blog_charset'), true);
    //header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
    echo '<?xml version="1.0"?>'."\n";
    echo Apml::generate_xml();
    exit;
  }
  
  /**
   * Contribute the WordPress Tags to the APML file
   *
   * @param array $apml current APML array
   * @return array updated APML array
   */
  function apml_add_tags($apml) {
    global $wpdb;
    
    $date = date('Y-m-d\Th:i:s');
    $url = get_bloginfo('url');

    $tag_max = $wpdb->get_var("SELECT MAX(count) FROM $wpdb->term_taxonomy WHERE taxonomy = 'post_tag';");
    $tags = array();
    
    foreach (get_tags() as $tag) {
      $tags[] = array('key' => $tag->name,
                      'value' => (($tag->count*100)/$tag_max)/100,
                      'from' => $url,
                      'updated' => $date
                     );
    }
    
    return Apml::apml_add_profile($apml, 'tags', null, array('Concepts' => $tags));
  }
  
  /**
   * Contribute the WordPress Categories to the APML file
   *
   * @param array $apml current APML array
   * @return array updated APML array
   */
  function apml_add_categories($apml) {
    global $wpdb;

    $date = date('Y-m-d\Th:i:s');
    $url = get_bloginfo('url');

    $cat_max = $wpdb->get_var("SELECT MAX(count) FROM $wpdb->term_taxonomy WHERE taxonomy = 'category'");
    $cats = array();
    
    foreach (get_categories() as $cat) {
      $cats[] = array('key' => isset($cat->name) ? $cat->name : $cat->cat_name,
                      'value' => (isset($cat->count) ? $cat->count : $cat->category_count) *100/$cat_max/100,
                      'from' => $url,
                      'updated' => $date
                     );
    }
    
    return Apml::apml_add_profile($apml, 'categories', null, array('Concepts' => $cats));
  }
  
  /**
   * Contribute the WordPress Links to the APML file
   *
   * @param array $apml current APML array
   * @return array updated APML array
   */
  function apml_add_links($apml) {
    global $wpdb;
    
    $date = date('Y-m-d\Th:i:s');
    $url = get_bloginfo('url');
    
    $sql = "SELECT link_url, link_name, link_rel, link_rating
      FROM $wpdb->links
      WHERE link_visible = 'Y'
      ORDER BY link_name" ;

    $results = $wpdb->get_results($sql);
    $links = array();

    foreach($results as $link) {
      $links[] = array('key' => $link->link_url,
                       'name' => $link->link_name,
                       'value' => $link->link_rating != 0 ? $link->link_rating*100/9/100 : "1.0",
                       'type' => 'text/html',
                       'from' => $url,
                       'updated' => $date
                      );
    }
    
    return Apml::apml_add_profile($apml, 'links', null, array('Sources' => $links));
  }
  
  /**
   * Contribute the WordPress Feeds to the APML file
   *
   * @param array $apml current APML array
   * @return array updated APML array
   */
  function apml_add_feeds($apml) {
    global $wpdb;
    
    $date = date('Y-m-d\Th:i:s');
    $url = get_bloginfo('url');
    
    $sql = "SELECT link_rss, link_name, link_rel, link_rating
      FROM $wpdb->links
      WHERE link_visible = 'Y' AND link_rss != ''
      ORDER BY link_name" ;

    $results = $wpdb->get_results($sql);
    $links = array();

    foreach($results as $link) {
      $links[] = array('key' => $link->link_rss,
                       'name' => $link->link_name,
                       'value' => $link->link_rating != 0 ? $link->link_rating*100/9/100 : "1.0",
                       'type' => 'text/xml',
                       'from' => $url,
                       'updated' => $date
                      );
    }
    
    return Apml::apml_add_profile($apml, 'feeds', null, array('Sources' => $links));
  }
  
  /**
   * Contribute the APML Service to XRDS-Simple.
   *
   * @param array $xrds current XRDS-Simple array
   * @return array updated XRDS-Simple array
   */
  function xrds_apml_service($xrds) {
    global $wp_rewrite;
    
    $xrds = xrds_add_service($xrds, 'main', 'APML Service', 
      array(
        'Type' => array( array('content' => 'http://www.apml.org/apml-0.6') ),
        'MediaType' => array( array('content' => 'application/apml+xml') ),
        'URI' => array( array('content' => get_option('home').'/index.php?apml') ),
      )
    );
  
    return $xrds;
  }
}
?>