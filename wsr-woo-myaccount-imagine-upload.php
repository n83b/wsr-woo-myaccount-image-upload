<?php
/*
Plugin Name: WSR Profile Image uplaoder
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
							$image = get_field('saclub_profile_pic');
							$size = 'thumbnail'; // (thumbnail, medium, large, full or custom size)
							if( $image ) {
								echo wp_get_attachment_image( $image, $size );
							}
						?>
						<input id="wsr_profile_upload" name="wsr_profile_upload" type="file" class="button" style="position: relative; z-index: 1;" multiple="false" >
						<?php wp_nonce_field( 'wsr_profile_upload', 'wsr_profile_upload_nonce' ); ?>
						<input type="hidden" name="profile_user_id" id="profile_user_id" value="<?php echo get_current_user_id() ?>" />
						<br />

					</span>
				</p>
				<?php
			}

			function wsr_upload_user_profile_image($user_id) {
				// Check that the nonce is valid, and the user can edit this post.
				if ( 
					isset( $_POST['wsr_profile_upload_nonce'], $_POST['profile_user_id'] ) 
					&& wp_verify_nonce( $_POST['wsr_profile_upload_nonce'], 'wsr_profile_upload' )
				) {
					// The nonce was valid and the user has the capabilities, it is safe to continue.
				
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
						//process image? Add image resize plugin, eg Imsanity to ensure no larger than .....

						// Save attachment id to user meta
						if ($attachment_id > 0){
							update_field('saclub_profile_pic', $attachment_id, 'user_' . $customer_id );
						}
					}
				
				} else {
					// The security check failed, maybe show the user an error.
					wc_add_notice( 'Security check failed.  Please try again.', 'error' );
				}
			}
	}
}

$wsr_Profile_Image_Uploader = WSR_Profile_Image_Uploader::getInstance();
