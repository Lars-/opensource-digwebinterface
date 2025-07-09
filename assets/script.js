function toggleNameserverOptions() {
    const selectedOption = document.querySelector('input[name="ns_type"]:checked').value;
    const customDiv = document.getElementById('custom-nameservers');
    const resolverSelect = document.getElementById('resolver-select');
    
    // Show/hide custom nameservers textarea
    if (selectedOption === 'custom') {
        customDiv.style.display = 'block';
    } else {
        customDiv.style.display = 'none';
    }
    
    // Enable/disable resolver dropdown
    if (selectedOption === 'resolver') {
        resolverSelect.disabled = false;
    } else {
        resolverSelect.disabled = true;
    }
}

function clearForm() {
    const form = document.getElementById('dig-form');
    
    // Clear all form inputs
    form.querySelectorAll('input[type="text"], textarea').forEach(input => {
        input.value = '';
    });
    
    // Reset checkboxes
    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Reset radio buttons to default (resolver)
    document.querySelector('input[name="ns_type"][value="resolver"]').checked = true;
    
    // Reset select dropdowns to first option
    form.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
    });
    
    // Hide custom nameservers
    document.getElementById('custom-nameservers').style.display = 'none';
    
    // Enable resolver dropdown
    document.getElementById('resolver-select').disabled = false;
    
    // Focus on hostname textarea
    document.querySelector('.hostname-textarea').focus();
}

function shareQuery() {
    const form = document.getElementById('dig-form');
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    const shareUrl = window.location.origin + window.location.pathname + '?' + params.toString();
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('Share URL copied to clipboard!');
        });
    } else {
        prompt('Copy this URL to share:', shareUrl);
    }
}

// AJAX query functionality
async function performQuery(hostname, type, nameserver, options) {
    const response = await fetch('/api/query.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            hostname: hostname,
            type: type,
            nameserver: nameserver,
            options: options,
            colorize: document.querySelector('input[name="colorize"]').checked
        })
    });
    
    const data = await response.json();
    if (!response.ok) {
        throw new Error(data.error || 'Query failed');
    }
    
    return data;
}

function createResultContainer(hostname, type) {
    const container = document.createElement('div');
    container.className = 'result-block';
    container.innerHTML = `
        <div class="result-header">
            <div class="result-header-top">
                <h3>Results for ${escapeHtml(hostname)} (${escapeHtml(type || 'Unspecified')})</h3>
                <div class="result-buttons">
                    <button class="copy-btn copy-command" style="display: none;">Copy Command</button>
                    <button class="copy-btn copy-result" style="display: none;">Copy Results</button>
                </div>
            </div>
            <div class="result-meta">
                <span class="loading-spinner">⟳</span> Querying...
            </div>
            <div class="command-line" style="display: none;">
                <code></code>
            </div>
        </div>
        <div class="result-content">
            <div class="loading-message">Executing query...</div>
        </div>
    `;
    return container;
}

