<?php
get_header();
?>
<div class="juru-content">
    <h1>Players</h1>
    <ul>
        <?php
        $users = get_users();
        foreach ($users as $user) {
            echo "<li>" . esc_html($user->display_name) . "</li>";
        }
        ?>
    </ul>
</div>
<?php
get_footer();
?>