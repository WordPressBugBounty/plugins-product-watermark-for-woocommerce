<?php
class berocket_watermark_imagick {
    public static $main = array();
    public function __construct() {
        self::$main = BeRocket_image_watermark::getInstance();
        add_filter('berocket_watermark_apply', array(__CLASS__, 'apply_image'), 10, 3);
        $elements = array(
            'text',
            'image'
        );
        foreach($elements as $element) {
            add_filter('berocket_apply_content_to_image_'.$element, array($this, $element), 10, 2);
        }
    }
    public static function apply_image($applied, $post_id, $additional = array()) {
        extract($additional);
        $get_prepared_image = self::get_prepared_image($all_sizes[$base_type]['fullpath']);
        if( $get_prepared_image === FALSE ) {
            echo '<span class="error">Incorrect MIME type(' . esc_html($all_sizes[$base_type]['path']) . ')</span>';
            BeRocket_error_notices::add_plugin_error(self::$main->info['id'], 'Incorrect MIME type', array(
                'type'          => $base_type,
                'path'          => $all_sizes[$base_type]['path'],
                'attachment_id' => $post_id
            ));
            return false;
        }
        list($mime_type, $image_content, $image_width, $image_height) = $get_prepared_image;
        $image_content = apply_filters('berocket_apply_all_content_to_image', 
            $image_content,
            $watermark,
            array(
                'image_width'  => $image_width,
                'image_height' => $image_height,
            )
        );
        self::save_ready_image($image_content, $mime_type, $all_sizes[$base_type]['path']);
        return true;
    }
    public static function get_prepared_image($path) {
        $allowed_mime_types = array(
            'image/jpeg' => 'jpeg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif',
        );
        $image_size = @getimagesize($path);
        $detected_mime = wp_get_image_mime($path);
        if(
            ! is_array($image_size)
            || empty($image_size[0])
            || empty($image_size[1])
            || ! isset($allowed_mime_types[$detected_mime])
        ) {
            return FALSE;
        }
        $image_width = (int) $image_size[0];
        $image_height = (int) $image_size[1];
        $options = self::$main->get_option();
        $max_img_width = max(1, intval($options['max_img_width']));
        $max_img_height = max(1, intval($options['max_img_height']));
        $memory_needed = $image_width * $image_height * 10;
        $memory_left = berocket_get_memory_data($memory_needed, br_get_value_from_array($options, 'php_memory_limit'));
        if(
            $image_width > $max_img_width
            || $image_height > $max_img_height
            || $memory_left['memory_check'] <= 0
        ) {
            return FALSE;
        }
        try {
            $images = new Imagick($path);
            if( $images->count() != 1 ) {
                return FALSE;
            }
            $image_width = $images->getImageWidth();
            $image_height = $images->getImageHeight();
            $mime_type = $allowed_mime_types[$detected_mime];
            return array($mime_type, $images, $image_width, $image_height);
        } catch (ImagickException $e) {
            return FALSE;
        }
    }
    public static function save_ready_image($image_content, $mime_type, $path, $destroy = true) {
        $upload_dir = wp_upload_dir();
        $image_content->writeImage($upload_dir['basedir'].'/'.$path);
    }
    function text($image_content, $args = array()) {
        $args = array_merge(array(
            'text'          => '',
            'text_alpha'    => 0,
            'font_color'    => '#000000',
            'text_angle'    => '',
            'font_size'     => '',
            'text_repeat'   => false,
            'image_data'    => array()
        ), $args);
        $args['text'] = isset($args['text']) && is_scalar($args['text']) ? (string) $args['text'] : '';
        $args['text_alpha'] = isset($args['text_alpha']) && is_scalar($args['text_alpha']) ? $args['text_alpha'] : 0;
        $args['text_angle'] = isset($args['text_angle']) && is_scalar($args['text_angle']) ? $args['text_angle'] : 0;
        $args['font_size'] = isset($args['font_size']) && is_scalar($args['font_size']) ? $args['font_size'] : 20;
        $font_color = isset($args['font_color']) && is_scalar($args['font_color'])
            ? sanitize_hex_color((string) $args['font_color'])
            : '';
        $args['font_color'] = $font_color ? $font_color : '#000000';
        $args['image_data'] = is_array($args['image_data']) ? $args['image_data'] : array();
        extract($args['image_data']);
        if( strlen($args['text']) ) {
            $text       = $args['text'];
            $font       = plugin_dir_path( BeRocket_image_watermark_file ).'fonts/arial.ttf';
            $alpha      = (100 - min(99, max(0, intval($args['text_alpha'])))) / 100;
            $text_angle = min(360, max(0, floatval($args['text_angle'])));
            $font_size  = min(72, max(8, intval($args['font_size'])));
            $font_color = $args['font_color'];
            if( strlen($font_color) == 7 ) {
                $font_color = sscanf($font_color, "#%02x%02x%02x");
            } else {
                $font_color = array(0, 0, 0);
            }
            $draw = new ImagickDraw();
            $draw->setFont($font);
            $imageColor = new ImagickPixel('rgba('.$font_color[0].','.$font_color[1].','.$font_color[2].','.$alpha.')');
            $draw->setFillColor($imageColor);
            $draw->setFillOpacity($alpha);
            $draw->setFontSize($font_size);
            $draw->rotate($text_angle);
            $font_metric = $image_content->queryFontMetrics($draw, $text);
            $textWidth = $font_metric['textWidth'];
            $textHeight = $font_metric['textHeight'];
            $text_angle_rad = deg2rad($text_angle);
            $textHeightNew = abs($textWidth * sin($text_angle_rad)) + abs($textHeight * cos($text_angle_rad));
            $textWidthNew = abs($textWidth * cos($text_angle_rad)) + abs($textHeight * sin($text_angle_rad));
            $temp_image = new Imagick();
            $temp_image->newImage($image_width, $image_height, "none", 'png');
            if( ! empty($args['text_repeat']) ) {
                $text_x_start = -$textWidthNew;
                $text_x_end = $image_width + $textWidthNew;
                $text_y_end = $image_height + $textHeightNew;
                while($text_x_start < $text_x_end) {
                    $text_y_start = -$textHeightNew;
                    while($text_y_start < $text_y_end) {
                        $temp_image->annotateImage($draw, $text_x_start, $text_y_start, 0 - $text_angle, $text);
                        $text_y_start += $textHeightNew + 20;
                    }
                    $text_x_start += $textWidthNew + 20;
                }
            } else {
                $text_x = $image_width / 2 - $textWidthNew / 2;
                $text_y = $image_height / 2 - $textHeightNew / 2;
                $temp_image->annotateImage($draw, $text_x, $text_y, $text_angle, $text);
            }
            if ($temp_image->getImageColorspace() != $image_content->getImageColorspace()) {
                $temp_image->transformimagecolorspace($image_content->getImageColorspace());
            }
            $image_content->compositeImage($temp_image, Imagick::COMPOSITE_OVER, 0, 0);
            $temp_image->destroy();
            $imageColor->destroy();
            $draw->destroy();
        }
        return $image_content;
    }
    function image($image_content, $args = array()) {
        $args = array_merge(array(
            'image'         => '',
            'width'         => '',
            'height'        => '',
            'left'          => '',
            'top'           => '',
            'ratio'         => '',
            'image_data'    => array()
        ), $args);
        $args['image'] = isset($args['image']) && is_string($args['image']) ? $args['image'] : '';
        $args['width'] = isset($args['width']) && is_scalar($args['width'])
            ? min(100, max(1, (float) $args['width']))
            : 50;
        $args['height'] = isset($args['height']) && is_scalar($args['height'])
            ? min(100, max(1, (float) $args['height']))
            : 50;
        $args['left'] = isset($args['left']) && is_scalar($args['left'])
            ? min(100 - $args['width'], max(0, (float) $args['left']))
            : 25;
        $args['top'] = isset($args['top']) && is_scalar($args['top'])
            ? min(100 - $args['height'], max(0, (float) $args['top']))
            : 25;
        $args['ratio'] = ! empty($args['ratio']);
        $args['image_data'] = is_array($args['image_data']) ? $args['image_data'] : array();
        extract($args['image_data']);
        if( ! empty($args['image']) ) {
            $watermark_image = self::$main->get_watermark_file_path($args['image']);
            if( ! $watermark_image ) {
                return $image_content;
            }
            $get_prepared_watermark = self::get_prepared_image($watermark_image);
            if( $get_prepared_watermark === FALSE ) {
                echo '<span class="error">' . esc_html__('Incorrect watermark image.', 'product-watermark-for-woocommerce') . '</span>';
                BeRocket_error_notices::add_plugin_error(self::$main->info['id'], 'Incorrect MIME type', array(
                    'path' => wp_basename($watermark_image),
                ));
                return $image_content;
            }
            list($watermark_type, $watermark_content, $watermark_width, $watermark_height) = $get_prepared_watermark;
            $ratio_w = $watermark_width / ($image_width / 100 * $args['width']);
            $ratio_h = $watermark_height / ($image_height / 100 * $args['height']);
            $ratio = max( $ratio_w, $ratio_h );
            if ( $args['ratio'] ) {
                $weight_dif = $watermark_width / $ratio_w - $watermark_width / $ratio;
                $height_dif = $watermark_height / $ratio_h - $watermark_height / $ratio;
                if( $args['width'] < 100 && $args['height'] < 100) {
                    $weight_dif = $weight_dif * ( 1 - ( ( 100 - $args['width'] ) - $args['left'] ) / ( 100 - $args['width'] ) );
                    $height_dif = $height_dif * ( 1 - ( ( 100 - $args['height'] ) - $args['top'] ) / ( 100 - $args['height'] ) );
                } else {
                    $weight_dif = $weight_dif / 2;
                    $height_dif = $height_dif / 2;
                }
                $width = $watermark_width / $ratio;
                $height = $watermark_height / $ratio;
            } else {
                $weight_dif = 0;
                $height_dif = 0;
                $width = $watermark_width / $ratio_w;
                $height = $watermark_height / $ratio_h;
            }
            $top = $image_height / 100 * $args['top'] + $height_dif;
            $left = $image_width / 100 * $args['left'] + $weight_dif;
            $watermark_content->scaleImage($width, $height);
            if ($watermark_content->getImageColorspace() != $image_content->getImageColorspace()) {
                $watermark_content->transformimagecolorspace($image_content->getImageColorspace());
            }
            $image_content->compositeImage($watermark_content, Imagick::COMPOSITE_OVER, $left, $top);
            $watermark_content->destroy();
        }
        return $image_content;
    }
}
new berocket_watermark_imagick();
