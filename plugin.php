<?php
/**
 * Recover Points plugin
 *
 * @package Recover Points
 * @version 1.0.0
 * @since   1.0.0
 */

class Recovery_Points extends Plugin {

	/**
	 * Zip file
	 *
	 * This variable define if the extension zip is loaded.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    boolean
	 */
	private $zip = false;

	/**
	 * Backup directories
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array
	 */
	private $backup_dirs = [
		PATH_PAGES,
		PATH_DATABASES
	];

	/**
	 * Initialize plugin
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function init() {

		$this->formButtons = false;

		// Plugin options for database.
		$this->dbFields = [
			'limit' => 15,
			'link'  => false
		];

		// Check for zip extension installed.
		$this->zip = extension_loaded( 'zip' );

		if ( ! $this->installed() ) {
			$Tmp = new dbJSON( $this->filenameDb );
			$this->db = $Tmp->db;
			$this->prepare();
		}
	}

	/**
	 * Uninstall
	 *
	 * Differs from parent method in that
	 * it retains the workspace directory
	 * when the plugin is deactivated.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return boolean
	 */
	public function uninstall() {

		// Delete database.
		$path = PATH_PLUGINS_DATABASES . $this->directoryName;
		Filesystem :: deleteRecursive( $path );

		return true;
	}

	/**
	 * Form post
	 *
	 * The form `$_POST` method.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function post() {

		if ( isset( $_POST['recovery_ID'] ) ) {
			$ID = $_POST['recovery_ID'];

			// Replace the content from the backup directory.
			$this->restore_content( $ID );

			// Clean backups until the $ID.
			$this->remove_until( $ID );
		}

		$args = $_POST;
		foreach ( $this->dbFields as $field => $value ) {

			if ( isset( $args[$field] ) ) {

				$final_value = Sanitize :: html( $args[$field] );
				if ( $final_value === 'false' ) {
					$final_value = false;
				} elseif ( $final_value === 'true' ) {
					$final_value = true;
				}

				settype( $final_value, gettype( $value ) );
				$this->db[$field] = $final_value;
			}
		}
		return $this->save();
	}

	/**
	 * Admin settings form
	 *
	 * @since  1.0.0
	 * @access public
	 * @global object $L Language class.
	 * @global object $syslog Syslog class.
	 * @return string Returns the markup of the form.
	 */
	public function form() {

		// Access global variables.
		global $L, $syslog;

		if ( $this->zip ) {
			$backups = $this->get_backups_zip();
		} else {
			$backups = $this->get_backups_directories();
		}

		$html = '';
		include( $this->phpPath() . '/views/page-form.php' );

		return $html;
	}

	/**
	 * Before admin load
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function beforeAdminLoad() {

		if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if ( ! isset( $_POST['disable_recovery'] ) ) {
				$this->create_point();
				$this->remove_old();
			}
		}
	}

	/**
	 * Sidebar link
	 *
	 * Link to the options screen in the admin sidebar menu.
	 *
	 * @since  1.0.0
	 * @access public
	 * @global object $L Language class.
	 * @return mixed
	 */
	public function adminSidebar() {

		// Access global variables.
		global $L;

		$html = '';
		if ( $this->link() ) {
			$html .= sprintf(
				'<a class="nav-link" href="%s"><span class="fa fa-rotate-left"></span> %s</a>',
				HTML_PATH_ADMIN_ROOT . 'configure-plugin/' . $this->className(),
				$L->get( 'Recovery' )
			);
		}
		return $html;
	}

	/**
	 * Create a recovery point
	 *
	 * @since  1.0.0
	 * @access private
	 * @return boolean
	 */
	private function create_point() {

		// Create restore point directory.
		$backupDir = $this->workspace() . $GLOBALS['ID_EXECUTION'];
		mkdir( $backupDir, 0755, true );

		// Copy all to restore point directory.
		foreach ( $this->backup_dirs as $dir ) {
			$destination = $backupDir . DS . basename( $dir );
			Filesystem :: copyRecursive( $dir, $destination );
		}

		// Compress backup directory.
		if ( $this->zip ) {
			if ( Filesystem :: zip( $backupDir, $backupDir . '.zip' ) ) {
				Filesystem :: deleteRecursive( $backupDir );
			}
		}
		return true;
	}

	/**
	 * Restore content
	 *
	 * Copy the content from the backup to `bl-content`.
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  string $ID
	 * @return void
	 */
	private function restore_content( $ID ) {

		// Remove current files.
		foreach ( $this->backup_dirs as $dir ) {
			Filesystem :: deleteRecursive( $dir );
		}

		// Zip file.
		$source = $this->workspace() . $ID . '.zip';
		if ( file_exists( $source ) ) {
			if ( $this->zip ) {
				return Filesystem :: unzip( $source, PATH_CONTENT );
			}
		}

		// Directory.
		$source = $this->workspace() . $ID;
		if ( file_exists( $source ) ) {
			$dest = rtrim( PATH_CONTENT, '/' );
			return Filesystem :: copyRecursive( $source, $dest );
		}
		return false;
	}

	/**
	 * Get backup directories
	 *
	 * Returns array with all backups directories,
	 * sorted by date newer first.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function get_backups_directories() {
		$workspace = $this->workspace();
		return Filesystem :: listDirectories( $workspace, $regex = '*', $sortByDate = true );
	}

	/**
	 * Get backups zip
	 *
	 * Returns array with all backups zip sorted by date newer first.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function get_backups_zip() {
		$workspace = $this->workspace();
		return Filesystem :: listFiles( $workspace, $regex = '*', 'zip', $sortByDate = true );
	}

	/**
	 * Remove old recovery points
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function remove_old() {

		if ( $this->zip ) {
			$backups = $this->get_backups_zip();
		} else {
			$backups = $this->get_backups_directories();
		}

		$i = 0;
		foreach ( $backups as $backup ) {
			$i = $i + 1;
			if ( $i > $this->limit() ) {
				if ( $this->zip ) {
					Filesystem :: rmfile( $backup );
				} else {
					Filesystem :: deleteRecursive( $backup );
				}
			}
		}
		return true;
	}

	/**
	 * Remove points until
	 *
	 * Delete old backups until the $ID.
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  string $ID
	 * @return boolean
	 */
	private function remove_until( $ID ) {

		if ( $this->zip ) {
			$backups = $this->get_backups_zip();
		} else {
			$backups = $this->get_backups_directories();
		}

		foreach ( $backups as $backup ) {
			$backup_ID = pathinfo(basename( $backup ), PATHINFO_FILENAME );

			if ( $this->zip ) {
				Filesystem :: rmfile( $backup );
			} else {
				Filesystem :: deleteRecursive( $backup );
			}

			if ( $backup_ID == $ID ) {
				return true;
			}
		}
		return true;
	}

	/**
	 * Points limit field value
	 *
	 * @since  1.0.0
	 * @access public
	 * @return integer
	 */
	public function limit() {
		return $this->getValue( 'limit' );
	}

	/**
	 * Admin menu field value
	 *
	 * @since  1.0.0
	 * @access public
	 * @return boolean
	 */
	public function link() {
		return $this->getValue( 'link' );
	}
}
