<?php
namespace bvalosek;

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

    // actually execute all the stuff we've found
    public function create()
    {
        $slug = $this->slug;
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

        // setup the post type itself
        \add_action('init', function() use ($args, $slug) {
            \register_post_type($slug, $args);
        });

        // setup save to handle any custom metas if we have something set
        \add_action('save_post', array($this, 'handle_post_save'));
    }

    // called whenever something is posted, lets determine if we want to do anything
    public function handle_post_save($post_id)
    {
        // check each meta to see if it matches a post variable, if so, save it
        foreach ($this->custom_metas as $meta) {
            foreach ($_POST as $k => $v) {
                if ($k == $meta['slug']) {
                    \update_post_meta($post_id, $k, strip_tags($v));
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

    public function custom_text_meta($title, $slug = NULL)
    {
        // autocreate slug if we need to from the title
        $slug = $slug ?: \sanitize_title_with_dashes($title);

        $info = array(
            'type'  => 'text',
            'label' => $title,
            'slug'  => $slug
        );

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
        \add_meta_box($info['slug'], $info['label'], function($post) use ($slug) {
            $value = \get_post_meta($post->ID, $slug, true);
            ?>
                <input
                    style="width: 100%"
                    type="text"
                    id="<?= $slug ?>"
                    name="<?= $slug ?>"
                    value="<?= esc_attr($value) ?>"
                >
            <?
        },
        $this->slug,
        $this->location);
    }
}
