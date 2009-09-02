<?php
/*
Plugin Name: DB Cache Reloaded
Plugin URI: http://www.poradnik-webmastera.com/projekty/db_cache_reloaded/
Description: The fastest cache engine for WordPress, that produces cache of database queries with easy configuration. (Disable and enable caching after update)
Author: Daniel Frużyński
Version: 1.0
Author URI: http://www.poradnik-webmastera.com/
Text Domain: db-cache-reloaded
*/

/*  Original code Copyright Dmitry Svarytsevych
    Modifications Copyright 2009  Daniel Frużyński  (email : daniel [A-T] poradnik-webmastera.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'DBCR_DEBUG', false );

// Support for older versions
if ( !defined('WP_CONTENT_DIR') ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

// Path to plugin
if ( !defined( 'DBCR_PATH' ) ) {
	define( 'DBCR_PATH', WP_CONTENT_DIR.'/plugins/db-cache-reloaded' );
}

class DBCacheReloaded {
	var $config = null;
	var $folders = null;
	
	// Constructor
	function DBCacheReloaded() {
		$this->config = unserialize( @file_get_contents( WP_CONTENT_DIR."/db-config.ini" ) );
		$this->folders = array( "/tmp", "/tmp/options", "/tmp/links", "/tmp/terms", "/tmp/users", "/tmp/posts" );
		
		// Initialise plugin
		add_action( 'init', array( &$this, 'init' ) );
		
		// Create options menu
		add_action( 'admin_menu', array( &$this, 'dbcr_admin_menu' ) );
		// Uninstall
		add_action( 'deactivate_db-cache-reloaded/db-cache.php', array( &$this, 'dbcr_uninstall' ) );
		
		// Add cleaning on publish and new comment
		// Posts
		add_action( 'publish_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'edit_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'delete_post', array( &$this, 'dbcr_clear' ), 0 );
		// Comments
		add_action( 'trackback_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'pingback_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'comment_post', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'edit_comment', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'wp_set_comment_status', array( &$this, 'dbcr_clear' ), 0 );
		// Other
		add_action( 'delete_comment', array( &$this, 'dbcr_clear' ), 0 );
		add_action( 'switch_theme', array( &$this, 'dbcr_clear' ), 0 );
		
		// Display stats in footer
		add_action( 'wp_footer', 'loadstats', 999999 );
	}
	
	// Initialise plugin
	function init() {
		if ( function_exists( 'load_plugin_textdomain' ) ) {
			load_plugin_textdomain( 'db-cache-reloaded', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)) );
		}
	}

	// Create options menu
	function dbcr_admin_menu() {
		add_submenu_page( 'options-general.php', 'DB Cache Reloaded', 'DB Cache Reloaded', 
			10, __FILE__, array( &$this, 'dbcr_options_page' ) );
	}

	// Enable
	function dbcr_enable() {
		$status = false;
		if ( @copy( DBCR_PATH."/db-module.php", WP_CONTENT_DIR."/db.php" ) ) {
			$status = true;
		}
		
		foreach( $this->folders as $folder ) {
			if ( $status && @mkdir( WP_CONTENT_DIR.$folder, 0755 ) ) {
				$status = true;
			}
			if ( @copy( DBCR_PATH."/.htaccess", WP_CONTENT_DIR.$folder."/.htaccess" ) ) {
				$status = true;
			}
		}
	
		if ( $status ) {
			echo '<div id="message" class="updated fade"><p>';
			_e('Caching activated.', 'db-cache-reloaded');
			echo '</p></div>';
			return true;
		} else {
			echo '<div id="message" class="error"><p>';
			_e('Caching can\'t be activated. Please <a href="http://codex.wordpress.org/Changing_File_Permissions" target="blank">chmod 755</a> <u>wp-content</u> folder', 'db-cache-reloaded');
			echo '</p></div>';
			return false;
		}
	}

	// Disable
	function dbcr_disable() {
		$this->dbcr_uninstall();
		echo '<div id="message" class="updated fade"><p>';
		_e('Caching deactivated. Cache files deleted.', 'db-cache-reloaded');
		echo '</p></div>';
		
		return true;
	}

	// Uninstall
	function dbcr_uninstall() {
		@unlink( WP_CONTENT_DIR."/db.php" );
		@unlink( WP_CONTENT_DIR."/db-config.ini" );
		@unlink( WP_CONTENT_DIR."/tmp/.htaccess" );
		$this->dbcr_clear();
		
		foreach( $this->folders as $folder ) {
			@unlink( WP_CONTENT_DIR.$folder."/.htaccess" );
			@rmdir( WP_CONTENT_DIR.$folder."/" );
		}
		@rmdir( WP_CONTENT_DIR."/tmp/" );
	}

	// Clears the cache folder
	function dbcr_clear() {
		if ( !class_exists('pcache') ) {
			include DBCR_PATH."/db-functions.php";
		}
		$dbcr = new pcache();
		
		$dbcr->storage = WP_CONTENT_DIR."/tmp";
		
		$dbcr->clean( false );
	}
	
	// Settings page
	function dbcr_options_page() {
		if ( !isset( $this->config['timeout'] ) || intval( $this->config['timeout'] ) == 0) {
			$this->config['timeout'] = 5;
		} else {
			$this->config['timeout'] = intval( $this->config['timeout']/60 );
		}
		if ( !isset( $this->config['enabled'] ) ) {
			$this->config['enabled'] = false;
			$cache_enabled = false;
		} else {
			$cache_enabled = true;
		}
		if ( !isset( $this->config['loadstat'] ) ) {
			$this->config['loadstat'] = __('<!-- Generated in {timer} seconds. Made {queries} queries to database and {cached} cached queries. Memory used - {memory} -->', 'db-cache-reloaded');
		}
		if ( !isset( $this->config['filter'] ) ) {
			$this->config['filter'] = "_posts|_postmeta";
		}
		if ( defined( 'DBCR_DEBUG' ) && DBCR_DEBUG ) {
			$this->config['debug'] = 1;
		}
		
		if ( !class_exists( 'pcache' ) ) {
			include DBCR_PATH."/db-functions.php";
		}
		
		if ( isset( $_POST['clear'] ) ) {
			check_admin_referer( 'db-cache-reloaded-update-options' );
			$db_cache = new pcache();
			$db_cache->storage = WP_CONTENT_DIR."/tmp";
			$db_cache->clean( false );
			echo '<div id="message" class="updated fade"><p>';
			_e('Cache files deleted.', 'db-cache-reloaded');
			echo '</p></div>';
		} elseif ( isset( $_POST['clearold'] ) ) {
			check_admin_referer( 'db-cache-reloaded-update-options' );
			$db_cache = new pcache();
			$db_cache->storage = WP_CONTENT_DIR."/tmp";
			$db_cache->clean();
			echo '<div id="message" class="updated fade"><p>';
			_e('Expired cache files deleted.', 'db-cache-reloaded');
			echo '</p></div>';
		} elseif ( isset( $_POST['save'] ) ) {
			check_admin_referer( 'db-cache-reloaded-update-options' );
			$saveconfig = $this->config = $this->dbcr_request( 'options' );
		
			if ( defined( 'DBCR_DEBUG' ) && DBCR_DEBUG ) {
				$saveconfig['debug'] = 1;
			}
			if ( $saveconfig['timeout'] == '' || !is_numeric( $saveconfig['timeout'] ) ) {
				$this->config['timeout'] = 5;
			}
		
			// Convert to seconds for save
			$saveconfig['timeout'] = intval( $this->config['timeout']*60 );
		
			if ( !isset( $saveconfig['filter'] ) ) {
				$saveconfig['filter'] = '';
			}
		
			// Activate/deactivate caching
			if ( !isset( $this->config['enabled'] ) && $cache_enabled ) {
				$this->dbcr_disable();
			} elseif ( isset( $this->config['enabled'] ) && $this->config['enabled'] == 1 && !$cache_enabled ) {
				if ( !$this->dbcr_enable() ) {
					unset( $this->config['enabled'] );
				} else {
					$this->config['lastclean'] = time();
				}
			}
		
			$file = fopen( WP_CONTENT_DIR."/db-config.ini", 'w+' );
			if ( $file ) {
				fwrite( $file, serialize( $saveconfig ) );
				fclose( $file );
				echo '<div id="message" class="updated fade"><p>';
				_e('Settings saved.', 'db-cache-reloaded');
				echo '</p></div>';
			} else {
				echo '<div id="message" class="error"><p>';
				_e('Settings can\'t be saved. Please <a href="http://codex.wordpress.org/Changing_File_Permissions" target="blank">chmod 755</a> file <u>config.ini</u>', 'db-cache-reloaded');
				echo '</p></div>';
			}
		}
?>
<div class="wrap">
<form method="post">
<?php wp_nonce_field('db-cache-reloaded-update-options'); ?>
<h2><?php _e('DB Cache Reloaded - Options', 'db-cache-reloaded'); ?></h2>
        
<h3><?php _e('Configuration', 'db-cache-reloaded'); ?></h3>
<table class="form-table">
	<tr valign="top">
		<?php $this->dbcr_field_checkbox( 'enabled', __('Enable', 'db-cache-reloaded') ); ?>
	</tr>
	<tr valign="top">
		<?php $this->dbcr_field_text( 'timeout', __('Expire a cached query after', 'db-cache-reloaded'),
			__('minutes. <em>(Expired files are deleted automatically)</em>', 'db-cache-reloaded'), 'size="5"' ); ?>
	</tr>
</table>

<h3><?php _e('Additional options', 'db-cache-reloaded'); ?></h3>
<table class="form-table">
	<tr valign="top">
		<?php $this->dbcr_field_text( 'filter', __('Cache filter', 'db-cache-reloaded'), 
			'<br/>'.__('Do not cache queries that contains this input contents. Divide different filters with \'|\' (vertical line, e.g. \'_posts|_postmeta\')', 'db-cache-reloaded'), 'size="100"' ); ?>
	</tr>
	<tr valign="top">
		<?php $this->dbcr_field_text( 'loadstat', __('Load stats template', 'db-cache-reloaded'), 
			'<br/>'.__('It shows resources usage statistics in your template footer. To disable view just leave this field empty.<br/>{timer} - generation time, {queries} - count of queries to DB, {cached} - cached queries, {memory} - memory', 'db-cache-reloaded'), 'size="100"' ); ?>
	</tr>
</table>

<p class="submit">
	<input class="button" type="submit" name="save" value="<?php _e('Save', 'db-cache-reloaded'); ?>">  
	<input class="button" type="submit" name="clear" value="<?php _e('Clear the cache', 'db-cache-reloaded'); ?>">
	<input class="button" type="submit" name="clearold" value="<?php _e('Clear the expired cache', 'db-cache-reloaded'); ?>">
</p>      
</form>
</div>
<?php
	}
	
	// Other functions used on options page
	function dbcr_request( $name, $default=null ) {
		if ( !isset( $_POST[$name]) ) {
			return $default;
		}
		
		return $_POST[$name];
	}
	
	function dbcr_field_checkbox( $name, $label='', $tips='', $attrs='' ) {
		echo '<th scope="row">';
		echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
		echo '<td><input type="checkbox" ' . $attrs . ' name="options[' . $name . ']" value="1" ';
		checked( isset( $this->config[$name] ) && $this->config[$name], true );
		echo '/> ' . $tips . '</td>';
	}
	
	function dbcr_field_text($name, $label='', $tips='', $attrs='') {
		if ( strpos($attrs, 'size') === false ) {
			$attrs .= 'size="30"';
		}
		echo '<th scope="row">';
		echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
		echo '<td><input type="text" ' . $attrs . ' name="options[' . $name . ']" value="' . 
			htmlspecialchars($this->config[$name]) . '"/>';
		echo ' ' . $tips;
		echo '</td>';
	}
	
	function dbcr_field_textarea( $name, $label='', $tips='', $attrs='' ) {
		if ( strpos( $attrs, 'cols' ) === false ) {
			$attrs .= 'cols="70"';
		}
		if ( strpos( $attrs, 'rows' ) === false ) {
			$attrs .= 'rows="5"';
		}
		
		echo '<th scope="row">';
		echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
		echo '<td><textarea wrap="off" ' . $attrs . ' name="options[' . $name . ']">' .
			htmlspecialchars($this->config[$name]) . '</textarea>';
		echo '<br />' . $tips;
		echo '</td>';
	}
}

$wp_db_cache_reloaded = new DBCacheReloaded();

function get_num_cachequeries() {
	global $wpdb;
	return isset( $wpdb->num_cachequeries ) ? $wpdb->num_cachequeries : 0;
}

/* 
Function to display load statistics
Put in your template <? loadstats(); ?>
*/
function loadstats() {
	global $wp_db_cache_reloaded;

	if ( strlen( $wp_db_cache_reloaded->config['loadstat'] ) > 7) {
		$stats['timer'] = timer_stop();
		$replace['timer'] = "{timer}";
		
		$stats['normal'] = get_num_queries();
		$replace['normal'] = "{queries}";
		
		$stats['cached'] = get_num_cachequeries();
		$replace['cached'] = "{cached}";
		
		if ( function_exists( 'memory_get_usage' ) ) {
			$stats['memory'] = round( memory_get_usage()/1024/1024, 2) . 'MB';
		} else {
			$stats['memory'] = 'N/A';
		}
		$replace['memory'] = "{memory}";
		
		$config['loadstat'] = str_replace( $replace, $stats, $wp_db_cache_reloaded->config['loadstat'] );
		
		echo $wp_db_cache_reloaded->config['loadstat'];
	}
	
	echo "\n<!-- Cached by DB Cache Reloaded -->\n";
}

?>