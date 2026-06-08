<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap sgs-admin">
  <h1>Small Groups</h1>

  <?php if ( $notice ) : ?>
  <div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
    <p><?php echo esc_html( $notice ); ?></p>
  </div>
  <?php endif; ?>

  <div class="sgs-card">
    <h2>Upload CSV</h2>
    <p>Export the groups Google Sheet as CSV, then upload it here. The previous snapshot stays live until you activate the new one.</p>
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
      <input type="hidden" name="action" value="sgs_upload">
      <?php wp_nonce_field( 'sgs_upload' ); ?>
      <table class="form-table">
        <tr>
          <th><label for="csv_file">CSV File</label></th>
          <td><input type="file" id="csv_file" name="csv_file" accept=".csv" required></td>
        </tr>
        <tr>
          <th></th>
          <td>
            <label>
              <input type="checkbox" name="sgs_activate" value="1" checked>
              Activate immediately after upload
            </label>
            <p class="description">Uncheck to review warnings before making the new data live.</p>
          </td>
        </tr>
      </table>
      <?php submit_button( 'Upload & Import' ); ?>
    </form>
  </div>

  <div class="sgs-card">
    <h2>Snapshot History</h2>
    <?php if ( empty( $snapshots ) ) : ?>
      <p>No snapshots yet. Upload a CSV above to get started.</p>
    <?php else : ?>
    <table class="widefat striped">
      <thead>
        <tr>
          <th>Uploaded</th>
          <th>Groups</th>
          <th>Schema Warnings</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ( $snapshots as $snap ) : ?>
        <tr>
          <td><?php echo esc_html( $snap['date'] ); ?></td>
          <td><?php echo esc_html( $snap['count'] ); ?></td>
          <td>
            <?php if ( empty( $snap['warnings'] ) ) : ?>
              <span class="sgs-ok">&#x2713; None</span>
            <?php else : ?>
              <details>
                <summary class="sgs-warn"><?php echo count( $snap['warnings'] ); ?> warning(s) — click to expand</summary>
                <ul class="sgs-warning-list">
                  <?php foreach ( $snap['warnings'] as $w ) : ?>
                    <li><?php echo esc_html( $w ); ?></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            <?php endif; ?>
          </td>
          <td>
            <?php if ( $snap['active'] ) : ?>
              <strong class="sgs-active">&#x25CF; Active</strong>
            <?php else : ?>
              Inactive
            <?php endif; ?>
          </td>
          <td class="sgs-actions">
            <?php if ( ! $snap['active'] ) : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
              <input type="hidden" name="action" value="sgs_activate">
              <input type="hidden" name="snapshot_id" value="<?php echo esc_attr( $snap['id'] ); ?>">
              <?php wp_nonce_field( 'sgs_activate' ); ?>
              <button type="submit" class="button button-primary">Activate</button>
            </form>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
                  onsubmit="return confirm('Delete this snapshot? This cannot be undone.')">
              <input type="hidden" name="action" value="sgs_delete">
              <input type="hidden" name="snapshot_id" value="<?php echo esc_attr( $snap['id'] ); ?>">
              <?php wp_nonce_field( 'sgs_delete' ); ?>
              <button type="submit" class="button button-link-delete">Delete</button>
            </form>
            <?php else : ?>
              <em>—</em>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
