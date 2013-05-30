<?php namespace bvalosek;

// Easily create Wordpress custom types with a fluent, chainable API
class CustomPostType
{
    protected $slug;
    protected $singular;
    protected $plural;
    protected $menu_icon;
    protected $menu_position = 25;
    protected $has_archive = true;
    protected $is_public = true;
    protected $supports = array('title', 'editor', 'thumbnail');
    protected $location = 'side';
    protected $columns = array(
        'cb' => '<input type="checkbox">',
        'title' => 'Title'
    );

    protected $custom_metas = array();

    // allow instantiation without new for cleaner one-off chaining
    public static function factory()
    {
        return new static();
    }

    // generate all the labels for the custom type
    public static function generate_labels($singular, $plural)
    {
        return array(
            'name'               => _x($plural, 'post type general name' ),
            'singular_name'      => _x($singular, 'post type singular name' ),
            'add_new'            => _x('Add New', 'u' ),
            'add_new_item'       => __('Add New '.$singular ),
            'edit_item'          => __('Edit '.$singular),
            'new_item'           => __('New '.$singular),
            'all_items'          => __('All '.$plural),
            'view_item'          => __('View '.$singular),
            'search_items'       => __('Search '.$singular),
            'not_found'          => __('No '.$plural.' found' ),
            'not_found_in_trash' => __('No '.$plural.' found in the Trash' ),
            'parent_item_colon'  => '',
            'menu_name'          => $plural
        );
    }

    // specify custom columns
    public function columns($cols)
    {
        foreach ($cols as $col) {
        }
    }

    // actually execute all the stuff we've found
    public function create()
    {
        // setup the post type itself
        add_action('init', array($this, 'handle_wp_init'));

        // setup save to handle any custom metas if we have something set
        add_action('save_post', array($this, 'handle_post_save'));

        // return the headers of the columns
        add_action('manage_edit-' . $this->slug . '_columns',
            array($this, 'get_columns'));

        // handle the display of the column info
        add_action('manage_' . $this->slug . '_posts_custom_column',
            array($this, 'handle_columns'), 10, 2);
    }

    // handle drawing of any of the custom column entries
    public function handle_columns($col, $post_id)
    {
        // see if it's any of our metas
        $this->handle_meta_column($col, $post_id);
    }

    // draw any of the column info that we created via custom meta
    protected function handle_meta_column($col, $post_id)
    {
        foreach ($this->custom_metas as $meta) {
            if ($meta['slug'] == $col) {
                echo get_post_meta($post_id, $col, true);
            }
        }
    }

    // the array of column header names/slugs
    public function get_columns()
    {
        // copy array and append date
        $cols = $this->columns;
        $cols['date'] = 'Last Updated';

        // pad with date at the end always
        return $cols;
    }

    // register the actual post type when WP boots
    public function handle_wp_init()
    {
        $labels = static::generate_labels($this->singular, $this->plural);
        $args = array(
            'labels'               => $labels,
            'description'          => 'All ' . $this->plural,
            'public'               => $this->is_public,
            'menu_position'        => $this->menu_position,
            'supports'             => $this->supports,
            'has_archive'          => $this->has_archive,
            'register_meta_box_cb' => array($this, 'register_meta_boxes')
        );

        if ($this->menu_icon)
            $args['menu_icon'] = $this->menu_icon;

        register_post_type($this->slug, $args);
    }

    // called whenever something is posted, lets determine if we want to do anything
    public function handle_post_save($post_id)
    {
        // check each meta to see if it matches a post variable, if so, save it
        foreach ($this->custom_metas as $meta) {
            foreach ($_POST as $k => $v) {
                if ($k == $meta['slug']) {
                    update_post_meta($post_id, $k, strip_tags($v));
                }
            }
        }
    }

    public function slug($s)
    {
        $this->slug = $s;
        return $this;
    }

    public function labels($s, $p)
    {
        $this->singular = $s;
        $this->plural   = $p;
        return $this;
    }

    public function supports($s)
    {
        $this->supports = $s;
        return $this;
    }

    public function menu_icon($i)
    {
        $this->menu_icon = $i;
        return $this;
    }

    public function custom_text_meta($title, $slug = NULL, $show_column = false)
    {
        // autocreate slug if we need to from the title
        $slug = $slug ?: sanitize_title_with_dashes($title);

        $info = array(
            'type'  => 'text',
            'label' => $title,
            'slug'  => $slug
        );

        // should we add stuff to the column flow?
        if ($show_column) {
            $this->columns[$slug] = $title;
        }

        array_push($this->custom_metas, $info);
        return $this;
    }

    // called by WP whenever we need to draw meta boxes
    public function register_meta_boxes()
    {
        foreach ($this->custom_metas as $meta) {
            switch ($meta['type']) {
            case 'text':
                $this->register_text_meta($meta);
                break;
            }
        }
    }

    // setup the drawing of a new meta box and saving for an arbitrary text field
    protected function register_text_meta(&$info)
    {
        $slug  = $info['slug'];

        // add the function to draw the meta box on the post edit screen
        add_meta_box($info['slug'], $info['label'], function($post) use ($slug) {
            $value = get_post_meta($post->ID, $slug, true);
            ?>
                <input
                    style="width: 100%"
                    type="text"
                    id="<?= $slug ?>"
                    name="<?= $slug ?>"
                    value="<?= esc_attr($value) ?>"
                >
            <?php
        },
        $this->slug,
        $this->location);
    }
}
