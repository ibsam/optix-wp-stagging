<?php
/*
Plugin Name: Form Submit Plugin
Description: Simple frontend form to submit name and email, and view entries in dashboard.
Version: 1.0
Author: Aapka Naam
*/

// ---------- Shortcode: [simple_form] ----------
function fsp_form_shortcode() {
    ob_start();
    ?>
    <form method="post">
        <label>Name:</label><br>
        <input type="text" name="fsp_name" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="fsp_email" required><br><br>

        <input type="submit" name="fsp_submit" value="Submit">
    </form>
    <?php

    // Handle form submission
    if (isset($_POST['fsp_submit'])) {
        $name = sanitize_text_field($_POST['fsp_name']);
        $email = sanitize_email($_POST['fsp_email']);

        global $wpdb;
        $table = $wpdb->prefix . 'fsp_entries';

        $wpdb->insert($table, [
            'name'  => $name,
            'email' => $email
        ]);

        echo "<p><strong>Data submitted successfully!</strong></p>";
    }

    return ob_get_clean();
}
add_shortcode('simple_form', 'fsp_form_shortcode');

// ---------- Create Custom Table on Activation ----------
function fsp_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'fsp_entries';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'fsp_create_table');

// ---------- Admin Menu ----------
function fsp_admin_menu() {
    add_menu_page(
        'Form Entries',           // Page title
        'Form Entries',           // Menu title in sidebar
        'manage_options',         // Required capability
        'fsp-form-entries',       // Slug
        'fsp_show_entries_page',  // Callback function
        'dashicons-feedback',     // Icon
        25                        // Menu position
    );
}
add_action('admin_menu', 'fsp_admin_menu');

function fsp_show_entries_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'fsp_entries';

    // Delete logic
    if (isset($_GET['delete_id'])) {
        $delete_id = intval($_GET['delete_id']);
        $wpdb->delete($table, ['id' => $delete_id]);
        echo "<div class='notice notice-success is-dismissible'><p>Entry deleted successfully.</p></div>";
    }

    // Get all entries
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");

    echo "<div class='wrap'>";
    echo "<h2>Form Entries</h2>";

    if ($results) {
        echo "<table class='widefat fixed'>";
        echo "<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Submitted At</th><th>Action</th></tr></thead><tbody>";

        foreach ($results as $row) {
            $delete_url = admin_url('admin.php?page=fsp-form-entries&delete_id=' . $row->id);

            echo "<tr>";
            echo "<td>{$row->id}</td>";
            echo "<td>{$row->name}</td>";
            echo "<td>{$row->email}</td>";
            echo "<td>{$row->submitted_at}</td>";
            echo "<td><a href='$delete_url' onclick=\"return confirm('Are you sure you want to delete this entry?')\" class='button button-small'>Delete</a></td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    } else {
        echo "<p>No entries found.</p>";
    }

    echo "</div>";
}
