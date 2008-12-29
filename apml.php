<?php
/*
Plugin Name: APML support for WordPress
Plugin URI: http://notizblog.org/projects/apml-for-wordpress/
Description: This plugin creates an APML Feed using tags and categories.
Version: 3.0
Author: Matthias Pfefferle
Author URI: http://notizblog.org/
*/

// register
if (isset($wp_version)) {
  add_filter('query_vars', array('Apml', 'query_vars'));
  add_action('parse_query', array('Apml', 'parse_query'));
  add_action('init', array('Apml', 'init'));
  add_filter('generate_rewrite_rules', array('Apml', 'rewrite_rules'));
  
  add_action('wp_head', array('Apml', 'meta_tags'), 5);
  
  add_filter('xrds_simple', array('Apml', 'xrds_apml_service'));
  add_filter('apml', array('Apml', 'apml_add_tags'));
}

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
  function init() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
  }
  
  /**
   * Insert the meta tags
   */
  function meta_tags() {
    global $wp_rewrite;
   
    echo '<link rel="meta" type="text/xml" title="APML" href="'.get_option('home').($wp_rewrite->using_mod_rewrite_permalinks() ? '/apml/' : '/index.php?apml').'" />' . "\n";
  }
  
  /**
   * Define the rewrite rules
   * 
   * @param array $wp_rewrite
   */
  function rewrite_rules($wp_rewrite) {
    $new_rules = array(
      'apml$' => 'index.php?apml',
      'wp-apml.php$' => 'index.php?apml'
    );
    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
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
  function parse_query() {
    global $wp_query;
    
    if( isset($wp_query->query_vars['apml']) ) {
      Apml::print_apml();
    }
  }
  
  /**
   *
   */
  function apml_add_profile($apml, $ProfileName, $ImplicitData, $ExplicitData) {
    $apml[$ProfileName]['ImplicitData']['Concepts'] = $ImplicitData['Concepts'];
    $apml[$ProfileName]['ExplicitData']['Concepts'] = $ExplicitData['Concepts'];
    
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
    
    $xml =  '<APML xmlns="http://www.apml.org/apml-1.0" version="1.0" >'."\n";
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
    header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
    echo '<?xml version="1.0"?>'."\n";
    echo Apml::generate_xml();
    exit;
  }
  
  /**
   * Contribute the WordPress Informations to the APML file
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
      $tags[] = array('Concept' => array('key' => $tag->name, 'value' => (($tag->count*100)/$tag_max)/100, 'from' => $url, 'updated' => $date));
    }
    
    return Apml::apml_add_profile($apml, 'tags', null, array('Concepts' => $tags));
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
        'Type' => array( array('content' => 'http://www.apml.org/apml-1.0') ),
        //'MediaType' => array( array('content' => 'application/apml+xml') ),
        'URI' => array( array('content' => get_option('home').($wp_rewrite->using_mod_rewrite_permalinks() ? '/apml/' : '/index.php?apml') ) ),
      )
    );
  
    return $xrds;
  }
}
?>