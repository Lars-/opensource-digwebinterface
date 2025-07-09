<div class="query-form">
    <form id="dig-form" method="POST" action="">
        <div class="form-grid">
            <div class="form-section">
                <h3>Hostnames or IP addresses</h3>
                <textarea name="hostnames" class="hostname-textarea" placeholder="Enter hostnames or IP addresses (one per line)" rows="5" autofocus><?php 
                    if (isset($_REQUEST['hostnames'])) {
                        if (is_array($_REQUEST['hostnames'])) {
                            echo htmlspecialchars(implode("\n", $_REQUEST['hostnames']));
                        } else {
                            echo htmlspecialchars($_REQUEST['hostnames']);
                        }
                    }
                ?></textarea>
                <div class="form-help">
                    <label>
                        <input type="checkbox" name="fix" value="1" <?php echo isset($_REQUEST['fix']) ? 'checked' : ''; ?>>
                        Fix (converts URLs and emails to hostnames)
                    </label>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Query Type</h3>
                <select name="type" class="type-select" style="width: 100%;">
                    <?php foreach ($config['record_types'] as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo (isset($_REQUEST['type']) && $_REQUEST['type'] === $value) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <h3 style="margin-top: 12px;">Options</h3>
                <div class="options-grid" style="grid-template-columns: 1fr;">
                    <?php foreach ($config['options'] as $key => $label): ?>
                        <label class="option-label">
                            <input type="checkbox" name="options[<?php echo $key; ?>]" value="1" <?php echo (isset($_REQUEST['options'][$key])) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </label>
                    <?php endforeach; ?>
                    
                    <label class="option-label">
                        <input type="checkbox" name="colorize" value="1" <?php echo isset($_REQUEST['colorize']) ? 'checked' : ''; ?>>
                        Colorize output
                    </label>
                </div>
            </div>
        
            <div class="form-section">
                <h3>Nameservers</h3>
                <div class="nameserver-options">
                    <div class="radio-group" style="flex-direction: column;">
                        <label class="radio-option">
                            <input type="radio" name="ns_type" value="resolver" <?php echo (!isset($_REQUEST['ns_type']) || $_REQUEST['ns_type'] === 'resolver') ? 'checked' : ''; ?> onchange="toggleNameserverOptions()">
                            <span>Resolver:</span>
                        </label>
                        <select name="resolver" id="resolver-select" class="resolver-select-inline" style="width: 100%; margin-left: 0; margin-bottom: 8px;" <?php echo (!isset($_REQUEST['ns_type']) || $_REQUEST['ns_type'] === 'resolver') ? '' : 'disabled'; ?>>
                            <?php foreach ($config['resolvers'] as $key => $resolver): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($_REQUEST['resolver']) && $_REQUEST['resolver'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($resolver['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <label class="radio-option">
                            <input type="radio" name="ns_type" value="all" <?php echo (isset($_REQUEST['ns_type']) && $_REQUEST['ns_type'] === 'all') ? 'checked' : ''; ?> onchange="toggleNameserverOptions()">
                            <span>All resolvers</span>
                        </label>
                        
                        <label class="radio-option">
                            <input type="radio" name="ns_type" value="authoritative" <?php echo (isset($_REQUEST['ns_type']) && $_REQUEST['ns_type'] === 'authoritative') ? 'checked' : ''; ?> onchange="toggleNameserverOptions()">
                            <span>Authoritative</span>
                        </label>
                        
                        <label class="radio-option">
                            <input type="radio" name="ns_type" value="nic" <?php echo (isset($_REQUEST['ns_type']) && $_REQUEST['ns_type'] === 'nic') ? 'checked' : ''; ?> onchange="toggleNameserverOptions()">
                            <span>NIC</span>
                        </label>
                        
                        <label class="radio-option">
                            <input type="radio" name="ns_type" value="custom" <?php echo (isset($_REQUEST['ns_type']) && $_REQUEST['ns_type'] === 'custom') ? 'checked' : ''; ?> onchange="toggleNameserverOptions()">
                            <span>Custom</span>
                        </label>
                    </div>
                </div>
                
                <div id="custom-nameservers" style="display: <?php echo (isset($_REQUEST['ns_type']) && $_REQUEST['ns_type'] === 'custom') ? 'block' : 'none'; ?>; margin-top: 8px;">
                    <textarea name="custom_nameservers" class="nameserver-textarea" placeholder="Enter nameservers (one per line)" rows="3"><?php 
                        if (isset($_REQUEST['custom_nameservers'])) {
                            echo htmlspecialchars($_REQUEST['custom_nameservers']);
                        }
                    ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="submit-btn">Dig!</button>
                <button type="button" class="clear-btn" onclick="clearForm()">Clear</button>
                <button type="button" class="share-btn" onclick="shareQuery()">Share</button>
            </div>
        </div>
    </form>
</div>