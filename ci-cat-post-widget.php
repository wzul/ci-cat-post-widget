<?php

/**
 * Plugin Name: CSS Igniter Latest Category Post Widget
 * Plugin URI: https://github.com/wzul/ci-cat-post-widget
 * Description: Enable display of category post in widget
 * Author: Wanzul Hosting Enterprise
 * Author URI: http://www.wanzul-hosting.com/
 * Version: 1.0
 * License: GPLv3
 * Domain Path: /languages/
 */
class CI_Category_Posts extends WP_Widget
{

    function __construct()
    {
        $widget_ops = array('description' => __('Displays a number of the latest (or random) category posts from a specific post type.', 'ci_theme'));
        $control_ops = array();
        parent::__construct('ci-latest-category-posts', __('-= CI Latest Category Posts =-', 'ci_theme'), $widget_ops, $control_ops);

        add_action('admin_enqueue_scripts', array(&$this, '_enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array(&$this, 'enqueue_custom_css'));
    }

    function widget($args, $instance)
    {
        extract($args);
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
        $post_type = $instance['post_type'];
        $category_name = $instance['cat'];
        $random = $instance['random'];
        $count = $instance['count'];
        $item_layout = $instance['item_layout'];

        global $columns;
        $columns = $instance['columns'];

        $background_color = $instance['background_color'];
        $background_image = $instance['background_image'];
        $parallax = $instance['parallax'] == 1 ? 'parallax' : '';
        $parallax_speed = $instance['parallax'] == 1 ? sprintf('data-speed="%s"', esc_attr($instance['parallax_speed'])) : '';

        if (!empty($background_color) || !empty($background_image)) {
            preg_match('/class=(["\']).*?widget.*?\1/', $before_widget, $match);
            if (!empty($match)) {
                $attr_class = preg_replace('/\bwidget\b/', 'widget widget-padded', $match[0], 1);
                $before_widget = str_replace($match[0], $attr_class, $before_widget);
            }
        }


        if (0 == $count) {
            return;
        }

        $item_classes = ci_theme_get_columns_classes($columns);


        echo $before_widget;

        $args = array(
            'post_type' => $post_type,
            'orderby' => 'date',
            'order' => 'DESC',
            'posts_per_page' => $count,
            'category_name' => $category_name //Put the category slug here
        );

        if (1 == $random) {
            $args['orderby'] = 'rand';
            unset($args['order']);
        }

        $q = new WP_Query($args);

        ?>
        <div class="widget-wrap <?php echo esc_attr($parallax); ?>" <?php echo $parallax_speed; ?>>

            <?php if (in_array($id, ci_theme_get_fullwidth_sidebars())): ?>
                <div class="container">
                    <div class="row">
                        <div class="col-xs-12">
                        <?php endif; ?>

                        <?php
                        if (!empty($title)) {
                            echo $before_title . $title . $after_title;
                        }

                        ?>

                        <div class="item-list">
                            <div class="row">
                                <?php
                                while ($q->have_posts()) {
                                    $q->the_post();

                                    ?>
                                    <div class="<?php echo $item_classes; ?>">
                                        <?php
                                        if ('horizontal' == $item_layout) {
                                            get_template_part('item-horizontal', get_post_type());
                                        } else {
                                            get_template_part('item', get_post_type());
                                        }

                                        ?>
                                    </div>
                                    <?php
                                }
                                wp_reset_postdata();

                                ?>
                            </div>
                        </div>

                        <?php if (in_array($id, ci_theme_get_fullwidth_sidebars())): ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <?php
        wp_reset_postdata();

        echo $after_widget;
    }

// widget

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        $instance['title'] = sanitize_text_field($new_instance['title']);
        $instance['post_type'] = sanitize_key($new_instance['post_type']);
        $instance['cat'] = sanitize_key($new_instance['cat']);
        $instance['random'] = ci_theme_sanitize_checkbox($new_instance['random']);
        $instance['count'] = intval($new_instance['count']);
        $instance['columns'] = intval($new_instance['columns']);
        $instance['item_layout'] = in_array($new_instance['item_layout'], array('vertical', 'horizontal')) ? $new_instance['item_layout'] : 'vertical';

        $instance['color'] = ci_sanitize_hex_color($new_instance['color']);
        $instance['background_color'] = ci_sanitize_hex_color($new_instance['background_color']);
        $instance['background_image'] = esc_url_raw($new_instance['background_image']);
        $instance['background_image_id'] = intval($new_instance['background_image_id']);
        $instance['background_repeat'] = in_array($new_instance['background_repeat'], array('repeat', 'no-repeat', 'repeat-x', 'repeat-y')) ? $new_instance['background_repeat'] : 'repeat';
        $instance['parallax'] = ci_theme_sanitize_checkbox($new_instance['parallax']);
        $instance['parallax_speed'] = round(floatval($new_instance['parallax_speed']), 1);

        return $instance;
    }

// save

    function form($instance)
    {
        $instance = wp_parse_args((array) $instance, array(
            'title' => '',
            'post_type' => 'post',
            'cat' => 'cat',
            'random' => '',
            'count' => 2,
            'columns' => 2,
            'item_layout' => 'vertical',
            'color' => '',
            'background_color' => '',
            'background_image' => '',
            'background_image_id' => '',
            'background_repeat' => 'repeat',
            'parallax' => '',
            'parallax_speed' => 0.3,
        ));

        $title = $instance['title'];
        $post_type = $instance['post_type'];
        $category_name = $instance['cat'];
        $random = $instance['random'];
        $count = $instance['count'];
        $columns = $instance['columns'];
        $item_layout = $instance['item_layout'];

        $color = $instance['color'];
        $background_color = $instance['background_color'];
        $background_image = $instance['background_image'];
        $background_image_id = $instance['background_image_id'];
        $background_repeat = $instance['background_repeat'];
        $parallax = $instance['parallax'];
        $parallax_speed = $instance['parallax_speed'];

        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'ci_theme'); ?></label><input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" class="widefat"/></p>
        <?php
        $types = get_post_types($args = array(
            'public' => true
            ), 'objects');
        unset($types['attachment']);

        ?>
        <p><label for="<?php echo $this->get_field_id('post_type'); ?>"><?php _e('Select a post type to display the latest post from', 'ci_theme'); ?></label>
            <select id="<?php echo $this->get_field_id('post_type'); ?>" name="<?php echo $this->get_field_name('post_type'); ?>">
                <?php foreach ($types as $key => $type): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($post_type, $key); ?>>
                        <?php echo $type->labels->name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label>
                <?php _e('Category', 'category-posts'); ?>:
            </label>
            <?php wp_dropdown_categories(array('show_option_all' => __('All categories', 'category-posts'), 'hide_empty' => 0, 'name' => $this->get_field_name('cat'), 'selected' => $category_name, 'class' => 'categoryposts-data-panel-filter-cat')); ?>

        </p>

        <p><label for="<?php echo $this->get_field_id('random'); ?>"><input type="checkbox" name="<?php echo $this->get_field_name('random'); ?>" id="<?php echo $this->get_field_id('random'); ?>" value="1" <?php checked($random, 1); ?> /><?php _e('Show random posts.', 'ci_theme'); ?></label></p>
        <p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number of posts to show:', 'ci_theme'); ?></label><input id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="number" min="1" step="1" value="<?php echo esc_attr($count); ?>" class="widefat" /></p>

        <p>
            <label for="<?php echo $this->get_field_id('columns'); ?>"><?php _e('Output Columns:', 'ci_theme'); ?></label>
            <select id="<?php echo $this->get_field_id('columns'); ?>" name="<?php echo $this->get_field_name('columns'); ?>">
                <?php
                for ($i = 1; $i <= 4; $i ++) {
                    echo sprintf('<option value="%s" %s>%s</option>', esc_attr($i), selected($columns, $i, false), sprintf(_n('1 Column', '%s Columns', $i, 'ci_theme'), $i)
                    );
                }

                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('item_layout'); ?>"><?php _e('Item Layout:', 'ci_theme'); ?></label>
            <select id="<?php echo $this->get_field_id('item_layout'); ?>" class="widefat" name="<?php echo $this->get_field_name('item_layout'); ?>">
                <option value="vertical" <?php selected('vertical', $item_layout); ?>><?php _ex('Vertical', 'item layout', 'ci_theme'); ?></option>
                <option value="horizontal" <?php selected('horizontal', $item_layout); ?>><?php _ex('Horizontal (1 or 2 columns)', 'item layout', 'ci_theme'); ?></option>
            </select>
        </p>

        <fieldset class="ci-collapsible">
            <legend><?php _e('Custom Colors', 'ci_theme'); ?> <i class="dashicons dashicons-arrow-down"></i></legend>
            <div class="elements">
                <p><label for="<?php echo $this->get_field_id('color'); ?>"><?php _e('Foreground Color:', 'ci_theme'); ?></label><input id="<?php echo $this->get_field_id('color'); ?>" name="<?php echo $this->get_field_name('color'); ?>" type="text" value="<?php echo esc_attr($color); ?>" class="colorpckr widefat" /></p>
                <p><label for="<?php echo $this->get_field_id('background_color'); ?>"><?php _e('Background Color:', 'ci_theme'); ?></label><input id="<?php echo $this->get_field_id('background_color'); ?>" name="<?php echo $this->get_field_name('background_color'); ?>" type="text" value="<?php echo esc_attr($background_color); ?>" class="colorpckr widefat" /></p>

                <p>
                    <input name="<?php echo $this->get_field_name('background_image'); ?>" type="hidden" value="<?php echo esc_url($background_image); ?>" class="uploaded" />
                    <label for="<?php echo $this->get_field_id('background_image_id'); ?>"><?php _e('Background Image:', 'ci_theme'); ?></label>
                    <input id="<?php echo $this->get_field_id('background_image_id'); ?>" name="<?php echo $this->get_field_name('background_image_id'); ?>" type="hidden" value="<?php echo esc_attr($background_image_id); ?>" class="uploaded-id" />
                    <span class="selected_image" style="display: block;">
                        <?php
                        $image_url = ci_get_image_src($background_image_id, 'thumbnail');
                        if (!empty($image_url)) {
                            echo sprintf('<img src="%s" /><a href="#" class="close media-modal-icon"></a>', $image_url);
                        }

                        ?>
                    </span>
                    <a href="#" id="<?php echo $this->get_field_id('image_upload_button'); ?>" class="button ci-upload"><?php _e('Upload', 'ci_theme'); ?></a>
                </p>

                <p>
                    <label for="<?php echo $this->get_field_id('background_repeat'); ?>"><?php _e('Background Repeat:', 'ci_theme'); ?></label>
                    <select id="<?php echo $this->get_field_id('background_repeat'); ?>" class="widefat" name="<?php echo $this->get_field_name('background_repeat'); ?>">
                        <option value="repeat" <?php selected('repeat', $background_repeat); ?>><?php _ex('Repeat', 'background repeat property', 'ci_theme'); ?></option>
                        <option value="repeat-x" <?php selected('repeat-x', $background_repeat); ?>><?php _ex('Repeat Horizontally', 'background repeat property', 'ci_theme'); ?></option>
                        <option value="repeat-y" <?php selected('repeat-y', $background_repeat); ?>><?php _ex('Repeat Vertically', 'background repeat property', 'ci_theme'); ?></option>
                        <option value="no-repeat" <?php selected('no-repeat', $background_repeat); ?>><?php _ex('No Repeat', 'background repeat property', 'ci_theme'); ?></option>
                    </select>
                </p>
                <p><label for="<?php echo $this->get_field_id('parallax'); ?>"><input type="checkbox" name="<?php echo $this->get_field_name('parallax'); ?>" id="<?php echo $this->get_field_id('parallax'); ?>" value="1" <?php checked($parallax, 1); ?> /><?php _e('Parallax effect (requires a background image).', 'ci_theme'); ?></label></p>
                <p><label for="<?php echo $this->get_field_id('parallax_speed'); ?>"><?php _e('Parallax speed (0.1 - 1.0):', 'ci_theme'); ?></label><input id="<?php echo $this->get_field_id('parallax_speed'); ?>" name="<?php echo $this->get_field_name('parallax_speed'); ?>" type="number" min="0.1" max="1.0" step="0.1" value="<?php echo esc_attr($parallax_speed); ?>" class="widefat" /></p>
            </div>
        </fieldset>
        <?php
    }

// form

