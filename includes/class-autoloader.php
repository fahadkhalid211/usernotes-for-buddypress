<?php
/**
 * Class autoloader for BP_UserNotes namespace.
 *
 * @package BP_UserNotes
 */

namespace BP_UserNotes;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class responsible for loading plugin classes based on namespace.
 */
final class Autoloader {

	/**
	 * Namespace prefix for plugin classes.
	 *
	 * @var string
	 */
	private const NAMESPACE_PREFIX = 'BP_UserNotes\\';

	/**
	 * Base directory for class files.
	 *
	 * @var string
	 */
	private static string $base_dir;

	/**
	 * Register the autoloader with SPL.
	 *
	 * @param string $base_dir Root directory containing class files.
	 * @return void
	 */
	public static function register( string $base_dir ): void {
		self::$base_dir = rtrim( $base_dir, '/\\' ) . DIRECTORY_SEPARATOR;
		spl_autoload_register( [ __CLASS__, 'autoload' ] );
	}

	/**
	 * Load a class file if it belongs to the plugin's namespace.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( string $class ): void {
		if ( 0 !== strncmp( self::NAMESPACE_PREFIX, $class, strlen( self::NAMESPACE_PREFIX ) ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( self::NAMESPACE_PREFIX ) );
		$parts          = explode( '\\', $relative_class );
		$class_name     = array_pop( $parts );

		$subpath = '';
		if ( ! empty( $parts ) ) {
			$subpath = strtolower( implode( DIRECTORY_SEPARATOR, $parts ) ) . DIRECTORY_SEPARATOR;
		}

		// WordPress file naming convention: class-{name}.php with lowercase and dashes.
		$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
		$file_path = self::$base_dir . $subpath . $file_name;

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
}
