<?php

defined('ABSPATH') || exit;

include __DIR__ . "/icons.php";

$pro_exists = class_exists('\MetForm_Pro\Base\Package');

// Helper function to check if an integration is already in use
$is_integration_in_use = function($integration_key) {
	$integration_keys = array(
		'mailchimp' => 'mf_mailchimp_api_key',
		'aweber' => 'met_form_aweber_mail_access_token_key',
		'activecampaign' => 'mf_active_campaign_api_key',
		'getresponse' => 'mf_get_response_api_key',
		'convertkit' => 'mf_ckit_api_key',
	);
	
	if (isset($integration_keys[$integration_key])) {
		return \MetForm\Utils\Util::is_using_settings_option($integration_keys[$integration_key]);
	}
	
	return false;
};

$aweber_btn_text = $code ? 'Re Connect Aweber' : 'Connect Aweber';
$aweber_connect_url = "https://api.wpmet.com/public/aweber-auth/auth.php?redirect_url=". get_admin_url() . "admin.php?page=metform-menu-settings&state=" . wp_create_nonce() . "&section_id=mf-newsletter_integration";

$news_letter_integrations = array(
	'mailchimp' => array(
		'label' => 'MailChimp',
		'description' => 'Integrate MetForm with Mailchimp to establish seamless email marketing with automation.',
		'doc_url' => 'https://wpmet.com/doc/integration/',
		'icon' => $icons['mailchimp'],
		'button_text' => 'Save',
		'status' => 'pro',
		'form_fields' => array(
			array(
				'name' => 'mf_mailchimp_api_key',
				'label' => 'API Key:',
				'placeholder' => 'Mailchimp API key',
				'help_text' => 'Enter here your Mailchimp API key.',
				'help_url' => 'https://admin.mailchimp.com/',
			),
		),
	),
	'aweber' => array(
		'label' => 'Aweber',
		'description' => 'Streamline your customer relationship with automated email marketing by linking AWeber with MetForm.',
		'doc_url' => 'https://wpmet.com/doc/aweber-integration/',
		'icon' => $icons['aweber'],
		'status'        => 'pro',
		'redirect_url' => 'https://www.aweber.com/',
		'button_text' => $aweber_btn_text,
		'button_url' => $aweber_connect_url,
	),
	'activecampaign' => array(
		'label' => 'ActiveCampaign',
		'description' => 'Connect MetForm with ActiveCampaign for powerful email automation and incredible customer experiences.',
		'doc_url' => 'https://wpmet.com/doc/activecampaign/',
		'icon' => $icons['activecampaign'],
		'button_text' => 'Save',
		'status' => 'pro',
		'form_fields' => array(
			array(
				'name' => 'mf_active_campaign_url',
				'label' => 'API URL:',
				'placeholder' => 'ActiveCampaign API URL',
				'help_text' => 'Enter here your ActiveCampaign API URL.',
				'help_url' => 'https://www.activecampaign.com/',
			),
			array(
				'name' => 'mf_active_campaign_api_key',
				'label' => 'API Key:',
				'placeholder' => 'ActiveCampaign API key',
				'help_text' => 'Enter here your ActiveCampaign API key.',
				'help_url' => 'https://www.activecampaign.com/',
			),
		),
	),
	'getresponse' => array(
		'label' => 'GetResponse',
		'description' => 'Capture leads and launch targeted email campaigns with MetForm and GetResponse integration.',
		'doc_url' => 'https://wpmet.com/doc/getresponse-integration/',
		'icon' => $icons['getresponse'],
		'button_text' => 'Save',
		'status' => 'pro',
		'form_fields' => array(
			array(
				'name' => 'mf_get_reponse_api_key',
				'label' => 'API Key:',
				'placeholder' => 'GetResponse API key',
			),
		),
	),
	'convertkit' => array(
		'label' => 'ConvertKit',
		'description' => 'Reinforce MetForm with ConvertKit to simplify email marketing and boost audience growth.',
		'doc_url' => 'https://wpmet.com/doc/convertkit-integration/',
		'icon' => $icons['convertkit'],
		'button_text' => 'Save',
		'status' => 'pro',
		'form_fields' => array(
			array(
				'name' => 'mf_ckit_api_key',
				'label' => 'API Key:',
				'placeholder' => 'ConvertKit API key',
				'help_text' => 'Enter here your ConvertKit API key.',
				'help_url' => 'https://app.convertkit.com/users/login',

			),
			array(
				'name' => 'mf_ckit_sec_key',
				'label' => 'Secret Key:',
				'placeholder' => 'ConvertKit API secret',
				'help_text' => 'Enter here your ConvertKit API secret.',
				'help_url' => 'https://app.convertkit.com/users/login',
			),
		),
	),
);

?>