    static function _enqueue_admin_scripts()
    {
        global $pagenow;

        if (in_array($pagenow, array('widgets.php', 'customize.php'))) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_media();
            ci_enqueue_media_manager_scripts();
        }
    }

    function enqueue_custom_css()
    {
        $settings = $this->get_settings();

        if (empty($settings))
            return;

        foreach ($settings as $instance_id => $instance) {
            $id = $this->id_base . '-' . $instance_id;

            if (!is_active_widget(false, $id, $this->id_base)) {
                continue;
            }

            $sidebar_id = false; // Holds the sidebar id that the widget is assigned to.
            $sidebar_widgets = wp_get_sidebars_widgets();
            if (!empty($sidebar_widgets)) {
                foreach ($sidebar_widgets as $sidebar => $widgets) {
                    // We need to check $widgets for emptiness due to https://core.trac.wordpress.org/ticket/14876
                    if (!empty($widgets) && array_search($id, $widgets) !== false) {
                        $sidebar_id = $sidebar;
                    }
                }
            }

            $color = $instance['color'];
            $background_color = $instance['background_color'];
            $background_image = $instance['background_image'];
            $background_repeat = $instance['background_repeat'];

            $css = '';

            if (!empty($color)) {
                $css .= 'color: ' . $color . '; ';
            }
            if (!empty($background_color)) {
                $css .= 'background-color: ' . $background_color . '; ';
            }
            if (!empty($background_image)) {
                $css .= 'background-image: url(' . esc_url($background_image) . ');';
                $css .= 'background-repeat: ' . $background_repeat . ';';
            }

            if (!empty($css)) {
                $css = '#' . $id . ' .widget-wrap { ' . $css . ' } ' . PHP_EOL;
                wp_add_inline_style('ci-style', $css);
            }
        }
    }
}

add_action('widgets_init', create_function('', 'return register_widget("CI_Category_Posts");'));
