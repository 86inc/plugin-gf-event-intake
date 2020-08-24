<?php
/**
 * My Tickets.
 *
 * Shows list of tickets customer has on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-box-office/myaccount/.php.
 *
 * HOWEVER, on occasion WooCommerce Box Office will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  Automattic/WooCommerce
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$events = Gf_Event_Intake_Event_CPT::get_user_events( get_current_user_id(), 'any' );
$has_events = count( $events ) > 0;
$create_event_page = Gf_Event_Intake_Event_CPT::get_create_event_page_link();
?>

<?php if ( $has_events ) : ?>

	<table class="woocommerce-MyAccount-my-events shop_table shop_table_responsive">
		<thead>
			<tr>
				<th class="ticket-name"><span class="nobr"><?php esc_html_e( 'Event', 'gf-event-intake' ); ?></span></th>
				<th class="ticket-status"><span class="nobr"><?php esc_html_e( 'Status', 'gf-event-intake' ); ?></span></th>
				<!-- <th class="ticket-product"><span class="nobr"><?php //esc_html_e( 'Product', 'gf-event-intake' ); ?></span></th> -->
				<th class="ticket-actions"><span class="nobr">&nbsp;</span></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $events as $event ) : ?>
			<?php
			$status_labels = [];
			if (empty($status_labels[$event->post_status])) {
				$status_obj = get_post_status_object($event->post_status);
				$status_labels[$event->post_status] = $status_obj->label;
			}
			$edit_page_link = Gf_Event_Intake_Event_CPT::get_edit_event_page_link( $event->ID );
			$related_products = [];
			// $related_products_array = $event->related_products;
			// if (!empty($related_products_array)) {
			// 	foreach($related_products_array as $related_product_id) {
			// 		$related_products[] = wc_get_product($related_product_id);
			// 	}
			// }
			?>
			<tr>
				<td class="ticket-event">
					<a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>"><?php echo esc_html( $event->post_title ); ?></a>
				</td>
				<td class="ticket-status">
					<?php echo esc_html( $status_labels[$event->post_status] ); ?>
				</td>
				<?php if (!empty($related_products)) : ?>
				<td class="ticket-product">
					<?php foreach($related_products as $related_product) : ?>
					<a href="<?php echo esc_url( $related_product->get_permalink() ); ?>"><?php echo esc_html( $related_product->get_title() ); ?></a>
					<?php endforeach; ?>
				</td>
				<?php endif; ?>
				<td class="ticket-actions">
					<a href="<?php echo esc_url( $edit_page_link ); ?>" class="button woocommerce-Button"><?php esc_html_e( 'Edit', 'gf-event-intake' ); ?></a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

<?php else : ?>
	<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
		<?php esc_html_e( 'No events have been created yet.', 'gf-event-intake' ); ?>
	</div>
<?php endif; ?>

<a class="woocommerce-Button button" href="<?php echo esc_url( $create_event_page ); ?>">
	<?php esc_html_e( 'Create an Event', 'gf-event-intake' ) ?>
</a>