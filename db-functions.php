<?php
/**
 * Cache framework
 * Author: Dmitry Svarytsevych, modified by Daniel Frużyński
 * http://design.lviv.ua
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

// Cache directory
// Need this here, because some people may upgrade manually by overwriting files 
// without deactivating cache
if ( !defined( 'DBCR_CACHE_DIR' ) ) {
	define( 'DBCR_CACHE_DIR', WP_CONTENT_DIR.'/tmp' );
}

class pcache {
	var $lifetime = 1800;
	var $storage = WP_CONTENT_DIR;

	function load( $tag = '' ) {
		if ( $tag == '' ) {
			return false;
		}

		$file = $this->storage.'/'.$tag;
		$result = false;
		
		// If file exists
		if ( $filemtime = @filemtime( $file ) ) {
			$f = @fopen( $file, 'r' );
			if ( $f ) {
				@flock( $f, LOCK_SH );
				// for PHP5
				if ( function_exists( 'stream_get_contents' ) ) {
					$result = unserialize( stream_get_contents( $f ) );
				} else { // for PHP4
					$result = '';
					while ( !feof( $f ) ) {
		  				$result .= fgets( $f, 4096 );
					}
					$result = unserialize( $result );
				}
				@flock( $f, LOCK_UN );
				@fclose( $f );

				// Remove if expired
				if ( ( $filemtime + $this->lifetime - time() ) < 0 ) {
					$this->remove( $tag );
				}
			}
		}

		return $result;
	}
	
	function save( $value = '', $tag = '' ) {
		if ( $tag == '' || $value == '' ) {
			return false;
		}
		
		$file = $this->storage."/".$tag;
		
		$f = @fopen( $file, 'w' );
		if ( !$f ) {
			return false;
		}
		
		@flock( $f, LOCK_EX );
		@fwrite( $f, serialize( $value ) );
		@flock( $f, LOCK_UN );
		@fclose( $f );
		@chmod( $file, 0644 );

		return true;
	}

	function remove( $tag = '', $dir = false ) {
		if ( $tag == '' ) {
			return false;
		}
		
		if ( !$dir ) {
			$dir = $this->storage;
		}
		
		$file = $dir.'/'.$tag;

		if ( is_file( $file ) && @unlink( $file ) ) {
			return true;
		}
		
		return false;
	}
	
	function clean( $old = true ) {
		$folders = array( DBCR_CACHE_DIR , DBCR_CACHE_DIR.'/options' , 
			DBCR_CACHE_DIR.'/links' , DBCR_CACHE_DIR.'/terms' , 
			DBCR_CACHE_DIR.'/users' , DBCR_CACHE_DIR.'/posts' );
		foreach( $folders as $folder ) {
			if ( $dir = @opendir( $folder ) ) {
				while ( $tag = readdir( $dir ) ) {
					if ( $tag != '.' && $tag != '..' && $tag != '.htaccess' ) {
						// Clean all
						if (!$old) {
							$this->remove( $tag, $folder );
						} elseif ( ( @filemtime( $file ) + $this->lifetime - time() ) < 0 ) {
							// Clean only old
						 	$this->remove( $tag );
						}
					}
				}
				closedir( $dir );
			}
		}
	}
}

?>