function updateResultContainer(container, data, resolverName) {
    const meta = container.querySelector('.result-meta');
    const commandLine = container.querySelector('.command-line');
    const commandCode = commandLine.querySelector('code');
    const content = container.querySelector('.result-content');
    const copyCommand = container.querySelector('.copy-command');
    const copyResult = container.querySelector('.copy-result');
    
    // Update meta
    meta.innerHTML = `Resolver: <strong>${escapeHtml(resolverName)}</strong>`;
    
    // Update command
    commandCode.textContent = data.command;
    commandLine.style.display = 'flex';
    
    // Update content
    content.innerHTML = data.formatted;
    
    // Update copy buttons
    copyCommand.setAttribute('data-copy', data.command);
    copyCommand.style.display = 'inline-block';
    copyResult.setAttribute('data-copy', data.output);
    copyResult.style.display = 'inline-block';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function submitFormAjax(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    // Clear previous results
    const resultsContainer = document.getElementById('ajax-results');
    if (resultsContainer) {
        resultsContainer.innerHTML = '';
    }
    
    // Parse form data
    const hostnames = formData.get('hostnames').split('\n').filter(h => h.trim());
    const type = formData.get('type');
    const nsType = formData.get('ns_type');
    const options = {};
    
    // Get options
    ['short', 'trace', 'tcp', 'dnssec', 'noquestion', 'nocomments', 'nostats'].forEach(opt => {
        if (formData.get(`options[${opt}]`)) {
            options[opt] = true;
        }
    });
    
    // Get fix option
    const shouldFix = formData.get('fix') === '1';
    
    // Process each hostname
    for (const hostname of hostnames) {
        let trimmedHostname = hostname.trim();
        if (!trimmedHostname) continue;
        
        // Apply fix if needed
        if (shouldFix) {
            // Fix URL
            if (trimmedHostname.match(/^https?:\/\//i) || trimmedHostname.includes('/')) {
                try {
                    const url = trimmedHostname.match(/^https?:\/\//i) ? trimmedHostname : 'http://' + trimmedHostname;
                    const parsed = new URL(url);
                    trimmedHostname = parsed.hostname;
                } catch (e) {
                    // If URL parsing fails, try to extract domain manually
                    trimmedHostname = trimmedHostname.replace(/^https?:\/\//i, '').split('/')[0];
                }
            }
            // Fix email
            else if (trimmedHostname.includes('@')) {
                const parts = trimmedHostname.split('@');
                if (parts.length > 1) {
                    trimmedHostname = parts[parts.length - 1];
                }
            }
        }
        
        // Create result container
        const container = createResultContainer(trimmedHostname, type);
        document.getElementById('ajax-results').appendChild(container);
        
        try {
            if (nsType === 'resolver') {
                // Single resolver query
                const resolver = formData.get('resolver');
                const resolverName = document.querySelector(`option[value="${resolver}"]`).textContent;
                const nameservers = getResolverNameservers(resolver);
                
                if (nameservers.length === 0) {
                    // Default resolver
                    const data = await performQuery(trimmedHostname, type, 'default', options);
                    updateResultContainer(container, data, resolverName);
                } else {
                    // Specific resolver with multiple nameservers
                    const queries = nameservers.map(ns => performQuery(trimmedHostname, type, ns, options));
                    const results = await Promise.all(queries);
                    
                    // Combine results
                    const combined = {
                        command: results.map(r => r.command).join('\n'),
                        formatted: '<div class="multi-nameserver-results">' + 
                            results.map(r => `
                                <div class="nameserver-result">
                                    <div class="nameserver-label">Nameserver: <strong>${escapeHtml(r.nameserver)}</strong></div>
                                    ${r.formatted}
                                </div>
                            `).join('') + '</div>',
                        output: results.map(r => r.output).join('\n\n')
                    };
                    
                    updateResultContainer(container, combined, resolverName);
                }
                
            } else if (nsType === 'all') {
                // All resolvers - parallel queries
                const allQueries = [];
                const resolverInfo = [];
                
                // Get all resolvers from config
                const resolverSelect = document.querySelector('select[name="resolver"]');
                Array.from(resolverSelect.options).forEach(option => {
                    const resolver = option.value;
                    const resolverName = option.textContent;
                    const nameservers = getResolverNameservers(resolver);
                    
                    if (nameservers.length === 0) {
                        allQueries.push(performQuery(trimmedHostname, type, 'default', options));
                        resolverInfo.push({ name: resolverName, nameservers: ['default'] });
                    } else {
                        nameservers.forEach(ns => {
                            allQueries.push(performQuery(trimmedHostname, type, ns, options));
                            resolverInfo.push({ name: resolverName, nameservers: [ns] });
                        });
                    }
                });
                
                // Execute all queries in parallel
                const results = await Promise.allSettled(allQueries);
                
                // Group and display results
                const groupedResults = {};
                results.forEach((result, index) => {
                    const info = resolverInfo[index];
                    if (!groupedResults[info.name]) {
                        groupedResults[info.name] = [];
                    }
                    
                    if (result.status === 'fulfilled') {
                        groupedResults[info.name].push(result.value);
                    } else {
                        groupedResults[info.name].push({
                            error: true,
                            message: result.reason.message
                        });
                    }
                });
                
                // Display grouped results
                let combinedHtml = '<div class="all-resolvers-results">';
                let combinedCommands = [];
                let combinedOutput = [];
                
                for (const [resolverName, results] of Object.entries(groupedResults)) {
                    combinedHtml += `<div class="resolver-group"><h4>${escapeHtml(resolverName)}</h4>`;
                    
                    results.forEach(data => {
                        if (data.error) {
                            combinedHtml += `<div class="error">Error: ${escapeHtml(data.message)}</div>`;
                        } else {
                            combinedHtml += data.formatted;
                            combinedCommands.push(data.command);
                            combinedOutput.push(data.output);
                        }
                    });
                    
                    combinedHtml += '</div>';
                }
                combinedHtml += '</div>';
                
                const combined = {
                    command: combinedCommands.join('\n'),
                    formatted: combinedHtml,
                    output: combinedOutput.join('\n\n')
                };
                
                updateResultContainer(container, combined, 'All Resolvers');
                
            } else if (nsType === 'authoritative') {
                // Authoritative query - two steps
                const meta = container.querySelector('.result-meta');
                meta.innerHTML = '<span class="loading-spinner">⟳</span> Getting authoritative nameservers...';
                
                // First get the nameservers
                const nsData = await performQuery(trimmedHostname, type, 'authoritative', options);
                
                if (nsData.multiple && nsData.nameservers) {
                    meta.innerHTML = `<span class="loading-spinner">⟳</span> Querying ${nsData.nameservers.length} authoritative nameservers...`;
                    
                    // Query each nameserver in parallel
                    const queries = nsData.nameservers.map(ns => performQuery(trimmedHostname, type, ns, options));
                    const results = await Promise.all(queries);
                    
                    // Combine results
                    const combined = {
                        command: results.map(r => r.command).join('\n'),
                        formatted: '<div class="multi-nameserver-results">' + 
                            results.map((r, i) => `
                                <div class="nameserver-result">
                                    <div class="nameserver-label">Nameserver: <strong>${escapeHtml(nsData.nameservers[i])}</strong></div>
                                    ${r.formatted}
                                </div>
                            `).join('') + '</div>',
                        output: results.map(r => r.output).join('\n\n')
                    };
                    
                    updateResultContainer(container, combined, 'Authoritative');
                }
                
            } else if (nsType === 'nic') {
                // NIC query - similar to authoritative
                const meta = container.querySelector('.result-meta');
                meta.innerHTML = '<span class="loading-spinner">⟳</span> Getting NIC nameservers...';
                
                const nsData = await performQuery(trimmedHostname, type, 'nic', options);
                
                if (nsData.multiple && nsData.nameservers) {
                    meta.innerHTML = `<span class="loading-spinner">⟳</span> Querying ${nsData.nameservers.length} NIC nameservers...`;
                    
                    const queries = nsData.nameservers.map(ns => performQuery(trimmedHostname, type, ns, options));
                    const results = await Promise.all(queries);
                    
                    const combined = {
                        command: results.map(r => r.command).join('\n'),
                        formatted: '<div class="multi-nameserver-results">' + 
                            results.map((r, i) => `
                                <div class="nameserver-result">
                                    <div class="nameserver-label">Nameserver: <strong>${escapeHtml(nsData.nameservers[i])}</strong></div>
                                    ${r.formatted}
                                </div>
                            `).join('') + '</div>',
                        output: results.map(r => r.output).join('\n\n')
                    };
                    
                    updateResultContainer(container, combined, 'NIC');
                }
                
            } else if (nsType === 'custom') {
                // Custom nameservers
                const customNs = formData.get('custom_nameservers').split('\n').filter(ns => ns.trim());
                
                if (customNs.length > 0) {
                    const queries = customNs.map(ns => performQuery(trimmedHostname, type, ns.trim(), options));
                    const results = await Promise.all(queries);
                    
                    const combined = {
                        command: results.map(r => r.command).join('\n'),
                        formatted: '<div class="multi-nameserver-results">' + 
                            results.map((r, i) => `
                                <div class="nameserver-result">
                                    <div class="nameserver-label">Nameserver: <strong>${escapeHtml(customNs[i])}</strong></div>
                                    ${r.formatted}
                                </div>
                            `).join('') + '</div>',
                        output: results.map(r => r.output).join('\n\n')
                    };
                    
                    updateResultContainer(container, combined, 'Custom');
                }
            }
            
        } catch (error) {
            const content = container.querySelector('.result-content');
            content.innerHTML = `<div class="error">Error: ${escapeHtml(error.message)}</div>`;
            
            const meta = container.querySelector('.result-meta');
            meta.innerHTML = '<span style="color: red;">Query failed</span>';
        }
    }
}

function getResolverNameservers(resolver) {
    // This should match the config in PHP
    const resolverConfigs = {
        'default': [],
        'google': ['8.8.8.8', '8.8.4.4'],
        'quad9': ['9.9.9.9', '149.112.112.112'],
        'cloudflare': ['1.1.1.1', '1.0.0.1'],
        'opendns': ['208.67.222.222', '208.67.220.220'],
        'level3': ['4.2.2.1', '4.2.2.2'],
        'verisign': ['64.6.64.6', '64.6.65.6'],
        'dns-watch': ['84.200.69.80', '84.200.70.40'],
        'comodo': ['8.26.56.26', '8.20.247.20'],
        'norton': ['198.153.192.1', '198.153.194.1'],
        'yandex': ['77.88.8.8', '77.88.8.1'],
        'adguard': ['94.140.14.14', '94.140.15.15'],
        'cleanbrowsing': ['185.228.168.9', '185.228.169.9'],
        'alternate-dns': ['76.76.19.19', '76.223.122.150'],
        'freenom': ['80.80.80.80', '80.80.81.81'],
        'puntcat': ['109.69.8.51']
    };
    
    return resolverConfigs[resolver] || [];
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('dig-form');
    
    // Add AJAX form submission
    form.addEventListener('submit', submitFormAjax);
    
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
        
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            clearForm();
        }
    });
    
    // Event delegation for dynamically added elements
    document.addEventListener('click', function(e) {
        // Handle copy buttons
        if (e.target.classList.contains('copy-btn')) {
            e.preventDefault();
            const text = e.target.getAttribute('data-copy');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    const originalText = e.target.textContent;
                    e.target.textContent = 'Copied!';
                    setTimeout(() => {
                        e.target.textContent = originalText;
                    }, 2000);
                });
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                const originalText = e.target.textContent;
                e.target.textContent = 'Copied!';
                setTimeout(() => {
                    e.target.textContent = originalText;
                }, 2000);
            }
        }
        
        // Handle clickable IPs and domains
        if (e.target.classList.contains('clickable-ip') || e.target.classList.contains('clickable-domain')) {
            e.preventDefault();
            
            const value = e.target.getAttribute('data-ip') || e.target.getAttribute('data-domain');
            const hostnameTextarea = document.querySelector('.hostname-textarea');
            
            // Get current value and trim
            let currentValue = hostnameTextarea.value.trim();
            
            // Append the new value on a new line
            if (currentValue) {
                hostnameTextarea.value = currentValue + '\n' + value;
            } else {
                hostnameTextarea.value = value;
            }
            
            // Scroll to the textarea and focus
            hostnameTextarea.scrollIntoView({ behavior: 'smooth' });
            hostnameTextarea.focus();
            
            // Move cursor to end
            hostnameTextarea.setSelectionRange(hostnameTextarea.value.length, hostnameTextarea.value.length);
        }
    });
    
    const currentParams = new URLSearchParams(window.location.search);
    
    // Check if custom nameservers should be shown based on URL params
    if (currentParams.get('ns_type') === 'custom') {
        toggleNameserverOptions();
    }
    
    const urlString = window.location.search;
    const dataSize = new Blob([urlString]).size;
    
    if (dataSize > 2000) {
        form.method = 'POST';
    }
});