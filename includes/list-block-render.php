<?php
/**
 * List Block Renderer
 *
 * @package QuickChoiceMovies
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render callback for the list block
 */
function qcm_render_list_block( $attributes, $content, $block ) {
    // Get the current post ID
    $post_id = get_the_ID();

    if ( ! $post_id ) {
        return '<div class="qcm-list-empty">' . esc_html__( 'No post found.', 'quick-choice-movies' ) . '</div>';
    }

    // Get the items from post meta
    $items_json = get_post_meta( $post_id, 'qcm_choice_items', true );

    if ( empty( $items_json ) ) {
        return '<div class="qcm-list-empty">' . esc_html__( 'No items found.', 'quick-choice-movies' ) . '</div>';
    }

    $items = json_decode( $items_json, true );

    if ( ! is_array( $items ) || empty( $items ) ) {
        return '<div class="qcm-list-empty">' . esc_html__( 'No items found.', 'quick-choice-movies' ) . '</div>';
    }

    // Build the HTML
    ob_start();
    ?>
    <div class="qcm-list-block">
        <h2 class="qcm-list-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
        <div class="qcm-list-grid">
            <?php foreach ( $items as $item ) : ?>
                <div class="qcm-list-item">
                    <?php if ( ! empty( $item['image'] ) ) : ?>
                        <img
                            src="<?php echo esc_url( $item['image'] ); ?>"
                            alt="<?php echo esc_attr( $item['title'] ); ?>"
                            class="qcm-list-item__image"
                        />
                    <?php endif; ?>
                    <div class="qcm-list-item__title">
                        <?php echo esc_html( $item['title'] ); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
