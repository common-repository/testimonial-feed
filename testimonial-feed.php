<?php
/*
Plugin Name: Testimonial Feed
Author: Lance Meman
Plugin URI: https://lancememan.com/plugins
Description: A simple and lightweight testimonial feed with star rating
Version: 1.1
Author: Lance Meman
Author URI: https://lancememan.com
License: GPL2
*/
/* Init styles */

function tfeed_styles_scripts() {
  wp_enqueue_style('tfeed-style', plugins_url( 'tfeed-style.css', __FILE__ ),array(),null,'all');
}
add_action( 'wp_enqueue_scripts', 'tfeed_styles_scripts' );

/* Create custom post type */
function tfeed_custom_post_type() {
  register_post_type('testi_feed',
      array(
        'labels'      => array(
        'name'          => __('Testimonial Feeds'),
        'singular_name' => __('Testimonials'),
        ),
      'public'      => true,
      'has_archive' => false,
      'rewrite'     => array( 'slug' => 'testimonial-feed' ),
			'menu_position'  => 5,
			'menu_icon'   => 'dashicons-format-chat',
      'supports' => array( 'title', 'editor')
      )
    );
}
add_action('init', 'tfeed_custom_post_type');

/* Rating Output Shortcode */
function tfeed_output_func( $atts ) {
  $vars = shortcode_atts( array(
    'count' => 4,
  ), $atts );

  $args = [
      'post_type'      => 'testi_feed',
      'posts_per_page' => $vars['count'],
  ];
  $loop = new WP_Query($args);
  while ($loop->have_posts()) {
      $loop->the_post();
      ?>
      <div class="tfeed-output-wrapper">
        <div class="tfeed-item">
          <h3><?php the_title(); ?></h3>
          <span class="tfeed-star-output count-<?php echo get_post_meta( get_the_ID(), 'tfeed_stars', true ); ?>"><i class="tfeed-star"></i><i class="tfeed-star"></i><i class="tfeed-star"></i><i class="tfeed-star"></i><i class="tfeed-star"></i></span>
          <div class="tfeed-text">
          <?php the_content(); ?>
          <p class="text-right">- <?php echo get_post_meta( get_the_ID(), 'tfeed_name', true ); ?> | <?php the_time('F j, Y'); ?></p>
          </div>
        </div>
      </div>
      <?php
  }
}
add_shortcode( 'tfeed_output', 'tfeed_output_func' );

/* Create meta boxes */
function tfeed_rating_metabox() {
        add_meta_box(
            'tfeed_stars',      // Unique ID
            'Stars Given',      // Box title
            'tfeed_stars_html', // Content callback, must be of type callable
            'testi_feed',            // Screen/Post type
            'side'              // Metabox Position
        );
}
add_action('add_meta_boxes', 'tfeed_rating_metabox');


/* Create meta boxes */
function tfeed_name_metabox() {
        add_meta_box(
            'tfeed_name',       // Unique ID
            'Customer Name',    // Box title
            'tfeed_name_html',  // Content callback, must be of type callable
            'testi_feed',       // Screen/Post type
            'side',             // Metabox Position
            'high'              // Pirority
        );
}
add_action('add_meta_boxes', 'tfeed_name_metabox');

function tfeed_name_html() {
  global $post;
  // Nonce field to validate form request came from current site
  wp_nonce_field( basename( __FILE__ ), 'tfeed_name_field' );
  // Get the location data if it's already been entered
  $tfeed_name = get_post_meta( $post->ID, 'tfeed_name', true );
  // Output the field
  echo '<input type="text" name="tfeed_name" value="' . sanitize_textarea_field( $tfeed_name )  . '" class="tfeed_admin_field">';
}

function tfeed_stars_html() {
  global $post;
  // Nonce field to validate form request came from current site
  wp_nonce_field( basename( __FILE__ ), 'tfeed_stars_field' );
  // Get the location data if it's already been entered
  $tfeed_stars = get_post_meta( $post->ID, 'tfeed_stars', true );
  // Output the field
  echo '<input type="number" min="1" max="5" maxlength="1" name="tfeed_stars" value="' . sanitize_textarea_field( $tfeed_stars )  . '" class="tfeed_admin_field" required>';
}

/* Save the metabox data*/
function tfeed_save_meta( $post_id, $post ) {
  // Return if the user doesn't have edit permissions.
  if ( ! current_user_can( 'edit_post', $post_id ) ) {
    return $post_id;
  }
  // Verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times.
  if ( ! isset( $_POST['tfeed_name'] ) || ! wp_verify_nonce( $_POST['tfeed_name_field'], basename(__FILE__) ) ) {
    return $post_id;
  }
    if ( ! isset( $_POST['tfeed_stars'] ) || ! wp_verify_nonce( $_POST['tfeed_stars_field'], basename(__FILE__) ) ) {
    return $post_id;
  }
  // Now that we're authenticated, time to save the data.
  // This sanitizes the data from the field and saves it into an array $tfeed_meta.
  $tfeed_meta['tfeed_name'] = sanitize_text_field( $_POST['tfeed_name'] );
  $tfeed_meta['tfeed_stars'] = sanitize_text_field( $_POST['tfeed_stars'] );
  // Cycle through the $tfeed_meta array.
  // Note, in this example we just have one item, but this is helpful if you have multiple.
  foreach ( $tfeed_meta as $key => $value ) :
    // Don't store custom data twice
    if ( 'revision' === $post->post_type ) {
      return;
    }
    if ( get_post_meta( $post_id, $key, false ) ) {
      // If the custom field already has a value, update it.
      update_post_meta( $post_id, $key, $value );
    } else {
      // If the custom field doesn't have a value, add it.
      add_post_meta( $post_id, $key, $value);
    }
    if ( ! $value ) {
      // Delete the meta key if there's no value
      delete_post_meta( $post_id, $key );
    }
  endforeach;
}
add_action( 'save_post', 'tfeed_save_meta', 1, 2 );


// Add the custom columns to the book post type:
add_filter( 'manage_testi_feed_posts_columns', 'set_custom_edit_testi_feed_columns' );
function set_custom_edit_testi_feed_columns($columns) {
    unset( $columns['author'] );
    unset( $columns['date'] );
    $columns['tfeed_name'] = __( 'Name', 'tfeed' );
    $columns['tfeed_stars'] = __( 'Rating', 'your_text_domain' );

     return array_merge ( $columns, array ( 
       'date' => __('Date')
     ) );
}

// Add the data to the custom columns for the book post type:
add_action( 'manage_testi_feed_posts_custom_column' , 'custom_testi_feed_column', 10, 2 );
function custom_testi_feed_column( $column, $post_id ) {
    switch ( $column ) {

        case 'tfeed_name' :
            echo get_post_meta( $post_id , 'tfeed_name' , true ); 
            break;

        case 'tfeed_stars' :
            echo get_post_meta( $post_id , 'tfeed_stars' , true ); 
            break;

    }
}