<?php
/*
Plugin Name: WSR Profile Image upload
Plugin URI: http://websector.com.au
Description: Upload an image from the front end
Version: 1.0.0
Author: WSR
Author URI: http://websector.com.au
License: A short license name. Example: GPL2
*/


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( !class_exists( 'WSR_Profile_Image_Uploader' ) ) {

	class WSR_Profile_Image_Uploader{

			static $instance = false;

			private $image_input_name = 'wsr_profile_upload';
			private $acfFieldName = 'saclub_profile_pic';

			private function __construct(){
				add_action('woocommerce_edit_account_form_tag', array($this, 'wsr_edit_account_form_tag'));
				add_action( 'woocommerce_edit_account_form', array($this, 'wsr_edit_my_account_page_woocommerce'), 20 );
				add_action('woocommerce_save_account_details', array($this, 'wsr_upload_user_profile_image'), 10, 1);
			}

			public static function getInstance() {
				if ( !self::$instance )
					self::$instance = new self();
				return self::$instance;
			}		

			public function wsr_edit_account_form_tag(){
				echo 'enctype="multipart/form-data"';
			}

			public function wsr_edit_my_account_page_woocommerce(){
				?>
				<p class="form-row " id="wsr_profileImageUpload_field" style="width: 200px;" data-priority="">
					<label for="wsr_profileImageUpload" class="">Profile picture</label>
					<span class="woocommerce-input-wrapper">
						<?php
							//output image here - get meta
							$image = get_field($this->acfFieldName, 'user_' . get_current_user_id());
							$size = 'thumbnail'; // (thumbnail, medium, large, full or custom size)
							if( $image ) {
								echo wp_get_attachment_image( $image, $size );
							}
						?>
						<input id="<?php echo $this->image_input_name ?>" name="<?php echo $this->image_input_name ?>" type="file" class="button" style="position: relative; z-index: 1;" multiple="false" >
						<p>Image must be .jpg or .png & no bigger than 1000 pixels</p>
						<?php wp_nonce_field( $this->image_input_name, $this->image_input_name . '_nonce' ); ?>
						<br />

					</span>
				</p>
				<?php
			}

			function wsr_upload_user_profile_image($user_id) {
				// Check that the nonce is valid, and the user can edit this post.
				if ( 
					isset( $_POST[$this->image_input_name . '_nonce']) && wp_verify_nonce( $_POST[$this->image_input_name . '_nonce'], 'wsr_profile_upload' )
				) {
					// The nonce was valid and the user has the capabilities, it is safe to continue.
				
					$allowed_image_types = array('image/jpeg','image/png');
					// Maximum size in bytes
					$max_image_size = 1000 * 1000; // 1 MB (approx)

					// Check if there's an image
					if (isset($_FILES[$this->image_input_name]['size']) && $_FILES[$this->image_input_name]['size'] > 0){
						// Check conditions
						if(in_array($_FILES[$this->image_input_name]['type'], $allowed_image_types) && $_FILES[$this->image_input_name]['size'] <= $max_image_size){
					
							// These files need to be included as dependencies when on the front end.
							require_once( ABSPATH . 'wp-admin/includes/image.php' );
							require_once( ABSPATH . 'wp-admin/includes/file.php' );
							require_once( ABSPATH . 'wp-admin/includes/media.php' );
							
							// Let WordPress handle the upload.
							// Remember, 'wsr_profile_upload' is the name of our file input in our form above.
							$attachment_id = media_handle_upload( 'wsr_profile_upload', 0 );
							
							if ( is_wp_error( $attachment_id ) ) {
								// There was an error uploading the image.
								wc_add_notice( 'Image not saved.  Please try again or contact the admin.', 'error' );

							} else {
								//Delete users current image form media library so it doesnt get cluttered
								$currentAttachmentID = get_field($this->acfFieldName, 'user_' . $user_id);
								wp_delete_attachment( $currentAttachmentID);

								// Save attachment id to user meta
								if ($attachment_id > 0){
									update_field($this->acfFieldName, $attachment_id, 'user_' . $user_id );
								}
							}
						}else{
							wc_add_notice( 'Profile image not saved.  Image must be .jpg or .png & under 1000px by 1000px .  Please try again.', 'error' );
						}
					}
				
				} else {
					// The security check failed, maybe show the user an error.
					wc_add_notice( 'Security check failed. Not all profile fields save.  Please try again.', 'error' );
				}
			}
	}
}

$wsr_Profile_Image_Uploader = WSR_Profile_Image_Uploader::getInstance();
