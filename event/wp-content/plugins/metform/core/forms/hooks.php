<?php
namespace MetForm\Core\Forms;
defined( 'ABSPATH' ) || exit;
Class Hooks{

  use \MetForm\Traits\Singleton;

  public function Init(){
    add_filter( 'the_content', [ $this, 'get_form_content_on_preview' ] );
    add_action( 'admin_init', [ $this, 'add_author_support' ], 10 );
    add_filter( 'manage_metform-form_posts_columns', [ $this, 'set_columns' ] );
    add_action( 'manage_metform-form_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
  }

  public function get_form_content_on_preview($content) {

    if (isset($GLOBALS['post']) && $GLOBALS['post']->post_type == 'metform-form') {
      return \MetForm\Utils\Util::render_form_content($content, get_the_ID());
    }
    return $content;
  }

  public function add_author_support(){
    add_post_type_support( 'metform-form', 'author' );
  }

  public function set_columns( $columns ) {

    $date_column = $columns['date'];
    $author_column = $columns['author'];

    unset( $columns['date'] );
    unset( $columns['author'] );

    $columns['shortcode'] = esc_html__( 'Shortcode', 'metform' );
    $columns['count'] = esc_html__( 'Entries', 'metform' );
    $columns['views_conversion'] = esc_html__( 'Views/ Conversion', 'metform' );
    $columns['author']      = esc_html( $author_column );
    $columns['date']      = esc_html( $date_column );

    return $columns;
  }

  public function render_column( $column, $post_id ) {
    switch ( $column ) {
      case 'shortcode':
        echo '<input class="wp-ui-text-highlight code" type="text" onfocus="this.select();" readonly="readonly" value="'.esc_attr('[metform form_id="'.$post_id.'"]').'" style="width:99%">';
        break;
      case 'count':
        $count = \MetForm\Core\Entries\Action::instance()->get_entry_count($post_id);

        global $wp;
        $current_url = admin_url();
        $current_url .="edit.php?post_type=metform-entry&mf_form_id=".esc_attr($post_id);

        $rest_url = get_rest_url();
        $mf_ex_nonce = wp_create_nonce('wp_rest');
        $url = $rest_url."metform/v1/entries/export/".$post_id;
        $export_url = \MetForm\Utils\Util::add_param_url($url, "_wpnonce", $mf_ex_nonce);

        ?> 
        <a data-metform-form-id="<?php echo esc_attr($post_id); ?>" class='<?php echo class_exists('\MetForm_Pro\Plugin') || $this->is_old_user() ? esc_attr("") : esc_attr("mf-entry-count-btn"); ?>  attr-btn attr-btn-primary mf-entry-filter' href="<?php echo esc_url($current_url); ?>"><?php echo esc_html($count); ?></a>
        <?php if(class_exists('\MetForm_Pro\Plugin') || $this->is_old_user()) : ?>
          <a class='attr-btn attr-btn-primary mf-entry-export-csv' href="<?php echo esc_url($export_url); ?>"><?php echo esc_html__('Export CSV', 'metform'); ?></a>
        <?php else : ?>
          <div class="mf-pro-badge-wrapper mf-entry-pro mf-svg-container mf-tooltip-wrapper" data-tooltip="<?php echo esc_attr__('Upgrade for premium access.', 'metform'); ?>">
              <div class="mf-svg-inner mf-export-pdf-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none"><path d="M10.225 6.025h-8.4a1.2 1.2 0 0 0-1.2 1.2v4.2a1.2 1.2 0 0 0 1.2 1.2h8.4a1.2 1.2 0 0 0 1.2-1.2v-4.2a1.2 1.2 0 0 0-1.2-1.2m-7.2 0v-2.4a3 3 0 1 1 6 0v2.4" stroke="#2271B1" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  <div class="mf-svg-text"><?php echo esc_html__('Export CSV', 'metform'); ?></div>
              </div>
          </div>
        <?php endif;
        break;
      case 'views_conversion':
        $views = \MetForm\Core\Forms\Action::instance()->get_count_views($post_id);
        $views = (int)$views;

        $count = \MetForm\Core\Entries\Action::instance()->get_entry_count($post_id);
        $count = (int)$count;

        if($views != 0){
          $conversion = ($count*100)/$views;
          $conversion = round($conversion, 2);
        }else{
          $conversion = 0;
        }
        echo esc_html($views."/ ".$conversion."%");
      break;
    }
  }
  
  /**
   * Check if the user is old user
   */
  public function is_old_user(){

    $install_date = get_option('metform_install_date', false);

    //if install date before 23 november 2025 then it is old user
    if($install_date && strtotime($install_date) < strtotime('2025-11-23')){
      return true;
    }

    return false;
  }
}
