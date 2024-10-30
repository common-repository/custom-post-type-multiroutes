<?php
/**
 * Plugin Name:     Custom Post Type Multiroutes
 * Description:     Gives your custom post types a custom multiple routing compatible with WPML.
 * Author:          Alejandro del Río
 * Text Domain:     cptmr
 * Domain Path:     /languages
 * Version:         0.1.2
 *
 * @package         Custom_Post_Type_Multiroutes
 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define('CPTMR_DIR', plugin_dir_path(__FILE__));

if( ! class_exists('CPTMultiroutes') ) :

  class CPTMultiroutes {
    /** @var string The plugin version number. */
    var $version = '0.1.1';
    var $langs = array();
    public $current_lang;
    public $plugin;
    public $settings;
    public $plugin_slug = 'cptmr';
    public $routingTypes;
    /**
     * __construct
     *
     * A dummy constructor to ensure CPTMultiroutes is only setup once.
     *
     * @date	24/12/19
     * @since	0.1.0
     *
     * @param	void
     * @return	void
     */	
    function __construct() {
      $this->plugin = plugin_basename( __FILE__ );
      $this->settings = get_option('cptmr_settings');
      $this->wpml = function_exists('icl_object_id');
      if( !empty($this->settings) ){
        $this->routingTypes = $this->get_routing_types();
      }
      if($this->wpml) {
        $this->langs = icl_get_languages();
        $this->current_lang = apply_filters( 'wpml_current_language', NULL );
      }else{
        $this->langs = array( get_locale() => '' );
        $this->current_lang = get_locale();
      }
    }

    /**
     * 
     */
    function register(){
      
      /**
       * Actions
       */
      add_action('admin_menu', array($this, 'admin_menu'));
      add_action('admin_init', array($this, 'update_options'));
      add_action('init', array($this, 'set_rewrite_rules'), 15);
      add_action('admin_init', array($this, 'add_posttypes_columns'), 10);
      add_action('restrict_manage_posts', array($this, 'add_routes_filter'), 10);
      add_action('add_meta_boxes', array($this, 'post_meta_box'));
      add_action('save_post', array($this, 'save_cptmr_route_meta_box_data'));
      add_action('pre_get_posts', array( $this, 'multiroute_post_filter'), 1 );
      add_action( 'plugins_loaded', array( $this, 'my_plugin_load_plugin_textdomain') );
      
      /**
       * Filters
       */
      add_filter("plugin_action_links_$this->plugin", array( $this, 'settings_link'));
      add_filter("post_type_link", array( $this, 'update_post_links'), 10, 2);
      if( $this->wpml ) {
        add_filter( 'icl_ls_languages', array( $this, 'wpml_selector_url_fix'));
      }
      add_filter( 'query_vars', array( $this, 'register_query_vars') );
      add_filter( 'parse_query', array( $this, 'sort_posts_by_route') );

    }
    
    public function my_plugin_load_plugin_textdomain() {
      load_plugin_textdomain( 'cptmr', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
    }

    /**
     * 
     */
    public function add_routes_filter( $post_type ){
      if((isset($this->routingTypes)) && in_array($post_type, $this->routingTypes)){
        $current_route = '';
        if( isset( $_GET['cptmr_route'] ) ) {
          $current_route = $_GET['cptmr_route'];
        } 
        echo '<select name="cptmr_route" id="cptmr_route">';
          echo '<option value="any" ' . selected( 'any', $current_route ) . '>' . __( 'Any routes', 'cptmr' ) . '</option>';
          echo '<option value="none" ' . selected( 'none', $current_route ) . '>' . __( 'Route not set', 'cptmr' ) . '</option>';          
          echo '<option value="all" ' . selected( 'all', $current_route ) . '>' . __( 'All routes', 'cptmr' ) . '</option>';
          foreach( $this->settings['cpt'][$post_type] as $key => $value ) { 
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $current_route ) . '>/' . $this->settings['cpt'][$post_type][$key][$this->current_lang] . '/</option>';
          }
        echo '</select>';
      }
    }

    /**
     * 
     */
    function sort_posts_by_route( $query ) {
      global $pagenow;
      $post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
      if ( is_admin() && $pagenow=='edit.php' && (isset($this->routingTypes)) && in_array($post_type, $this->routingTypes) && isset( $_GET['cptmr_route'] ) && $_GET['cptmr_route'] !='any' ) {
        $route = $_GET['cptmr_route'];

        $compare = '=';
        if($route == 'none'){
          $compare = 'NOT EXISTS';
        }

        $query->query_vars['meta_key'] = '_cptmr_route';
        $query->query_vars['meta_value'] = $route;
        $query->query_vars['meta_compare'] = $compare;

        
      }
    }

    /**
     * 
     */
    public function add_posttypes_columns(){
      if(isset($this->settings['cpt'])){
        foreach ($this->settings['cpt'] as $cpt => $ruleSet) {
          // Add the custom columns to the post type:
          add_filter( 'manage_' . $cpt . '_posts_columns', array( $this, 'set_custom_edit_columns') );
          add_action( 'manage_' . $cpt . '_posts_custom_column' , array( $this, 'custom_column') , 10, 2 );
          add_filter( 'manage_' . $cpt . '_posts_columns',  array( $this, 'reorder_edit_columns') );
          add_filter( 'manage_edit-' . $cpt . '_sortable_columns', array( $this, 'my_sortable_cake_column') );
        }
      }
    }

    public function my_sortable_cake_column( $columns ) {
      $columns['post_route'] = 'route';
      return $columns;
    }

    /**
     * 
     */
    public function reorder_edit_columns ( $columns ) {
      unset($columns['date']);
      unset($columns['post_route']);
      return array_merge ( $columns, array ( 
        'post_route'   => __( 'Route', 'cptmr' ),
        'date' => __('Date')
      ) );
    }

    /**
     * 
     */
    public function set_custom_edit_columns( $columns ){
      unset( $columns['author'] );
      $columns['post_route'] = __( 'Route', 'cptmr' );
      return $columns;
    }

    /**
     * 
     */
    public function custom_column( $column, $post_id ){
      switch ( $column ) {
        case 'post_route' :
          $routeID = get_post_meta( $post_id, '_cptmr_route', true );
          $posttype = get_post_type($post_id);
          if(!empty($this->settings['cpt'][$posttype][$routeID])){
            $outputRoute = $this->settings['cpt'][$posttype][$routeID][$this->current_lang];
          }else{
            if($routeID == 'all'){
              $outputRoute = __('All routes', 'cptmr');
            }
          }
          if ( !empty( $routeID ) )
            echo '/' . $outputRoute . '/' . ' <br><span style="font-size: 10px;">Route ID: ' . $routeID . '</span>';
          else
            _e( 'Route not set', 'cptmr' );
          break;
      }
    }

    /**
     * 
     */
    public function settings_link( $links ){
      $settings_link = '<a href="options-general.php?page=cptmr_settings">' . __('Settings', 'cptmr') . '</a>';
      array_push( $links, $settings_link );
      return $links;
    }

    /**
     * 
     */
    public function admin_menu() {
      add_options_page(
        __( 'CPT Multiroutes', 'cptmr' ),
        __( 'CPT Multiroutes', 'cptmr' ),
        'manage_options',
        'cptmr_settings',
        array($this, 'admin_index')
      );
    }

    /**
     * 
     */
    public function admin_index() {
      $post_types = $this->get_cpt();
      require CPTMR_DIR . 'settings.php';
    }

    /**
     * 
     */
    public function update_options() {
      if (!isset($_POST['cptmr_submit']))
          return false;

      check_admin_referer('nonce_cptmr');
      
      $input_options = array();
      $input_options['cptmr_settings'] = isset($_POST['cptmr_settings']) ? $_POST['cptmr_settings'] : '';
      $data = $this->sanitize_settings_array( $input_options );

      // Check if any route has been removed
      // If any post was linked to that route remove its post meta
      if(!empty($this->settings['cpt'])){
        foreach ($this->settings['cpt'] as $cpt => $routes) {
          foreach ($routes as $key => $route) {
            if( !isset($data['cptmr_settings']['cpt'][$cpt][$key]) ){
              $args = array(
                'post_type' => $cpt,
                'meta_query' => array(
                  array(
                    'key' => '_cptmr_route',
                    'value' => $key
                  )
                )
              );
              $posts = get_posts( $args );
              if( !empty($posts) ){
                foreach ($posts as $post) {
                  delete_post_meta( $post->ID, '_cptmr_route' );
                }
              }
            }
          }
        }
      }

      update_option('cptmr_settings', $data['cptmr_settings']);

      wp_redirect('admin.php?page=cptmr_settings&msg=update');
    }

    /**
     * 
     */
    public function sanitize_settings_array( $array ) {
      foreach ( (array) $array as $k => $v ) {
         if ( is_array( $v ) ) {
             $array[$k] =  $this->sanitize_settings_array( $v );
         } else {
             $array[$k] = sanitize_text_field( $v );
         }
      }
     return $array;                                                       
   }

    /**
     * 
     */
    public function get_cpt(){
      $post_types_args = array(
        'show_ui'      => true,
        'show_in_menu' => true,
        '_builtin' => false
      );
      return get_post_types($post_types_args, 'objects');
    }
    
    /**
     * 
     */
    function get_routing_types(){
      $types = array();
      if(!empty($this->settings['cpt'])){
        foreach ($this->settings['cpt'] as $cpt => $ruleSet) {
          $types[] = $cpt;
        }
      }
      return $types;
    }

    /**
     * 
     */
    function post_meta_box() {
      if(isset($this->settings['cpt'])){
        foreach ($this->settings['cpt'] as $cpt => $ruleSet) {
          add_meta_box(
            'cptmr_route',
            __( 'Visible on:', 'cptmr' ),
            array($this, 'cptmr_route_meta_box_callback'),
            $cpt,
            'side'
          );
        }
      }
    }

    /**
     * 
     */
    function cptmr_route_meta_box_callback( $post ) {
      // Add a nonce field so we can check for it later.
      wp_nonce_field( 'cptmr_route_nonce', 'cptmr_route_nonce' );

      $value = get_post_meta( $post->ID, '_cptmr_route', true );

      if ($post->post_status == 'auto-draft'){
        echo __('Save this post to configure visibility.', 'cptmr');
        return;
      }
      $isOriginal = true;
      if($this->wpml){
        global $sitepress;
        $originalID =  icl_object_id( $post->ID, $post->post_type, false, $sitepress->get_default_language());
        $isOriginal = ($post->ID == $originalID);
        $value = get_post_meta( $originalID, '_cptmr_route', true );
      }
      
      if($isOriginal){
      ?>
      <p>  
        <select name="cptmr_route" id="cptmr_route">  
          <? 
          foreach ($this->settings['cpt'][$post->post_type] as $key => $route) {
            echo '<option value="' . $key . '" ' . (($key == $value) ? 'selected': '') . '>' . $route[$this->current_lang] . '</option>';
          }
          echo '<option value="all" ' . ((( $value == 'all' ) || (!$value)) ? 'selected': '') . '>' . __('All routes', 'cptmr') . '</option>';
          ?>
        </select>  
      </p> 
      <?
      }else{
        printf(__('Visibility can be configured only on the <a href="%s">original post.</a>', 'cptmr'), get_edit_post_link( $originalID ));
        ?>
      <p>  
        <select name="cptmr_route" id="cptmr_route" disabled="disabled">  
        <? 
          foreach ($this->settings['cpt'][$post->post_type] as $key => $route) {
            echo '<option value="' . $key . '" ' . (($key == $value) ? 'selected': '') . '>' . $route[$this->current_lang] . '</option>';
          }
          echo '<option value="all" ' . ((( $value == 'all' ) || (!$value)) ? 'selected': '') . '>' . __('All routes', 'cptmr') . '</option>';
          ?>
        </select>  
      </p> 
      <?
      }
    }

    /**
     * When the post is saved, saves our custom data.
     *
     * @param int $post_id
     */
    function save_cptmr_route_meta_box_data( $post_id ) {
      // Check if our nonce is set.
      if ( ! isset( $_POST['cptmr_route_nonce'] ) ) {
        return;
      }
      // Verify that the nonce is valid.
      if ( ! wp_verify_nonce( $_POST['cptmr_route_nonce'], 'cptmr_route_nonce' ) ) {
        return;
      }
      // If this is an autosave, our form has not been submitted, so we don't want to do anything.
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
      }
      // Check the user's permissions.
      if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
        $post_type = sanitize_text_field( $_POST['post_type'] );
        if ( ! current_user_can( 'edit_page', $post_id ) ) {
          return;
        }
      } else {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
          return;
        }
      }
      /* OK, it's safe for us to save the data now. */
      // Make sure that it is set.
      if ( ! isset( $_POST['cptmr_route'] ) ) {
        return;
      }
      // Sanitize user input.
      $post_route = sanitize_text_field( $_POST['cptmr_route'] );
      // Update the meta field in the database.
      update_post_meta( $post_id, '_cptmr_route', $post_route );

      // Get translation ID of this post and update their _cptmr_route
      if($this->wpml){
        global $sitepress;
        $originalID = icl_object_id( $post_id, $post_type, false, $sitepress->get_default_language());
        $isOriginal = ($post_id == $originalID);
        if($isOriginal){
          $langs = icl_get_languages();
          foreach ($langs as $code => $lang) {
            if($sitepress->get_default_language() != $code){
              update_post_meta( icl_object_id( get_the_ID(), $post_type, false, $code ), '_cptmr_route', $my_data );
            }
          }
        }
      }
      
    }

    /**
     * 
     */
    function wpml_selector_url_fix( $languages ) {
      $postType = get_post_type();
      if( !is_admin() && isset($this->settings['cpt'][$postType]) ){
        global $sitepress;
        global $wp;

        // If CPT is on 'all' defined routes, it is necessary to identify the parent item
        // so the correct url is displayed on theme lang selector
        $metaRoute = get_post_meta( icl_object_id( get_the_ID(), $postType, false, $sitepress->get_default_language() ), '_cptmr_route', true );
        if(($metaRoute == 'all') || is_post_type_archive( $postType )){
          $currentKey = $this->get_current_key($postType);
        }

        foreach ($languages as $lang => $language) {
          // The str_replace only works for WPML setup with languages as subfolder.
          // Check WPML configuration first.
          $languageSlug = ( apply_filters( 'wpml_setting', 0, 'language_negotiation_type' ) == 1 ) ? $language['code'] . '/' : '' ;

          if(is_post_type_archive( $postType )){
            $languages[$lang]['url'] = get_site_url() . '/' . $languageSlug . $this->settings['cpt'][$postType][$currentKey][$language['code']];
          }elseif(isset($currentKey) && is_single()){
            $translatedPost = get_post( icl_object_id( get_queried_object_id(), 'post', false, $language['code'] ) ); 
            $languages[$lang]['url'] = get_site_url() . '/' . $languageSlug . $this->settings['cpt'][$postType][$currentKey][$language['code']] . '/' . $translatedPost->post_name;
          }else{
            $languages[$lang]['url'] = str_replace('/' . ICL_LANGUAGE_CODE . '/', '/' . $language['code'] . '/', $language['url']);
          }
        }
      }

      return $languages;
    }
    
    /**
     * 
     */
    function get_current_key( $postType ){
      global $wp;
      foreach ($this->settings['cpt'][$postType] as $key => $route) {
        // Find $route[ICL_LANGUAGE_CODE] in the current URL and add it to link
        $currentUrl = home_url( $wp->request );
        if (strpos($currentUrl, $route[ICL_LANGUAGE_CODE]) !== false) {
          $currentKey = $key;
        }
      }
      return $currentKey;
    }
     
    /**
     * 
     */
    function register_query_vars( $vars ) {
      $vars[] .= 'cptmr_route';
      return $vars;
    }

    /**
     * 
     */
    function update_post_links( $post_link, $post ){
      if((isset($this->routingTypes)) && in_array($post->post_type, $this->routingTypes)){
        $currentLang = get_locale();
        $originalPostID = $post->ID;
        $homeUrl = get_home_url();
        $homeUrl = ( substr($homeUrl, -1) == '/' ) ? $homeUrl : $homeUrl . '/';

        if($this->wpml) {
          global $sitepress;
          $currentLang = $sitepress->get_current_language();
          $originalPostID = icl_object_id( $post->ID, $post->post_type, false, $sitepress->get_default_language() );
        }
        $routeKey = get_post_meta( $originalPostID, '_cptmr_route', true );
        // Single routes
        if( is_admin() || is_singular() ){
          if( ( $routeKey == 'all' ) || !$routeKey ) { // all available routes
            global $wp;
            $currentUrl = home_url( $wp->request );
            $newLink = $post_link;
            foreach ($this->settings['cpt'][$post->post_type] as $key => $route) {
              if( count($this->settings['cpt'][$post->post_type]) == 1){
                $newLink = $homeUrl . $route[$currentLang] . '/' . $post->post_name;
              }
              if( ( count($this->settings['cpt'][$post->post_type]) > 1 ) &&
                  ( strpos($currentUrl, $route[$currentLang]) !== false ) ){
                $newLink = $homeUrl . $route[$currentLang] . '/' . $post->post_name;
              }
            }
          }else{
            $newLink = $homeUrl . $this->settings['cpt'][$post->post_type][$routeKey][$currentLang] . '/' . $post->post_name;
          }
        }

        // Archive routes
        if( is_post_type_archive( $post->post_type ) && in_array($post->post_type, $this->routingTypes)){
          global $wp;
          $currentUrl = home_url( $wp->request );
          $newLink = $post_link;
          foreach ($this->settings['cpt'][$post->post_type] as $key => $route) {
            if(( count($this->settings['cpt'][$post->post_type]) > 1 ) &&
                ( strpos($currentUrl, $route[$currentLang]) !== false )){
              $newLink = $homeUrl . $route[$currentLang] . '/' . $post->post_name;
            }
          }
        }
        return $newLink;
      }
      return $post_link;
    }

    /**
     * 
     */
    function set_rewrite_rules(){
      if(isset($this->settings['cpt'])){

        add_rewrite_tag( '%cptmr_route%', '([^&]+)' );

        foreach ($this->settings['cpt'] as $cpt => $ruleSet) {
          $post_object = get_post_type_object($cpt);
          foreach ($ruleSet as $id => $routes) {
            foreach ($routes as $route) {
              // Set archive routes if CPT ('has_archive = true' and 'cpt' in 'rewrite_archive' settings).
              if( $post_object->has_archive && isset($this->settings['rewrite_archive']) && in_array($cpt, $this->settings['rewrite_archive']) ){
                add_rewrite_rule(
                  $route . '/?$',
                  'index.php?post_type=' . $cpt . '&cptmr_route=' . $id,
                  'top'
                );
              }
              add_rewrite_rule(
                $route . '/([^/]+)/?$',
                'index.php?post_type=' . $cpt . '&name=$matches[1]&cptmr_route=' . $id,
                'top'
              );
            }
          }
        }
      }
    }

    /**
     * 
     */
    function multiroute_post_filter($query) {
          
      $cptmr_route = get_query_var( 'cptmr_route' );
      if( !is_admin() && !empty($cptmr_route) && (is_archive() || is_single()) && isset($query->query['post_type']) && (in_array($query->query['post_type'], $this->routingTypes)) ){
        
        $meta_query = array(
          'relation' => 'OR',
          array(
            'key' => '_cptmr_route',
            'value' => array($cptmr_route, 'all'),
            'compare' => 'IN'
          ),
          array(
            'key' => '_cptmr_route',
            'value' => '1',
            'compare' => 'NOT EXISTS'
          )
        );
        $query->set('meta_query', $meta_query);

      }

      if(is_admin()){
        $orderby = $query->get( 'orderby');
        if( 'route' == $orderby ) {

          $query->set( 'meta_query', array(
            'relation' => 'OR',
            array(
                'key' => '_cptmr_route', 
                'compare' => 'EXISTS'
            ),
            array(
                'key' => '_cptmr_route', 
                'compare' => 'NOT EXISTS'
            )
        ) );
        $query->set( 'orderby', 'meta_value title' ); 


        }
      }
    }

    /**
     * 
     */
    function activate(){
      flush_rewrite_rules();
    }

    /**
     * 
     */
    function deactivate(){
      flush_rewrite_rules();
    }

  }
  
endif; // class_exists check


if(class_exists('CPTMultiroutes')){
  $cptmrPlugin = new CPTMultiroutes();
  $cptmrPlugin->register();
}

register_activation_hook(__FILE__, array($cptmrPlugin, 'activate'));
register_deactivation_hook(__FILE__, array($cptmrPlugin, 'deactivate'));
register_uninstall_hook(__FILE__, 'cptmr_uninstall');

function cptmr_uninstall() {
    flush_rewrite_rules();
    // Deleting all post _cptmr_route meta_keys
    delete_post_meta_by_key( '_cptmr_route' );

    // Deleting settings from options table
    delete_option('cptmr_settings');
}
