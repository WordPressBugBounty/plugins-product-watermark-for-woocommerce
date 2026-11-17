<?php
define( "BeRocket_image_watermark_domain", 'product-watermark-for-woocommerce'); 
define( "image_watermark_TEMPLATE_PATH", plugin_dir_path( __FILE__ ) . "templates/" );
load_plugin_textdomain('product-watermark-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
require_once(plugin_dir_path( __FILE__ ).'berocket/framework.php');
foreach (glob(__DIR__ . "/includes/*.php") as $filename)
{
    include_once($filename);
}
if( ( ! defined("BPIW_VER_PAID_DISABLE") || ! BPIW_VER_PAID_DISABLE ) && file_exists(__DIR__ . "/paid/paid.php") ) {
    include_once(__DIR__ . "/paid/paid.php");
}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
include_once(plugin_dir_path( __FILE__ ) . "includes/admin/funnels.php");

class BeRocket_image_watermark extends BeRocket_Framework {
    public static $settings_name = 'br-image_watermark-options';
    protected static $instance;
    public $import_export = array();
    protected $disable_settings_for_admin = array();
    protected $check_init_array = array(
        array(
            'check' => 'framework_version',
            'data' => array(
                'version' => '2.1',
                'operator' => '>=',
                'notice'   => 'Please update all BeRocket plugins to the most recent version. Product Watermark for WooCommerce is not working correctly with older versions.'
            )
        ),
        array(
            'check' => 'temp_php_extension',
            'data' => array(
                'extension' => 'gd',
                'notice'   => 'The required PHP module, GD, is not installed, or has been disabled. Product Watermark for WooCommerce required GD module to work with images.'
            )
        ),
    );
    function __construct () {
        $this->info = array(
            'id'          => 14,
            'lic_id'      => 77,
            'version'     => BeRocket_image_watermark_version,
            'plugin'      => '',
            'slug'        => '',
            'key'         => '',
            'name'        => '',
            'plugin_name' => 'image_watermark',
            'plugin_sku'  => 'watermarks',
            'full_name'   => 'Product Watermark for WooCommerce',
            'norm_name'   => 'Product Watermark',
            'price'       => '',
            'domain'      => 'product-watermark-for-woocommerce',
            'templates'   => image_watermark_TEMPLATE_PATH,
            'plugin_file' => BeRocket_image_watermark_file,
            'plugin_dir'  => __DIR__,
        );
        $this->defaults = array(
            'enable_live'       => '0',
            'jpeg_quantity'     => '90',
            'max_img_width'     => '2500',
            'max_img_height'    => '2500',
            'php_memory_limit'  => (function_exists('ini_get') ? ini_get('memory_limit') : ''),
            'shop_thumbnail'    => array(
                'ratio'             => array(0 => '1', 1 => '1', 2 => '1', 3 => '1', 4 => '1'),
                'text'              => '',
                'text_alpha'        => '30',
                'text_angle'        => '0',
                'font_size'         => '20',
                'image_count'       => '0',
                'font_color'        => '#FFFFFF',
                'image'             => array(0 => '', 1 => '', 2 => '', 3 => '', 4 => ''),
                'top'               => array(0 => '25', 1 => '25', 2 => '25', 3 => '25', 4 => '25'),
                'left'              => array(0 => '25', 1 => '25', 2 => '25', 3 => '25', 4 => '25'),
                'width'             => array(0 => '50', 1 => '50', 2 => '50', 3 => '50', 4 => '50'),
                'height'            => array(0 => '50', 1 => '50', 2 => '50', 3 => '50', 4 => '50'),
            ),
            'shop_single'       => array(
                'ratio'             => array(0 => '1', 1 => '1', 2 => '1', 3 => '1', 4 => '1'),
                'text'              => '',
                'text_alpha'        => '30',
                'text_angle'        => '0',
                'font_size'         => '20',
                'image_count'       => '0',
                'font_color'        => '#FFFFFF',
                'image'             => array(0 => '', 1 => '', 2 => '', 3 => '', 4 => ''),
                'top'               => array(0 => '25', 1 => '25', 2 => '25', 3 => '25', 4 => '25'),
                'left'              => array(0 => '25', 1 => '25', 2 => '25', 3 => '25', 4 => '25'),
                'width'             => array(0 => '50', 1 => '50', 2 => '50', 3 => '50', 4 => '50'),
                'height'            => array(0 => '50', 1 => '50', 2 => '50', 3 => '50', 4 => '50'),
            ),
            'shop_catalog'      => array(
                'ratio'             => array(0 => '1', 1 => '1', 2 => '1', 3 => '1', 4 => '1'),
                'text'              => '',
                'text_alpha'        => '30',
                'text_angle'        => '0',
                'font_size'         => '20',
                'image_count'       => '0',
                'font_color'        => '#FFFFFF',
                'image'             => array(0 => '', 1 => '', 2 => '', 3 => '', 4 => ''),
                'top'               => array(0 => '25', 1 => '25', 2 => '25', 3 => '25', 4 => '25'),
                'left'              => array(0 => '25', 1 => '25', 2 => '25', 3 => '25', 4 => '25'),
                'width'             => array(0 => '50', 1 => '50', 2 => '50', 3 => '50', 4 => '50'),
                'height'            => array(0 => '50', 1 => '50', 2 => '50', 3 => '50', 4 => '50'),
            ),
            'addons'            => array(
                '/media_buttons/media_buttons.php',
            ),
            'image_lib' => ''
        );
        $this->values = array(
            'settings_name' => 'br-image_watermark-options',
            'option_page'   => 'br-image_watermark',
            'premium_slug'  => 'woocommerce-products-image-watermark',
            'free_slug'     => 'product-watermark-for-woocommerce',
            'hpos_comp'     => true
        );
        $this->active_libraries = array('addons', 'tooltip');
        $this->feature_list = array();
        add_filter('BeRocket_framework_check_init_temp_php_extension', array($this, 'temp_php_extension_check'), 10, 2);
        parent::__construct( $this );
        add_action('wp_ajax_berocket_watermark_image_details', array($this, 'ajax_watermark_image_details'));
        if( $this->check_framework_version() ) {
            if ( $this->init_validation() ) {
                $options = $this->get_option();
                if( ! empty($options['image_lib']) && $options['image_lib'] == 'imagick' && class_exists('Imagick') ) {
                    include_once __DIR__ . '/includes/imagelib/imagick.php';
                } else {
                    include_once __DIR__ . '/includes/imagelib/gd.php';
                }
                //add meta key to image that must be watermarked
                add_action('added_post_meta', array($this, 'set_thumbnail_id'), 10, 4);
                add_action('updated_post_meta', array($this, 'set_thumbnail_id'), 10, 4);
                add_action('added_post_meta', array($this, 'set_product_image_gallery'), 10, 4);
                add_action('updated_post_meta', array($this, 'set_product_image_gallery'), 10, 4);
                
                add_filter('get_post_metadata', array($this, 'get_thumbnail_id'), 1, 4);
                add_filter('get_post_metadata', array($this, 'get_product_image_gallery'), 1, 4);
                add_filter('woocommerce_product_get_image_id', array($this, 'get_image_id_prop'), 500, 1);
                add_filter('berocket_apply_all_content_to_image', array($this, 'add_single_image_watermark'), 10, 3);
                add_action( 'wp_ajax_berocket_single_image', array ( $this, 'berocket_single_image' ) );
            }
        } else {
            add_filter( 'berocket_display_additional_notices', array(
                $this,
                'old_framework_notice'
            ) );
        }
    }
    function temp_php_extension_check($result_and, $check_and) {
        $result_and = extension_loaded( $check_and['data']['extension'] );
        if( ! $result_and ) {
            $this->check_lib->show_notice($result_and, $check_and['data']);
        }
        return $result_and;
    }
    function berocket_single_image() {
        if( ! check_ajax_referer('berocket_image_watermark_ajax', '_ajax_nonce', false) ) {
            wp_send_json_error(array('message' => __('Security check failed.', 'product-watermark-for-woocommerce')), 403);
        }
        if( ! current_user_can($this->option_page_capability()) ) {
            wp_send_json_error(array('message' => __('You are not allowed to manage watermarks.', 'product-watermark-for-woocommerce')), 403);
        }

        $id = isset($_POST['id']) && is_scalar($_POST['id']) ? absint($_POST['id']) : 0;
        $generation = isset($_POST['generation']) && is_string($_POST['generation'])
            ? sanitize_key(wp_unslash($_POST['generation']))
            : '';
        if(
            ! $id
            || ! in_array($generation, array('create', 'restore'), true)
            || 'attachment' !== get_post_type($id)
            || ! wp_attachment_is_image($id)
            || ! current_user_can('edit_post', $id)
        ) {
            wp_send_json_error(array('message' => __('Invalid image or operation.', 'product-watermark-for-woocommerce')), 400);
        }

        ob_start();
        $success = $this->add_watermark_to_images($id, $generation);
        $message = trim(wp_strip_all_tags(ob_get_clean()));
        if( ! $success ) {
            wp_send_json_error(array(
                'message' => $message ? $message : __('The image could not be processed.', 'product-watermark-for-woocommerce'),
            ), 500);
        }
        update_post_meta($id, 'br_watermark', 'restore' === $generation ? '1' : '2');

        wp_send_json_success(array(
            'id'         => $id,
            'generation' => $generation,
            'message'    => $message,
        ));
    }
    function init_validation() {
        return parent::init_validation() && $this->check_framework_version();
    }
    function check_framework_version() {
        return ( ! empty(BeRocket_Framework::$framework_version) && version_compare(BeRocket_Framework::$framework_version, 2.1, '>=') );
    }
    function old_framework_notice($notices) {
        $notices[] = array(
            'start'         => 0,
            'end'           => 0,
            'name'          => $this->info[ 'plugin_name' ].'_old_framework',
            'html'          => __('<strong>Please update all BeRocket plugins to the most recent version. Product Watermark for WooCommerce is not working correctly with older versions.</strong>', 'product-watermark-for-woocommerce'),
            'righthtml'     => '',
            'rightwidth'    => 0,
            'nothankswidth' => 0,
            'contentwidth'  => 1600,
            'subscribe'     => false,
            'priority'      => 10,
            'height'        => 50,
            'repeat'        => false,
            'repeatcount'   => 1,
            'image'         => array(
                'local'  => '',
                'width'  => 0,
                'height' => 0,
                'scale'  => 1,
            )
        );
        return $notices;
    }
    public function init () {
        parent::init();
        $options = $this->get_option();
        if( ! empty($options['enable_live']) ) {
            if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
                add_filter( 'image_downsize', array( $this, 'replace_image' ), 5, 3 );
            } else {
                add_filter( 'image_downsize', array( $this, 'replace_image' ), 200, 3 );
            }
        }
    }
    public function replace_image ($status, $post_id, $size) {
        $post_id = absint($post_id);
        if( ! $post_id || 'attachment' !== get_post_type($post_id) || ! wp_attachment_is_image($post_id) ) {
            return $status;
        }
        $post_ready = get_option('br_watermarked');
        if( ! isset( $post_ready ) || ! is_array( $post_ready ) ) {
            $post_ready = array();
        }
        $br_watermark = get_post_meta($post_id, 'br_watermark', true);
        if( in_array( $post_id, $post_ready ) ) {
            $br_watermark = '2';
            update_post_meta($post_id, 'br_watermark', $br_watermark);
        }
        if( $br_watermark == '1' ) {
            if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
                remove_filter( 'image_downsize', array( $this, 'replace_image' ), 5, 3 );
            } else {
                remove_filter( 'image_downsize', array( $this, 'replace_image' ), 200, 3 );
            }
            $watermark_applied = $this->add_watermark_to_images($post_id, 'create');
            update_post_meta($post_id, 'br_watermark', $watermark_applied ? '2' : '1');
            $options = $this->get_option();
            if( ! empty($options['enable_live']) ) {
                if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) {
                    add_filter( 'image_downsize', array( $this, 'replace_image' ), 5, 3 );
                } else {
                    add_filter( 'image_downsize', array( $this, 'replace_image' ), 200, 3 );
                }
            }
        }
        return $status;
    }
    public function convert_image_types($types = array()) {
        $types = array_merge(array(
            'shop_thumbnail'                => 'shop_single',
            'woocommerce_thumbnail'         => 'shop_single',
            'woocommerce_gallery_thumbnail' => 'shop_single',
            'thumbnail'                     => 'shop_single',
            'shop_catalog'                  => 'shop_single',
            'medium'                        => 'shop_single',
            'medium_large'                  => 'shop_single',
            'shop_catalog'                  => 'shop_single',
            'woocommerce_single'            => 'shop_single',
            'shop_single'                   => 'shop_single',
            'large'                         => 'shop_single',
            'full'                          => 'shop_single',
            'full_scaled'                   => 'shop_single'
        ), $types);
        
        return apply_filters('br_watermark_replace_types', $types);
    }
    public function add_watermark_to_images($post_id, $generation = 'create') {
        $post_id = absint($post_id);
        if(
            ! $post_id
            || ! in_array($generation, array('create', 'restore'), true)
            || 'attachment' !== get_post_type($post_id)
            || ! wp_attachment_is_image($post_id)
        ) {
            return false;
        }
        do_action('brwm_add_watermark_to_images_start', $post_id, $generation);
        $operation_success = false;
        $options = $this->get_option();
        $types = $this->convert_image_types();
		$image_data_class = new BeRocket_attachment_data($post_id);
		$image_data = $image_data_class->get_attachment_info();
		if( $image_data != FALSE ) {
            do_action('brwm_add_watermark_to_images_before_all', $post_id, $generation);
			$fullsize_filepath = $this->get_upload_file_path($image_data['relative_path']);
            if( ! $fullsize_filepath ) {
                return false;
            }
			$this->backup_image($fullsize_filepath, 'restore');
			$all_sizes = array(
				'full'	=> array(
					'fullpath' => $fullsize_filepath,
					'path' => $image_data['relative_path']
				)
            );
            if( ! empty($image_data['scaled']) ) {
                $scaled_filepath = $this->get_upload_file_path($image_data['scaled']);
                if( $scaled_filepath ) {
                    $all_sizes['full_scaled'] = array(
                        'fullpath' => $scaled_filepath,
                        'path' => $image_data['scaled']
                    );
                }
            }
            foreach($image_data['registered_sizes'] as $size) {
                if( ! empty($size['filename']) ) {
                    $size_relative_path = $image_data['folder'] . DIRECTORY_SEPARATOR . $size['filename'];
                    $size_filepath = $this->get_upload_file_path($size_relative_path);
                    if( ! $size_filepath ) {
                        continue;
                    }
                    $this->backup_image($size_filepath, 'restore');
                    if( $fullsize_filepath == $size_filepath && isset($types[$size['label']]) ) {
                        unset($types[$size['label']]);
                    }
                    $all_sizes[$size['label']] = array(
                        'fullpath' => $size_filepath,
                        'path' => $size_relative_path
                    );
                }
            }
            if( $generation == 'restore' ) {
                $image_name = explode('.', $image_data['file_name']);
                $image_dimension = array_pop($image_name);
                $image_name = implode('.', $image_name);
                $image_name = $image_name . '*_br_backup.' . $image_dimension;
                $image_path = dirname($fullsize_filepath) . DIRECTORY_SEPARATOR . $image_name;
                $files = glob($image_path);
                if( is_array($files) ) {
                    foreach($files as $backup_filepath) {
                        $filepath = str_replace('_br_backup.', '.', $backup_filepath);
                        if( file_exists($filepath) ) {
                            $this->backup_image($filepath, 'restore');
                        } else {
                            unlink($backup_filepath);
                        }
                    }
                }
                $operation_success = true;
            } else {
                try {
                    $types_applied = 0;
                    foreach( $types as $base_type => $type ) {
                        if( ! array_key_exists($base_type, $all_sizes) ) continue;
                        if( file_exists($all_sizes[$base_type]['fullpath']) ) {
                            $this->backup_image($all_sizes[$base_type]['fullpath'], $generation);
                            if( $generation == 'create' && 
                                isset( $options[$type] )
                            ) {
                                if( function_exists('set_time_limit')) {
                                    set_time_limit(60);
                                }
                                $watermark = $options[$type];
                                $watermark_applied = apply_filters('berocket_watermark_apply',
                                    false,
                                    $post_id,
                                    array(
                                        'all_sizes' => $all_sizes,
                                        'base_type' => $base_type,
                                        'watermark' => $watermark
                                    )
                                );
                                if( $watermark_applied ) {
                                    $types_applied++;
                                }
                            }
                        }
                    }
                    if( $types_applied == 0 ) {
                        echo '<span class="error">Attachment do not have sizes or watermark do not setuped('.$post_id.')</span>';
                        BeRocket_error_notices::add_plugin_error($this->info['id'], 'Attachment do not have sizes or watermark do not setuped', array(
                            'types'         => $types,
                            'image_data'    => $image_data,
                            'attachment_id' => $post_id
                        ));
                    } else {
                        $operation_success = true;
                    }
                } catch (Throwable $e) {
                    echo '<span class="error">', esc_html($e->getMessage()), '</span>';
                    BeRocket_error_notices::add_plugin_error($this->info['id'], 'Exception', array(
                        'error'         => $e->getMessage(),
                        'types'         => $types,
                        'image_data'    => $image_data,
                        'attachment_id' => $post_id
                    ));
                }
            }
            include_once( ABSPATH . 'wp-admin/includes/image.php' );
            $img_metadata = wp_get_attachment_metadata($post_id);
            if( $img_metadata != false ) {
                $attachment_metadata = wp_get_attachment_metadata($post_id);
                $metadata_source_path = isset($img_metadata['file']) && is_string($img_metadata['file'])
                    ? $this->get_upload_file_path($img_metadata['file'])
                    : false;
                if( $metadata_source_path ) {
                    add_filter('intermediate_image_sizes_advanced', array($this, 'intermediate_image_sizes_advanced'));
                    wp_generate_attachment_metadata($post_id, $metadata_source_path);
                    remove_filter('intermediate_image_sizes_advanced', array($this, 'intermediate_image_sizes_advanced'));
                    wp_update_attachment_metadata($post_id, $attachment_metadata);
                }
            }
            do_action('brwm_add_watermark_to_images_after_all', $post_id, $generation);
		} else {
            echo '<span class="error">Image data error('.$post_id.')</span>';
            BeRocket_error_notices::add_plugin_error($this->info['id'], 'Image data error', array(
                'image_data'       => $image_data,
                'image_data_class' => $image_data_class,
                'attachment_id'    => $post_id
            ));
        }
        do_action('brwm_add_watermark_to_images_end', $post_id, $generation);
        return $operation_success;
    }
    public function add_single_image_watermark($image_content, $watermark, $image_data) {
        $watermark = is_array($watermark) ? $watermark : array();
        $image_content = apply_filters('berocket_apply_content_to_image_image', 
            $image_content, 
            array(
                'image'         => br_get_value_from_array($watermark, array('image',0)),
                'width'         => br_get_value_from_array($watermark, array('width',0), 50),
                'height'        => br_get_value_from_array($watermark, array('height',0), 50),
                'left'          => br_get_value_from_array($watermark, array('left',0), 25),
                'top'           => br_get_value_from_array($watermark, array('top',0), 25),
                'ratio'         => br_get_value_from_array($watermark, array('ratio',0)),
                'image_data'    => $image_data
            )
        );
        return $image_content;
    }
    public function backup_image($image_path, $generation = 'create') {
        $file_name = basename($image_path);
        $path = str_replace( $file_name, '', $image_path );
        $pattern = '/(\.\w+?$)/i';
        $replacement = '_br_backup$1';
        $new_file_name = preg_replace( $pattern, $replacement, $file_name);
        $new_path = $path.$new_file_name;   
        if ( $generation == 'restore' && is_file( $new_path ) ) {
            rename( $new_path, $image_path );
        } elseif ( $generation == 'create' && is_file( $new_path ) ) {
            copy( $new_path, $image_path );
        } elseif ( $generation == 'create' && is_file( $image_path ) ) {
            copy( $image_path, $new_path );
        }
    }

    public function get_upload_file_path($relative_path) {
        if( ! is_string($relative_path) || '' === $relative_path ) {
            return false;
        }
        $upload_dir = wp_get_upload_dir();
        if( ! empty($upload_dir['error']) || empty($upload_dir['basedir']) ) {
            return false;
        }
        $relative_path = ltrim(wp_normalize_path($relative_path), '/');
        $base_path = realpath($upload_dir['basedir']);
        $file_path = realpath(trailingslashit($upload_dir['basedir']) . $relative_path);
        if( false === $base_path || false === $file_path || ! is_file($file_path) ) {
            return false;
        }

        $base_path = trailingslashit(wp_normalize_path($base_path));
        $file_path = wp_normalize_path($file_path);
        $path_is_inside_uploads = '\\' === DIRECTORY_SEPARATOR
            ? 0 === stripos($file_path, $base_path)
            : 0 === strpos($file_path, $base_path);

        return $path_is_inside_uploads ? $file_path : false;
    }

    public function get_watermark_file_path($image_url) {
        if( ! is_string($image_url) || '' === $image_url ) {
            return false;
        }
        $upload_dir = wp_get_upload_dir();
        if( ! empty($upload_dir['error']) || empty($upload_dir['baseurl']) ) {
            return false;
        }

        $image_url = esc_url_raw($image_url);
        $image_parts = wp_parse_url($image_url);
        $upload_parts = wp_parse_url($upload_dir['baseurl']);
        if(
            ! $image_url
            || ! is_array($image_parts)
            || ! is_array($upload_parts)
            || empty($image_parts['host'])
            || empty($upload_parts['host'])
            || 0 !== strcasecmp($image_parts['host'], $upload_parts['host'])
            || (isset($image_parts['port']) ? (int) $image_parts['port'] : null) !== (isset($upload_parts['port']) ? (int) $upload_parts['port'] : null)
        ) {
            return false;
        }

        $image_path = isset($image_parts['path']) ? rawurldecode($image_parts['path']) : '';
        $upload_path = isset($upload_parts['path']) ? rtrim(rawurldecode($upload_parts['path']), '/') : '';
        if( '' === $upload_path || 0 !== strpos($image_path, $upload_path . '/') ) {
            return false;
        }

        $relative_path = ltrim(substr($image_path, strlen($upload_path)), '/');
        return $this->get_upload_file_path($relative_path);
    }

    /** Read only local, existing Media Library images this user may manage. */
    public function watermark_image_details($image_url) {
        if (!current_user_can($this->option_page_capability())) {
            return false;
        }
        $path = $this->get_watermark_file_path($image_url);
        if (!$path || !is_readable($path)) {
            return false;
        }
        $attachment_id = attachment_url_to_postid($image_url);
        if (!$attachment_id || !wp_attachment_is_image($attachment_id)
            || !current_user_can('edit_post', $attachment_id)
            || realpath(get_attached_file($attachment_id)) !== realpath($path)) {
            return false;
        }
        // Native readers retain compatibility with the plugin's WordPress 5.0 minimum.
        $size = @getimagesize($path);
        $bytes = @filesize($path);
        if (!$size || empty($size[0]) || empty($size[1]) || empty($size['mime']) || false === $bytes) {
            return false;
        }
        return array(
            'width' => (int) $size[0],
            'height' => (int) $size[1],
            'mime' => $size['mime'],
            'bytes' => (int) $bytes,
            /* translators: 1: image width, 2: image height, 3: MIME type, 4: file size. */
            'label' => sprintf(__('%1$s × %2$s px · %3$s · %4$s', 'product-watermark-for-woocommerce'),
                number_format_i18n($size[0]), number_format_i18n($size[1]), $size['mime'], size_format($bytes, 1)),
        );
    }

    public function ajax_watermark_image_details() {
        if (!check_ajax_referer('berocket_watermark_image_details', '_ajax_nonce', false)
            || !current_user_can($this->option_page_capability())) {
            wp_send_json_error(array('message' => __('You are not allowed to view image details.', 'product-watermark-for-woocommerce')), 403);
        }
        $url = isset($_POST['url']) && is_string($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        $details = $this->watermark_image_details($url);
        if (!$details) {
            wp_send_json_error(array('message' => __('Image details unavailable.', 'product-watermark-for-woocommerce')), 404);
        }
        wp_send_json_success($details);
    }

    public function set_thumbnail_id($return, $object_id, $meta_key, $meta_value) {
        if( $meta_key == '_thumbnail_id' && in_array(get_post_type($object_id), array('product', 'product_variation'), true) ) {
            update_post_meta($object_id, '_thumbnail_id_watermarked', '1');
            $meta_value = array($meta_value);
            $this->set_image_array_for_watermark($object_id, $meta_key, $meta_value);
        }
    }

    public function set_product_image_gallery($return, $object_id, $meta_key, $meta_value) {
        if(
            $meta_key == '_product_image_gallery'
            && in_array(get_post_type($object_id), array('product', 'product_variation'), true)
        ) {
            update_post_meta($object_id, '_product_image_gallery_watermarked', '1');
            $meta_value = is_scalar($meta_value) ? explode(',', (string) $meta_value) : array();
            $this->set_image_array_for_watermark($object_id, $meta_key, $meta_value);
        }
    }

    public function get_thumbnail_id($return, $object_id, $meta_key, $single) {
        if( $meta_key == '_thumbnail_id' && in_array(get_post_type($object_id), array('product', 'product_variation'), true) ) {
            $watermarked = get_post_meta($object_id, '_thumbnail_id_watermarked', true);
            if( empty($watermarked) ) {
                update_post_meta($object_id, '_thumbnail_id_watermarked', '1');
                remove_filter('get_post_metadata', array($this, 'get_thumbnail_id'), 1, 4);
                $return = get_metadata('post', $object_id, $meta_key, $single);
                $meta_value = array($return);
                $this->set_image_array_for_watermark($object_id, $meta_key, $meta_value);
                add_filter('get_post_metadata', array($this, 'get_thumbnail_id'), 1, 4);
            }
        }
        return $return;
    }

    public function get_product_image_gallery($return, $object_id, $meta_key, $single) {
        if(
            $meta_key == '_product_image_gallery'
            && in_array(get_post_type($object_id), array('product', 'product_variation'), true)
        ) {
            $watermarked = get_post_meta($object_id, '_product_image_gallery_watermarked', true);
            if( empty($watermarked) ) {
                update_post_meta($object_id, '_product_image_gallery_watermarked', '1');
                remove_filter('get_post_metadata', array($this, 'get_product_image_gallery'), 1, 4);
                $return = get_metadata('post', $object_id, $meta_key, $single);
                if( is_array($return) ) {
                    $meta_value = array();
                    foreach($return as $return_val) {
                        if( is_scalar($return_val) ) {
                            $meta_value = array_merge($meta_value, explode(',', (string) $return_val));
                        }
                    }
                } else {
                    $meta_value = is_scalar($return) ? explode(',', (string) $return) : array();
                }
                $this->set_image_array_for_watermark($object_id, $meta_key, $meta_value);
                add_filter('get_post_metadata', array($this, 'get_product_image_gallery'), 1, 4);
            }
        }
        return $return;
    }

    public function get_image_id_prop($return) {
        global $post;
        if( ! empty($post) && in_array(get_post_type($post), array('product', 'product_variation'), true) ) {
            $watermarked = get_post_meta($post->ID, '_thumbnail_id_watermarked', true);
            if( empty($watermarked) ) {
                update_post_meta($post->ID, '_thumbnail_id_watermarked', '1');
                remove_filter('woocommerce_product_get_image_id', array($this, 'get_image_id_prop'), 500, 1);
                $meta_value = array($return);
                $this->set_image_array_for_watermark($post->ID, '', $meta_value);
                add_filter('woocommerce_product_get_image_id', array($this, 'get_image_id_prop'), 500, 1);
            }
        }
        return $return;
    }

    public function set_image_array_for_watermark($object_id, $meta_key, $meta_value) {
        $post_type = get_post_type($object_id);
        if( $post_type == 'product' || $post_type == 'product_variation' ) {
            if(is_array($meta_value) && count($meta_value) ) {
                foreach($meta_value as $attachment_id) {
                    $attachment_id = is_scalar($attachment_id) ? absint($attachment_id) : 0;
                    if(
                        $attachment_id
                        && 'attachment' === get_post_type($attachment_id)
                        && wp_attachment_is_image($attachment_id)
                    ) {
                        $br_watermark = get_post_meta($attachment_id, 'br_watermark', true);
                        if( empty($br_watermark) ) {
                            update_post_meta($attachment_id, 'br_watermark', '1');
                        }
                    }
                }
            }
        }
    }
    public function intermediate_image_sizes_advanced($sizes) {
        $types = $this->convert_image_types();
        foreach( $types as $type => $base_type ) {
            if( isset($sizes[$type]) ) {
                unset($sizes[$type]);
            }
        }
        return $sizes;
    }
    public function set_styles () {}
    public function admin_init() {
        parent::admin_init();
        wp_enqueue_script( 'berocket_image_watermark_admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), BeRocket_image_watermark_version );
        wp_localize_script(
            'berocket_image_watermark_admin',
            'berocketImageWatermark',
            array(
                'nonce' => wp_create_nonce('berocket_image_watermark_ajax'),
                'detailsNonce' => wp_create_nonce('berocket_watermark_image_details'),
                'detailsLoading' => __('Loading image details…', 'product-watermark-for-woocommerce'),
                'detailsUnavailable' => __('Image details unavailable.', 'product-watermark-for-woocommerce'),
            )
        );
        wp_register_style( 'berocket_image_watermark_admin_style', plugins_url( 'css/admin.css', __FILE__ ), "", BeRocket_image_watermark_version );
        wp_enqueue_style( 'berocket_image_watermark_admin_style' );
        if( ! empty($_GET['page']) && $_GET['page'] == $this->values[ 'option_page' ] ) {
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-draggable' );
            wp_enqueue_script( 'jquery-ui-resizable' );
            wp_register_style( 'jquery-ui-smoothness', plugins_url( 'css/jquery-ui.min.css', __FILE__ ), "", BeRocket_image_watermark_version );
            wp_enqueue_style('jquery-ui-smoothness');
        }
    }
    public function admin_settings( $tabs_info = array(), $data = array() ) {
        $image_lib = array(
            array('value' => '',        'text' => __('GD Library', 'product-watermark-for-woocommerce')),
        );
        if( class_exists('Imagick') ) {
            $image_lib[] = array('value' => 'imagick', 'text' => __('Image Magick (Imagick)', 'product-watermark-for-woocommerce'));
        }
        parent::admin_settings(
            array(
                'General' => array(
                    'icon' => 'cog',
                    'name' => __( 'General', "BeRocket_AJAX_domain" )
                ),
                'Advanced' => array(
                    'icon' => 'cogs',
                    'name' => __( 'Advanced', "BeRocket_AJAX_domain" )
                ),
                'Addons' => array(
                    'icon' => 'plus',
                    'name' => __( 'Addons', "BeRocket_AJAX_domain" )
                ),
                'License' => array(
                    'icon' => 'unlock-alt',
                    'name' => __( 'License', "BeRocket_AJAX_domain" ),
                    'link' => admin_url( 'admin.php?page=berocket_account' )
                ),
            ),
            array(
            'General' => array(
                'jpeg_quantity' => array(
                    "label"     => __('JPEG image quality', 'product-watermark-for-woocommerce'),
                    "type"      => "number",
                    "name"      => "jpeg_quantity",
                    "extra"     => "min='1' max='100'",
                    "value"     => '',
                ),
                'max_img_height' => array(
                    "label"     => __('Maximum image height', 'product-watermark-for-woocommerce'),
                    "type"      => "number",
                    "name"      => "max_img_height",
                    "extra"     => "min='1'",
                    "value"     => '',
                ),
                'max_img_width' => array(
                    "label"     => __('Maximum image width', 'product-watermark-for-woocommerce'),
                    "type"      => "number",
                    "name"      => "max_img_width",
                    "extra"     => "min='1'",
                    "value"     => '',
                ),
                'php_memory_limit' => array(
                    "label"     => __('PHP memory limit on your server', 'product-watermark-for-woocommerce'),
                    "type"      => "text",
                    "name"      => "php_memory_limit",
                    "value"     => '',
                ),
                'watermarks_shop_single_0' => array(
                    "label"     => "",
                    "section"   => 'watermarks',
                    'name_start'=> 'shop_single',
                    'name_end'  => 0,
                    'tr_class'  => 'br_wm_img_shop_single berocket_image_count_0'
                ),
                'generate' => array(
                    "label"     => "",
                    "section"   => 'generate'
                ),
            ),
            'Addons' => array(
                'addons' => array(
                    "label"     => "",
                    "section"   => 'addons'
                ),
            ),
            'Advanced' => array(
                'fix_gd' => array(
                    "label"     => __('Transparency fix', 'product-watermark-for-woocommerce'),
                    "label_for" => __('Provide less quality of image, but fixes some glitches with transparency', 'product-watermark-for-woocommerce'),
                    "type"      => "checkbox",
                    "name"      => "fix_gd",
                    "value"     => '1',
                ),
                'image_lib' => array(
                    "label"    => __( 'Image Processing', "product-watermark-for-woocommerce" ),
                    "label_for"=> __( 'Image processing library that will be used to add watermarks. Library will be diplayed only if installed in PHP', "product-watermark-for-woocommerce" )
                    .'<br>'.__( 'Plugin support: GD Library, Image Magick (Imagick)', "product-watermark-for-woocommerce" )
                    .(class_exists('Imagick') ? '' : '<br><strong style="color:red;">'.__('Server do not have Image Magick library', 'product-watermark-for-woocommerce').'</strong>'),
                    "name"     => "image_lib",
                    "type"     => "selectbox",
                    "options"  => $image_lib,
                    "value"    => '',
                ),
            ),
        ) );
    }
    public function section_watermarks($data, $options) {
        $defaults = $this->defaults;
        $water_name = $data['name_start'];
        $i = $data['name_end'];
        ob_start();
        $height = max(15, intval(br_get_value_from_array($options, array($water_name, 'height', $i), 50)));
        $height = intval($height/5)*5;
        $width = max(15, intval(br_get_value_from_array($options, array($water_name, 'width', $i), 50)));
        $width = intval($width/5)*5;
        $top = min(100-$height, max(0, intval(br_get_value_from_array($options, array($water_name, 'top', $i)))));
        $left = min(100-$width, max(0, intval(br_get_value_from_array($options, array($water_name, 'left', $i)))));
        $image_url = br_get_value_from_array($options, array($water_name, 'image', $i));
        $image_details = $image_url ? $this->watermark_image_details($image_url) : false;
        $small_logo = $image_details && $image_details['width'] < 64;
        echo '<td colspan="2">';
        ?>
        <div class="br-watermark-preview<?php echo $small_logo ? ' br-watermark-small-logo' : ''; ?>" data-background="white">
            <?php echo br_upload_image('br-image_watermark-options['.$water_name.'][image]['.$i.']', $image_url); ?>
            <p class="br-watermark-image-details description" role="status" aria-live="polite"<?php echo $image_url ? '' : ' hidden'; ?>><?php
                echo esc_html($image_details ? $image_details['label'] : ($image_url ? __('Image details unavailable.', 'product-watermark-for-woocommerce') : ''));
            ?></p>
            <p class="br-watermark-logo-warning" role="status"<?php echo $small_logo ? '' : ' hidden'; ?>>
                <?php esc_html_e('This logo is less than 64 pixels wide.', 'product-watermark-for-woocommerce'); ?>
            </p>
            <p class="br-watermark-preview-controls" hidden>
                <label>
                    <?php esc_html_e('Preview background', 'product-watermark-for-woocommerce'); ?>
                    <select class="br-watermark-preview-background">
                        <option value="white"><?php esc_html_e('White', 'product-watermark-for-woocommerce'); ?></option>
                        <option value="dark"><?php esc_html_e('Dark', 'product-watermark-for-woocommerce'); ?></option>
                        <option value="checkerboard"><?php esc_html_e('Checkerboard', 'product-watermark-for-woocommerce'); ?></option>
                    </select>
                </label>
                <span class="description"><?php esc_html_e('Preview only. Does not change your images.', 'product-watermark-for-woocommerce'); ?></span>
            </p>
            <p>
                <label><input type="checkbox" class="br-watermark-ratio" name="br-image_watermark-options[<?php echo $water_name; ?>][ratio][<?php echo $i; ?>]" value="1"<?php if( ! empty($options[$water_name]['ratio'][$i]) ) echo ' checked'; ?>><?php _e('Save aspect ratio', 'product-watermark-for-woocommerce');?></label>
            </p>
            <table>
                <tr>
                    <td>
                        <div class="br_watermark_parent">
                            <div class="br_watermark" data-id="<?php echo $water_name.'_'.$i; ?>" style="<?php echo 'width:', ($width * 2), 'px;height:', ($height * 2), 'px;top:', ($top * 2), 'px;left:', ($left * 2), 'px;'; ?>">
                            </div>
                        </div>
                    </td>
                    <td>
                        <p>
                            <label>Top: <span class="<?php echo $water_name.'_'.$i; ?>_top"><?php echo $top; ?></span> %</label>
                            <input class="<?php echo $water_name.'_'.$i; ?>_top_input" type="hidden" name="br-image_watermark-options[<?php echo $water_name; ?>][top][<?php echo $i; ?>]" value="<?php echo $top; ?>">
                        </p>
                        <p>
                            <label>Left: <span class="<?php echo $water_name.'_'.$i; ?>_left"><?php echo $left; ?></span> %</label>
                            <input class="<?php echo $water_name.'_'.$i; ?>_left_input" type="hidden" name="br-image_watermark-options[<?php echo $water_name; ?>][left][<?php echo $i; ?>]" value="<?php echo $left; ?>">
                        </p>
                        <p>
                            <label>Height: <span class="<?php echo $water_name.'_'.$i; ?>_height"><?php echo $height; ?></span> %</label>
                            <input class="<?php echo $water_name.'_'.$i; ?>_height_input" type="hidden" name="br-image_watermark-options[<?php echo $water_name; ?>][height][<?php echo $i; ?>]" value="<?php echo $height; ?>">
                        </p>
                        <p>
                            <label>Width: <span class="<?php echo $water_name.'_'.$i; ?>_width"><?php echo $width; ?></span> %</label>
                            <input class="<?php echo $water_name.'_'.$i; ?>_width_input" type="hidden" name="br-image_watermark-options[<?php echo $water_name; ?>][width][<?php echo $i; ?>]" value="<?php echo $width; ?>">
                        </p>
                    </td>
                </tr>
            </table>
            <div class="br-watermark-preview-controls br-watermark-comparison" data-mode="after" hidden>
                <div class="br-watermark-button-group" role="group" aria-label="<?php esc_attr_e('Watermark preview', 'product-watermark-for-woocommerce'); ?>">
                    <button type="button" class="button not_berocket_button" data-mode="before" aria-pressed="false"><?php esc_html_e('Before', 'product-watermark-for-woocommerce'); ?></button>
                    <button type="button" class="button button-primary not_berocket_button" data-mode="after" aria-pressed="true"><?php esc_html_e('After', 'product-watermark-for-woocommerce'); ?></button>
                </div>
                <div class="br-watermark-sample">
                    <img src="<?php echo esc_url(plugins_url('images/watermark-sample.jpg', __FILE__)); ?>" width="400" height="400" loading="lazy" alt="<?php esc_attr_e('Sample product photo', 'product-watermark-for-woocommerce'); ?>">
                    <img class="br-watermark-comparison-logo" alt="" hidden>
                </div>
            </div>
        </div>
        <?php
        echo '</td>';
        return ob_get_clean();
    }
    public function section_generate($data, $options) {
        ob_start();
        echo '<td colspan="2">';
        include image_watermark_TEMPLATE_PATH . 'generate.php';
        echo '</td>';
        return ob_get_clean();
    }
    public function sanitize_option( $input ) {
        $input = is_array($input) ? $input : array();
        if( empty($input['php_memory_limit']) ) {
            $input['php_memory_limit'] = (function_exists('ini_get') ? ini_get('memory_limit') : '');
        }
        $input = parent::sanitize_option( $input );
        $input['enable_live'] = empty($input['enable_live']) ? '0' : '1';
        $input['fix_gd'] = empty($input['fix_gd']) ? '0' : '1';
        $input['jpeg_quantity'] = isset($input['jpeg_quantity']) && is_scalar($input['jpeg_quantity'])
            ? min(100, max(1, absint($input['jpeg_quantity'])))
            : 90;
        foreach(array('max_img_width', 'max_img_height') as $dimension) {
            $input[$dimension] = isset($input[$dimension]) && is_scalar($input[$dimension])
                ? min(10000, max(1, absint($input[$dimension])))
                : 2500;
        }
        $input['php_memory_limit'] = isset($input['php_memory_limit']) && is_scalar($input['php_memory_limit'])
            ? sanitize_text_field($input['php_memory_limit'])
            : '';
        $input['image_lib'] = isset($input['image_lib']) && 'imagick' === $input['image_lib'] && class_exists('Imagick')
            ? 'imagick'
            : '';

        foreach(array('shop_thumbnail', 'shop_single', 'shop_catalog') as $watermark_name) {
            $watermark = isset($input[$watermark_name]) && is_array($input[$watermark_name])
                ? $input[$watermark_name]
                : array();
            $watermark['text'] = isset($watermark['text']) && is_scalar($watermark['text'])
                ? sanitize_text_field($watermark['text'])
                : '';
            $watermark['text_repeat'] = empty($watermark['text_repeat']) ? '0' : '1';
            $watermark['text_alpha'] = isset($watermark['text_alpha']) && is_scalar($watermark['text_alpha'])
                ? min(100, max(0, absint($watermark['text_alpha'])))
                : 30;
            $watermark['text_angle'] = isset($watermark['text_angle']) && is_scalar($watermark['text_angle'])
                ? min(360, max(0, (float) $watermark['text_angle']))
                : 0;
            $watermark['font_size'] = isset($watermark['font_size']) && is_scalar($watermark['font_size'])
                ? min(72, max(8, absint($watermark['font_size'])))
                : 20;
            $font_color = isset($watermark['font_color']) && is_scalar($watermark['font_color'])
                ? sanitize_hex_color((string) $watermark['font_color'])
                : '';
            $watermark['font_color'] = $font_color ? $font_color : '#FFFFFF';
            $watermark['image_count'] = isset($watermark['image_count']) && is_scalar($watermark['image_count'])
                ? min(4, absint($watermark['image_count']))
                : 0;

            foreach(array('image', 'ratio', 'top', 'left', 'width', 'height') as $array_key) {
                $watermark[$array_key] = isset($watermark[$array_key]) && is_array($watermark[$array_key])
                    ? $watermark[$array_key]
                    : array();
            }
            for($i = 0; $i < 5; $i++) {
                $image_url = isset($watermark['image'][$i]) && is_scalar($watermark['image'][$i])
                    ? esc_url_raw($watermark['image'][$i])
                    : '';
                $watermark['image'][$i] = $image_url && $this->get_watermark_file_path($image_url)
                    ? $image_url
                    : '';
                $watermark['ratio'][$i] = empty($watermark['ratio'][$i]) ? '0' : '1';
                $watermark['width'][$i] = isset($watermark['width'][$i]) && is_scalar($watermark['width'][$i])
                    ? min(100, max(1, absint($watermark['width'][$i])))
                    : 50;
                $watermark['height'][$i] = isset($watermark['height'][$i]) && is_scalar($watermark['height'][$i])
                    ? min(100, max(1, absint($watermark['height'][$i])))
                    : 50;
                $watermark['left'][$i] = isset($watermark['left'][$i]) && is_scalar($watermark['left'][$i])
                    ? min(100 - $watermark['width'][$i], max(0, absint($watermark['left'][$i])))
                    : 25;
                $watermark['top'][$i] = isset($watermark['top'][$i]) && is_scalar($watermark['top'][$i])
                    ? min(100 - $watermark['height'][$i], max(0, absint($watermark['top'][$i])))
                    : 25;
            }
            $input[$watermark_name] = $watermark;
        }
        if( !empty($input['addons']) && is_array($input['addons']) ) {
            $input['addons'] = array_values(array_intersect(
                array_filter($input['addons'], 'is_string'),
                array('/media_buttons/media_buttons.php')
            ));
        } else {
            $input['addons'] = array();
        }
        return $input;
    }
}

new BeRocket_image_watermark;
