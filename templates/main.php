<?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
        <button type="submit" class="btn btn-outline btn-primary" name="scan">
            <i class="fas fa-sync-alt me-2"></i><?php echo _("Scan for Networks"); ?>
        </button>
    <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row" id="wifiClientContent">
  <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col">
                        <i class="<?php echo $__template_data['icon']; ?> me-2"></i><?php echo htmlspecialchars($__template_data['title']); ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php $status->showMessages(); ?>
                <form role="form" action="<?php echo $__template_data['action']; ?>" method="POST">
                    <?php echo CSRFTokenFieldTag() ?>
                    
                    <!-- Interface Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="interface" class="form-label"><?php echo _("WiFi Interface"); ?></label>
                            <?php if (!empty($__template_data['availableInterfaces'])) : ?>
                                <div class="input-group">
                                    <select class="form-select" id="interface" name="interface">
                                        <?php foreach ($__template_data['availableInterfaces'] as $iface) : ?>
                                            <?php 
                                                $info = $__template_data['interfaceInfo'][$iface];
                                                $displayText = htmlspecialchars($iface);
                                                if ($info['status'] === 'up' && !empty($info['ssid'])) {
                                                    $displayText .= ' (' . htmlspecialchars($info['ssid']) . ')';
                                                }
                                            ?>
                                            <option value="<?php echo htmlspecialchars($iface); ?>" 
                                                    <?php echo $iface === $__template_data['selectedInterface'] ? 'selected' : ''; ?>>
                                                <?php echo $displayText; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-primary" name="scan">
                                        <i class="fas fa-sync-alt me-2"></i><?php echo _("Scan"); ?>
                                    </button>
                                </div>
                            <?php else : ?>
                                <div class="alert alert-warning">
                                    <?php echo _("No wireless interfaces available for scanning"); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Network Scan Results Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><?php echo _("Network"); ?></th>
                                    <th><?php echo _("Channel"); ?></th>
                                    <th><?php echo _("Frequency"); ?></th>
                                    <th><?php echo _("Signal"); ?></th>
                                    <th><?php echo _("Security"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($__template_data['scanResults'])) : ?>
                                    <?php foreach ($__template_data['scanResults'] as $network) : ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($network['ssid']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($network['channel']); ?></td>
                                            <td>
                                                <?php if ($network['frequency']) : ?>
                                                    <?php echo htmlspecialchars($network['frequency']); ?> GHz
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="signal-strength">
                                                    <?php
                                                        $signalClass = 'bg-danger';
                                                        if ($network['quality'] >= 70) $signalClass = 'bg-success';
                                                        elseif ($network['quality'] >= 40) $signalClass = 'bg-warning';
                                                    ?>
                                                    <div class="progress" style="width: 100px;">
                                                        <div class="progress-bar <?php echo $signalClass; ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $network['quality']; ?>%"
                                                             aria-valuenow="<?php echo $network['quality']; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <small><?php echo $network['signal']; ?> dBm</small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($network['encryption'] === 'none') : ?>
                                                    <span class="badge bg-danger"><?php echo _("Open"); ?></span>
                                                <?php else : ?>
                                                    <span class="badge bg-success">
                                                        <?php echo htmlspecialchars($network['encryption']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <?php if (empty($__template_data['scanResults'])) : ?>
                                                <?php echo _("No networks found. Click Scan to search for nearby networks."); ?>
                                            <?php else : ?>
                                                <?php echo _("No networks found"); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add some custom styles -->
<style>
.signal-strength {
    display: flex;
    align-items: center;
    gap: 10px;
}
.signal-strength .progress {
    margin-bottom: 0;
    height: 0.5rem;
}
.signal-strength small {
    color: #6c757d;
    min-width: 55px;
}
</style>

