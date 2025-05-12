<?php
/**
 * Form page
 *
 * @package    Recover Points
 * @subpackage Views
 * @since      1.0.0
 */

?>
<p class="page-description"><?php echo $this->description(); ?></p>

<hr />

<nav class="mb-3">
	<div class="nav nav-tabs" id="nav-tab" role="tablist">
		<a class="nav-item nav-link active" id="nav-recovery-tab" data-toggle="tab" href="#recovery" role="tab" aria-controls="nav-recovery" aria-selected="false"><?php $L->p( 'Recovery' ); ?></a>

		<a class="nav-item nav-link" id="nav-settings-tab" data-toggle="tab" href="#settings" role="tab" aria-controls="nav-settings" aria-selected="false"><?php $L->p( 'Settings' ); ?></a>
	</div>
</nav>

<div class="tab-content" id="nav-tabContent">
	<div id="recovery" class="tab-pane active fade show mt-4" role="tabpanel" aria-labelledby="nav-recovery-tab">

		<input type="hidden" name="disable_recovery" value="true">
		<?php
		$count = 0;
		foreach ( $backups as $backup ) {

			$count++;

			$ID   = pathinfo( basename( $backup ), PATHINFO_FILENAME );
			$info = $syslog->get( $ID );

			if ( false !== $info ) {
				echo '<div class="recovery-point">';
				printf(
					'<h3>%s</h3>',
					$L->get( $info['dictionaryKey'] )
				);
				printf(
					'<p>%s %s<br />%s %s</p>',
					$L->get( 'Date:' ),
					$info['date'],
					$L->get( 'Username:' ),
					$info['username']
				);
				printf(
					'<p><button name="recovery_ID" value="%s" class="button btn btn-primary btn-sm" type="submit" onclick="return confirm(\'%s\');">%s</button></p>',
					$ID,
					$L->get( 'Are you sure you want to restore to this recovery point?' ),
					$L->get( 'Restore' )
				);
				echo '</div>';
			}

			if ( $count < count( $backups ) ) {
				echo '<hr />';
			}
		}

		if ( empty( $backups ) ) {
			printf(
				'<p>%s</p>',
				$L->get( 'There are no recovery points' )
			);
		} ?>
	</div>

	<div id="settings" class="tab-pane fade show mt-4" role="tabpanel" aria-labelledby="nav-settings-tab">

		<div class="form-field form-group row">
			<label class="form-label col-sm-2 col-form-label" for="limit"><?php $L->p( 'Number of Recoveries' ); ?></label>
			<div class="col-sm-10 row">
				<div class="form-range-controls">
					<span class="form-range-value border p-1"><span id="limit_value"><?php echo ( $this->getValue( 'limit' ) ? $this->getValue( 'limit' ) : $this->dbFields['limit'] ); ?></span></span>
					<input type="range" class="form-control-range custom-range custom-range" onInput="$('#limit_value').html($(this).val())" id="limit" name="limit" value="<?php echo $this->getValue( 'limit' ); ?>" min="1" max="25" step="1" />
					<span class="btn btn-secondary btn-md form-range-button hide-if-no-js" onClick="$('#limit_value').text('<?php echo $this->dbFields['limit']; ?>');$('#limit').val('<?php echo $this->dbFields['limit']; ?>');"><?php $L->p( 'Default' ); ?></span>
				</div>
				<small class="form-text"><?php $L->p( 'The maximum number of recovery points to keep available.' ); ?></small>
			</div>
		</div>

		<div class="form-field form-group row">
			<label class="form-label col-sm-2 col-form-label" for="link"><?php $L->p( 'Admin Menu' ); ?></label>
			<div class="col-sm-10">
				<select class="form-select" id="link" name="link">

					<option value="false" <?php echo ( ! $this->link() ? 'selected' : '' ); ?>><?php $L->p( 'Hide Link' ); ?></option>

					<option value="true" <?php echo ( $this->link() ? 'selected' : '' ); ?>><?php $L->p( 'Show Link' ); ?></option>
				</select>
				<small class="form-text text-muted"><?php $L->p( 'Show a link to this page in the admin menu.' ); ?></small>
			</div>
		</div>

		<p><button type="submit" class="button btn btn-primary btn-md"><?php $L->p( 'Save' ); ?></button></p>
	</div>
</div>

<script>
// Open current tab after refresh page.
$( function() {
	$( 'a[data-toggle="tab"]' ).on( 'click', function(e) {
		window.localStorage.setItem( 'recovery_active_tab', $( e.target ).attr( 'href' ) );
	});
	var active_tab = window.localStorage.getItem( 'recovery_active_tab' );
	if ( active_tab ) {
		$( '#nav-tab a[href="' + active_tab + '"]' ).tab( 'show' );
		window.localStorage.removeItem( 'recovery_active_tab' );
	}
});
</script>
