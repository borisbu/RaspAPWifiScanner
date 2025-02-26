<?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
        <input type="submit" class="btn btn-outline btn-primary" name="scan" value="<?php echo _("Scan for Networks"); ?>" />
        <?php if (!empty($__template_data['currentNetwork'])) : ?>
            <input type="submit" class="btn btn-warning" name="disconnect" value="<?php echo _("Disconnect"); ?>" />
        <?php endif; ?>
    <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col">
                        <i class="<?php echo $__template_data['icon']; ?> me-2"></i><?php echo htmlspecialchars($__template_data['title']); ?>
                    </div>
                    <?php if (!empty($__template_data['currentNetwork'])) : ?>
                    <div class="col">
                        <span class="float-end">
                            <i class="fas fa-wifi text-success"></i>
                            <?php echo _("Connected to: ") . htmlspecialchars($__template_data['currentNetwork']['ssid']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <?php $status->showMessages(); ?>
                <form role="form" action="<?php echo $__template_data['action']; ?>" method="POST" class="needs-validation" novalidate>
                    <?php echo CSRFTokenFieldTag() ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="interface" class="form-label"><?php echo _("WiFi Interface"); ?></label>
                            <?php if (!empty($__template_data['availableInterfaces'])) : ?>
                                <select class="form-select" id="interface" name="interface">
                                    <?php foreach ($__template_data['availableInterfaces'] as $iface) : ?>
                                        <option value="<?php echo htmlspecialchars($iface); ?>" 
                                                <?php echo $iface === $__template_data['interface'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($iface); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else : ?>
                                <div class="alert alert-warning">
                                    <?php echo _("No WLAN interfaces found"); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Rest of the template remains the same -->
                    
                    <div class="table-responsive">
                        <!-- Existing network table code -->
                    </div>

                    <?php echo $buttons ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Connect Modal remains the same --> 