<?php
class Sort
{ 
    private $data = array();
    private $tab_post_types = array();
    private $tab_restricted_post_types = array(
        'attachment',
        'nav_menu_item',
        'revision',
        'page'
    );
    private $current_type = 'post'; // post or link
    
    public function __construct($data)
    {
        $this->setData($data);
        $this->set_tab_post_types();
    }
    
    //SET
    public function setData($v)
    {
        $arr = parse_str($v, $out);
        $k = array_keys($out);
        $this->current_type = $k[0];
        $this->data = $out[$this->current_type];
    }
    
    public function set_tab_post_types()
    {
        $this->tab_post_types = get_post_types('','names');  
    }
    
    public function set_tab_restricted_post_types($v)
    {
        $this->tab_restricted_post_types = array_merge($this->tab_restricted_post_types, $v);
    }
    
    //GET   
    public function getData()
    {
        return $this->data;
    }
    
    public function get_tab_post_types()
    {
        return $this->tab_post_types; 
    }
    
    //METHODS
    public function sortAll()
    {   
        if($this->current_type == 'post')
        {
            foreach($this->tab_post_types as $name_post_type)
            {
                if(in_array($name_post_type, $this->tab_restricted_post_types))
                    continue;
                
                $tab = array();
                foreach($this->data as $ID)
                {
                    if(get_post_type($ID) == $name_post_type)
                        $tab[] = $ID;
                }
                if(!empty($tab))
                    $this->sortByCategories($name_post_type, $tab);
            }
        }
        else if($this->current_type == 'link')
        {
            $tab = array();
            foreach($this->data as $ID)
                $tab[] = $ID;
            if(!empty($tab))
                $this->sortByCategories('link', $tab);
        }
    }
    
    private function sortByCategories($name_post_type, $tab)
    {
        $categories = get_categories(array('type' => $name_post_type));
        foreach($categories as $category)
        {
            $tab_cat = array();
            foreach($tab as $ID)
            {
                if($this->current_type == 'post')
                    $current_category = get_the_category($ID);
                else if($this->current_type == 'link')
                    $current_category = get_terms('link_category');
                
                $current_category = $current_category[0]->cat_name;
                if($current_category == $category->name)
                    $tab_cat[] = $ID;
                else
                {
                    $current_post_type = get_post_type($ID);
                    if($name_post_type == $current_post_type)
                        $tab_cat[] = $ID;
                }
            }
            if(!empty($tab_cat))
                $this->sortByPos($tab_cat);
        }
    }
    
    private function sortByPos($tab_cat)
    {
        global $wpdb;
        $table_name = $wpdb->prefix.$this->current_type."s";
        
        if($this->current_type == 'post')
            $tag_where = "id";
        else if($this->current_type == 'link')
            $tag_where = "link_id";
        
        $tab_pos = array();
        foreach($tab_cat as $ID)
        {
            $pos = $wpdb->get_row("SELECT `sbm_pos` FROM ".$table_name." WHERE ".$tag_where."=".$ID);
            $tab_pos[] = $pos->sbm_pos;
        }
        $minPos = min($tab_pos);
        
        $i=0;
        foreach($tab_cat as $ID) 
        {
            $new_pos = $minPos+$i;
            $wpdb->update($table_name, array('sbm_pos' => $new_pos), array($tag_where=>$ID));
            
            $i++;
        }	
    }
}