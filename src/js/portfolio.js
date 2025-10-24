/**
 * Portfolio Data Manager
 * Handles dynamic loading and display of portfolio data from JSON
 * Manages wallet switching and data updates across the portfolio section
 */

export class PortfolioManager {
    constructor() {
        this.data = null; // Portfolio data from JSON
        this.activeWalletId = null; // Currently selected wallet ID
        this.init();
    }

    /**
     * Initialize the portfolio manager
     * Loads data, renders buttons, and sets active wallet
     */
    async init() {
        try {
            await this.loadData();
            this.renderWalletButtons();
            this.setActiveWallet(this.data.activeWalletId || this.data.wallets[0].id);
        } catch (error) {
            console.error('Error loading portfolio data:', error);
        }
    }

    /**
     * Load portfolio data from JSON file with retry logic
     * @param {number} retries - Number of retry attempts
     * @returns {Promise<void>}
     * @throws {Error} If HTTP request fails after all retries
     */
    async loadData(retries = 3) {
        for (let i = 0; i < retries; i++) {
            try {
                const response = await fetch('./assets/data/portfolio-data.json');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                this.data = await response.json();
                return;
            } catch (error) {
                if (i === retries - 1) throw error;
                // Exponential backoff: 1s, 2s, 3s
                await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
            }
        }
    }

    /**
     * Render wallet selection buttons dynamically using DocumentFragment
     * Creates buttons for each wallet in the data with performance optimization
     */
    renderWalletButtons() {
        const walletButtonsContainer = document.querySelector('.wallet-selection');
        if (!walletButtonsContainer) {
            console.error('Wallet selection container not found');
            return;
        }
        
        // Clear existing buttons
        walletButtonsContainer.innerHTML = '';

        // Use DocumentFragment for better performance
        const fragment = document.createDocumentFragment();
        
        // Create button for each wallet
        this.data.wallets.forEach(wallet => {
            const button = this.createWalletButton(wallet);
            fragment.appendChild(button);
        });
        
        // Append all buttons at once
        walletButtonsContainer.appendChild(fragment);
    }

    /**
     * Create individual wallet button element
     * @param {Object} wallet - Wallet data object
     * @returns {HTMLButtonElement} Created button element
     */
    createWalletButton(wallet) {
        const button = document.createElement('button');
        button.className = 'btn btn-outline-dark rounded-pill px-4';
        button.textContent = wallet.name;
        button.setAttribute('data-wallet-id', wallet.id);
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-label', `Выбрать ${wallet.name}`);
        
        // Add click event listener
        button.addEventListener('click', () => this.setActiveWallet(wallet.id));
        
        return button;
    }

    /**
     * Set active wallet and update UI
     * @param {number} walletId - ID of the wallet to activate
     */
    setActiveWallet(walletId) {
        const wallet = this.data.wallets.find(w => w.id === walletId);
        if (!wallet) return;

        this.activeWalletId = walletId;
        this.updateWalletButtons(walletId);
        this.updatePortfolioData(wallet);
    }

    /**
     * Update wallet button states (active/inactive)
     * @param {number} activeId - ID of the currently active wallet
     */
    updateWalletButtons(activeId) {
        const buttons = document.querySelectorAll('.wallet-selection button');
        buttons.forEach(button => {
            const walletId = parseInt(button.getAttribute('data-wallet-id'));
            const isActive = walletId === activeId;
            
            // Update button styles based on active state
            button.className = isActive 
                ? 'btn btn-dark rounded-pill px-4'
                : 'btn btn-outline-dark rounded-pill px-4';
            button.setAttribute('aria-pressed', isActive.toString());
        });
    }

    /**
     * Update all portfolio data for the selected wallet
     * @param {Object} wallet - Wallet data object
     */
    updatePortfolioData(wallet) {
        // Update capital value in 3 places: header, chart center, portfolio card
        this.updateElement('.portfolio-value', wallet.capital);
        this.updateElement('.chart-value', wallet.capital);
        this.updateElement('[data-stat="portfolio"] .stat-value', wallet.capital);
        
        // Update statistics
        this.updateElement('[data-stat="winrate"] .stat-value', wallet.winRate);
        this.updateElement('[data-stat="annual-return"] .stat-value', wallet.annualReturn);
        
        // Update chart subtitle with yearly return percentage
        this.updateChartSubtitle(wallet.yearlyReturn);
        
        // Update assets list and circular chart
        this.updateAssetsList(wallet.assets);
        this.updateCircularChart(wallet.assets);
        
        // Update chart images
        this.updateChartImages(wallet);
    }

