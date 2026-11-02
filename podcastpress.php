<?php
/**
 * Plugin Name: PodcastPress
 * Description: Publish accessible podcast episodes on your WordPress site.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL-3.0-or-later
 * Text Domain: podcastpress
 *
 * @package PodcastPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the podcast episode post type.
 *
 * @return void
 */
function podcastpress_register_episode_post_type() {
	$labels = array(
		'name'                  => __( 'Podcast Episodes', 'podcastpress' ),
		'singular_name'         => __( 'Podcast Episode', 'podcastpress' ),
		'add_new_item'          => __( 'Add New Podcast Episode', 'podcastpress' ),
		'edit_item'             => __( 'Edit Podcast Episode', 'podcastpress' ),
		'new_item'              => __( 'New Podcast Episode', 'podcastpress' ),
		'view_item'             => __( 'View Podcast Episode', 'podcastpress' ),
		'search_items'          => __( 'Search Podcast Episodes', 'podcastpress' ),
		'not_found'             => __( 'No podcast episodes found.', 'podcastpress' ),
		'not_found_in_trash'    => __( 'No podcast episodes found in Trash.', 'podcastpress' ),
		'all_items'             => __( 'Podcast Episodes', 'podcastpress' ),
		'menu_name'             => __( 'PodcastPress', 'podcastpress' ),
		'item_published'        => __( 'Podcast episode published.', 'podcastpress' ),
		'item_updated'           => __( 'Podcast episode updated.', 'podcastpress' ),
	);

	register_post_type(
		'podcastpress_episode',
		array(
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => false,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-microphone',
			'rewrite'       => array( 'slug' => 'podcast-episodes' ),
			'supports'      => array( 'title', 'editor', 'excerpt', 'revisions' ),
		)
	);
}
add_action( 'init', 'podcastpress_register_episode_post_type' );

/**
 * Register episode rewrite rules when the plugin is activated.
 *
 * @return void
 */
function podcastpress_activate() {
	podcastpress_register_episode_post_type();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'podcastpress_activate' );

/**
 * Add the episode audio and transcript fields.
 *
 * @return void
 */