<?php $news_letter_integration_function = function ($settings) use ($news_letter_integrations, $pro_exists, $is_integration_in_use) {

	foreach ($news_letter_integrations as $integration_key => $integration) : ?>
		<div class="mf-dashboard__settings-api">
			<div class="mf-dashboard__settings-api__header">
				<div class="mf-dashboard__settings-api__header-title">
					<div class="icon-wrapper">
						<span>
							<?php \MetForm\Utils\Util::metform_content_renderer($integration['icon']); ?>
						</span>
						<div class="mf-dashboard__settings-api__header-action-button <?php 
							$is_old_pro_user = $pro_exists && \MetForm\Utils\Util::is_old_pro_user();
							$should_disable = !$is_old_pro_user && ((! $pro_exists && $integration['status'] == 'pro' && !($integration_key == 'mailchimp' && $is_integration_in_use($integration_key))) || ($integration_key == 'activecampaign'  && !\MetForm\Utils\Util::is_top_tier() && !($pro_exists && $is_integration_in_use($integration_key))) || (($integration_key == 'aweber' || $integration_key == 'getresponse' || $integration_key == 'convertkit') && (!\MetForm\Utils\Util::is_top_tier() && !\MetForm\Utils\Util::is_mid_tier()) && !($pro_exists && $is_integration_in_use($integration_key))));
							echo esc_attr( $should_disable ? 'disable' : '' ); 
						?>">
							<button class="manage-btn mf-modal-<?php echo esc_attr($integration_key); ?>-integration">
								<span> <svg xmlns="http://www.w3.org/2000/svg" width="15" height="14" viewBox="0 0 15 14" fill="none">
										<path d="M7.63674 8.90702C8.68995 8.90702 9.54374 8.05323 9.54374 7.00002C9.54374 5.94681 8.68995 5.09302 7.63674 5.09302C6.58353 5.09302 5.72974 5.94681 5.72974 7.00002C5.72974 8.05323 6.58353 8.90702 7.63674 8.90702Z" stroke="#181A26" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
										<path d="M1.28003 7.55939V6.44061C1.28003 5.77952 1.82035 5.23285 2.4878 5.23285C3.63836 5.23285 4.10875 4.41919 3.53029 3.42119C3.19974 2.84909 3.3968 2.10536 3.97526 1.77482L5.07496 1.14551C5.57714 0.846741 6.22552 1.02473 6.52428 1.52691L6.59421 1.64768C7.16631 2.64568 8.1071 2.64568 8.68555 1.64768L8.75548 1.52691C9.05424 1.02473 9.70262 0.846741 10.2048 1.14551L11.3045 1.77482C11.883 2.10536 12.08 2.84909 11.7495 3.42119C11.171 4.41919 11.6414 5.23285 12.792 5.23285C13.4531 5.23285 13.9997 5.77316 13.9997 6.44061V7.55939C13.9997 8.22048 13.4594 8.76715 12.792 8.76715C11.6414 8.76715 11.171 9.58081 11.7495 10.5788C12.08 11.1573 11.883 11.8946 11.3045 12.2252L10.2048 12.8545C9.70262 13.1533 9.05424 12.9753 8.75548 12.4731L8.68555 12.3523C8.11345 11.3543 7.17267 11.3543 6.59421 12.3523L6.52428 12.4731C6.22552 12.9753 5.57714 13.1533 5.07496 12.8545L3.97526 12.2252C3.3968 11.8946 3.19974 11.1509 3.53029 10.5788C4.10875 9.58081 3.63836 8.76715 2.4878 8.76715C1.82035 8.76715 1.28003 8.22048 1.28003 7.55939Z" stroke="#181A26" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
									</svg> Manage</span>
							</button>
						</div>
					</div>
					<div class="mf-dashboard__settings-api__header-wrap">
						<h2 class="integration-label"><?php echo esc_html($integration['label']); ?></h2>
					</div>
				</div>
				<p class="mf-dashboard__settings-api__header-description"><?php echo esc_html($integration['description']); ?></p>
			</div>
			<div class="mf-dashboard__settings-api__footer">
				<div class="mf-dashboard__settings-api__footer-switch">
					<?php
					$is_old_pro_user = $pro_exists && \MetForm\Utils\Util::is_old_pro_user();
					$should_show_upgrade = !$is_old_pro_user && (( !$pro_exists && $integration['status'] == 'pro' && !($integration_key == 'mailchimp' && $is_integration_in_use($integration_key))) || ($integration_key == 'activecampaign'  && !\MetForm\Utils\Util::is_top_tier() && !($pro_exists && $is_integration_in_use($integration_key))) || (($integration_key == 'aweber' || $integration_key == 'getresponse' || $integration_key == 'convertkit') && (!\MetForm\Utils\Util::is_top_tier() && !\MetForm\Utils\Util::is_mid_tier()) && !($pro_exists && $is_integration_in_use($integration_key))));
					
					if ( $should_show_upgrade ) {
						 
							$tooltip_text = 'Upgrade for premium access.';

							if($pro_exists){
								if( !\MetForm\Utils\Util::is_top_tier() && $integration_key == 'activecampaign'){
									$tooltip_text = 'Get access by upgrading to MetForm Agency plan.';
								}

								if( !\MetForm\Utils\Util::is_top_tier() && !\MetForm\Utils\Util::is_mid_tier() && ($integration_key == 'aweber' || $integration_key == 'getresponse' || $integration_key == 'convertkit')){
									$tooltip_text = 'Get access by upgrading to MetForm Professional plan.';
								}
							}
						 ?>
						<div class="mf-entry-pro mf-svg-container mf-pro-badge-wrapper mf-tooltip-wrapper" data-tooltip="<?php echo esc_attr($tooltip_text); ?>">
							<div class="mf-svg-inner mf-upgrade-btn">
								<svg xmlns="http://www.w3.org/2000/svg" width="13" height="14" viewBox="0 0 13 14" fill="none"><path d="M10.225 6.025h-8.4a1.2 1.2 0 0 0-1.2 1.2v4.2a1.2 1.2 0 0 0 1.2 1.2h8.4a1.2 1.2 0 0 0 1.2-1.2v-4.2a1.2 1.2 0 0 0-1.2-1.2m-7.2 0v-2.4a3 3 0 1 1 6 0v2.4" stroke="#E81454" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
								<div style="font-size: 14px;" class="mf-svg-text"><?php echo esc_html__('Upgrade', 'metform'); ?></div>
							</div>
                    	</div>
					<?php
					} else { ?>
						<a class="mf-dashboard__settings-api__footer-pro-btn" href="<?php echo esc_url($integration['doc_url']); ?>" target="_blank" rel="noopener noreferrer">
							<svg xmlns="http://www.w3.org/2000/svg" width="12" height="14" viewBox="0 0 12 14" fill="none">
								<path d="M3.5 10.125H8.5" stroke="#3970FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
								<path d="M3.5 7.625H6" stroke="#3970FF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
								<path d="M11 12V5.125L6.625 0.75H2.25C1.55964 0.75 1 1.30964 1 2V12C1 12.6904 1.55964 13.25 2.25 13.25H9.75C10.4404 13.25 11 12.6904 11 12Z" stroke="#3970FF" stroke-width="1.5" stroke-linejoin="round" />
								<path d="M6.625 0.75V3.875C6.625 4.56536 7.18463 5.125 7.875 5.125H11" stroke="#3970FF" stroke-width="1.5" stroke-linejoin="round" />
							</svg>
							Documentation
						</a>
					<?php } ?>
				</div>
			</div>
		</div>
		<!-- pro newsletter integration modal  -->
		<div class="attr-modal mf-api-modal mf-api-modal-animate" id="metform_<?php echo esc_attr($integration_key); ?>_modal" tabindex="-1" role="dialog" aria-labelledby="metform_<?php echo esc_attr($integration_key); ?>_modalLabel" style="display:none;">
			<form action="" method="post" id="<?php echo esc_attr($integration_key); ?>">
				<div class="attr-modal-dialog mf-api-modal-dialog" role="document">
					<div class="attr-modal-content">
						<div class="mf-api-modal-close-btn" data-dismiss="modal" aria-label="Close Modal">
							<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M13 1 1 13M1 1l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</div>
						<div class="mf-dashboard__settings-api__content ">
							<div class="mf-dashboard__settings-api__content-header">
								<h2>Add <?php echo esc_html($integration['label']); ?> Integrations</h2>
							</div>
							<div class="mf-dashboard__settings-api__lists-content">
								<div class="mf-dashboard__settings-api__content-input">
									<?php if (isset($integration['form_fields'])) {
										foreach ($integration['form_fields'] as $field_key => $field) { 
										?>
											<h4 class="field-key"><?php echo esc_html($field['label']); ?></h4>
											<input name="<?php echo esc_attr($field['name']); ?>" type="text" placeholder="<?php echo esc_attr($field['placeholder']); ?>" value="<?php echo esc_attr((isset($settings[$field['name']])) ? $settings[$field['name']] : ''); ?>">
											<?php if (isset($field['help_text']) && isset($field['help_url'])): ?>
												<p class="help-text"><?php echo esc_html($field['help_text']); ?> <a href="<?php echo esc_url($field['help_url']); ?>">Get API</a></p>
											<?php endif; ?>
										<?php } ?>
										<div class="mf-dashboard__settings-api__btn-group">
											<button type="button" data-dismiss="modal" class="components-button mf-settings-form-submit-btn save-btn"> <?php echo esc_html(! empty($integration['button_text']) ? $integration['button_text'] : 'Save'); ?> </button>
											<button type="button" class="components-button cancel-btn" data-dismiss="modal">Cancel</button>
										</div>
									<?php } ?>
									<?php if (isset($integration['redirect_url'])):  ?>
										<label for="attr-input-label" class="mf-setting-label mf-setting-label attr-input-label">Redirect url:</label>
										<p class="description"><?php echo esc_html($integration['redirect_url']); ?></p>
										<a href="<?php echo esc_attr($integration['button_url']); ?>" target="_blank" type="button" class="components-button save-btn"><?php echo esc_html($integration['button_text']); ?></a>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
		<!-- pro modal  -->
<?php endforeach;
} ?>