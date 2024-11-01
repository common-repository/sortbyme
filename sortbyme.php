<?php
/* 
Plugin Name: SortByMe 
Plugin URI: 
Description: SortByMe can manually manage the order of your items and custom post types by drag & drop. This allows to design parts more like a CMS, while keeping the original blog side, or not!
Version: 2.0.2
Author: Djib
Author URI: http://www.djib.me
*/  

if (!class_exists('SortByMe')) :
    
    class SortByMe
    {   
        public $current_screen;
        public $current_post_type;

        public $adminOptionsName = 'adminOptions_SortByMe';
        public $adminOptions = array(
            'version' => '2.0.0'
        );
        private $tab_restricted_post_types = array(
            'attachment',
            'nav_menu_item',
            'revision'
        );
    
        public function __construct()  
        {  
            load_plugin_textdomain('sortbyme', plugins_url('lang/' , __FILE__ ));  
            
            add_action('admin_init', array(&$this, 'register_settings_fields'));
            add_action('admin_menu', array(&$this, 'admin_panel'));
            add_action('admin_head', array(&$this, 'start'));

            add_action('wp_ajax_SortByMe', array(&$this, 'sortItems'));

            add_action('current_screen', array(&$this, 'activate_querie_admin'));
            add_action('pre_get_posts', array(&$this, 'activate_querie_front'));
        }

        public function get_current_post_type()
        {
            $this->current_post_type = get_post()->post_type;
        }
        
        public function activate_querie_admin()
        {
            $screen = get_current_screen();
            $options = get_option('sbm_settings_group');

            $get_post_types = get_post_types(); 
            foreach($get_post_types as $post)
            {
                if($post == $screen->post_type)
                {
                    if($options["sbm_activate_posts_".$post] == "1")
                        add_filter('posts_orderby', array(&$this, 'activate_SortByMe'));  
                }
            }
        }

        public function activate_querie_front($query)
        {
            if(!is_admin())
            {
                $options = get_option('sbm_settings_group');

                $this->current_post_type = $query->query['post_type'];

               
                if($this->current_post_type && !in_array($this->current_post_type, $this->tab_restricted_post_types))
                {
                    $get_post_types = get_post_types(); 
                    foreach($get_post_types as $post)
                    {
                        if($post == $this->current_post_type)
                        {
                            if($options["sbm_activate_posts_".$post] == "1")
                                add_filter('posts_orderby', array(&$this, 'activate_SortByMe'));
                        }
                    }
                }
            }
        }

        public function activate_SortByMe($q)
        {
            $q = "sbm_pos ASC";
            return $q;
        }
        
        public function admin_panel()
        {
            $this->addConfigMenu();
        }

        public function start()
        {
            
            if(is_admin())
            {       
                $this->sbm_get_current_screen();

                if($this->detectPage())
                    $this->initSortByMeScripts();
            }
        }
        
        private function detectPage()
        {
            $options = get_option('sbm_settings_group');

            $get_post_types = get_post_types(); 
            foreach($get_post_types as $post)
            {
                if($this->current_screen == $post)
                {
                    if($options["sbm_activate_posts_".$post] == "1")
                        return true;
                }
                
            }
            return false;
        }

        public function sbm_get_current_screen()
        {
            $this->current_screen = get_current_screen()->post_type;
        }
        
        public function activate()
        {
            global $wpdb;
            $table_posts = $wpdb->prefix.'posts';
            if($wpdb->get_var("SHOW TABLES LIKE '".$table_posts."'") == $table_posts)
            {
                $sql = "ALTER TABLE `".$table_posts."` ADD `sbm_pos` INT NOT NULL DEFAULT '0'";
                $wpdb->query($sql);
            }
            $this->getAdminOptions();
        }
        
        public function deactivate()
        {
            global $wpdb;
            $table_posts = $wpdb->prefix.'posts';
            $table_links = $wpdb->prefix.'links';
            if($wpdb->get_var("SHOW TABLES LIKE '".$table_posts."'") == $table_posts)
            {
                $sql = "ALTER TABLE `".$table_name."` DROP `sbm_pos`";
                $wpdb->query($sql);
            }
            if($wpdb->get_var("SHOW TABLES LIKE '".$table_links."'") == $table_links)
            {
                $sql = "ALTER TABLE `".$table_links."` DROP `sbm_pos`";
                $wpdb->query($sql);
            }
            delete_option($this->adminOptionsName);
            delete_option('sbm_settings_group');
        }
        
        public function getAdminOptions()
        {
            $adminOptions = $this->adminOptions;
            $adminOptionsBDD = get_option($this->adminOptionsName);
            
            if(!empty($adminOptionsBDD))
            {
                foreach($adminOptions as $key=>$options)
                    $adminOptions[$key] = $options;
            }
            update_option($this->adminOptionsName, $adminOptions);
            
            return $adminOptions;
        }
        
        function initSortByMeScripts()
        {
            wp_enqueue_style('sortByMe_css', plugins_url( 'css/sortByMe.css' , __FILE__ ));
            
            wp_enqueue_script('sortByMe_js', plugins_url( 'js/sortByMe.js' , __FILE__ ), array('jquery', 'jquery-ui-sortable'), false, true);
            wp_enqueue_script('sortByMe_js', plugins_url( 'js/sortByMe.js' , __FILE__ ));
            
            wp_localize_script('sortByMe_js', 'SortByMe', 
                array(
                    'ajaxurl'  => admin_url('admin-ajax.php'),
                    'action'   => 'SortByMe',
                    'nonce'    => wp_create_nonce('sortByMe_nonce')
                )
            );
        }
        
        public function is_good_xmlHttp()
        {
            $error_code = 0;
            
            if(!check_ajax_referer('sortByMe_nonce','_ajax_nonce', FALSE)) 
                $error_code = 1;
            
            return $error_code;
        }
        
        public function sortItems()
        {
            if($this->is_good_xmlHttp() === 0)
            {
                $num_posts = (array)wp_count_posts('post');
                if(isset($_POST['order'])) 
                {
                    require_once('php/Sort.class.php');
                    $sort = new Sort($_POST['order']);
                    $sort->sortAll();
                }
            }

            exit;
        }
        
        function addConfigMenu()
        {
            add_options_page('SortByMe', 'SortByMe', 10, __FILE__, array(&$this, 'initConfigPage'));
        }
        
        function register_settings_fields()
        {
            register_setting('sbm_settings_group', 'sbm_settings_group');
        }
        
        function initConfigPage()
        {
            $restricted_post_type = array(
                'attachment',
                'revision',
                'nav_menu_item'
            );
            $get_post_types = get_post_types('','names'); 
            foreach($restricted_post_type as $v)
                unset($get_post_types[$v]);

            ?>
            <div class="wrap">
                <?php screen_icon(); ?>
                <h2>Sort by Me</h2>
            </div>
            <div class="wrap">  
                <p><b>SortByMe</b> permet d'activer au sein même de vos articles par <i>"glisser-déposer"</i> un ordre différent que celui de la date par défaut.</p> 
                
                <form method="post" action="options.php">
                    <?php
                    settings_fields('sbm_settings_group');
                    $options = get_option('sbm_settings_group');
                    ?>
                    <table class="form-table">
                        
                        <?php foreach($get_post_types as $post_type) : ?>
                        
                        <tr valign="top">
                            <th scope="row"><label for="blogname">Activer sur les <b><?php echo strtoupper($post_type.'s'); ?></b></label></th>
                            <td>
                                <select name="<?php echo 'sbm_settings_group[sbm_activate_posts_'.$post_type.']'; ?>">
                                    <option value="1" <?php if($options['sbm_activate_posts_'.$post_type] == 1): ?> selected="selected"<?php endif; ?>>Oui</option>
                                    <option value="0" <?php if($options['sbm_activate_posts_'.$post_type] == 0): ?> selected="selected"<?php endif; ?>>Non</option>
                                </select>
                                <!-- <span class="description">&nbsp;<?php //_e("Permet d'activer <b>SortByMe</b> sur vos articles ou custom post type.", 'sortbyme'); ?></span> -->
                            </td>
                        </tr>

                        <?php endforeach; ?>
                        
                    </table>  
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                    </p>
                </form>
            </div>  
            <?php
        }
    }  

endif;

/* ACTIVATION */
if(class_exists('SortByMe')) :
    
    $sbm = new SortByMe();
    
    function activate_SortByMe()
    {
        $sbm = new SortByMe();
        $sbm->activate();
    }
    function deactivate_SortByMe()
    {
        $sbm = new SortByMe();
        $sbm->deactivate();
    }

    register_activation_hook(__FILE__, 'activate_SortByMe');
    register_deactivation_hook(__FILE__, 'deactivate_SortByMe');

endif;
?>
