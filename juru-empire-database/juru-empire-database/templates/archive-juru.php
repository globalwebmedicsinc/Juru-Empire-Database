<?php
/**
 * Template Name: Juru Archive
 * Description: Archive template for Juru Empire Database Players and Fauna
 */
get_header(); ?>
<div class="juru-archive">
    <h1><?php post_type_archive_title(); ?></h1>
    <?php if (have_posts()) : ?>
        <ul>
        <?php while (have_posts()) : the_post(); ?>
            <li>
                <a href="<?php the_permalink(); ?>">
                    <?php the_title(); ?>
                    <?php if (get_post_type() === 'juru_fauna') : ?>
                        <?php $planet_id = get_post_meta(get_the_ID(), '_juru_planet', true); ?>
                        <?php if ($planet_id) : ?>
                            (Planet: <?php echo esc_html(get_the_title($planet_id)); ?>)
                        <?php endif; ?>
                    <?php endif; ?>
                </a>
            </li>
        <?php endwhile; ?>
        </ul>
        <?php the_posts_navigation(); ?>
    <?php else : ?>
        <p>No <?php echo esc_html(get_post_type_object(get_post_type())->labels->name); ?> found.</p>
    <?php endif; ?>
</div>
<?php get_footer(); ?>