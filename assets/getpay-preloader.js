/**
 * Optimized GetPay Preloader Script
 * Fast-loading with improved performance and reduced delays
 */

(function() {
    'use strict';
    
    let preloaderHidden = false;
    let redirectTimer = null;
    const loader = document.getElementById('getpay-preloader');
    const loadingText = document.getElementById('loading-text');
    
    /**
     * Hide the preloader instantly
     */
    function hidePreloader() {
        if (preloaderHidden || !loader) return;
        
        // Use requestAnimationFrame for smoother animation
        requestAnimationFrame(() => {
            loader.style.opacity = '0';
            loader.style.transition = 'opacity 0.15s ease-out';
            
            // Reduced timeout for faster hiding
            setTimeout(() => {
                loader.style.display = 'none';
                preloaderHidden = true;
            }, 150);
        });
    }
    
    /**
     * Update loading text efficiently
     */
    function updateLoadingText(text) {
        if (loadingText && loadingText.textContent !== text) {
            loadingText.textContent = text;
        }
    }
    
    /**
     * Fast payment status check
     */
    function checkPaymentStatus() {
        // Check GetPay readiness
        if (typeof window.GetPay !== 'undefined' && window.GetPay.isReady) {
            updateLoadingText('Ready!');
            setTimeout(hidePreloader, 100); // Reduced delay
            return true;
        }
        
        // Check for redirect indicators
        const isRedirecting = window.location.href.includes('redirect') || 
                            window.location.href.includes('payment-processor') ||
                            document.querySelector('[data-payment-redirect]');
        
        if (isRedirecting) {
            updateLoadingText('Redirecting...');
            return true;
        }
        
        return false;
    }
    
    /**
     * Initialize with faster timing
     */
    function initPreloader() {
        // Reduced maximum wait time to 5 seconds
        const maxWaitTime = 5000;
        const checkInterval = 200; // More frequent checks
        let elapsedTime = 0;
        
        // Initial quick check
        if (checkPaymentStatus()) return;
        
        const statusCheck = setInterval(() => {
            elapsedTime += checkInterval;
            
            if (checkPaymentStatus()) {
                clearInterval(statusCheck);
                if (redirectTimer) clearTimeout(redirectTimer);
                return;
            }
            
            // Faster loading text updates
            if (elapsedTime >= 1000 && elapsedTime < 2500) {
                updateLoadingText('Preparing payment...');
            } else if (elapsedTime >= 2500) {
                updateLoadingText('Almost ready...');
            }
            
            // Hide after maximum wait time
            if (elapsedTime >= maxWaitTime) {
                clearInterval(statusCheck);
                hidePreloader();
            }
        }, checkInterval);
        
        // Reduced fallback timer to 1 second
        redirectTimer = setTimeout(() => {
            if (!preloaderHidden && !window.location.href.includes('redirect')) {
                hidePreloader();
            }
        }, 1000);
    }
    
    /**
     * Optimized visibility change handler
     */
    function handleVisibilityChange() {
        if (!preloaderHidden) {
            updateLoadingText(document.hidden ? 'Processing...' : 'Loading...');
        }
    }
    
    /**
     * Fast beforeunload handler
     */
    function handleBeforeUnload() {
        if (!preloaderHidden) {
            updateLoadingText('Redirecting...');
        }
    }
    
    // Immediate initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPreloader);
    } else {
        // Use setTimeout(0) to ensure DOM is ready
        setTimeout(initPreloader, 0);
    }
    
    // Optimized event listeners
    document.addEventListener('visibilitychange', handleVisibilityChange, { passive: true });
    window.addEventListener('beforeunload', handleBeforeUnload, { passive: true });
    
    // Fast GetPay bundle load handler
    window.addEventListener('getpay-loaded', function() {
        updateLoadingText('Ready!');
        setTimeout(hidePreloader, 100);
    }, { once: true });
    
    // Expose optimized methods
    window.GetPayPreloader = {
        hide: hidePreloader,
        updateText: updateLoadingText,
        isHidden: () => preloaderHidden,
        forceHide: () => {
            clearTimeout(redirectTimer);
            hidePreloader();
        }
    };
    
    // Remove the initial 3-second timeout from original code
    // This was causing unnecessary delays
    
})();

/**
 * CSS optimizations for faster rendering
 * Add this to your CSS file or <style> tag
 */
/*
#getpay-preloader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #4c6ef5;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    will-change: opacity;
    transform: translateZ(0);
}

#loading-text {
    color: white;
    font-size: 18px;
    font-weight: 500;
    text-align: center;
    will-change: contents;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(255,255,255,0.3);
    border-top: 3px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
    will-change: transform;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
*/