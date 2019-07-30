<?php
/**
 * Plugin Name:     CAWeb Document Sitemap
 * Plugin URI:      https://caweb.cdt.ca.gov
 * Description:     Creates a sitemap for PDF and Word documents
 * Author:          Tim Loden
 * Author URI:      https://caweb.cdt.ca.gov
 * Text Domain:     caweb-doc-sitemap
 * Version:         1.0.0
 *
 * @package         Caweb_doc_sitemap
 */

// add plugin page under tools

add_action('admin_menu', 'caweb_doc_page_create');

function caweb_doc_page_create() {
	$parent_slug = 'tools.php';
    $page_title = 'CAWeb Document Sitemap';
    $menu_title = 'Document Sitemap';
    $capability = 'manage_sites';
    $menu_slug = 'caweb_docuemnt_sitemap';
    $function = 'caweb_doc_page';

    add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
}

function caweb_doc_page() {
	
	// set out put directory

	$directory = wp_upload_dir();
	$file = $directory['basedir'] . '/pdf-word-sitemap.xml';
    
    $site_id = get_current_blog_id();


    // get correct posts table

    if ($site_id === 1) {
    	$wp_posts_table = 'wp_posts';
    } else {
		$wp_posts_table = 'wp_' . $site_id . '_posts';
    }
    
    // start admin output

    ?>
    	<div class="wrap">
		<h1>CAWeb Document Sitemap</h1>
		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
		<input type="hidden" name="action" value="create_doc_sitemap">
		<?php submit_button('Create Document Sitemap') ?>
		</form>
	<?php



	//caweb_doc_create_xml();

    echo('</div>');

}

add_action( 'admin_post_create_doc_sitemap', 'caweb_doc_create_xml' );

function caweb_doc_create_xml() {
	$site_id = get_current_blog_id();
	$directory = wp_upload_dir();

	if (is_multisite()) {
		$file = $directory['basedir'] . '/sites/' . $site_id . '/pdf-word-sitemap.xml';
	} else {
		$file = $directory['basedir'] . '/pdf-word-sitemap.xml';
	}

	if ($site_id === 1) {
    	$wp_posts_table = 'wp_posts';
    } else {
		$wp_posts_table = 'wp_' . $site_id . '_posts';
    }

	global $wpdb;

	$count = 0;
	
	$results = $wpdb->get_results( "SELECT `guid` FROM {$wp_posts_table} WHERE `post_mime_type` IN ('application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')", OBJECT );

	$dom = new DOMDocument('1.0','UTF-8');
 	$dom->formatOutput = true;

 	$urlset = $dom->createElement('urlset');
	$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
	$urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
	$urlset->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

	foreach ( $results as $result ) {
		$url = $dom->createElement('url');
		$urlset->appendChild($url);
    	$url->appendChild( $dom->createElement('loc', $result->guid) );
    	$count++;
	}
	$dom->appendChild($urlset);

	//echo '<xmp>'. $dom->saveXML() .'</xmp>';

	$output = $dom->saveXML();

	$dom->save($file);

	if (is_multisite()) {
		$file_url = $directory['baseurl'] . '/sites/' . $site_id . '/pdf-word-sitemap.xml';

	} else {
		$file_url = $directory['baseurl'] . '/pdf-word-sitemap.xml';

	}

	wp_redirect(admin_url('admin.php?page=caweb_docuemnt_sitemap&count=' . $count . '&path=' . $file_url .'&message=success'));
}

add_action( 'admin_notices', 'caweb_doc_admin_notices' );

function caweb_doc_admin_notices() {
   
   if ( ! isset( $_GET['count'] ) ) {
     return;
   }

   $count = $_GET['count'];
   $path = $_GET['path'];

   ?>
   <div class="updated">
      <p>Sitemap created with <strong><?php echo $count; ?></strong> entries<br><br>File location: <strong><?php echo $path; ?></strong></p>
   </div>
   <?php
}

register_deactivation_hook( __FILE__, 'caweb_doc_deactivation' );

function caweb_doc_deactivation() {
   
    $directory = wp_upload_dir();
   	$site_id = get_current_blog_id();

    if (is_multisite()) {
		$file_url = $directory['basedir'] . '/sites/' . $site_id . '/pdf-word-sitemap.xml';
		wp_delete_file($file_url);
	} else {
		$file_url = $directory['basedir'] . '/pdf-word-sitemap.xml';
		wp_delete_file($file_url);
	}
}