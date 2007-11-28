<?php

if (empty($wp)) {
	require_once('./wp-config.php');
	wp();
}

$tag_max = $wpdb->get_var("SELECT MAX(count) FROM $wpdb->term_taxonomy WHERE taxonomy = 'post_tag';");
$tags = get_tags(); // Always query top tags
$cat_max = $wpdb->get_var("SELECT MAX(count) FROM $wpdb->term_taxonomy WHERE taxonomy = 'category';");
$categories = get_categories();
$date = date('Y-m-d\Th:i:s');
$url = get_bloginfo('url');
$url = str_replace('https://', '', $url);
$url = str_replace('http://', '', $url);
//print_r($categories);

header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);

echo ('<?xml version="1.0"?>')

?>
<APML xmlns="http://www.apml.org/apml-0.6" version="0.6" ><Head>   <Title>Taxonomy APML for <?php echo get_bloginfo('name', 'display') ?></Title>   <Generator>wordpress/<?php bloginfo_rss('version') ?></Generator>   <DateCreated><?php echo $date; ?></DateCreated></Head><Body defaultprofile="taxonomy">    <Profile name="tags">        <ImplicitData>            <Concepts>
<?php foreach ($tags as $tag) : ?>
                <Concept key="<?php echo $tag->name; ?>" value="<?php echo (($tag->count*100)/$tag_max)/100; ?>" from="<?php echo $url; ?>" updated="<?php echo $date; ?>"/>
<?php endforeach; ?>
            </Concepts>        </ImplicitData>    </Profile>
    <Profile name="categories">        <ImplicitData>            <Concepts>
<?php foreach ($categories as $cat) : ?>
                <Concept key="<?php echo $cat->name; ?>" value="<?php echo (($cat->count*100)/$cat_max)/100; ?>" from="<?php echo $url; ?>" updated="<?php echo $date; ?>"/>
<?php endforeach; ?>
            </Concepts>        </ImplicitData>    </Profile></Body></APML>