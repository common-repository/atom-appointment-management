<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$aam_cta1 = false;
if ($this->get_option('cta1_img')) {
	$aam_cta1 = array(
		'img' => wp_get_attachment_image_src( $this->get_option('cta1_img') , 'large')[0],
		'url' => $this->parse_link_option($this->get_option('cta1_link'))
	);
}

$aam_cta2 = false;
if ($this->get_option('cta2_img')) {
	$aam_cta2 = array(
		'img' => wp_get_attachment_image_src( $this->get_option('cta2_img') , 'large')[0],
		'url' => $this->parse_link_option($this->get_option('cta2_link'))
	);
}

$aam_formfields = $this->get_option('formfields');
$aam_privacy_mode = $this->get_option('privacy_mode');
$aam_privacy_text = $this->get_option('privacy_text');
$aam_privacy_link = $this->parse_link_option($this->get_option('privacy_link'));
?>

<div class="aam-modal-container">

	<div class="aam-modal-toolbar">
		<button id="aam-modal-close">
			<span class="fc-button fc-button-primary"><span class="fc-icon fc-icon-chevron-left"></span></span>
			<?php _e('To overview', 'atom-appointment-management'); ?>
		</button>
		<h2>
			<span class="aam-event-title"></span><a href="" class="aam-event-moreinfo" target="_blank" title="<?php _e('More information', 'atom-appointment-management'); ?>">i</a>
		</h2>
		<h3>
			<span class="aam-event-date"></span> |
			<span class="aam-event-time"></span>
		</h3>
	</div>

	<div id="aam-confirmation">
		<div class="atom-confirmation-container">
			<div class="aam-confirmation-content aam-user-editor-content">
				<?php echo apply_filters('the_content', sprintf($this->get_option('modal_inquiry_thankyou'), '<span class="atom-email"></span>')); ?>
			</div>

			<?php if ($aam_cta1 || $aam_cta2) { ?>
			<div class="atom-confirmation-ctas">
				<?php if ($aam_cta1) { ?>
					<a href="<?php echo $aam_cta1['url']; ?>" class="atom-confirmation-cta">
						<img src="<?php echo $aam_cta1['img']; ?>" alt="">
					</a>
				<?php } ?>

				<?php if ($aam_cta2) { ?>
					<a href="<?php echo $aam_cta2['url']; ?>" class="atom-confirmation-cta">
						<img src="<?php echo $aam_cta2['img']; ?>" alt="">
					</a>
				<?php } ?>
			</div>
			<?php } ?>

		</div>
	</div>

	<div class="aam-modal-content">

		<div class="atom-modal-box atom-modal-box-left">

			<div class="aam-user-editor-content aam-mobile-only">
				<?php echo apply_filters('the_content', $this->get_option('modal_infotext')); ?>
			</div>

			<form id="atom-aam-form">

				<?php
				foreach ($aam_formfields as $key => $field) {
					$required_attr = ' data-required="false"';
					$required_marker = '';
					if ((isset($field['required']) && $field['required'])) {
						$required_attr = ' data-required="true"';
						$required_marker = ' *';
					}

					if ($field['type'] == 'select') {

						echo '<select class="atom-aam-input" id="atom-aam-input-' . $field['id'] . '" type="' . $field['type'] . '">';
						echo '<option selected disabled value="">' . $field['label'] . '</option>';

						$select_options = explode(',', $field['selectvalues']);
						foreach ($select_options as $select_option) {
							$select_option = trim($select_option);
							if (!empty($select_option)) {
								echo '<option>' . $select_option . '</option>';
							}
						}

						echo '</select>';

					} else if ($field['type'] == 'textarea') {

						echo '<textarea class="atom-aam-input" id="atom-aam-input-' . $field['id'] . '" type="' . $field['type'] . '" placeholder="' . $field['label'] . $required_marker . '"' . $required_attr . '></textarea>';

					} else {

						echo '<input class="atom-aam-input" id="atom-aam-input-' . $field['id'] . '" type="' . $field['type'] . '" placeholder="' . $field['label'] . $required_marker . '"' . $required_attr . '>';

					}

				}
				?>

				<?php if ($aam_privacy_mode) { ?>
					<label for="atom-aam-consent">
						<input id="atom-aam-consent" name="atom-consent" type="checkbox" />
						<span>
							<?php
							echo $aam_privacy_text;
							if (!empty($aam_privacy_link) && $aam_privacy_link != '#') {
								echo '<br /><a href="' . $aam_privacy_link . '" target="_blank">' . __('More information on our privacy policy', 'atom-appointment-management') . '</a>';
							}
							?>
						</span>
					</label>
				<?php } ?>

				<div class="aam-submit-container">
					<button class="fc-button" type="submit" id="atom-aam-form-send">
						<?php echo $this->get_option('send_button_label'); ?>
					</button>
				</div>
			</form>

		</div>

		<div class="atom-modal-box atom-modal-box-right">

			<div class="aam-user-editor-content aam-large-only">
				<?php echo apply_filters('the_content', $this->get_option('modal_infotext')); ?>
			</div>

			<?php if (current_user_can('administrator')) { ?>
				<div class="atom-aam-admin" id="atom-admin-rule">
					<div class="aam-admin-label"><?php _e('Remove', 'atom-appointment-management') ?></div>
					<button type="button" class="fc-button atom-aam-single-exception" data-action="remove"><?php _e('Remove this time slot', 'atom-appointment-management'); ?></button>
					<button type="button" class="fc-button atom-aam-category-exception" data-action="remove"><?php _e('Remove all time slots of this category on this day', 'atom-appointment-management'); ?></button>
					<button type="button" class="fc-button atom-aam-day-exception" data-action="remove"><?php _e('Remove all time slots on this day', 'atom-appointment-management'); ?></button>
					<div class="aam-admin-label"><?php _e('Mark as fully booked', 'atom-appointment-management') ?></div>
					<button type="button" class="fc-button atom-aam-single-exception" data-action="full"><?php _e('Remove this time slot', 'atom-appointment-management'); ?></button>
					<button type="button" class="fc-button atom-aam-category-exception" data-action="full"><?php _e('Remove all time slots of this category on this day', 'atom-appointment-management'); ?></button>
					<button type="button" class="fc-button atom-aam-day-exception" data-action="full"><?php _e('Remove all time slots on this day', 'atom-appointment-management'); ?></button>
				</div>
				<div class="atom-aam-admin" id="atom-admin-single">
					<div class="aam-admin-label"><?php _e('Remove', 'atom-appointment-management') ?></div>
					<button type="button" class="fc-button atom-aam-remove-slot" data-action="remove"><?php _e('Remove this time slot', 'atom-appointment-management'); ?></button>
					<div class="aam-admin-label"><?php _e('Mark as fully booked', 'atom-appointment-management') ?></div>
					<button type="button" class="fc-button atom-aam-remove-slot" data-action="full"><?php _e('Remove this time slot', 'atom-appointment-management'); ?></button>
				</div>
				<div class="atom-aam-admin" id="atom-admin-recurring">
					<div class="aam-admin-label"><?php _e('Remove', 'atom-appointment-management') ?></div>
					<button type="button" class="fc-button atom-aam-remove-slot" data-action="remove"><?php _e('Remove these time slots', 'atom-appointment-management'); ?></button>
					<div class="aam-admin-label"><?php _e('Mark as fully booked', 'atom-appointment-management') ?></div>
					<button type="button" class="fc-button atom-aam-remove-slot" data-action="full"><?php _e('Remove these time slots', 'atom-appointment-management'); ?></button>
				</div>
			<?php } ?>

		</div>

	</div>

</div>
