<?php
namespace Saponine\Studio;
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<template id="sn-mobile-footer-template">
	<div class="sn-mobile-footer-content">
		<details class="sn-footer-disclosure" data-sn-footer-links>
			<summary><?php esc_html_e( 'دسترسی‌های سریع', 'saponine-studio' ); ?></summary>
			<nav aria-label="<?php esc_attr_e( 'دسترسی‌های سریع', 'saponine-studio' ); ?>"></nav>
		</details>
		<details class="sn-footer-disclosure sn-footer-contact-disclosure" open>
			<summary><?php esc_html_e( 'اطلاعات تماس', 'saponine-studio' ); ?></summary>
			<?php Service_Pages::contact_details( Service_Pages::contact_values() ); ?>
		</details>
	</div>
</template>
