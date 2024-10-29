<?php 
/**
 * Plugin Name:       Automated Import Products to Cart
 * Plugin URI:        https://atakanau.blogspot.com/2024/07/auto-import-products-cart-wp-plugin.html?m=1
 * Description:       Automatic WooCommerce Products Import to Cart via Drag and Drop file WordPress plugin.
 * Version:           1.0.0
 * Requires Plugins:  woocommerce
 * Author:            Atakan Au
 * Author URI:        https://atakanau.blogspot.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if(!defined('WPINC')){
	die;
}

/**
 * Plugin constants.
 */
if(!defined('ATAKANAU_AIPCDDF_VERSION')){
	define('ATAKANAU_AIPCDDF_VERSION', '1.0.0'); // Plugin version.
}

/**
 * AtakanAu_Woo_Cart_Dropfile
 */
class AtakanAu_Woo_Cart_DragDropFile{

	/**
	 * Static property to hold our singleton instance
	 *
	 */
	static $instance = false;
	public $plugin_name = false;

	/**
	 * This is our constructor
	 *
	 * @return void
	 */
	private function __construct(){
		// back end
		add_action('plugins_loaded', array($this, 'textdomain'));
		add_action('wp_ajax_atakanau_woo_cart_dropfile_upload_handler', array($this, 'handle_form_data')); // for logged in users
		add_action('wp_ajax_atakanau_woo_cart_dropfile_import_handler', array($this, 'handle_import_data')); // for logged in users
		// add_action('wp_ajax_atakanau_woo_cart_dropfile_delete_file', array($this, 'delete_file')); // for logged in users

		// front end
		add_action('wp_enqueue_scripts', array($this, 'enqueue_front_styles'), 10);
		add_action('wp_enqueue_scripts', array($this, 'enqueue_front_scripts'), 10);
		add_shortcode('atakanau_woo_cart_dropfile', array($this, 'form_shortcode'), 10, 2);

		$this->plugin_name = 'auto-import-products-cart-dragdropfile';
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return AtakanAu_Woo_Cart_Dropfile
	 */
	public static function getInstance(){
		if(!self::$instance){
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * load textdomain
	 *
	 * @return void
	 */
	public function textdomain(){
		load_plugin_textdomain('auto-import-products-cart-dragdropfile', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	/**
	 * Call front-end JS
	 *
	 * @return void
	 */
	public function enqueue_front_scripts(){
		if( ! is_admin() ){
			wp_register_script('aipcddf-dropzone', plugin_dir_url(__FILE__) . 'assets/js/dropzone-v5.9.3.js', array('jquery'), ATAKANAU_AIPCDDF_VERSION, true);

			wp_register_script('aipcddf', plugin_dir_url(__FILE__) . 'assets/js/aipcddf.js', array('jquery'), ATAKANAU_AIPCDDF_VERSION, true);
			wp_localize_script('aipcddf', 'atakanau_woo_cart_dropfile_cntrl', array(
				'upload_file' => admin_url('admin-ajax.php?action=atakanau_woo_cart_dropfile_upload_handler')
				,'import_file' => admin_url('admin-ajax.php?action=atakanau_woo_cart_dropfile_import_handler')
				// ,'delete_file' => admin_url('admin-ajax.php?action=atakanau_woo_cart_dropfile_delete_file')
			));
		}
	}

	/**
	 * Call front-end CSS
	 *
	 * @return void
	 */
	public function enqueue_front_styles(){
		if( ! is_admin() ){
			// Enqueue the stylesheet
			wp_register_style('aipcddf-dropzone', plugin_dir_url(__FILE__) . 'assets/css/dropzone_v6.0.0-beta.2-min.css', array(), ATAKANAU_AIPCDDF_VERSION, 'all');
			wp_register_style('aipcddf', plugin_dir_url(__FILE__) . 'assets/css/aipcddf.css', array(), ATAKANAU_AIPCDDF_VERSION, 'all');
		}
	}

	/**
	 * Callback method for displaying our form using shortcode
	 *
	 * @param  array $atts
	 * @param  string $content
	 * @return void
	 */
	public function form_shortcode($atts, $content = null){
		$a = shortcode_atts( array(
			'foo' => 'something',
			'bar' => 'something else',
		), $atts );
		$form_html = '';

		if( is_user_logged_in() ){ // show field only for logged in users
			$form_html .= '<div id="auto-import-products-cart-dragdropfile-wrapper">';
			$form_html .= '<div id="auto-import-products-cart-dragdropfile-uploder" class="dropzone0"></div>'; // element for dropzone field
			$form_html .= '<div id="auto-import-products-cart-dragdropfile-log"></div>';
			$form_html .= '<div id="auto-import-products-cart-dragdropfile-table"></div>';
			$form_html .= wp_nonce_field('atakanau_woo_cart_dropfile_register_ajax_nonce', 'auto-import-products-cart-dragdropfile-nonce', true, false); // returns security nonce field
			$form_html .= '</div>';
		}

		wp_enqueue_script('aipcddf-dropzone');
		wp_enqueue_script('aipcddf');
		wp_enqueue_style('aipcddf-dropzone');
		wp_enqueue_style('aipcddf');

		return $form_html;
	}

	public function log($reg, $name='', $fname='wcdf'){
		// $this->log($,'');
		$date_now = gmdate("Y-m-d");
		$time_now = gmdate("H:i:s");
		$upload_dir = wp_upload_dir();
		$filePath = $upload_dir['basedir'] . "/log-$date_now.txt";
		$filePath = WP_PLUGIN_DIR . '/' . $this->plugin_name . "/log-$fname-$date_now.txt";
		global $wp_filesystem;
		if(empty($wp_filesystem)){
			require_once(ABSPATH . '/wp-admin/includes/file.php');
			WP_Filesystem();
		}
		$WP_Filesystem_Class = new WP_Filesystem_Direct($wp_filesystem);
	
		$contents = $WP_Filesystem_Class->get_contents($filePath);
		$newcontents = "--- $time_now ---\n"
			."$name:: " . print_r($reg,true) . "\n"
			;
		$WP_Filesystem_Class->put_contents($filePath, $contents.$newcontents);
	}

	/**
	 * Handle form data received from frontend via AJAX
	 *
	 * @return void
	 */
	public function handle_form_data(){
		$error_message = false;
		if(isset($_POST['auto-import-products-cart-dragdropfile-nonce'])
			&& wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['auto-import-products-cart-dragdropfile-nonce'] ) ) , 'atakanau_woo_cart_dropfile_register_ajax_nonce' )
		){
			if( !empty($_FILES) && isset($_FILES['auto-import-products-cart-dragdropfile-file']) ){
				$file_array = $_FILES['auto-import-products-cart-dragdropfile-file'];

				// TO DO: Add Extra File Support - excel, openoffice
				$allowed_mime_types = array(
					"text/csv"
					,"application/csv"
				);
				$my_file = array();
				$fields = array( 'name', 'type', 'tmp_name', 'error', 'size' );
				foreach( $fields as $field ){
					$my_file[$field] = sanitize_text_field( $file_array[$field] );		  
				}
				$my_file['name'] = sanitize_file_name( $my_file['name'] );
				//
				if( $my_file['error'] === 4 ){
					$error_message = __('Error: ', 'auto-import-products-cart-dragdropfile') . $my_file['error'];
				}
				if( (int) $my_file['error'] !== UPLOAD_ERR_OK){ // If there is some errors, during file upload
					$error_message = __('Error: ', 'auto-import-products-cart-dragdropfile') . $my_file['error'];
				}
				
				// Check for MIME-type is allowed
				if( in_array( $my_file['type'], $allowed_mime_types ) && in_array( mime_content_type( $my_file['tmp_name'] ), $allowed_mime_types ) ){
					global $wp_filesystem;
					if(empty($wp_filesystem)){
						require_once(ABSPATH . '/wp-admin/includes/file.php');
						WP_Filesystem();
					}
					$WP_Filesystem_Direct = new WP_Filesystem_Direct($wp_filesystem);
					$my_contents = $WP_Filesystem_Direct->get_contents( $my_file['tmp_name'] );
			
					// try to catch eval( base64() ) 
					if( 0 !== $this->check_suspicious_on_file_contents( $my_contents ) ){
						$error_message = __('Error: Suspicious content', 'auto-import-products-cart-dragdropfile');
						$WP_Filesystem_Direct->delete($file_path);
					}
				}
				else{
					$error_message = __('File format not allowed', 'auto-import-products-cart-dragdropfile');
				}
			}
			else{
				$error_message = __('There is nothing to upload!', 'auto-import-products-cart-dragdropfile');
			}
		}else{
			$error_message = __('Security check failed!', 'auto-import-products-cart-dragdropfile');
		}

		if(!$error_message){
			$fna = explode( '.', $my_file['name'] );
			$ext = end( $fna );
			if( !$ext || in_array($ext,  ["php", "asp", "aspx", "jsp", "cfm", "cgi", "py", "rb", "go", "java", "sh", "htm", "html"]) ){
				$ext = 'log';
			}
			$file_name = uniqid( str_pad(wp_rand( 1, 9999 ), 4, "0", STR_PAD_LEFT) , true ) . '.' . $ext;

			// Create $fileDir directory if it doesn't exist
			$upload_dir = wp_upload_dir();
			$base_dir = $upload_dir['basedir'];
			$file_dir = $base_dir . '/' . $this->plugin_name;
			$file_path = $file_dir . '/' . $file_name;
			if( ! file_exists( $file_dir ) ){
				$WP_Filesystem_Direct->mkdir( $file_dir );
				$WP_Filesystem_Direct->put_contents( $file_dir . '/index.html' , '' );
			}

			// Save the uploaded file to a uploads location
			if( ! $WP_Filesystem_Direct->move($my_file['tmp_name'], $file_path) ){ // Handle the error
				$error_message = __('Error: File not saved.', 'auto-import-products-cart-dragdropfile');
			}
			else{
				// Check columns "sku" "quantity"
				// TO DO: Add Extra File Support - excel, openoffice
				$spreadsheet = $this->check_spreadsheet_csv($file_path);

				if($spreadsheet['sku'] !== false && $spreadsheet['quantity'] !== false){
					if( 1 < $spreadsheet['lines'] ){
						wp_send_json(array(
							'status' => 'success'
							,'column_sku'	=> $spreadsheet['sku']
							,'column_qty'	=> $spreadsheet['quantity']
							,'counted'		=> $spreadsheet['lines']
							,'filename'		=> $file_name
							,'mimetype'		=> $my_file['type']
							,'message'		=> __('File uploaded', 'auto-import-products-cart-dragdropfile')
						));
					}else{
						$WP_Filesystem_Direct->delete($file_path);
						$error_message = __('There is nothing to import.', 'auto-import-products-cart-dragdropfile');
					}
				}else{
					$WP_Filesystem_Direct->delete($file_path);
					$error_message = __('Error: "sku" or "quantity" columns not found.', 'auto-import-products-cart-dragdropfile');
				}
			}
		}
		if($error_message){
			wp_send_json(array(
				'status'	=> 'error'
				,'message'	=> $error_message
			));
		}

	}

	/**
	 * Handle form data received from frontend via AJAX
	 *
	 * @return void
	 */
	public function handle_import_data(){
		$error_message = false;
		if( ! isset($_POST['auto-import-products-cart-dragdropfile-nonce'])
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['auto-import-products-cart-dragdropfile-nonce'] ) ) , 'atakanau_woo_cart_dropfile_register_ajax_nonce' )
		){
			$error_message = __('Security check failed!', 'auto-import-products-cart-dragdropfile');
		}
		$params = array();
		$fields = array( 'column_sku', 'column_qty', 'counted', 'step' );
		foreach( $fields as $field ){
			$params[$field] = (int) sanitize_text_field( isset( $_POST[$field] ) ? $_POST[$field] : '' );
		}
		$fields = array( 'filename' );
		foreach( $fields as $field ){
			$params[$field] = sanitize_text_field( isset( $_POST[$field] ) ? $_POST[$field] : '' );
		}

