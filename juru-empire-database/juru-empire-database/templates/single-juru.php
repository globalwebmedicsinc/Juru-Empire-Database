<?php
get_header();
if (have_posts()) : while (have_posts()) : the_post();
?>
<div class="juru-content">
    <h1><?php the_title(); ?></h1>
    <?php if (has_post_thumbnail()) : ?>
        <?php
        $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
        error_log('Juru Empire Database: Thumbnail URL for post ' . get_the_ID() . ': ' . ($thumbnail_url ? $thumbnail_url : 'Empty'));
        ?>
        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" style="max-width:100%; height:auto; border-radius:6px;">
    <?php else : ?>
        <p>No featured image set for this post.</p>
    <?php endif; ?>
    
    <?php if (get_post_type() === "juru_system") : ?>
        <?php
        $economy_type = get_post_meta(get_the_ID(), "_juru_economy_type", true);
        $economy_strength = get_post_meta(get_the_ID(), "_juru_economy_strength", true);
        $orbital_bodies = get_post_meta(get_the_ID(), "_juru_orbital_bodies", true);
        ?>
        <?php if ($economy_type) : ?>
            <p><strong>Economy Type:</strong> <?php echo esc_html($economy_type); ?></p>
        <?php else : ?>
            <p><em>No economy type specified.</em></p>
        <?php endif; ?>
        <?php if ($economy_strength) : ?>
            <p><strong>Economy Strength:</strong> <?php echo esc_html($economy_strength); ?></p>
        <?php else : ?>
            <p><em>No economy strength specified.</em></p>
        <?php endif; ?>
        <?php if ($orbital_bodies) : ?>
            <p><strong>Orbital Bodies:</strong> <?php echo esc_html($orbital_bodies); ?></p>
        <?php else : ?>
            <p><em>No orbital bodies specified.</em></p>
        <?php endif; ?>
        <h3>Planets</h3>
        <?php
        $planets = new WP_Query([
            "post_type" => "juru_planet",
            "post_status" => ["publish", "draft", "pending"],
            "meta_query" => [
                [
                    "key" => "_juru_system",
                    "value" => get_the_ID(),
                    "compare" => "="
                ]
            ],
            "posts_per_page" => -1,
            "orderby" => "title",
            "order" => "ASC",
            "no_found_rows" => true
        ]);
        if ($planets->have_posts()) :
            echo "<ul>";
            while ($planets->have_posts()) : $planets->the_post();
                echo "<li><a href=\"" . esc_url(get_permalink()) . "\">" . esc_html(get_the_title()) . " (" . esc_html(get_post_status()) . ")</a></li>";
            endwhile;
            echo "</ul>";
            wp_reset_postdata();
        else :
            error_log("Juru Empire Database: No planets found for system ID " . get_the_ID() . ". Meta query: " . json_encode($planets->query));
            echo "<p><em>No planets found for this system.</em></p>";
        endif;
        ?>
    <?php elseif (get_post_type() === "juru_planet") : ?>
        <?php
        $system_id = get_post_meta(get_the_ID(), "_juru_system", true);
        $system_title = $system_id ? get_the_title($system_id) : "";
        $system_link = $system_id ? get_permalink($system_id) : "";
        $portal_address = get_post_meta(get_the_ID(), "_juru_portal_address", true);
        ?>
        <?php if ($system_id && $system_title) : ?>
            <p><strong>System:</strong> <a href="<?php echo esc_url($system_link); ?>"><?php echo esc_html($system_title); ?></a></p>
        <?php else : ?>
            <p><em>No system assigned.</em></p>
        <?php endif; ?>
        <?php if ($portal_address) : ?>
            <p><strong>Portal Address:</strong> <?php echo esc_html($portal_address); ?></p>
        <?php else : ?>
            <p><em>No portal address specified.</em></p>
        <?php endif; ?>
        <h3>Points of Interest</h3>
        <?php
        $pois = new WP_Query([
            "post_type" => "juru_poi",
            "meta_query" => [
                [
                    "key" => "_juru_planet",
                    "value" => get_the_ID(),
                    "compare" => "="
                ]
            ],
            "posts_per_page" => -1,
            "orderby" => "title",
            "order" => "ASC",
            "no_found_rows" => true
        ]);
        $poi_by_type = [];
        if ($pois->have_posts()) :
            while ($pois->have_posts()) : $pois->the_post();
                $poi_type = get_post_meta(get_the_ID(), "_juru_poi_type", true);
                if (!isset($poi_by_type[$poi_type])) {
                    $poi_by_type[$poi_type] = [];
                }
                $poi_by_type[$poi_type][] = [
                    "title" => get_the_title(),
                    "permalink" => get_permalink()
                ];
            endwhile;
            wp_reset_postdata();
        ?>
            <div class="juru-poi-cards-container">
                <?php foreach ($poi_by_type as $type => $poi_list) : ?>
                    <?php
                    $type_label = ucwords(str_replace("_", " ", $type));
                    ?>
                    <div class="juru-poi-card">
                        <h4><?php echo esc_html($type_label); ?></h4>
                        <ul>
                            <?php foreach ($poi_list as $poi) : ?>
                                <li><a href="<?php echo esc_url($poi["permalink"]); ?>"><?php echo esc_html($poi["title"]); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <?php error_log("Juru Empire Database: No POIs found for planet ID " . get_the_ID() . ". Meta query: " . json_encode($pois->query)); ?>
            <p><em>No points of interest found for this planet.</em></p>
        <?php endif; ?>
        <?php if (get_option("juru_display_map", true)) : ?>
            <h3>Planetary Map</h3>
            <?php
            $max_coord_x = get_option("juru_max_coord_x", 1000);
            $max_coord_y = get_option("juru_max_coord_y", 1000);
            $pois = get_posts([
                "post_type" => "juru_poi",
                "post_status" => ["publish", "draft", "pending"],
                "meta_query" => [
                    [
                        "key" => "_juru_planet",
                        "value" => get_the_ID(),
                        "compare" => "="
                    ]
                ],
                "posts_per_page" => -1,
                "no_found_rows" => true
            ]);
            ?>
            <div id="planet-map" style="width:<?php echo esc_attr($max_coord_x); ?>px; height:<?php echo esc_attr($max_coord_y); ?>px; background: url('https://i.imgur.com/eqo3DP3.png') no-repeat center center fixed; background-size: cover; position: relative;">
                <?php if ($pois) : ?>
                    <?php foreach ($pois as $poi) : ?>
                        <?php
                        $coord_x = get_post_meta($poi->ID, "_juru_coord_x", true);
                        $coord_y = get_post_meta($poi->ID, "_juru_coord_y", true);
                        $poi_type = get_post_meta($poi->ID, "_juru_poi_type", true);
                        $color = $poi_type === "ships" ? "#ff0000" : ($poi_type === "trading_outpost" ? "#00ff00" : ($poi_type === "archives_outpost" ? "#0000ff" : ($poi_type === "minor_settlement" ? "#ffa500" : ($poi_type === "crashed_freighter" ? "#800080" : "#008080"))));
                        ?>
                        <?php if ($coord_x !== "" && $coord_y !== "") : ?>
                            <div style="position:absolute; left:<?php echo esc_attr($coord_x); ?>px; top:<?php echo esc_attr($coord_y); ?>px; width:10px; height:10px; background:<?php echo esc_attr($color); ?>; border-radius:50%;" title="<?php echo esc_attr($poi->post_title) . " (" . esc_html($poi_type) . ")"; ?>"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:#374151;">No POIs to display on map.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php elseif (get_post_type() === "juru_poi") : ?>
        <?php
        $planet_id = get_post_meta(get_the_ID(), "_juru_planet", true);
        $planet_title = $planet_id ? get_the_title($planet_id) : "";
        $planet_link = $planet_id ? get_permalink($planet_id) : "";
        $poi_type = get_post_meta(get_the_ID(), "_juru_poi_type", true);
        $coord_x = get_post_meta(get_the_ID(), "_juru_coord_x", true);
        $coord_y = get_post_meta(get_the_ID(), "_juru_coord_y", true);
        ?>
        <?php if ($planet_id && $planet_title) : ?>
            <p><strong>Planet:</strong> <a href="<?php echo esc_url($planet_link); ?>"><?php echo esc_html($planet_title); ?></a></p>
        <?php else : ?>
            <p><em>No planet assigned.</em></p>
        <?php endif; ?>
        <?php if ($poi_type) : ?>
            <p><strong>POI Type:</strong> <?php echo esc_html($poi_type); ?></p>
        <?php else : ?>
            <p><em>No POI type specified.</em></p>
        <?php endif; ?>
        <?php if ($coord_x !== "" && $coord_y !== "") : ?>
            <p><strong>Coordinates:</strong> X: <?php echo esc_html($coord_x); ?>, Y: <?php echo esc_html($coord_y); ?></p>
        <?php else : ?>
            <p><em>No coordinates specified.</em></p>
        <?php endif; ?>
        <?php if (get_option("juru_display_map", true)) : ?>
            <?php
            $max_coord_x = get_option("juru_max_coord_x", 1000);
            $max_coord_y = get_option("juru_max_coord_y", 1000);
            $color = $poi_type === "ships" ? "#ff0000" : ($poi_type === "trading_outpost" ? "#00ff00" : ($poi_type === "archives_outpost" ? "#0000ff" : ($poi_type === "minor_settlement" ? "#ffa500" : ($poi_type === "crashed_freighter" ? "#800080" : "#008080"))));
            ?>
            <div id="poi-map" style="width:<?php echo esc_attr($max_coord_x); ?>px; height:<?php echo esc_attr($max_coord_y); ?>px; background:#f0f0f0; position:relative; border:1px solid #d1d5db; border-radius:6px;">
                <?php if ($coord_x !== "" && $coord_y !== "") : ?>
                    <div style="position:absolute; left:<?php echo esc_attr($coord_x); ?>px; top:<?php echo esc_attr($coord_y); ?>px; width:10px; height:10px; background:<?php echo esc_attr($color); ?>; border-radius:50%;" title="<?php echo esc_attr(get_the_title()) . " (" . esc_html($poi_type) . ")"; ?>"></div>
                <?php else : ?>
                    <p style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); color:#374151;">No coordinates to display.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php elseif (get_post_type() === "juru_fauna") : ?>
        <?php
        $planet_id = get_post_meta(get_the_ID(), "_juru_planet", true);
        $planet_title = $planet_id ? get_the_title($planet_id) : "";
        $planet_link = $planet_id ? get_permalink($planet_id) : "";
        $description = get_post_meta(get_the_ID(), "_juru_fauna_description", true);
        $diet = get_post_meta(get_the_ID(), "_juru_fauna_diet", true);
        $produces = get_post_meta(get_the_ID(), "_juru_fauna_produces", true);
        $image_2_id = get_post_meta(get_the_ID(), "_juru_fauna_image_2", true);
        $image_3_id = get_post_meta(get_the_ID(), "_juru_fauna_image_3", true);
        ?>
        <?php if ($planet_id && $planet_title) : ?>
            <p><strong>Planet:</strong> <a href="<?php echo esc_url($planet_link); ?>"><?php echo esc_html($planet_title); ?></a></p>
        <?php else : ?>
            <p><em>No planet assigned.</em></p>
        <?php endif; ?>
        <?php if ($description) : ?>
            <p><strong>Description:</strong> <?php echo esc_html($description); ?></p>
        <?php else : ?>
            <p><em>No description specified.</em></p>
        <?php endif; ?>
        <?php if ($diet) : ?>
            <p><strong>Diet:</strong> <?php echo esc_html($diet); ?></p>
        <?php else : ?>
            <p><em>No diet specified.</em></p>
        <?php endif; ?>
        <?php if ($produces) : ?>
            <p><strong>Produces:</strong> <?php echo esc_html($produces); ?></p>
        <?php else : ?>
            <p><em>No produces specified.</em></p>
        <?php endif; ?>
        <div class="juru-fauna-images">
            <?php if (has_post_thumbnail()) : ?>
                <div class="juru-fauna-image">
                    <strong>Featured Image:</strong><br>
                    <?php the_post_thumbnail('medium', ['style' => 'max-width: 150px; margin-top: 0.5rem; border-radius: 6px;']); ?>
                </div>
            <?php endif; ?>
            <?php if ($image_2_id) : ?>
                <div class="juru-fauna-image">
                    <strong>Additional Image 1:</strong><br>
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($image_2_id, 'medium')); ?>" alt="<?php echo esc_attr(get_the_title()) . ' Additional Image 1'; ?>" style="max-width: 150px; margin-top: 0.5rem; border-radius: 6px;">
                </div>
            <?php endif; ?>
            <?php if ($image_3_id) : ?>
                <div class="juru-fauna-image">
                    <strong>Additional Image 2:</strong><br>
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($image_3_id, 'medium')); ?>" alt="<?php echo esc_attr(get_the_title()) . ' Additional Image 2'; ?>" style="max-width: 150px; margin-top: 0.5rem; border-radius: 6px;">
                </div>
            <?php endif; ?>
            <?php if (!has_post_thumbnail() && !$image_2_id && !$image_3_id) : ?>
                <p><em>No images available.</em></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (is_user_logged_in()) : ?>
    <div id="juru-drawer" class="juru-drawer">
        <form id="juru-drawer-form" data-type="">
            <div class="juru-system-fields" style="display:none;">
                <label for="juru_system_title">Title: <input type="text" id="juru_system_title" required /></label>
                <label for="juru_system_economy_type">Economy Type: <input type="text" id="juru_system_economy_type" /></label>
                <label for="juru_system_economy_strength">Economy Strength: <input type="text" id="juru_system_economy_strength" /></label>
                <label for="juru_system_orbital_bodies">Orbital Bodies (1-6): <input type="number" id="juru_system_orbital_bodies" min="1" max="6" /></label>
            </div>
            <div class="juru-planet-fields" style="display:none;">
                <label for="juru_planet_title">Title: <input type="text" id="juru_planet_title" required /></label>
                <label for="juru_planet_system">System: <select id="juru_planet_system"><option value="">Select a System</option></select></label>
                <label for="juru_planet_portal_address">Portal Address: <input type="text" id="juru_planet_portal_address" /></label>
            </div>
            <div class="juru-poi-fields" style="display:none;">
                <label for="juru_poi_title">Title: <input type="text" id="juru_poi_title" required /></label>
                <label for="juru_poi_planet">Planet: <select id="juru_poi_planet"><option value="">Select a Planet</option></select></label>
                <label for="juru_poi_type">POI Type: 
                    <select id="juru_poi_type">
                        <option value="ships">Ships</option>
                        <option value="trading_outpost">Trading Outpost</option>
                        <option value="archives_outpost">Archives Outpost</option>
                        <option value="minor_settlement">Minor Settlement</option>
                        <option value="crashed_freighter">Crashed Freighter</option>
                        <option value="settlements">Settlements</option>
                    </select>
                </label>
                <label for="juru_poi_coord_x">X Coordinate: <input type="number" id="juru_poi_coord_x" step="0.01" min="0" max="<?php echo esc_attr(get_option("juru_max_coord_x", 1000)); ?>" /></label>
                <label for="juru_poi_coord_y">Y Coordinate: <input type="number" id="juru_poi_coord_y" step="0.01" min="0" max="<?php echo esc_attr(get_option("juru_max_coord_y", 1000)); ?>" /></label>
            </div>
            <div class="juru-fauna-fields" style="display:none;">
                <label for="juru_fauna_title">Title: <input type="text" id="juru_fauna_title" required /></label>
                <label for="juru_fauna_planet">Planet: <select id="juru_fauna_planet"><option value="">Select a Planet</option></select></label>
                <label for="juru_fauna_description">Description: <textarea id="juru_fauna_description" rows="4" cols="50"></textarea></label>
                <label for="juru_fauna_diet">Diet: <input type="text" id="juru_fauna_diet" /></label>
                <label for="juru_fauna_produces">Produces: <input type="text" id="juru_fauna_produces" /></label>
                <label for="juru_fauna_image_2">Additional Image 1: <input type="hidden" id="juru_fauna_image_2" /><button type="button" class="button juru-upload-image" data-target="juru_fauna_image_2">Select Image</button><img id="juru_fauna_image_2_preview" style="max-width:150px;display:none;" /></label>
                <label for="juru_fauna_image_3">Additional Image 2: <input type="hidden" id="juru_fauna_image_3" /><button type="button" class="button juru-upload-image" data-target="juru_fauna_image_3">Select Image</button><img id="juru_fauna_image_3_preview" style="max-width:150px;display:none;" /></label>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php
endwhile; endif;
get_footer();