    /**
     * Update element text content by selector
     * @param {string} selector - CSS selector for the element
     * @param {string} value - New text content
     */
    updateElement(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = value;
        }
    }

    /**
     * Update chart subtitle with yearly return percentage
     * @param {string} yearlyReturn - Yearly return percentage
     */
    updateChartSubtitle(yearlyReturn) {
        const chartSubtitle = document.querySelector('.chart-subtitle');
        if (chartSubtitle) {
            chartSubtitle.innerHTML = `^${yearlyReturn} доходности<br>за последний год`;
        }
    }

    /**
     * Update assets list with new asset data
     * @param {Array} assets - Array of asset objects
     */
    updateAssetsList(assets) {
        const assetsContainer = document.querySelector('.assets-list');
        if (!assetsContainer) return;

        // Clear existing assets
        const existingAssets = assetsContainer.querySelectorAll('.asset-item');
        existingAssets.forEach(asset => asset.remove());

        // Create new asset items
        assets.forEach(asset => {
            const assetItem = document.createElement('div');
            assetItem.className = 'asset-item d-flex align-items-center justify-content-between mb-2 py-2';
            
            const assetIcon = this.getAssetIconClass(asset.name);
            const iconElement = this.createAssetIcon(asset);
            
            // Create asset item HTML with icon and percentage
            assetItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="asset-icon ${assetIcon} rounded-circle d-flex align-items-center justify-content-center me-2">
                        ${iconElement}
                    </div>
                    <span class="text-dark">${asset.name} - ${asset.percentage}%</span>
                </div>
            `;
            
            assetsContainer.appendChild(assetItem);
        });
    }

    /**
     * Create asset icon element (image or fallback letter)
     * @param {Object} asset - Asset object with name and icon
     * @returns {string} HTML string for icon element
     */
    createAssetIcon(asset) {
        // Try to load icon image, fallback to letter if not found
        const iconPath = `./assets/icons/tokenico/${asset.icon}.png`;
        
        // Return image element with error handling
        return `<img src="${iconPath}" alt="${asset.name}" class="asset-icon-img" style="width: 16px; height: 16px; object-fit: contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                <span class="text-white small fw-bold" style="display: none;">${asset.name.charAt(0)}</span>`;
    }

    /**
     * Get Bootstrap color class for asset icon
     * @param {string} assetName - Name of the asset
     * @returns {string} Bootstrap color class
     */
    getAssetIconClass(assetName) {
        const iconClasses = {
            'USDT': 'bg-success',    // Green for USDT
            'Bitcoin': 'bg-warning', // Orange for Bitcoin
            'ETH': 'bg-primary',     // Blue for Ethereum
            'AVAX': 'bg-danger'      // Red for Avalanche
        };
        return iconClasses[assetName] || 'bg-secondary';
    }

    /**
     * Update circular chart with new asset percentages
     * @param {Array} assets - Array of asset objects with percentages
     */
    updateCircularChart(assets) {
        const chartSegment = document.querySelector('.chart-segment');
        if (!chartSegment) return;

        // Calculate cumulative percentages for conic-gradient
        let cumulativePercentage = 0;
        const gradientStops = assets.map(asset => {
            const startAngle = cumulativePercentage * 3.6; // Convert percentage to degrees (360° = 100%)
            cumulativePercentage += asset.percentage;
            const endAngle = cumulativePercentage * 3.6;
            
            const color = this.getAssetColor(asset.name);
            return `${color} ${startAngle}deg ${endAngle}deg`;
        }).join(', ');

        // Apply the conic gradient to the chart segment
        chartSegment.style.background = `conic-gradient(${gradientStops})`;
    }

    /**
     * Get hex color for asset in circular chart
     * @param {string} assetName - Name of the asset
     * @returns {string} Hex color code
     */
    getAssetColor(assetName) {
        const colors = {
            'USDT': '#D1F767',    // Green for USDT
            'Bitcoin': '#E8A005', // Orange for Bitcoin
            'ETH': '#FF5343',     // Red for Ethereum
            'AVAX': '#627EEA'     // Blue for Avalanche
        };
        return colors[assetName] || '#6c757d'; // Default gray for unknown assets
    }

    /**
     * Update chart images with wallet-specific chart URLs
     * @param {Object} wallet - Wallet data object with chart URLs
     */
    updateChartImages(wallet) {
        // Update portfolio change chart
        const portfolioChart = document.querySelector('.chart-container img[alt="Portfolio Change Chart"]');
        if (portfolioChart) {
            portfolioChart.src = wallet.portfolioChart;
        }

        // Update Sharpe ratio chart
        const sharpeChart = document.querySelector('.chart-container img[alt="Sharpe Ratio Chart"]');
        if (sharpeChart) {
            sharpeChart.src = wallet.sharpeChart;
        }
    }
}
