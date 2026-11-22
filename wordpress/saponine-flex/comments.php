<?php
if ( ! defined( 'ABSPATH' ) || post_password_required() ) { return; }
?>
<section id="comments">
	<?php if ( have_comments() ) : ?>
		<h2><?php esc_html_e( 'دیدگاه‌ها', 'saponine-flex' ); ?></h2>
		<ol class="sn-comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 48 ) ); ?></ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
