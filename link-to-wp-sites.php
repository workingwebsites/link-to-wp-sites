<?php 
/*
Plugin Name: Custom plugin - Link to WP Sites
Plugin URI: https://workingwebsites.ca
Description: Creates a menu to link to other WP sites user may have.
Author: Working Websites
Version: 1.0
Author URI: http://workingwebsites.ca

*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/*
 * Sets up rental settings in admin area to configure the site.
 * Includes:  Dates, Current Season and Password for session login
*/

class wp_link_to_sites_settings_admin{
    //Holds the values to be used in the fields callbacks
    private $options;
	
	var $num_sites = 2;

    //Start up
    public function __construct()    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    //Add options page under "Settings" 
    public function add_plugin_page()    {
        add_options_page(
            'WP Sites Settings', 
            'Link to WP Sites Settings', 
            'manage_options', 
            'wp-site-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    //Options page callback
    public function create_admin_page(){
        // Set class property
        $this->options = get_option( 'site_url_option' );		
        ?>
        <div class="wrap">
            <h1>Link to WP Sites Settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'site_url_group' );
                do_settings_sections( 'wp-site-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    //Register and add settings
    public function page_init(){ 
		register_setting(
            'site_url_group', 
            'site_url_option', 
            array( $this, 'sanitize' ) 
        );
		
        add_settings_section(
            'setting_section_id',
            'Websites to Link To', 
            array( $this, 'print_section_info' ), 
            'wp-site-setting-admin' 
        );
		
		//Add the fields
		for ($i = 0; $i < $this->num_sites  ; $i ++) {
			//Set name
			$site_name = 'Site '.($i+1);
			
			//Create fields for each site
			add_settings_field(
				'wp_site_'.$i, 
				$site_name, 
				array( $this, 'site_url_callback' ), 
				'wp-site-setting-admin', 
				'setting_section_id', 
				array( 'field_id' => $i )          
			); 
		}
		
    }

    /**
     * Sanitize each setting field as needed
    */
    public function sanitize( $input ){
        $new_input = array();

		foreach ($input As $field) {
			$name = sanitize_text_field( $field['name'] );
			$url = sanitize_text_field( $field['url'] );
			
			$new_input[] = array('name' => $name, 'url' => $url);
		}
        
		return $new_input;
    }

    //Print the Section text
    public function print_section_info(){
        print "<p>Enter the URL of the site you want to link to.<br />Include 'http://' in the URL.</p>";
    }


	//Get the settings option array and print one of its values
    public function site_url_callback($arg){
		$id = $arg['field_id'];
		$value_name = isset( $this->options[$id]['name'] ) ? esc_attr( $this->options[$id]['name']) : '';
		$value_url = isset( $this->options[$id]['url'] ) ? esc_attr( $this->options[$id]['url']) : '';
				
        printf(
            '<label style="display: inline-block; width: 3em">Name:</label> <input type="text" style="width: 20em;" id="site_url_option_'.$id.'_name" name="site_url_option['.$id.'][name]" value="'.$value_name.'" />'			
        );
		
		 printf(
            '<br /> <label style="display: inline-block; width: 3em">URL:</label> <input type="text" style="width: 25em;" id="site_url_option_'.$id.'_url" name="site_url_option['.$id.'][url]" value="'.$value_url.'" />'			
        );
    }

}	// end of class



class wp_link_to_sites_view_pages{
	
	private $current_user;
	private $ar_allowed_users;
	
		
    //Start up
    public function __construct()    {
		//Set allowed users
		$this->ar_allowed_users = array('glow', 'workingwebsites', 'grahamlowe45');
		
		//Load stuff
		add_action( 'admin_bar_menu', array( $this, 'toolbar_link_main'), 999 );
		add_action( 'admin_bar_menu', array( $this, 'toolbar_link_sites'), 999 );
		
		add_action( 'plugins_loaded', array( $this, 'check_if_user_logged_in' ) );
    }


	//Check for current user
	public function check_if_user_logged_in(){
        if ( is_user_logged_in() ){
           $this->current_user = wp_get_current_user();
        }
    }
    
	//Add to top menu
	public function toolbar_link_main( $wp_admin_bar ) {
		$args = array(
			'id'    => 'wplink2_main',
			'title' => 'Other Sites',
			'meta'  => array('class' => 'wplink2_main')
		);
		$wp_admin_bar->add_node( $args );
	}
	
	
	//Add sites to top menu
	public function toolbar_link_sites( $wp_admin_bar ) {
		
		//Creates sub links for each site
		$this->options = get_option( 'site_url_option' );
		
		//Bail if there's nothing
		if(empty($this->options)){ 
			return; 
		}
		
		//Bail if not allowed to see other sites
		 if(! in_array ( $this->current_user->user_login, $this->ar_allowed_users ) ){
			return; 
		 }
		
		//Build the list
		foreach ($this->options As $option) {
			$args = array(
				'id'    => 'wplink2_site_'.$option['name'],
				'title' => $option['name'],
				'parent'=> 'wplink2_main',
				'href'  => $option['url'],
				'meta'  => array( 'class' => 'wplink2_sites' )
			);
			$wp_admin_bar->add_node( $args );
		}
	}

}

// Run it
//if( is_admin() ){	
	//Add the settings page
	$wp_link_to_sites = new wp_link_to_sites_settings_admin();
	
	//Add the links to sites
	$wp_link_to_sites_view_pages = new wp_link_to_sites_view_pages();
	
//}

?>