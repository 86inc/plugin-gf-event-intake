<?php

get_header();

if (!function_exists('genesis_markup')) {
    printf("Missing Genesis theme");
    get_footer();
    exit;
}

/**
 * Fires after the header, before the content sidebar wrap.
 *
 * @since 1.0.0
 */
do_action( 'genesis_before_content_sidebar_wrap' );

genesis_markup(
    [
        'open'    => '<div %s>',
        'context' => 'content-sidebar-wrap',
    ]
);

    /**
     * Fires before the content, after the content sidebar wrap opening markup.
     *
     * @since 1.0.0
     */
    do_action( 'genesis_before_content' );

    genesis_markup(
        [
            'open'    => '<main %s>',
            'context' => 'content',
        ]
    );

        /**
         * Fires before the loop hook, after the main content opening markup.
         *
         * @since 1.0.0
         */
        do_action( 'genesis_before_loop' );

        if ( have_posts() ) {

            /**
             * Fires inside the standard loop, before the while() block.
             *
             * @since 2.1.0
             */
            do_action( 'genesis_before_while' );
    
            while ( have_posts() ) {
    
                the_post();
    
                /**
                 * Fires inside the standard loop, before the entry opening markup.
                 *
                 * @since 2.0.0
                 */
                //do_action( 'genesis_before_entry' );
    
                genesis_markup(
                    [
                        'open'    => '<article %s>',
                        'context' => 'entry',
                    ]
                );
    
                /**
                 * Fires inside the standard loop, to display the entry header.
                 *
                 * @since 2.0.0
                 */
                //do_action( 'genesis_entry_header' );
                ?>

                <header class="entry-header">
                <h1 class="entry-title"><?php the_title() ?></h1>
                </header>
                <?php
                /**
                 * Fires inside the standard loop, after the entry header action hook, before the entry content.
                 * opening markup.
                 *
                 * @since 2.0.0
                 */
                do_action( 'genesis_before_entry_content' );
    
                genesis_markup(
                    [
                        'open'    => '<div %s>',
                        'context' => 'entry-content',
                    ]
                );
            
                the_post_thumbnail('one-half', array('class' => 'aligncenter'));
                $venue_post_id = Gf_Event_Intake_Admin::get_acf_post_option('venue', get_the_ID());
                $venue_location = __('No Event location has been set.');
                if (!empty($venue_post_id)) {
                    $venue = get_post($venue_post_id);
                    if (!empty($venue) && ($venue->post_status === 'publish')) {
                        $venue_location = sprintf('%s</br>%s', $venue->post_title, $venue->post_content);
                    }
                }

                $cast_crew = Gf_Event_Intake_Admin::get_acf_post_option('cast_crew', get_the_ID());
                $related_products = Gf_Event_Intake_Admin::get_acf_post_option('related_products', get_the_ID());
                $related_products_data = array();
                if (!empty($related_products)) {
                    foreach($related_products as $related_product_id) {
                        $product = get_post($related_product_id);
                        if ($product->post_status !== 'publish') {
                            continue;
                        }
                        $related_products_data[$related_product_id] = array(
                            'link' => get_permalink($related_product_id),
                            'title' => $product->post_title,
                        );
                    }
                }
                ?>
                <h2>by <?php the_author() ?></h2>
                <div class="textlarge"><?php the_content(); ?></div>
                <h3 style="margin: 30px auto 16px auto;">Event Dates</h3>
                <?php if (empty($related_products_data)) : ?>
                    <div class="textevent">No event date information</div>
                <?php else : ?>    
                    <?php foreach($related_products_data as $related_product) : ?>
                    <div class="textsmall">
                    <?php
                    printf('<a href="%s" target="_blank">%s</a>', $related_product['link'], $related_product['title'] );
                    ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <h3 style="margin: 30px auto 16px auto;">Event Location</h3>
                <div class="textevent"><?php echo $venue_location ?></div>
                <h3 style="margin: 30px auto 16px auto;">Producer Notes</h3>
                <div class="textlarge">Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe.</div>
                <h3 style="margin: 30px 0 16px 7px; text-align: left;">Event Members</h3>
                <span class="cast-crew">
                <?php if (empty($cast_crew)) : ?>
                    No event members yet!
                <?php else : ?>    
                    <?php foreach($cast_crew as $crew_member) : ?>
                    <div class="textsmall">
                    <?php 
                    if ( empty( $crew_member['image']['id'] ) ) {
                        printf("no image set");
                    } else {
                        $thumbnail_url = wp_get_attachment_image_src( $crew_member['image']['id'], 'thumbnail' );
                        printf('<img class="alignleft size-tiny" src="%s" width="80" height="80" />', $thumbnail_url[0] );
                    }
                    if (!empty($crew_member['title'])) {
                        printf('<span class="crew-title">%s</span>', $crew_member['title']);
                    }
                    if (!empty($crew_member['subtitle'])) {
                        printf('<span class="crew-subtitle">%s</span>', $crew_member['subtitle']);
                    }
                    if (!empty($crew_member['desc'])) {
                        printf('<span class="crew-desc">%s</span>', $crew_member['desc']);
                    }
                    ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </span>
                <p style="margin: 30px 0; color: #000000;"></p>
                <?php
                

                genesis_markup(
                    [
                        'close'   => '</div>',
                        'context' => 'entry-content',
                    ]
                );
    
                /**
                 * Fires inside the standard loop, before the entry footer action hook, after the entry content.
                 * opening markup.
                 *
                 * @since 2.0.0
                 */
                do_action( 'genesis_after_entry_content' );
    
                /**
                 * Fires inside the standard loop, to display the entry footer.
                 *
                 * @since 2.0.0
                 */
                //do_action( 'genesis_entry_footer' );
    
                genesis_markup(
                    [
                        'close'   => '</article>',
                        'context' => 'entry',
                    ]
                );
    
                /**
                 * Fires inside the standard loop, after the entry closing markup.
                 *
                 * @since 2.0.0
                 */
                do_action( 'genesis_after_entry' );
    
            } // End of one post.
    
            /**
             * Fires inside the standard loop, after the while() block.
             *
             * @since 1.0.0
             */
            do_action( 'genesis_after_endwhile' );
    
        } else { // If no posts exist.
    
            /**
             * Fires inside the standard loop when they are no posts to show.
             *
             * @since 1.0.0
             */
            do_action( 'genesis_loop_else' );
    
        } // End loop.
        

        ?>
        

        <?php

        /**
         * Fires after the loop hook, before the main content closing markup.
         *
         * @since 1.0.0
         */
        do_action( 'genesis_after_loop' );

    genesis_markup(
        [
            'close'   => '</main>', // End .content.
            'context' => 'content',
        ]
    );

    /**
     * Fires after the content, before the main content sidebar wrap closing markup.
     *
     * @since 1.0.0
     */
    do_action( 'genesis_after_content' );

genesis_markup(
    [
        'close'   => '</div>',
        'context' => 'content-sidebar-wrap',
    ]
);

/**
 * Fires before the footer, after the content sidebar wrap.
 *
 * @since 1.0.0
 */
do_action( 'genesis_after_content_sidebar_wrap' );

get_footer();