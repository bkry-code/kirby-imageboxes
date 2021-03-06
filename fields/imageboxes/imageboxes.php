<?php

class ImageboxesField extends InputField {

    static public $assets = array(
        'css' => array(
            'styles.css'
        )
    );
    
    public $columns = 2;
    protected $cache;

    public function value() {
        $value = parent::value();/*
        if(empty($value)) {
            // get the first key of options
            $options = $this->options();
            if(is_array($options)) {
                reset($options);
                $value = key($options);        
            }
        }*/
        if(!is_array($value)) {
            $value = str::split($value, ',');
        }

        // Remove items from value array that are not present in the options array
        return array_keys(array_intersect_key(array_flip($value), $this->options()));
    }
    public function input() {
        $value   = func_get_arg(0);
        $input = parent::input($value);
        $input->removeClass('input');
        $input->addClass('checkbox');
        $input->attr('type', 'radio');
        $input->attr(array(
            'name'     => $this->name() . '[]',
            'type'     => 'checkbox',
            'value'    => $value,
            'checked'  => ($this->value === 'all') ? true : in_array($value, (array)$this->value()),
            'required' => false,
        ));
        
        if($this->readonly) {
            $input->attr('disabled', true);      
        }
        
        return $input;
    }
    public function label() {
        $label = parent::label();
        if(is_null($label)) return null;

        // use a legend to avoid having a label
        // that is just connected to the first input
        return $label->tag('legend')->attr('for', false);
    }
    public function options() {
        if($this->cache) return $this->cache;

        return $this->cache = fieldoptions::build($this);
    }


    public function item($value, $options) {

        $input = $this->input($value);
        
        // Get the input's label
        $text = $options['label'];
        
        // Get display options
        $display = $this->display();
        
        
        /* Defaults
        ----------------------------*/
        
        $ratio = false;
        $mobile = false;
        $position = 'center center';
        
        
        /* Display options
        ----------------------------*/
        
        if ($display) {

            /* Mobile
            ----------------------------*/
            
            // Get mobile option if specified
            if (array_key_exists('mobile', $display)) {
                $mobile = $display['mobile'];
            }
            
            /* Ratio
            ----------------------------*/
            
            if (array_key_exists('ratio', $display)) {

                // Get and convert ratio (3/2 -> 66.666%)
                $ratio = $display['ratio'];
                $convertedRatio = 1;

                // Do the math
                if (preg_match('/(\d+)(?:\s*)([\/])(?:\s*)(\d+)/', $ratio, $matches) !== false){
                    $convertedRatio = $matches[3] / $matches[1];
                }

                $convertedRatio = $convertedRatio * 100;
                $convertedRatio = round($convertedRatio, 3, PHP_ROUND_HALF_DOWN);
                
                $ratio = $convertedRatio;
            }
            
            
            /* Position
            ----------------------------*/
            
            if (array_key_exists('position', $display)) {
                $position = $display['position']; 
            }
            
        }
        
        $mobileClass = $mobile ? '' : ' mobile-disabled';
            
            
        /* Color
        ----------------------------*/
        
        // If the 'color' option is specified
        if (array_key_exists('color', $options)) {
            
            $color = $options['color'];
            $ratio = ($ratio) ? $ratio : 25;
            
            $imageDiv = '<div class="checkbox-illustration as-background'. $mobileClass .'" style=" padding-top: '. $ratio .'%; background-color: '. $color .';"></div>';
        }
         
        
        /* Image
        ----------------------------*/
        
        // If the 'image' option is specified
        elseif (array_key_exists('image', $options)) {
            
            $image = $options['image'];
            
            /* Get image URL
            ----------------------------*/

            // If a query is made about images, set parent directory as page URI or 'page' option 
            if ($this->query() && $this->query()['fetch'] == 'images') {

                $query = $this->query();

                // If there is a 'page' options -> Get the page URI
                if (array_key_exists('page', $query)) {
                    $uri = $this->getPage($query['page'])->uri();
                } 
                // If there is no 'page' option -> Get the current page URI
                else {
                    $uri = $this->page()->uri();
                }

                $imageurl = kirby()->urls->index() . '/' . $uri . '/' . $image;
            }
            // Otherwise, image is to be found in the main assets/images folder
            else {
                $imageurl = kirby()->urls()->assets() .'/images/'. $image;
            }
            
            /* Build the DIV
            ----------------------------*/
            
            // If no ratio
            if (!$ratio) {
                $imageDiv = '<img src="'. $imageurl .'">';
                $imageDiv = '<div class="checkbox-illustration'. $mobileClass .'">'. $imageDiv .'</div>';
            }
            // If ratio is specified
            else {
                $imageDiv = '<div class="checkbox-illustration as-background'. $mobileClass .'" style="background-image: url('. $imageurl .'); background-position: '. $position .'; padding-top: '. $ratio .'%;"></div>';
            }
               
        }


        /* Build the input
        ----------------------------*/
        
        $label = new Brick('label');
        $label->addClass('input');
        $label->addClass('input-with-checkbox');
        $label->attr('data-focus', 'true');
        $label->append($imageDiv);
        $label->append($input);
        $label->append('<span>' . $this->i18n($text) . '</span>');

        if($this->readonly) {
            $label->addClass('input-is-readonly');
        }

        return $label;

    }
    
    
    public function getPage($uri) {
        
        if(str::startsWith($uri, '../')) {
            
            if($currentPage = $this->page) {
                $path = $uri;
                while(str::startsWith($path, '../')) {
                    if($parent = $currentPage->parent()) {
                        $currentPage = $parent;
                    } else {
                        $currentPage = site();
                    }
                    $path = str::substr($path, 3);
                }
                if(!empty($path)) {
                    $currentPage = $currentPage->find($path);
                }
                $page = $currentPage;
            } else {
                $page = null;
            }
        } else if($uri == '/') {
            $page = site();
        } else {
            $page = page($uri);
        }
        return $page;
    }
    
    public function content() {

        $html = '<ul class="input-list field-grid cf">';

        switch($this->columns()) {
            case 2:
                $width = ' field-grid-item-1-2';
                break;
            case 3:
                $width = ' field-grid-item-1-3';
                break;
            case 4:
                $width = ' field-grid-item-1-4';
                break;
            case 5:
                $width = ' field-grid-item-1-5';
                break;
            default:
                $width = '';
                break;
        }

        foreach($this->options() as $key => $value) {
            
            
            $html .= '<li class="input-list-item field-grid-item' . $width . '">';
            $html .= $this->item($key, $value);
            $html .= '</li>';
        }

        $html .= '</ul>';

        $content = new Brick('div');
        $content->addClass('field-content');
        $content->append($html);

        return $content;

    }

    public function result() {
        $result = parent::result();
        return is_array($result) ? implode(', ', $result) : '';
    }
    
    public function validate() {
        if(is_array($this->value())) {
            foreach($this->value() as $v) {
                if(!array_key_exists($v, $this->options())) return false;
            }
            return true;
        } else {
            return array_key_exists($this->value(), $this->options());
        }
    }

}
