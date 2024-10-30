<?php

/**
 * @packge: CF7WoocommerceProductlistDropdown
 */
/**
 * Plugin Name: CF7 Woocommerce Product List Dropdown
 * Description: Plugin to create woocommerce Product Dropdown List in Contact Form 7
 * Version: 1.1.0
 * Author: Sorted Pixel
 * Author URI: https://sortedpixel.com
 * License: GPLv2 or later
 * Text Domain: cf7-woocommerce-product-list-dropdown
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
Copyright 2005-2015 Automattic, Inc.
 */

?>

<?php

/**

 * A base module for [products] and [products*]

 *

 * @author Sorted Pixel

 * @date 25/5/18

 * @license MIT License

 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'is_plugin_active' ) )
	 require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	 
if(is_plugin_active( 'woocommerce/woocommerce.php') && is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )){
	
	class WooProductList{
		function __construct() {
			$this->plugin_url       = plugin_dir_url( __FILE__ );
			$this->plugin_path      = plugin_dir_path( __FILE__ );
			$this->version          = '1.3.1';
			// $this->add_actions();
			
			add_action( 'wpcf7_init', array($this, 'sp_wpcf7_add_shortcode_products') );
			
			//validatation filter 
			add_filter( 'wpcf7_validate_products', array($this, 'sp_wpcf7_products_validation_filter'), 10, 2 );
			add_filter( 'wpcf7_validate_products*', array($this, 'sp_wpcf7_products_validation_filter'), 10, 2 );
			
			if ( is_admin() ) {

				add_action( 'admin_init', array($this,'sp_wpcf7_add_products_tag_generator_menu'), 25 );
		
			}
		
		}

		/* Shortcode handler */

		function sp_wpcf7_add_shortcode_products() {

			wpcf7_add_form_tag( array( 'products', 'products*' ),

				'sp_wpcf7_products_shortcode_handler', true );

		}

		function sp_wpcf7_products_shortcode_handler( $sp_tag ) {

			$sp_tag = new WPCF7_Shortcode( $sp_tag );

			if ( empty( $sp_tag->name ) )

				return '';

			$sp_validation_error = wpcf7_get_validation_error( $sp_tag->name );

			$class = wpcf7_form_controls_class( $sp_tag->type );

			if ( $sp_validation_error )

				$class .= ' wpcf7-not-valid';

			$atts = array();

			$atts['class'] = $sp_tag->get_class_option( $class );

			$atts['id'] = $sp_tag->get_id_option();

			$atts['tabindex'] = $sp_tag->get_option( 'tabindex', 'int', true );



			if ( $sp_tag->is_required() )

				$atts['aria-required'] = 'true';



			$atts['aria-invalid'] = $sp_validation_error ? 'true' : 'false';



			$sp_multiple = $sp_tag->has_option( 'multiple' );

			$include_blank = $sp_tag->has_option( 'include_blank' );

			$first_as_label = $sp_tag->has_option( 'first_as_label' );



			// product query settings 

			$sp_product_posts = get_posts( array(

				'post_type' => 'product',

				'post_status' => 'publish',

				'numberposts' => -1,

			) );



			// Display product

			$sp_values = array();

			foreach ( $sp_product_posts as $sp_product ) {

				// Get product SKU

				$sku = get_post_meta( $sp_product->ID, '_sku', true );

				// Set `values` with SKU & product title

				$sp_values[] = '#' . $sku . ' | ' . $sp_product->post_title;

			}



			$sp_values = $sp_values;

			$labels = array_values( $sp_values );



			$sp_shifted = false;



			if ( $include_blank || empty( $sp_values ) ) {

				array_unshift( $labels, '---' );

				array_unshift( $sp_values, '' );

				$sp_shifted = true;

			} elseif ( $first_as_label ) {

				$sp_values[0] = '';

			}



			$html = '';

			$sp_hangover = wpcf7_get_hangover( $sp_tag->name );



			foreach ( $sp_values as $key => $sp_value ) {

				$selected = false;



				if ( $sp_hangover ) {

					if ( $sp_multiple ) {

						$selected = in_array( esc_sql( $sp_value ), (array) $sp_hangover );

					} else {

						$selected = ( $sp_hangover == esc_sql( $sp_value ) );

					}

				} else {

					if ( ! $sp_shifted && in_array( (int) $key + 1, (array) $defaults ) ) {

						$selected = true;

					} elseif ( $sp_shifted && in_array( (int) $key, (array) $defaults ) ) {

						$selected = true;

					}

				}



				$item_atts = array(

					'value' => $sp_value,

					'selected' => $selected ? 'selected' : '' );



				$item_atts = wpcf7_format_atts( $item_atts );



				$label = isset( $labels[$key] ) ? $labels[$key] : $sp_value;



				$html .= sprintf( '<option %1$s>%2$s</option>',

					$item_atts, esc_html( $label ) );

			}



			if ( $sp_multiple )

				$atts['multiple'] = 'multiple';



			$atts['name'] = $sp_tag->name . ( $sp_multiple ? '[]' : '' );



			$atts = wpcf7_format_atts( $atts );



			$html = sprintf(

				'<span class="sp-wpcf7-form-control-wrap %1$s"><select %2$s>%3$s</select>%4$s</span>',

				sanitize_html_class( $sp_tag->name ), $atts, $html, $sp_validation_error );



			return $html;

		}





		/* Validation filter */

		function sp_wpcf7_products_validation_filter( $result, $sp_tag ) {

			$sp_tag = new WPCF7_Shortcode( $sp_tag );



			$sp_name = $sp_tag->name;



			if ( isset( $_POST[$sp_name] ) && is_array( $_POST[$sp_name] ) ) {

				foreach ( $_POST[$sp_name] as $key => $sp_value ) {

					if ( '' === $sp_value )

						unset( $_POST[$sp_name][$key] );

				}

			}



			$empty = ! isset( $_POST[$sp_name] ) || empty( $_POST[$sp_name] ) && '0' !== $_POST[$sp_name];



			if ( $sp_tag->is_required() && $empty ) {

				$result->invalidate( $sp_tag, wpcf7_get_message( 'invalid_required' ) );

			}



			return $result;

		}


		/* Tag generator */

		function sp_wpcf7_add_products_tag_generator_menu() {

			$sp_tag_generator = WPCF7_TagGenerator::get_instance();

			$sp_tag_generator->add( 'products', __( 'WooCommerce Products drop-down menu', 'contact-form-7' ),

				'sp_wpcf7_tag_products_generator_menu' );

		}


		function sp_wpcf7_tag_products_generator_menu( $contact_form, $args = '' ) {

			$args = wp_parse_args( $args, array() );

			$type = 'products';



			$description = __( "Generate a form-tag for a WooCommerce Products drop-down menu. For more details, see %s.", 'contact-form-7' );



			$desc_link = wpcf7_link( __( '#', 'contact-form-7' ), __( 'WooCommerce Product dropdown menu', 'contact-form-7' ) );



		?> 

		<div class="control-box">

		<fieldset>

		<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend> 



		<table class="form-table">

		<tbody>

			<tr>

			<th scope="row"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></th>

			<td>

				<fieldset>

				<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>

				<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>

				</fieldset>

			</td>

			</tr>



			<tr>

			<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>

			<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>

			</tr>



			<tr>

			<th scope="row"><?php echo esc_html( __( 'Options', 'contact-form-7' ) ); ?></th>

			<td>

				<fieldset>

				<label><input type="checkbox" name="multiple" class="option" /> <?php echo esc_html( __( 'Allow multiple selections', 'contact-form-7' ) ); ?></label><br />

				<label><input type="checkbox" name="include_blank" class="option" /> <?php echo esc_html( __( 'Insert a blank item as the first option', 'contact-form-7' ) ); ?></label>

				</fieldset>

			</td>

			</tr>



			<tr>

			<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'contact-form-7' ) ); ?></label></th>

			<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>

			</tr>



			<tr>

			<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'contact-form-7' ) ); ?></label></th>

			<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>

			</tr>



		</tbody>

		</table>

		</fieldset>

		</div>

		<div class="insert-box">

			<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />



			<div class="submitbox">

			<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />

			</div>



			<br class="clear" />



			<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'contact-form-7' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>

		</div>

		<?php
		
		}

	}
	new WooProductList();
}else{
			// If CF7 isn't installed and activated, throw an error.
				?>
				<div class="wpcf7-redirect-error error notice">
					<h3>
						<?php esc_html_e( 'Contact form product dropdown list', 'woocommerce' );?>
					</h3>
					<p>
						<?php esc_html_e( 'Error: Please install and activate woocommerce & contact form plugin.', 'woocommerce' );?>
					</p>
				</div>
				<?php
		}
	
