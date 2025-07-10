<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <link rel="stylesheet" href="assets/main.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <p class="subtitle"><?php echo htmlspecialchars($pageDescription); ?></p>
        </header>
        
        <main>
            <?php include 'templates/form.php'; ?>
            
            <?php if ($error): ?>
                <div class="error">
                    <h3>Error</h3>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <noscript>
                <?php if ($results): ?>
                    <div class="results">
                        <?php foreach ($results as $result): ?>
                            <div class="result-block">
                                <div class="result-header">
                                    <div class="result-header-top">
                                        <h3>Results for <?php echo htmlspecialchars($result['hostname']); ?> (<?php echo htmlspecialchars($result['type'] ?: 'Unspecified'); ?>)</h3>
                                        <div class="result-buttons">
                                            <button class="copy-btn copy-command" data-copy="<?php echo htmlspecialchars($result['command']); ?>">Copy Command</button>
                                            <button class="copy-btn copy-result" data-copy="<?php echo htmlspecialchars($result['plain']); ?>">Copy Results</button>
                                        </div>
                                    </div>
                                    <div class="result-meta">
                                        Resolver: <strong><?php echo htmlspecialchars($result['resolver_name']); ?></strong>
                                        <?php if (!empty($result['nameservers']) && $result['nameservers'][0] !== 'authoritative' && $result['nameservers'][0] !== 'nic'): ?>
                                            (<?php echo htmlspecialchars(implode(', ', $result['nameservers'])); ?>)
                                        <?php endif; ?>
                                        <?php if (isset($result['multiple']) && $result['multiple']): ?>
                                            <span style="color: #999; font-size: 11px;">[Multiple queries]</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isset($options['show_command']) && $options['show_command']): ?>
                                    <div class="command-line">
                                        <code><?php echo htmlspecialchars($result['command']); ?></code>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="result-content">
                                    <?php echo $result['formatted']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </noscript>
            
            <div class="results" id="ajax-results">
                <!-- AJAX results will be inserted here -->
            </div>
        </main>
        
        <footer>
            <p>Open Source DIG Web Interface - A local DNS lookup tool</p>
            <p>Keyboard shortcuts: <kbd>Ctrl+Enter</kbd> to submit, <kbd>Ctrl+L</kbd> to clear</p>
            <p>Developed by <a href="https://ljpc.solutions" target="_blank" rel="noopener">LJPc Solutions</a></p>
        </footer>
    </div>
    
    <script>
        // Pass resolvers configuration to JavaScript
        window.resolversConfig = <?php echo json_encode($config['resolvers']); ?>;
    </script>
    <script src="assets/app.js"></script>
</body>
</html>