// Existing functions
function toggleNameserverOptions() {
    const selectedOption = document.querySelector('input[name="ns_type"]:checked').value;
    const customDiv = document.getElementById('custom-nameservers');
    const resolverSelect = document.getElementById('resolver-select');
    
    if (selectedOption === 'custom') {
        customDiv.style.display = 'block';
    } else {
        customDiv.style.display = 'none';
    }
    
    if (selectedOption === 'resolver') {
        resolverSelect.disabled = false;
    } else {
        resolverSelect.disabled = true;
    }
}

function clearForm() {
    const form = document.getElementById('dig-form');
    
    form.querySelectorAll('input[type="text"], textarea').forEach(input => {
        input.value = '';
    });
    
    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    document.querySelector('input[name="ns_type"][value="resolver"]').checked = true;
    
    form.querySelectorAll('select').forEach(select => {
        select.selectedIndex = 0;
    });
    
    document.getElementById('custom-nameservers').style.display = 'none';
    document.getElementById('resolver-select').disabled = false;
    document.querySelector('.hostname-textarea').focus();
    
    // Clear results
    const resultsContainer = document.querySelector('.results-ajax');
    if (resultsContainer) {
        resultsContainer.innerHTML = '';
    }
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

// AJAX Query functionality
let queryCounter = 0;
const activeQueries = new Map();

async function queryAPI(data) {
    const response = await fetch('api/query.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    });
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return await response.json();
}

function createResultBlock(queryId, hostname, type, resolverName) {
    const resultBlock = document.createElement('div');
    resultBlock.className = 'result-block';
    resultBlock.dataset.queryId = queryId;
    
    resultBlock.innerHTML = `
        <div class="result-header">
            <div class="result-header-top">
                <h3>Querying ${escapeHtml(hostname)} (${escapeHtml(type || 'Unspecified')})...</h3>
                <div class="spinner"></div>
            </div>
            <div class="result-meta">
                Resolver: <strong>${escapeHtml(resolverName)}</strong>
            </div>
        </div>
    `;
    
    return resultBlock;
}

function createHostnameGroupBlock(groupId, hostname, type) {
    const groupBlock = document.createElement('div');
    groupBlock.className = 'hostname-group';
    groupBlock.dataset.groupId = groupId;
    groupBlock.dataset.hostname = hostname;
    
    groupBlock.innerHTML = `
        <div class="hostname-group-header">
            <h3>Results for ${escapeHtml(hostname)} (${escapeHtml(type || 'Unspecified')})</h3>
            <div class="hostname-group-actions">
                <button class="copy-btn copy-all-results" data-group-id="${groupId}">Copy All Results</button>
            </div>
        </div>
        <div class="nameserver-results" data-group-id="${groupId}">
            <!-- Nameserver results will be added here -->
        </div>
    `;
    
    return groupBlock;
}

function createNameserverResultBlock(queryId, nameserver, resolverName) {
    const resultBlock = document.createElement('div');
    resultBlock.className = 'nameserver-result';
    resultBlock.dataset.queryId = queryId;
    
    resultBlock.innerHTML = `
        <div class="nameserver-header">
            <div class="nameserver-info">
                <strong>${escapeHtml(resolverName)}</strong>
                ${nameserver ? `<span class="nameserver-ip">(${escapeHtml(nameserver)})</span>` : ''}
                <span class="loading-indicator">Loading...</span>
            </div>
            <div class="nameserver-actions" style="display: none;">
                <button class="copy-btn copy-command" data-copy="">Copy Command</button>
                <button class="copy-btn copy-result" data-copy="">Copy Result</button>
            </div>
        </div>
        <div class="nameserver-content">
            <!-- Results will be added here -->
        </div>
    `;
    
    return resultBlock;
}

function updateNameserverResult(queryId, data, options) {
    const resultBlock = document.querySelector(`[data-query-id="${queryId}"]`);
    if (!resultBlock) return;
    
    // Hide loading indicator
    const loadingIndicator = resultBlock.querySelector('.loading-indicator');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
    
    // Show action buttons
    const actions = resultBlock.querySelector('.nameserver-actions');
    if (actions) {
        actions.style.display = 'flex';
        const copyCommand = actions.querySelector('.copy-command');
        const copyResult = actions.querySelector('.copy-result');
        if (copyCommand) copyCommand.setAttribute('data-copy', data.command);
        if (copyResult) copyResult.setAttribute('data-copy', data.plain);
    }
    
    // Add command line if show_command option is enabled
    const content = resultBlock.querySelector('.nameserver-content');
    if (content) {
        let html = '';
        if (options && options.show_command) {
            html += `<div class="command-line"><code>${escapeHtml(data.command)}</code></div>`;
        }
        html += data.formatted;
        content.innerHTML = html;
    }
}

function updateResultBlock(queryId, data, options) {
    const resultBlock = document.querySelector(`[data-query-id="${queryId}"]`);
    if (!resultBlock) return;
    
    const headerTop = resultBlock.querySelector('.result-header-top');
    headerTop.innerHTML = `
        <h3>Results for ${escapeHtml(data.hostname)} (${escapeHtml(data.type || 'Unspecified')})</h3>
        <div class="result-buttons">
            <button class="copy-btn copy-command" data-copy="${escapeHtml(data.command)}">Copy Command</button>
            <button class="copy-btn copy-result" data-copy="${escapeHtml(data.plain)}">Copy Results</button>
        </div>
    `;
    
    // Update resolver info if nameserver is specified
    const resultMeta = resultBlock.querySelector('.result-meta');
    if (data.nameserver) {
        resultMeta.innerHTML = `Resolver: <strong>${escapeHtml(data.resolver_name)}</strong> (${escapeHtml(data.nameserver)})`;
    }
    
    // Add command line only if show_command option is enabled
    if (options && options.show_command) {
        const commandLine = document.createElement('div');
        commandLine.className = 'command-line';
        commandLine.innerHTML = `<code>${escapeHtml(data.command)}</code>`;
        resultBlock.querySelector('.result-header').appendChild(commandLine);
    }
    
    // Add result content
    const resultContent = document.createElement('div');
    resultContent.className = 'result-content';
    resultContent.innerHTML = data.formatted;
    resultBlock.appendChild(resultContent);
}

function showError(queryId, error) {
    const resultBlock = document.querySelector(`[data-query-id="${queryId}"]`);
    if (!resultBlock) return;
    
    const headerTop = resultBlock.querySelector('.result-header-top');
    headerTop.innerHTML = `<h3 style="color: var(--danger);">Query Failed</h3>`;
    
    const errorContent = document.createElement('div');
    errorContent.className = 'error';
    errorContent.innerHTML = `<p>${escapeHtml(error)}</p>`;
    resultBlock.appendChild(errorContent);
}

async function performQuery(hostname, type, nameserver, resolverName, options, colorize, fix) {
    const queryId = `query-${++queryCounter}`;
    
    // Add result block
    const resultsContainer = document.querySelector('.results-ajax');
    const resultBlock = createResultBlock(queryId, hostname, type, resolverName);
    resultsContainer.appendChild(resultBlock);
    
    try {
        const response = await queryAPI({
            hostname,
            type,
            nameserver,
            resolver_name: resolverName,
            options,
            colorize,
            fix
        });
        
        if (response.success) {
            updateResultBlock(queryId, response.data, options);
        } else {
            showError(queryId, response.error);
        }
    } catch (error) {
        showError(queryId, error.message);
    }
}

async function performGroupedQuery(hostname, type, nameserver, resolverName, options, colorize, fix, groupId) {
    const queryId = `query-${++queryCounter}`;
    
    // Find the hostname group container
    const groupContainer = document.querySelector(`[data-group-id="${groupId}"] .nameserver-results`);
    if (!groupContainer) return;
    
    // Add nameserver result block
    const resultBlock = createNameserverResultBlock(queryId, nameserver, resolverName);
    groupContainer.appendChild(resultBlock);
    
    try {
        const response = await queryAPI({
            hostname,
            type,
            nameserver,
            resolver_name: resolverName,
            options,
            colorize,
            fix
        });
        
        if (response.success) {
            updateNameserverResult(queryId, response.data, options);
        } else {
            showNameserverError(queryId, response.error);
        }
    } catch (error) {
        showNameserverError(queryId, error.message);
    }
}

function showNameserverError(queryId, error) {
    const resultBlock = document.querySelector(`[data-query-id="${queryId}"]`);
    if (!resultBlock) return;
    
    // Hide loading indicator
    const loadingIndicator = resultBlock.querySelector('.loading-indicator');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
    
    // Show error in content
    const content = resultBlock.querySelector('.nameserver-content');
    if (content) {
        content.innerHTML = `<div class="error"><p>${escapeHtml(error)}</p></div>`;
    }
}

async function queryAuthoritative(hostname, type, options, colorize, fix, useGrouping = false, groupId = null) {
    // Extract domain from hostname
    const domain = extractDomain(hostname);
    
    try {
        // Get NS records
        const nsResponse = await queryAPI({
            hostname: domain,
            type: 'NS',
            get_ns_only: true
        });
        
        if (nsResponse.success && nsResponse.nameservers && nsResponse.nameservers.length > 0) {
            if (useGrouping && groupId) {
                // Use grouped queries
                const queries = nsResponse.nameservers.map(ns => 
                    performGroupedQuery(hostname, type, ns, 'Authoritative', options, colorize, fix, groupId)
                );
                await Promise.allSettled(queries);
            } else {
                // Create a hostname group for authoritative queries
                const authGroupId = `group-${hostname}-auth-${Date.now()}`;
                const resultsContainer = document.querySelector('.results-ajax');
                const groupBlock = createHostnameGroupBlock(authGroupId, hostname, type);
                resultsContainer.appendChild(groupBlock);
                
                // Query each authoritative nameserver in the group
                const queries = nsResponse.nameservers.map(ns => 
                    performGroupedQuery(hostname, type, ns, 'Authoritative', options, colorize, fix, authGroupId)
                );
                
                await Promise.allSettled(queries);
            }
        } else {
            throw new Error('No authoritative nameservers found');
        }
    } catch (error) {
        if (useGrouping && groupId) {
            // Add error to existing group
            const queryId = `query-${++queryCounter}`;
            const groupContainer = document.querySelector(`[data-group-id="${groupId}"] .nameserver-results`);
            if (groupContainer) {
                const errorBlock = document.createElement('div');
                errorBlock.className = 'error';
                errorBlock.innerHTML = `<p>Authoritative lookup failed: ${escapeHtml(error.message)}</p>`;
                groupContainer.appendChild(errorBlock);
            }
        } else {
            const queryId = `query-${++queryCounter}`;
            const resultsContainer = document.querySelector('.results-ajax');
            const resultBlock = createResultBlock(queryId, hostname, type, 'Authoritative');
            resultsContainer.appendChild(resultBlock);
            showError(queryId, error.message);
        }
    }
}

async function queryNIC(hostname, type, options, colorize, fix, useGrouping = false, groupId = null) {
    const tld = extractTLD(hostname);
    
    try {
        // Get TLD nameservers from root servers
        const tldResponse = await queryAPI({
            tld: tld,
            get_tld_ns: true
        });
        
        if (tldResponse.success && tldResponse.nameservers && tldResponse.nameservers.length > 0) {
            if (useGrouping && groupId) {
                // Use grouped queries
                const queries = tldResponse.nameservers.map(ns => 
                    performGroupedQuery(hostname, type, ns, 'NIC', options, colorize, fix, groupId)
                );
                await Promise.allSettled(queries);
            } else {
                // Create a hostname group for NIC queries
                const nicGroupId = `group-${hostname}-nic-${Date.now()}`;
                const resultsContainer = document.querySelector('.results-ajax');
                const groupBlock = createHostnameGroupBlock(nicGroupId, hostname, type);
                resultsContainer.appendChild(groupBlock);
                
                // Query each TLD nameserver in the group
                const queries = tldResponse.nameservers.map(ns => 
                    performGroupedQuery(hostname, type, ns, 'NIC', options, colorize, fix, nicGroupId)
                );
                
                await Promise.allSettled(queries);
            }
        } else {
            throw new Error('No TLD nameservers found');
        }
    } catch (error) {
        if (useGrouping && groupId) {
            // Add error to existing group
            const queryId = `query-${++queryCounter}`;
            const groupContainer = document.querySelector(`[data-group-id="${groupId}"] .nameserver-results`);
            if (groupContainer) {
                const errorBlock = document.createElement('div');
                errorBlock.className = 'error';
                errorBlock.innerHTML = `<p>NIC lookup failed: ${escapeHtml(error.message)}</p>`;
                groupContainer.appendChild(errorBlock);
            }
        } else {
            const queryId = `query-${++queryCounter}`;
            const resultsContainer = document.querySelector('.results-ajax');
            const resultBlock = createResultBlock(queryId, hostname, type, 'NIC');
            resultsContainer.appendChild(resultBlock);
            showError(queryId, error.message);
        }
    }
}

async function handleAjaxSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    // Clear previous results
    let resultsContainer = document.querySelector('.results-ajax');
    if (!resultsContainer) {
        resultsContainer = document.createElement('div');
        resultsContainer.className = 'results results-ajax';
        document.querySelector('main').appendChild(resultsContainer);
    } else {
        resultsContainer.innerHTML = '';
    }
    
    // Get form values
    const hostnames = formData.get('hostnames').trim().split('\n').filter(h => h.trim());
    const type = formData.get('type');
    const nsType = formData.get('ns_type');
    const colorize = formData.get('colorize') === '1';
    const fix = formData.get('fix') === '1';
    
    // Get options
    const options = {};
    const checkboxes = form.querySelectorAll('input[name^="options["]:checked');
    checkboxes.forEach(cb => {
        const match = cb.name.match(/options\[([^\]]+)\]/);
        if (match) {
            options[match[1]] = true;
        }
    });
    
    // Get resolvers configuration
    const resolversConfig = window.resolversConfig || {};
    
    // Determine if we need hostname grouping
    const needsGrouping = (nsType === 'all' || 
                          (nsType === 'resolver' && resolversConfig[formData.get('resolver')]?.servers?.length > 1) ||
                          (nsType === 'custom' && formData.get('custom_nameservers').trim().split('\n').filter(ns => ns.trim()).length > 1));
    
    // Create queries based on nameserver type
    const queries = [];
    
    for (const hostname of hostnames) {
        // Create hostname group if needed
        let groupId = null;
        if (needsGrouping) {
            groupId = `group-${hostname}-${Date.now()}`;
            const groupBlock = createHostnameGroupBlock(groupId, hostname, type);
            resultsContainer.appendChild(groupBlock);
        }
        
        switch (nsType) {
            case 'resolver':
                const selectedResolver = formData.get('resolver');
                const resolver = resolversConfig[selectedResolver] || { name: 'Default', servers: [] };
                
                if (resolver.servers.length > 0) {
                    // Query each server in the resolver
                    for (const server of resolver.servers) {
                        if (needsGrouping) {
                            queries.push(performGroupedQuery(hostname, type, server, resolver.name, options, colorize, fix, groupId));
                        } else {
                            queries.push(performQuery(hostname, type, server, resolver.name, options, colorize, fix));
                        }
                    }
                } else {
                    // Default resolver (no specific nameserver)
                    queries.push(performQuery(hostname, type, null, resolver.name, options, colorize, fix));
                }
                break;
                
            case 'all':
                // Query all resolvers
                for (const [key, resolver] of Object.entries(resolversConfig)) {
                    if (resolver.servers.length > 0) {
                        for (const server of resolver.servers) {
                            queries.push(performGroupedQuery(hostname, type, server, resolver.name, options, colorize, fix, groupId));
                        }
                    } else {
                        queries.push(performGroupedQuery(hostname, type, null, resolver.name, options, colorize, fix, groupId));
                    }
                }
                break;
                
            case 'authoritative':
                queries.push(queryAuthoritative(hostname, type, options, colorize, fix, needsGrouping, groupId));
                break;
                
            case 'nic':
                queries.push(queryNIC(hostname, type, options, colorize, fix, needsGrouping, groupId));
                break;
                
            case 'custom':
                const customNs = formData.get('custom_nameservers').trim().split('\n').filter(ns => ns.trim());
                for (const ns of customNs) {
                    if (needsGrouping) {
                        queries.push(performGroupedQuery(hostname, type, ns.trim(), 'Custom', options, colorize, fix, groupId));
                    } else {
                        queries.push(performQuery(hostname, type, ns.trim(), 'Custom', options, colorize, fix));
                    }
                }
                break;
        }
    }
    
    // Execute all queries in parallel
    await Promise.allSettled(queries);
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function extractDomain(hostname) {
    // Simple domain extraction - in production use a proper TLD list
    const parts = hostname.split('.');
    if (parts.length <= 2) {
        return hostname;
    }
    return parts.slice(-2).join('.');
}

function extractTLD(hostname) {
    const parts = hostname.split('.');
    return parts[parts.length - 1];
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('dig-form');
    
    // Add AJAX submit handler
    form.addEventListener('submit', handleAjaxSubmit);
    
    // Keyboard shortcuts
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
    
    // Event delegation for dynamic content
    document.addEventListener('click', function(e) {
        // Copy buttons
        if (e.target.classList.contains('copy-btn')) {
            let text = '';
            
            // Handle copy all results button
            if (e.target.classList.contains('copy-all-results')) {
                const groupId = e.target.getAttribute('data-group-id');
                const groupBlock = document.querySelector(`[data-group-id="${groupId}"]`);
                if (groupBlock) {
                    const hostname = groupBlock.dataset.hostname;
                    const results = groupBlock.querySelectorAll('.nameserver-result');
                    text = `Results for ${hostname}\n${'='.repeat(50)}\n\n`;
                    
                    results.forEach(result => {
                        const nameserverInfo = result.querySelector('.nameserver-info strong')?.textContent || '';
                        const nameserverIp = result.querySelector('.nameserver-ip')?.textContent || '';
                        const commandLine = result.querySelector('.command-line code')?.textContent || '';
                        const resultOutput = result.querySelector('.result-output')?.textContent || '';
                        const error = result.querySelector('.error')?.textContent || '';
                        
                        text += `${nameserverInfo} ${nameserverIp}\n`;
                        if (commandLine) text += `Command: ${commandLine}\n`;
                        text += `${resultOutput || error}\n\n`;
                    });
                }
            } else {
                // Regular copy button
                text = e.target.getAttribute('data-copy');
            }
            
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
        
        // Clickable IPs and domains
        if (e.target.classList.contains('clickable-ip') || e.target.classList.contains('clickable-domain')) {
            e.preventDefault();
            
            const value = e.target.getAttribute('data-ip') || e.target.getAttribute('data-domain');
            const hostnameTextarea = document.querySelector('.hostname-textarea');
            
            let currentValue = hostnameTextarea.value.trim();
            
            if (currentValue) {
                hostnameTextarea.value = currentValue + '\n' + value;
            } else {
                hostnameTextarea.value = value;
            }
            
            hostnameTextarea.scrollIntoView({ behavior: 'smooth' });
            hostnameTextarea.focus();
            hostnameTextarea.setSelectionRange(hostnameTextarea.value.length, hostnameTextarea.value.length);
        }
    });
    
    // Check URL params
    const currentParams = new URLSearchParams(window.location.search);
    if (currentParams.get('ns_type') === 'custom') {
        toggleNameserverOptions();
    }
});