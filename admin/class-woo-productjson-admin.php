<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.cedcommerce.com
 * @since      1.0.0
 *
 * @package    Woo_Productjson
 * @subpackage Woo_Productjson/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Productjson
 * @subpackage Woo_Productjson/admin
 * @author     Faiq Masood <faiqmasood@cedcommerce.com>
 */
class Woo_Productjson_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Productjson_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Productjson_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-productjson-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Productjson_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Productjson_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-productjson-admin.js', array( 'jquery' ), $this->version, false );

	}
	public function my_admin_menu() {
		add_menu_page( 'JSON Product Importer (product.json)', 'JSON Product Importer (product.json)', 'manage_options', 'ced_json_prodimport', array( $this, 'ced_json_product_show'), 'dashicons-admin-generic', 7  );
	}

	public function ced_json_product_show(){
	?>	
		<form action="" method="post" enctype="multipart/form-data">
			<input type="file" name="fileToUpload" id="fileToUpload">
			<input type="submit" value="Upload File" name="submittheform">
		</form>
		<?php	
			global $wp_filesystem;
			WP_Filesystem();
			$content_directory = $wp_filesystem->wp_content_dir() . 'uploads/';
			$wp_filesystem->mkdir( $content_directory . 'JSONCustomDirectory' );
			$target_dir_location = $content_directory . 'JSONCustomDirectory/';
	
			if(isset($_POST["submittheform"]) && isset($_FILES['fileToUpload'])) {
			
				$name_file = $_FILES['fileToUpload']['name'];
				$tmp_name = $_FILES['fileToUpload']['tmp_name'];
			
				if( move_uploaded_file( $tmp_name, $target_dir_location.$name_file ) ) {
					echo $target_dir_location.$name_file;
					echo "File was successfully uploaded";
				} else {
					echo "The file was not uploaded";
				}
			
			}

			define( 'FILE_TO_IMPORT', $target_dir_location.$name_file );
			if ( ! file_exists( FILE_TO_IMPORT ) ) :
				die( 'Unable to find ' . FILE_TO_IMPORT );
			endif;	
			
			$content 			= file_get_contents(FILE_TO_IMPORT);
			$products_data 		= json_decode($content,true);
			
			$response = $products_data;
			for($i=0;$i<count($response);$i++){
				$product_id = $this->create_product($response[$i]);
				$this->set_attributes($product_id, $response[$i]);
			}
			
	}

	public function set_attributes($product_id, $response){
		if(!count($response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['aeop_s_k_u_propertys']['aeop_sku_property'])>0){
			for($m=0;$m<count($response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']);$m++) {
				$props = array();
				foreach($response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku'][$m]['aeop_s_k_u_propertys']['aeop_sku_property'] as $vpp){				
					$props[wc_sanitize_taxonomy_name($vpp['sku_property_name'])] = $vpp['sku_property_value'];					
				}
				$var_data = array(
					'sku'			=> $response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku'][$m]['id'],
					'price'			=> $response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku'][$m]['sku_price'],
					'offer_price'	=> $response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku'][$m]['offer_sale_price'],
					'stock'			=> $response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku'][$m]['ipm_sku_stock']
				);
				$this->create_variation($product_id, $var_data, $props);
			}		
		}else{
			
				$props = array();
							
				$props[wc_sanitize_taxonomy_name($response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['aeop_s_k_u_propertys']['aeop_sku_property']['sku_property_name'])] = $response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['aeop_s_k_u_propertys']['aeop_sku_property']['sku_property_value'];					
				
				$var_data = array(
					'sku'			=> $response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['id'],
					'price'			=> $response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['sku_price'],
					'offer_price'	=> $response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['offer_sale_price'],
					'stock'			=> $response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['ipm_sku_stock']
				);
				$this->create_variation($product_id, $var_data, $props);
					
		}			
	}
	
	public function create_product($response){	
			$objProduct = new WC_Product_Variable();
			$objProduct->set_name($response['result']['subject']);
			$objProduct->set_status("publish"); 
			$objProduct->set_catalog_visibility('visible'); 
			$objProduct->set_description($response['result']['detail']);
			$objProduct->set_sku($response['result']['product_id']);
			$objProduct->set_manage_stock(true);
			$objProduct->set_stock_quantity($response['result']["total_available_stock"]);
			$objProduct->set_stock_status('instock');
			$objProduct->set_backorders('no');
			$objProduct->set_reviews_allowed(true);
			$objProduct->set_sold_individually(false);			
			$objProduct->set_regular_price($response['result']['item_offer_site_sale_price']); 
			$objProduct->set_price($response['result']['item_offer_site_sale_price']);
			
			$attributes=array();
			if(!count($response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['aeop_s_k_u_propertys']['aeop_sku_property'])>0){
					for($n=0;$n<count($response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku'][0]['aeop_s_k_u_propertys']['aeop_sku_property']);$n++){
					$mynames = array();
					$myvalues = array();				
					for($m=0;$m<count($response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']);$m++){								
							array_push($mynames,$response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku'][$m]['aeop_s_k_u_propertys']['aeop_sku_property'][$n]['sku_property_name']);
							array_push($myvalues,$response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku'][$m]['aeop_s_k_u_propertys']['aeop_sku_property'][$n]['sku_property_value']);
							$att = array('id'=>0, 'name'=>array_unique($mynames)[0], 'options'=>array_unique($myvalues), 'visible'=> true, 'position'=>$n, 'variation'=>true );			
							array_push($attributes, $this->create_attribute($att));				
					}
				}
			}else{
				$att = array('id'=>0, 'name'=>$response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['aeop_s_k_u_propertys']['aeop_sku_property']['sku_property_name'], 'options'=>array($response['result']['aeop_ae_product_s_k_us']['aeop_ae_product_sku']['aeop_s_k_u_propertys']['aeop_sku_property']['sku_property_value']), 'visible'=> true, 'position'=>0, 'variation'=>true );			
				array_push($attributes, $this->create_attribute($att));	
			}
			$objProduct->set_attributes( $attributes );
			$product_id 	= $objProduct->save();
			$img_urls 		= $response['result']['image_u_r_ls'];
			$img_url_arr	= array();
			$img_url_arr 	= explode(';',$img_urls);
			if(count($img_url_arr)>1){
				$attach_id 		= $this->insert_picture($product_id, $img_url_arr[0]);
				$objProduct->set_image_id($attach_id);
				
				$attach_ids = array();
				for($c=1;$c<count($img_url_arr);$c++){
					$attach_ids[] 		= $this->insert_picture($product_id, $img_url_arr[$c]);
				}
				$objProduct->set_gallery_image_ids($attach_ids);				
			}
		
				
			
			$product_id 	= $objProduct->save();
			return $product_id;
	}
	public function create_attribute($att){
		$attribute = new WC_Product_Attribute();
		$attribute->set_id($att['id']); 
		$attribute->set_name($att['name']); 
		$attribute->set_options($att['options']); 
		$attribute->set_position($att['position']); 
		$attribute->set_visible($att['visible']); 
		$attribute->set_variation($att['variation']);		
		return $attribute;
	}
	public function create_variation( $product_id, $var_data, $props){
		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product_id );
		$variation->set_attributes($props);
		$variation->set_status('publish');
		$variation->set_stock_status('instock');
		//$variation->set_sku($var_data['sku']);
		$variation->set_regular_price($var_data['price']);
		$variation->set_price($var_data['price']);
		$variation->set_sale_price($var_data['offer_price']);
		$variation->set_stock_quantity($var_data['stock']);
		$variation->save();
	}

	public function insert_picture($post_id, $img_url){
		$image_url        = $img_url; 
		$pathinfo 		  = pathinfo($image_url);
		$image_name       = $pathinfo['filename'].'.'.$pathinfo['extension'];
		$upload_dir       = wp_upload_dir(); 
		$image_data       = file_get_contents($image_url); 
		$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); 
		$filename         = basename( $unique_file_name ); 

		if( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}	
		file_put_contents( $file, $image_data );
		$wp_filetype = wp_check_filetype( $filename, null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		return $attach_id;
	}
	
}
