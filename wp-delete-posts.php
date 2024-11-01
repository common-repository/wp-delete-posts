<?php
/*
Plugin Name: WP Delete Posts
Plugin Script: wp-delete-posts.php
Plugin URI: http://marto.lazarov.org/plugins/wp-delete-posts
Description: Delete unwanted posts by keyword
Version: 1.0.4
Author: mlazarov
Author URI: http://marto.lazarov.org
*/

if (!class_exists('wp_delete_posts')) {
	class wp_delete_posts {
		var $optionsName = 'wp_delete_posts_options';
		var $localizationDomain = "wp_delete_posts";

		function wp_delete_posts() {
			$this->__construct();
		}
		function __construct() {

			// Setting filters, actions, hooks....
			add_action("admin_menu", array (
				& $this,
				"admin_menu_link"
			));

		}

		// -----------------------------------------------------------------------------------------------------------
		/**
		* @desc Adds the options subpanel
		*/
		function admin_menu_link() {
			add_management_page('WP Delete Posts', 'WP Delete Posts', 8, basename(__FILE__), array (
				& $this,
				'admin_options_page'
			));
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array (
				& $this,
				'filter_plugin_actions'
			), 10, 2);
		}

		// -----------------------------------------------------------------------------------------------------------
		/**
		* Adds the Settings link to the plugin activate/deactivate page
		*/
		function filter_plugin_actions($links, $file) {
			$settings_link = '<a href="tools.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links

			return $links;
		}

		// -----------------------------------------------------------------------------------------------------------
		/**
		* Administration options page
		*/
		function admin_options_page() {
			global $wpdb;

			?>
			<div class="wrap">
				<div id="dashboard" style="width:450px;padding:10px;">
					<h3>WP Delete Posts by Keywords</h3>
					<form method="post">
						<div  style="">
							<p><strong style="color:red;">Warning</strong>:
							This will delete all posts matching keywords added bellow!<br/>
							Remember to back up your database first in case something goes wrong!</p>
							Enter keywords separeted by comma:<br/>
							<textarea name="keywords" cols="56" rows="5" style="width:430px;height:60px;"><?=htmlspecialchars($_POST['keywords']);?></textarea><br/>

							Limit posts:<br/>
							<input type="text" name="limit" value="<?=($_POST['limit']?(int)$_POST['limit']:10);?>" size="4"/>
							<div style="text-align:right;"><input type="submit" name="wp_delete_posts" class="button-primary" value="Search Posts" /></div>
						</div>
					</form>
				</div>

				<?
				// DELETE Posts
				if ($_POST['wp_delete_posts']) {
					?>
					<div id="status" style="margin-top:20px;padding:10px;">
					<?
					$force = ($_POST['skip_trash']==1?1:0);

					$table_name = $wpdb->prefix . "posts";
					$words = explode(',',$_POST['keywords']);
					$keywords = array();
					foreach($words as $id=>$kw){
						$kw = trim($kw);
						$keywords[$id] = "post_title LIKE '%".$wpdb->escape($kw)."%' OR post_content LIKE '%".$wpdb->escape($kw)."%'";
						$search[$id] = $kw;
						$replace[$id] = '<font color="blue">'.htmlspecialchars($kw).'</font>';
					}
					if(count($keywords)){
						$SQL="SELECT ID,post_title FROM $table_name WHERE (".implode(' OR ',$keywords).") AND post_type='post' LIMIT ".(int)$_POST['limit'];
						?>
						<div style="padding:10px;border:1px solid #E3E3E3;background-color:white;">
						<?
						//echo $SQL.'<br/>';
						$posts = $wpdb->get_results($SQL);
						$postscount = count($posts);
						echo "<strong>Found $postscount posts matching:</strong> ".htmlspecialchars(implode(' or ',$words))."<br/>";
						echo "Limit: ".(int)$_POST['limit'];
						echo "<hr/>";
						foreach ($posts as $post) {
							$postid = $post->ID;
							if ($postid <> '') {
								echo "[$postid]» ".str_replace($search,$replace,$post->post_title);
								$custom_field_keys = get_post_custom_keys($postid);
								//foreach ($custom_field_keys as $key => $value) {
									//delete_post_meta($postid, $key, '');
								//	echo "delete_post_meta($postid, $key, '');";
								//}
								if($_POST['sure']==1){
									$result = wp_delete_post($postid,$force);
									//echo "\$result = wp_delete_post($postid,$force);";
									echo ' | <font color="red">deleted'.($force?'':' to trash').'</font>';
								}
								echo '<br/>';
							}
						}
						?>
						</div>
						<?
					}
					if($_POST['sure']!=1 && $postscount>0){
						?>
						<br/>
						<form method="post">
							<input type="checkbox" name="sure" value="1"/> Yes, delete these posts<br/>
							<input type="checkbox" name="skip_trash" value="1"/> Don't move deleted posts to trash (purge posts - there is no recovery option)<br/><br/>
							<input type="hidden" name="limit" value="<?=(int)$_POST['limit'];?>"/>
							<input type="hidden" name="keywords" value="<?=implode(',',$words);?>"/>
							<input type="submit" name="wp_delete_posts" class="button-primary" value="Delete Posts" />
						</form>
						<?
					}
					echo '</div>';
				}
				?>

			</div>
			<?php

		}

	} //End Class
}

if (class_exists('wp_delete_posts')) {
	$wp_delete_posts_var = new wp_delete_posts();
}
?>
