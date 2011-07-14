<?php
class MoreTemplates {
  
  static $instance;
  
  static function load() {
    return self::$instance ? self::$instance : ( self::$instance = new MoreTemplates() );
  }
  
  private function __construct() {
    add_action('init', array($this, 'init'));
    //add_action('wp_footer', create_function('', 'get_ajax_module("test");') );
  }
  
  private static $modules = array();
  
  function init() {
    // make sure jquery is being loaded
    wp_enqueue_script('jquery');
    
    // scan the active theme dir for modular parts
    $path = TEMPLATEPATH.'/modules';
    if (file_exists($path)) {
      $dir = @opendir($path);
      while($file = @readdir($dir)) {
        if (preg_match('/(.*)\.php$/', $file, $matches)) {
          $template = $matches[1];
          $fx_name = $this->get_ajax_fx_name($template);
          self::$modules[$fx_name] = $template;
          add_action($fx_name, array($this, $fx_name));
          add_action($this->get_ajax_nopriv_fx_name($template), array($this, $fx_name));
        }
      }
      @closedir($dir);
    }
  }
  
  private function get_ajax_nopriv_fx_name($template) {
    return "wp_ajax_nopriv_mod_{$template}";
  }
  
  private function get_ajax_fx_name($template) {
    return "wp_ajax_mod_{$template}";
  }
  
  function __call($fx_name, $args) {
    if (($template = @self::$modules[$fx_name]) && $_POST) {
      extract($_POST);
      $post = $post_id ? get_post($post_id) : null;
      require(TEMPLATEPATH.'/modules/'.$template.'.php');
    }
    exit;
  }

  function get_ajax_module($template, $args = null) {
    $fx_name = $this->get_ajax_fx_name($template);
    
    if (isset(self::$modules[$fx_name])) {
      ?>
        <script>
          (function($, D, W) {
            W.__am__ = W.__am__ ? W.__am__ : { c: 0 };
            var id = __am__.c++;
            D.write('<div id="mod_'+id+'" class="ajax_module_<?php echo $template ?> ajax_module_loading"></div>');
            var args = $.extend({ action: 'mod_<?php echo $template ?>', post_id: '<?php echo get_the_ID() ?>' }, <?php echo json_encode($args) ?>);
            jQuery.post('<?php echo admin_url('admin-ajax.php') ?>', args, function(html) {
              $('#mod_'+id).removeClass('ajax_module_loading').html(html);
            });
          })(jQuery, document, window);
        </script>
      <?php
    } else {
      $path = TEMPLATEPATH.'/modules/'.$template.'.php';
      echo "<!-- file not found: {$path} -->\n";
    }
  }
  
}

if (!function_exists('get_ajax_module')):

function get_ajax_module($template, $args = null) {
  return MoreTemplates::load()->get_ajax_module($template, $args);
}

endif; // !function_exists('get_ajax_module')

MoreTemplates::load();