function podcastpress_add_episode_meta_box() {
	add_meta_box(
		'podcastpress_episode_details',
		__( 'Podcast Episode Details', 'podcastpress' ),
		'podcastpress_render_episode_meta_box',
		'podcastpress_episode',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_podcastpress_episode', 'podcastpress_add_episode_meta_box' );

/**
 * Render the episode audio and transcript fields.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function podcastpress_render_episode_meta_box( $post ) {
	$audio_url  = get_post_meta( $post->ID, '_podcastpress_audio_url', true );
	$transcript = get_post_meta( $post->ID, '_podcastpress_transcript', true );

	wp_nonce_field( 'podcastpress_save_episode', 'podcastpress_episode_nonce' );
	?>
	<p>
		<label for="podcastpress_audio_url"><?php esc_html_e( 'Audio file URL', 'podcastpress' ); ?></label>
		<input
			type="url"
			class="widefat"
			id="podcastpress_audio_url"
			name="podcastpress_audio_url"
			value="<?php echo esc_attr( $audio_url ); ?>"
			placeholder="https://example.com/episode.mp3"
			autocomplete="url"
		/>
		<span class="description"><?php esc_html_e( 'Enter an audio file URL using HTTPS or HTTP.', 'podcastpress' ); ?></span>
	</p>
	<p>
		<label for="podcastpress_transcript"><?php esc_html_e( 'Full episode transcript (required for accessibility)', 'podcastpress' ); ?></label>
		<textarea
			class="widefat"
			id="podcastpress_transcript"
			name="podcastpress_transcript"
			rows="10"
			required
			aria-describedby="podcastpress_transcript_help"
		><?php echo esc_textarea( $transcript ); ?></textarea>
		<span class="description" id="podcastpress_transcript_help"><?php esc_html_e( 'Provide a complete text version of the spoken audio. The transcript is displayed with the episode.', 'podcastpress' ); ?></span>
	</p>
	<?php
}

/**
 * Save the episode audio URL and transcript.
 *
 * @param int $post_id Episode ID.
 * @return void
 */
function podcastpress_save_episode_meta( $post_id ) {
	if (
		! isset( $_POST['podcastpress_episode_nonce'] ) ||
		! is_string( $_POST['podcastpress_episode_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['podcastpress_episode_nonce'] ) ), 'podcastpress_save_episode' )
	) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['podcastpress_audio_url'] ) && is_string( $_POST['podcastpress_audio_url'] ) ) {
		$audio_url = esc_url_raw( wp_unslash( $_POST['podcastpress_audio_url'] ), array( 'http', 'https' ) );

		if ( $audio_url ) {
			update_post_meta( $post_id, '_podcastpress_audio_url', $audio_url );
		} else {
			delete_post_meta( $post_id, '_podcastpress_audio_url' );
		}
	}

	if ( isset( $_POST['podcastpress_transcript'] ) && is_string( $_POST['podcastpress_transcript'] ) ) {
		update_post_meta(
			$post_id,
			'_podcastpress_transcript',
			sanitize_textarea_field( wp_unslash( $_POST['podcastpress_transcript'] ) )
		);
	}
}
add_action( 'save_post_podcastpress_episode', 'podcastpress_save_episode_meta' );

/**
 * Display the latest podcast episodes.
 *
 * Use [podcastpress] or [podcastpress limit="5" heading="Latest episodes"].
 *
 * @param array $attributes Shortcode attributes.
 * @return string
 */
function podcastpress_render_shortcode( $attributes ) {
	$attributes = shortcode_atts(
		array(
			'limit'   => 10,
			'heading' => __( 'Podcast episodes', 'podcastpress' ),
		),
		$attributes,
		'podcastpress'
	);

	$heading = sanitize_text_field( $attributes['heading'] );
	if ( '' === $heading ) {
		$heading = __( 'Podcast episodes', 'podcastpress' );
	}

	$limit    = min( 50, max( 1, absint( $attributes['limit'] ) ) );
	$episodes = new WP_Query(
		array(
			'post_type'              => 'podcastpress_episode',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'meta_key'               => '_podcastpress_audio_url',
			'meta_value'             => '',
			'meta_compare'           => '!=',
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
		)
	);

	if ( ! $episodes->have_posts() ) {
		return '';
	}

	$section_id = wp_unique_id( 'podcastpress-' );
	ob_start();
	?>
	<section class="podcastpress" aria-labelledby="<?php echo esc_attr( $section_id ); ?>">
		<h2 id="<?php echo esc_attr( $section_id ); ?>"><?php echo esc_html( $heading ); ?></h2>
		<?php
		while ( $episodes->have_posts() ) :
			$episodes->the_post();
			$episode_id = get_the_ID();
			$title_id   = wp_unique_id( 'podcastpress-episode-' );
			$audio_url  = get_post_meta( $episode_id, '_podcastpress_audio_url', true );
			$transcript = get_post_meta( $episode_id, '_podcastpress_transcript', true );
			?>
			<article class="podcastpress__episode" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
				<h3 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( get_the_title() ); ?></h3>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<?php if ( get_the_excerpt() ) : ?>
					<p class="podcastpress__description"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<audio controls preload="none" aria-label="<?php echo esc_attr( sprintf( __( 'Audio player for %s', 'podcastpress' ), get_the_title() ) ); ?>">
					<source src="<?php echo esc_url( $audio_url ); ?>" />
					<?php esc_html_e( 'Your browser does not support audio playback.', 'podcastpress' ); ?>
				</audio>
				<p><a href="<?php echo esc_url( $audio_url ); ?>"><?php echo esc_html( sprintf( __( 'Download audio: %s', 'podcastpress' ), get_the_title() ) ); ?></a></p>
				<?php if ( $transcript ) : ?>
					<details class="podcastpress__transcript">
						<summary><?php esc_html_e( 'Read the episode transcript', 'podcastpress' ); ?></summary>
						<div><?php echo wpautop( esc_html( $transcript ) ); ?></div>
					</details>
				<?php else : ?>
					<p class="podcastpress__transcript-missing"><?php esc_html_e( 'A transcript has not been added for this episode.', 'podcastpress' ); ?></p>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
	</section>
	<?php

	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'podcastpress', 'podcastpress_render_shortcode' );
