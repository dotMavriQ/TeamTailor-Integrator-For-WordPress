/**
 * TeamTailor Integrator Admin JavaScript
 *
 * Handles all admin UI interactions.
 */
(function($) {
    'use strict';

    // Helper function to initialize Prism.js
    const initPrism = function() {
        if (typeof Prism !== 'undefined') {
            Prism.highlightAll();
            
            // Check for any pre elements that might be causing horizontal overflow
            const preElements = document.querySelectorAll('pre[class*="language-"]');
            preElements.forEach(pre => {
                // Ensure pre elements don't cause overflow
                pre.style.maxWidth = '100%';
                pre.style.whiteSpace = 'pre-wrap';
                pre.style.wordBreak = 'break-word';
                
                // Add a subtle fade effect to the bottom of long JSON outputs
                if (pre.scrollHeight > pre.clientHeight) {
                    pre.style.maskImage = 'linear-gradient(to bottom, black 95%, transparent 100%)';
                    pre.style.webkitMaskImage = 'linear-gradient(to bottom, black 95%, transparent 100%)';
                }
                
                // Add special visual styling to JSON syntax (brace-matching)
                if (pre.className.includes('language-json')) {
                    // Add line numbers for easier reference
                    pre.classList.add('line-numbers');
                }
                
                // Log width for debugging
                console.log('Pre element width:', pre.offsetWidth + 'px');
            });
            
            // Set up copy button if it exists
            const copyBtn = document.getElementById('teamtailor-copy-json');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    const jsonCode = document.getElementById('teamtailor-json-code');
                    if (!jsonCode) return;
                    
                    // Create a temporary textarea to copy from
                    const textarea = document.createElement('textarea');
                    textarea.value = jsonCode.textContent;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    
                    // Update button text temporarily
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                });
            }
        }
    };

    // Document ready
    $(function() {
        // Check and log document width to debug overflow issues
        console.log('Document width:', document.documentElement.scrollWidth + 'px');
        // Handle tabs
        $('.teamtailor-tab').on('click', function(e) {
            e.preventDefault();
            
            var tabId = $(this).attr('data-tab');
            
            // Update active tab
            $('.teamtailor-tab').removeClass('teamtailor-tab-active');
            $(this).addClass('teamtailor-tab-active');
            
            // Show active content
            $('.teamtailor-tab-content').hide();
            $('#' + tabId).show();
            
            // If switching to API tab, re-initialize Prism highlight
            if (tabId === 'tab-api') {
                initPrism();
            }
            
            // Store active tab in sessionStorage
            sessionStorage.setItem('teamtailorActiveTab', tabId);
        });

        // Set active tab from sessionStorage or default to first tab
        var activeTab = sessionStorage.getItem('teamtailorActiveTab') || $('.teamtailor-tab:first').attr('data-tab');
        $('.teamtailor-tab[data-tab="' + activeTab + '"]').click();

        // Show success and error messages with fadeout after 5 seconds
        $('.teamtailor-notice:not(.is-dismissible)').delay(5000).fadeOut(500);
        
        // Toggle sections
        $('.teamtailor-toggle-section').on('click', function(e) {
            e.preventDefault();
            $(this).next('.teamtailor-toggle-content').slideToggle();
            $(this).toggleClass('teamtailor-toggle-active');
        });
        
        // API test button with loading indicator
        $('#teamtailor-test-api-btn').on('click', function() {
            const $button = $(this);
            const $form = $button.closest('form');
            const $responseArea = $('#teamtailor-api-response');
            
            // Show loading state
            $button.addClass('teamtailor-loading').prop('disabled', true);
            $responseArea.html('<div class="teamtailor-loading-message">Fetching data from TeamTailor API...</div>');
            
            // Store original button text
            $button.data('original-text', $button.val() || $button.text());
            
            // Form will submit normally, but we add a visual loading indicator
            setTimeout(function() {
                // This timeout just ensures the loading state is visible
                // The actual processing happens server-side when the form submits
                $form.submit();
            }, 100);
        });
        
        // Sync button with loading indicator and AJAX support
        $('#teamtailor-sync-btn').on('click', function(e) {
            e.preventDefault(); // Prevent normal form submit
            
            const $button = $(this);
            const $form = $button.closest('form');
            const formData = $form.serialize();
            
            // Show loading state and disable button
            $button.addClass('teamtailor-loading').prop('disabled', true);
            $button.val($button.data('loading-text'));
            
            // Add a status area after the form if it doesn't exist
            if ($('#teamtailor-sync-status').length === 0) {
                $form.after('<div id="teamtailor-sync-status"></div>');
            }
            
            // Show initial status
            $('#teamtailor-sync-status').html(
                '<div class="teamtailor-status-box">' +
                '<p><strong>Starting sync process...</strong></p>' +
                '</div>'
            );
            
            // Submit via AJAX
            $.ajax({
                url: ajaxurl, // WordPress AJAX URL
                type: 'POST',
                data: formData + '&action=teamtailor_sync_jobs', // Use our registered AJAX action
                success: function(response) {
                    // Display the sync results
                    $('#teamtailor-sync-status').html(response);
                    
                    // Reset button state
                    $button.removeClass('teamtailor-loading').prop('disabled', false);
                    $button.val('Sync from TeamTailor');
                    
                    // Scroll to status
                    $('html, body').animate({
                        scrollTop: $('#teamtailor-sync-status').offset().top - 50
                    }, 500);
                },
                error: function(xhr, status, error) {
                    // Show error
                    $('#teamtailor-sync-status').html(
                        '<div class="teamtailor-notice teamtailor-notice-error">' +
                        '<p><strong>Error:</strong> ' + error + '</p>' +
                        '<p>Status: ' + status + '</p>' +
                        '</div>'
                    );
                    
                    // Reset button state
                    $button.removeClass('teamtailor-loading').prop('disabled', false);
                    $button.val('Sync from TeamTailor');
                }
            });
        });
        
        // Initialize Prism
        initPrism();
        
        // Add event listener for window resize to log width changes
        $(window).resize(function() {
            console.log('Document width after resize:', document.documentElement.scrollWidth + 'px');
            
            // Check for extremely wide elements that might cause overflow
            const allElements = document.querySelectorAll('*');
            const viewportWidth = window.innerWidth;
            
            allElements.forEach(element => {
                const width = element.offsetWidth;
                if (width > viewportWidth * 1.5) {
                    console.log('Wide element found:', element, width + 'px');
                }
            });
        });
    });

    // Re-initialize Prism after ajax calls and reset loading buttons
    $(document).ajaxComplete(function(event, xhr, settings) {
        initPrism();
        
        // Reset all loading buttons
        $('.teamtailor-loading').each(function() {
            const $button = $(this);
            $button.removeClass('teamtailor-loading').prop('disabled', false);
            
            // Restore original text if it was saved
            const originalText = $button.data('original-text');
            if (originalText) {
                if ($button.is('input')) {
                    $button.val(originalText);
                } else {
                    $button.text(originalText);
                }
            }
        });
    });
    
    // Detect when page has fully loaded and reset any loading indicators
    $(window).on('load', function() {
        // Reset all loading buttons
        $('.teamtailor-loading').removeClass('teamtailor-loading').prop('disabled', false);
        
        // Clean up any remaining loading messages
        $('.teamtailor-loading-message').remove();
    });

})(jQuery);