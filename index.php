<?php
/*
* Plugin Name: Maildog
* Plugin URI: https://maildog.email
* Description: Integración de Maildog para formularios de Contact Form
* Version: 1.0.0
* Author: Maildog
* Author URI: https://maildog.email
* License: ECPT_LICENSE
* Domain Path: /lang
*/

function mdgcf7_insert_script() {
  echo "<!-- Maildog code integration -->
  <script>
   !function(e,n,t,c,y,s,r,u){s=n.createElement(t),r=n.getElementsByTagName(t)[0],
   s.async=!0,s.src=c,r.parentNode.insertBefore(s,r),
   s.onload = function () {Maildog.init(y, false)}}
   (window,document,'script','//services.maildog.email/js/async.js', false);
  </script>";
}

add_action('wp_head', 'mdgcf7_insert_script');

function mdgcf7_acfEvent () {
  echo "<!-- Maildog ContactForm event -->
  <script>
    document.addEventListener( 'wpcf7mailsent', function( event ) {

      let formFields = event.detail.inputs
      let formElement = jQuery(event.target).find('form')

      function getMaildogDataByForm (form) {
        let formId = form.attr('id')
        let MaildogData = formId.split('-')[1].split('_')

        return {
          id: MaildogData[0],
          token: MaildogData[1]
        }
      }

      function formatData (fields) {
          let data = {
            custom: {}
          };

          var validKeys = ['name', 'nombre', 'email', 'correo', 'phone', 'telefono', 'subject', 'asunto', 'comment', 'comentario', 'message', 'mensaje'];

          for (i in fields) {
            if(!fields[i].value) continue 

            if (fields[i].name.indexOf('[]') > -1) {
        			fields[i].name = fields[i].name.substring(fields[i].name.length - 2, 0)
        		}

            if(fields[i].name.indexOf('custom-') < 0 && validKeys.indexOf(fields[i].name) > -1 && !data[fields[i].name]){
              data[fields[i].name] = fields[i].value;
              continue
            }

            if (fields[i].name.indexOf('custom-') > -1) {
              fields[i].name = fields[i].name.substring(7)
            }

            data.custom[fields[i].name] = fields[i].value;
          }

        return data
      }

    Maildog.async(formatData(formFields), getMaildogDataByForm(formElement), function (err, res) {
        if(err) return console.log(err)
        console.log(res)
      })

    }, false );
  </script>
  <!-- end Maildog code -->";
}

add_action('wp_footer', 'mdgcf7_acfEvent');
add_action('plugins_loaded', 'mdgcf7_lang');

// load languages for plugin
function mdgcf7_lang() {
  $domain = 'maildog';
  $plugin_path = dirname(plugin_basename( __FILE__ ) .'/lang/' );

  load_plugin_textdomain( 'maildog', false, plugin_basename( dirname( __FILE__ ) ) . '/lang/' );
}

add_action( 'admin_init', 'mdgcf7_parent_contactForm' );

// Maildog look if Contact form is active
function mdgcf7_parent_contactForm() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
      add_action( 'admin_notices', 'mdgcf7_parent_notice' );
      deactivate_plugins( plugin_basename( __FILE__ ) );

      if ( isset( $_GET['activate'] ) ) {
          unset( $_GET['activate'] );
      }
    }
}

// If wasn't active show admin notice
function mdgcf7_parent_notice(){ ?>
    <div class="error">
      <p><?php _e('To use <b>Maildog</b> it is necessary that you have installed and activated the plugin', 'maildog');?>
      <a href="'<?php admin_url(); ?> 'plugin-install.php?tab=search&s=contact+form+7">Contact Form 7</a>
    </p>
  </div>
<?php }

function mdgcf7_tab_callback(){
	$wpcf = WPCF7_ContactForm::get_current();

	wp_enqueue_style( 'custom_wp_admin_css', plugins_url( '/css/style-maildog.css', __FILE__ ), false, '1.0.0');
  $maildogId = (get_post_meta($wpcf->id(), '_listId', true)) ? get_post_meta($wpcf->id(), '_listId', true) : get_option('id');
  $maildogToken = (get_post_meta($wpcf->id(), '_maildogToken', true)) ? get_post_meta($wpcf->id(), '_maildogToken', true) : get_option('maildogToken');
	?>
		<div class="container-maildog" style="margin-bottom:10px;">
			<div class="logo-maildog">
				<img src="<?= plugin_dir_url( __FILE__ );?>/images/logo_white.png"><br />
				<span>Wordpress Plugin</span>
			</div>
			<p><?php _e('Note: Maildog require that youll replace the names of the fields in the form by: name, email, phone (if you have)', 'maildog');?></p>
				<p><label for="_listId">List id</label><br>
				<input type="text" class="mdog-field" name="_listId" value="<?= $maildogId; ?>"></p>
        <p><label for="_maildogToken">Token</label><br>
        <input type="text" class="mdog-field" name="_maildogToken" value="<?= $maildogToken; ?>"></p>
			</div>
			<p style="text-align:center">
				<a href="http://maildog.email" target="_blank"><?php _e('Go to my Maildog account', 'maildog');?></a>
			</p>

	<?php
}

// define the wpcf7_editor_panels callback
function mdgcf7_tab_cf7( $panels ) {

    $panels['maildog'] = array(
			'title'     => 'Maildog',
			'callback'  => 'mdgcf7_tab_callback'
		);
    return $panels;

};

// add the filter
add_filter( 'wpcf7_editor_panels', 'mdgcf7_tab_cf7', 10, 1 );

/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
function mdgcf7_save_callback($post_id) {
  $post = get_post($post_id);
  if ($post->post_type === 'wpcf7_contact_form') {
  	update_post_meta( $post_id, '_listId', $_POST['_listId'] );
    update_post_meta( $post_id, '_maildogToken', $_POST['_maildogToken'] );
    update_option('maildog_data', [
      'id' => $_POST['_listId'],
      'maildogToken' => $_POST['_maildogToken']
    ]);
  }
}

add_action( 'save_post', 'mdgcf7_save_callback' );

// define the wpcf7_form_hidden_fields callback
function mdgcf7_add_hidden( $array ) {
  $wpcf = WPCF7_ContactForm::get_current();

  $maildogId = get_post_meta($wpcf->id(), '_listId', true);
  $maildogToken = get_post_meta($wpcf->id(), '_maildogToken', true);

  return array(
    '_listId' => ($maildogId) ? $maildogId : get_option('id'),
	 	'_maildogToken' => ($maildogToken) ? $maildogToken : get_option('maildogToken')
  );
}

// add the filter
add_filter( 'wpcf7_form_hidden_fields', 'mdgcf7_add_hidden', 10, 1 );

// define the wpcf7_form_name_attr callback
function mdgcf7_filter_wpcf7($a) {
  $wpcf = WPCF7_ContactForm::get_current();
  $maildogId = get_post_meta($wpcf->id(), '_listId', true);
  $maildogToken = get_post_meta($wpcf->id(), '_maildogToken', true);

  return 'maildog-'.$maildogId.'_'.$maildogToken;
};

// add the filter
add_filter( 'wpcf7_form_id_attr', 'mdgcf7_filter_wpcf7', 10, 1 );