		if($error_message){
			wp_send_json(array(
				'status'	=> 'error'
				,'message'	=> $error_message
			));
		}
		else{
			global $wp_filesystem;
			if(empty($wp_filesystem)){
				require_once(ABSPATH . '/wp-admin/includes/file.php');
				WP_Filesystem();
			}
			$filesystem = new WP_Filesystem_Direct($wp_filesystem);

			$upload_dir = wp_upload_dir();
			$base_dir = $upload_dir['basedir'];
			$file_dir = $base_dir . '/' . $this->plugin_name;
			$file_path = $file_dir . '/' . $params['filename'];

			$file_content = $filesystem->get_contents_array($file_path);
			if( isset( $file_content[ $params['step'] ] ) ){
				$data = str_getcsv($file_content[ $params['step'] ]);
				if(    isset( $data[ $params['column_sku'] ] ) && $data[ $params['column_sku'] ] 
					&& isset( $data[ $params['column_qty'] ] ) && 0 < (int) $data[ $params['column_qty'] ] 
				){
					$params['import'] = $this->add_product_to_cart_by_sku( $data[ $params['column_sku'] ], $data[ $params['column_qty'] ]);
					if($params['step'] == $params['counted']){
						$filesystem->delete($file_path);
					}
				}
				else{
					$params['skipped'] = $data;
				}
			}else{
				$error_message = __('Invalid CSV data.', 'auto-import-products-cart-dragdropfile');
				$filesystem->delete($file_path);
			}
			wp_send_json($params);
		}

	}

	/**
	 * Return count of regex matches for common type of upload attack eval(base64($malicious_payload))
	 * @param  string $str [description]
	 * @return int count of matches
	 */
	public function check_suspicious_on_file_contents( $str = '' ){
		// Not a string, bail
		if( ! is_string( $str ) )
			return 0;

		return preg_match_all( '/<\?php|eval\s*\(|base64_decode|gzinflate|gzuncompress/imsU', $str, $matches );
	}

	/**
	 * Returns the column numbers of the "sku" and "quantity" columns in a CSV file.
	 * @param  string $path: Path to the CSV file
	 * @return array indexes of the "sku" and "quantity" columns
	 */
	public function check_spreadsheet_csv( $path = '' ){
		global $wp_filesystem;
		if(empty($wp_filesystem)){
			require_once(ABSPATH . '/wp-admin/includes/file.php');
			WP_Filesystem();
		}
		$filesystem = new WP_Filesystem_Direct($wp_filesystem);
		$file_content = $filesystem->get_contents($path);
		$file_content = preg_replace('/[\n\r\t ]+$/', '', $file_content);
		$header_line = strtok($file_content, "\n");
		$columns = explode(",", $header_line);
		$lines = count( explode( "\n", $file_content ) ) - 1;

		$sku_column = array_search("sku", array_map('strtolower', $columns));
		$quantity_column = array_search("quantity", array_map('strtolower', $columns));

		// $this->log( array('sku' => $sku_column, 'quantity' => $quantity_column, 'lines' => $lines) , 'spreadsheet' );
		return array('sku' => $sku_column, 'quantity' => $quantity_column, 'lines' => $lines);
	}

	public function add_product_to_cart_by_sku($sku, $quantity){
		$result = false;
		$product_id = wc_get_product_id_by_sku($sku);
		if($product_id){
			$cart = WC()->cart;
			$cart_item_key = $cart->add_to_cart($product_id, $quantity);
			if($cart_item_key){
				$result = 'success';
			}else{
				$result = 'error cart';
			}
		}else{
			$result = 'error sku';
		}
		return $result;
	}


	/**
	 * Delete attachment by id via AJAX
	 *
	 * @return void
	 */
	public function delete_file(){

		if(isset($_POST['attachment_id']) 
			&& isset($_POST['auto-import-products-cart-dragdropfile-nonce'])
			&& wp_verify_nonce( sanitize_text_field( wp_unslash ( $_POST['auto-import-products-cart-dragdropfile-nonce'] ) ) , 'atakanau_woo_cart_dropfile_register_ajax_nonce' )
		){
			$attachment_id = absint($_POST['attachment_id']);

			$result = wp_delete_attachment($attachment_id, true); // permanently delete attachment

			if($result){
				wp_send_json(array('status' => 'ok'));
			}
		}
		wp_send_json(array('status' => 'error'));
	}
}
// end class

// Instantiate our class
$AtakanAu_Woo_Cart_Dropfile = AtakanAu_Woo_Cart_DragDropFile::getInstance();
