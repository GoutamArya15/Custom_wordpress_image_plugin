<?php
/*
Plugin Name: Gallery
Description: A simple plugin to upload images and store their information in the database.
Version: 10.1
*/

register_activation_hook(__FILE__, 'add_gallery_image');
function add_gallery_image()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "image_data";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        `id` INT NOT NULL AUTO_INCREMENT,
        `image_name` VARCHAR(255) NOT NULL,
        PRIMARY KEY (`id`)
    ) $charset_collate ENGINE=InnoDB";

    $wpdb->query($sql);
}

register_deactivation_hook(__FILE__, 'remove_gallery_image');
function remove_gallery_image()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "image_data";
    $delete = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($delete);
}

function custom_menu()
{
    add_menu_page(
        __('Custom Menu Title', 'textdomain'),
        'Add Images',
        'manage_options',
        'custompage',
        'my_custom_menu_page',
        null,
        60
    );
}

add_action('admin_menu', 'custom_menu');
function my_custom_menu_page()
{
    if (isset($_POST['submit'])) {
        if (empty($_FILES['image']['name'])) {
            echo "<p style='color:red'>Please choose an image!</p>";
        } else {
            global $wpdb;
            $image = $_FILES['image'];
            $table = $wpdb->prefix . 'image_data';
            if ($image['error'] === UPLOAD_ERR_OK) {
                $override = array('test_form' => false);
                $uploaded_file = wp_handle_upload($image, $override);
                if (!isset($uploaded_file['error'])) {
                    $file_path = $uploaded_file['file'];
                    $file_type = $uploaded_file['type'];
                    $file_name = basename($uploaded_file['file']);
                    $data = array('image_name' => $file_name);
                    $format = array('%s');
                    $wpdb->insert($table, $data, $format);
                    $attachment = array(
                        'guid'           => $uploaded_file['url'],
                        'post_mime_type' => $file_type,
                        'post_title'     => sanitize_file_name($file_name),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    $attach_id = wp_insert_attachment($attachment, $file_path);
                    // print_r($attach_id);

                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    echo '<img src="' . esc_url($uploaded_file['url']) . '" alt="">';
                } else {
                    echo 'Error uploading file: ' . esc_html($uploaded_file['error']);
                }
            } else {
                echo 'File upload error: ' . esc_html($image['error']);
            }
        }
    }
?>
    <div style="margin-top:70px; margin-left:50px">
        <form action="<?php echo esc_url(admin_url('admin.php?page=custompage')); ?>" method="post"
            enctype="multipart/form-data">
            <p>Image: <input type="file" name="image"></p>
            <input type="submit" name="submit" value="Submit">
        </form>
    </div>
<?php
}
add_shortcode('images', 'image_shortcode');
function image_shortcode()
{
    global $wpdb;
    $retrieve_data = $wpdb->get_results("SELECT `image_name` FROM `wp_image_data`; ");
    wp_enqueue_style('style.css', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_style('lightbox.min.css', plugin_dir_url(__FILE__) . 'assets/css/lightbox.min.css');
    wp_enqueue_script('lightbox-plus-jquery.min.js', plugin_dir_url(__FILE__) . 'assets/js/lightbox-plus-jquery.min.js');
?>
    <!-- image slider html  -->
    <div class="gallery">

        <?php foreach ($retrieve_data as $image_name) { ?>
            <a href="<?php echo $image_name->image_name ?>" data-lightbox='mygallery' data-title="Deer"><img
                    src="<?php echo $image_name->image_name ?>"></a>
        <?php } ?>
    </div>
    <!-- image slider images html end  -->
<?php
}
?>