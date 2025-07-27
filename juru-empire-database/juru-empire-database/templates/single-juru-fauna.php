<?php
/*
 * Template Name: Single Fauna
 * Template Post Type: juru_fauna
 */

get_header(); ?>

<div class="single-juru_fauna">
    <?php
    while (have_posts()) : the_post();
        $planet_id = get_post_meta(get_the_ID(), 'juru_planet', true);
        $description = get_post_meta(get_the_ID(), 'juru_fauna_description', true);
        $diet = get_post_meta(get_the_ID(), 'juru_fauna_diet', true);
        $produces = get_post_meta(get_the_ID(), 'juru_fauna_produces', true);
        $image_1_id = get_post_meta(get_the_ID(), 'juru_fauna_image_1', true);
        $image_2_id = get_post_meta(get_the_ID(), 'juru_fauna_image_2', true);
        $image_3_id = get_post_meta(get_the_ID(), 'juru_fauna_image_3', true);

        $planet_title = $planet_id ? get_the_title($planet_id) : 'N/A';
    ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
            </header>

            <div class="entry-content">
                <div class="juru-fauna-details">
                    <p><strong>Planet:</strong> <?php echo esc_html($planet_title); ?></p>
                    <?php if ($description) : ?>
                        <p><strong>Description:</strong> <?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                    <?php if ($diet) : ?>
                        <p><strong>Diet:</strong> <?php echo esc_html($diet); ?></p>
                    <?php endif; ?>
                    <?php if ($produces) : ?>
                        <p><strong>Produces:</strong> <?php echo esc_html($produces); ?></p>
                    <?php endif; ?>

                    <div class="juru-fauna-images">
                        <?php
                        $images = [
                            $image_1_id => 'Featured Image',
                            $image_2_id => 'Additional Image 1',
                            $image_3_id => 'Additional Image 2'
                        ];
                        foreach ($images as $image_id => $label) {
                            if ($image_id) {
                                $image_url = wp_get_attachment_image_url($image_id, 'medium');
                                if ($image_url) {
                                    echo '<div class="juru-fauna-image">';
                                    echo '<strong>' . esc_html($label) . ':</strong><br>';
                                    echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title()) . ' ' . esc_attr($label) . '" class="img-thumbnail" style="max-width: 150px; margin-top: 0.5rem;">';
                                    echo '</div>';
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
</div>

<?php get_footer(); ?>